<?php
/**
 * Visor Público de Documentos con Token Corto de Seguridad.
 * Permite a los clientes del taller consultar su Presupuesto, Comprobante de Ingreso/Custodia
 * o Ficha de Entrega desde su teléfono móvil vía WhatsApp sin requerir inicio de sesión.
 */

// Cargar configuración de base de datos y helpers
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/dominio/tokens_documentos.php';
require_once __DIR__ . '/includes/integraciones/whatsapp_helper.php';
require_once __DIR__ . '/includes/core/auth.php';

$pdo = getDB();

// Obtener token desde la URL (soporta ?t= o ?token=)
$tokenParam = trim($_GET['t'] ?? ($_GET['token'] ?? ''));

// Si no hay token o es inválido, mostrar vista de acceso no autorizado
$docAuth = !empty($tokenParam) ? validarTokenDocumento($pdo, $tokenParam) : null;

// Cargar datos de la empresa / taller para membrete
$nombreTaller = obtenerNombreTaller($pdo);
$configStmt = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('MINIMARKET_RUT', 'MINIMARKET_GIRO', 'MINIMARKET_DIRECCION', 'MINIMARKET_TELEFONO', 'MINIMARKET_LOGO_URL', 'EMPRESA_EMAIL')");
$configEmpresa = [];
while ($r = $configStmt->fetch()) {
    $configEmpresa[$r['Clave']] = $r['Valor'];
}

$rutTaller = $configEmpresa['MINIMARKET_RUT'] ?? '76.123.456-7';
$giroTaller = $configEmpresa['MINIMARKET_GIRO'] ?? 'Servicio Técnico Automotriz y Venta de Repuestos';
$dirTaller = $configEmpresa['MINIMARKET_DIRECCION'] ?? '';
$telTaller = $configEmpresa['MINIMARKET_TELEFONO'] ?? '';
$logoTaller = $configEmpresa['MINIMARKET_LOGO_URL'] ?? '';

// Si el token es inválido o no existe
if (!$docAuth) {
    http_response_code(403);
    $errorTitulo = 'Documento no disponible o código inválido';
    $errorMensaje = 'El enlace que intentas abrir es inválido, ha caducado o el código de seguridad no coincide. Por favor, solicita un nuevo enlace directo al taller.';
    include __DIR__ . '/views/doc_publico_error.view.php';
    exit;
}

$tipoDoc = $docAuth['TipoDocumento'];
$refId = (int)$docAuth['ReferenciaID'];
$token = $docAuth['Token'];

// Cargar información según el tipo de documento
$documento = null;
$ot = null;
$lineas = [];
$totales = [];

if (in_array($tipoDoc, ['presupuesto', 'ingreso', 'entrega'], true)) {
    // Cargar datos de la Orden de Trabajo vinculada
    $stmtOT = $pdo->prepare("
        SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.VIN, v.KilometrajeUltimo,
               c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono, c.Email AS ClienteEmail,
               c.RutCuerpo, c.RutDv, u.Nombre AS MecanicoNombre
        FROM ordenestrabajo ot
        JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
        JOIN clientes c ON ot.ClienteID = c.ClienteID
        LEFT JOIN usuarios u ON ot.MecanicoID = u.UsuarioID
        WHERE ot.OrdenTrabajoID = :ot
        LIMIT 1
    ");
    $stmtOT->execute([':ot' => $refId]);
    $ot = $stmtOT->fetch();

    if (!$ot) {
        http_response_code(404);
        $errorTitulo = 'Orden de Trabajo no encontrada';
        $errorMensaje = 'No se encontró la Orden de Trabajo asociada a este documento.';
        include __DIR__ . '/views/doc_publico_error.view.php';
        exit;
    }
}

if ($tipoDoc === 'presupuesto') {
    // Cargar presupuesto más reciente
    $stmtP = $pdo->prepare("
        SELECT * FROM presupuestos 
        WHERE OrdenTrabajoID = :ot 
        ORDER BY PresupuestoID DESC 
        LIMIT 1
    ");
    $stmtP->execute([':ot' => $refId]);
    $presupuesto = $stmtP->fetch();

    if (!$presupuesto) {
        http_response_code(404);
        $errorTitulo = 'Presupuesto no disponible';
        $errorMensaje = 'Esta orden de trabajo aún no cuenta con un presupuesto emitido.';
        include __DIR__ . '/views/doc_publico_error.view.php';
        exit;
    }

    // Cargar líneas de detalle
    $stmtL = $pdo->prepare("
        SELECT * FROM presupuestodetalle 
        WHERE PresupuestoID = :pid 
        ORDER BY TipoLinea ASC, PresupuestoDetalleID ASC
    ");
    $stmtL->execute([':pid' => $presupuesto['PresupuestoID']]);
    $lineas = $stmtL->fetchAll();

    // Clasificar líneas
    $lineasRepuesto = [];
    $lineasManoObra = [];
    $lineasTerceros = [];
    $totalPresupuesto = 0;
    $totalAprobado = 0;

    foreach ($lineas as $l) {
        if ($l['PoliticaCobro'] !== 'SoloSiNoAprueba') {
            $totalPresupuesto += (int)$l['Subtotal'];
        }
        if (!empty($l['Aprobado'])) {
            $totalAprobado += (int)$l['Subtotal'];
        }

        if ($l['TipoLinea'] === 'Repuesto') {
            $lineasRepuesto[] = $l;
        } elseif ($l['TipoLinea'] === 'ManoObra') {
            $lineasManoObra[] = $l;
        } else {
            $lineasTerceros[] = $l;
        }
    }

    // Preparar enlace de WhatsApp para que el cliente responda aprobando
    $folioOT = function_exists('formatFolioOT') ? formatFolioOT($ot['OrdenTrabajoID']) : 'OT-' . $ot['OrdenTrabajoID'];
    $waAprobarTexto = "Hola, apruebo los trabajos del presupuesto para mi auto {$ot['Marca']} {$ot['Modelo']} ({$ot['Patente']}), Folio {$folioOT}. (Token: {$token})";
    $urlWaRespuesta = !empty($telTaller) ? generarUrlWhatsapp($telTaller, $waAprobarTexto) : '';

} elseif ($tipoDoc === 'ingreso') {
    // Cargar checklist de recepción
    $stmtChk = $pdo->prepare("
        SELECT ci.Nombre, ci.Categoria, cr.Valor, cr.Detalle
        FROM checklistrecepcion cr
        JOIN checklistitems ci ON cr.ChecklistItemID = ci.ChecklistItemID
        WHERE cr.OrdenTrabajoID = :ot
        ORDER BY ci.Orden ASC
    ");
    $stmtChk->execute([':ot' => $refId]);
    $checklist = $stmtChk->fetchAll();
    $checklistPorCategoria = [];
    foreach ($checklist as $row) {
        $checklistPorCategoria[$row['Categoria']][] = $row;
    }

    // Estación de servicio y observaciones
    $stmtEst = $pdo->prepare("SELECT Observaciones FROM estacionservicio_ot WHERE OrdenTrabajoID = :ot");
    $stmtEst->execute([':ot' => $refId]);
    $observacionesEstacion = $stmtEst->fetchColumn() ?: '';

} elseif ($tipoDoc === 'entrega') {
    // Cargar resumen de entrega, venta y garantía
    $stmtVenta = $pdo->prepare("
        SELECT v.*, pv.MetodoPago
        FROM ventas v
        LEFT JOIN pagosventa pv ON v.VentaID = pv.VentaID
        WHERE v.VentaID = :vid
        LIMIT 1
    ");
    $stmtVenta->execute([':vid' => (int)($ot['VentaID'] ?? 0)]);
    $venta = $stmtVenta->fetch();
}

// Cargar vista del documento público
include __DIR__ . '/views/doc_publico.view.php';
