<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$user = currentUser();
$error = '';
$message = '';

$vehiculoId = (int)($_GET['id'] ?? $_POST['vehiculo_id'] ?? 0);
$patenteParam = strtoupper(trim($_GET['patente'] ?? ''));

$vehiculo = null;
if ($vehiculoId > 0) {
    $stmt = $pdo->prepare("
        SELECT v.*, c.ClienteID, c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono, c.Email AS ClienteEmail,
               CONCAT(COALESCE(c.RutCuerpo, ''), IF(c.RutDv IS NOT NULL, CONCAT('-', c.RutDv), '')) AS ClienteRUT
        FROM vehiculos v
        JOIN clientes c ON v.ClienteID = c.ClienteID
        WHERE v.VehiculoID = :id AND v.Activo = TRUE
    ");
    $stmt->execute([':id' => $vehiculoId]);
    $vehiculo = $stmt->fetch();
} elseif ($patenteParam !== '') {
    $stmt = $pdo->prepare("
        SELECT v.*, c.ClienteID, c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono, c.Email AS ClienteEmail,
               CONCAT(COALESCE(c.RutCuerpo, ''), IF(c.RutDv IS NOT NULL, CONCAT('-', c.RutDv), '')) AS ClienteRUT
        FROM vehiculos v
        JOIN clientes c ON v.ClienteID = c.ClienteID
        WHERE v.Patente = :pat AND v.Activo = TRUE
        LIMIT 1
    ");
    $stmt->execute([':pat' => $patenteParam]);
    $vehiculo = $stmt->fetch();
    if ($vehiculo) {
        $vehiculoId = (int)$vehiculo['VehiculoID'];
    }
}

if (!$vehiculo) {
    http_response_code(404);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Vehículo no encontrado</h2><a href='vehiculos.php' class='btn btn-primary'>Volver al listado</a></div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'actualizar_km') {
        $nuevoKm = (int)($_POST['kilometraje'] ?? 0);
        if ($nuevoKm > 0) {
            $pdo->prepare("UPDATE vehiculos SET KilometrajeUltimo = :km WHERE VehiculoID = :id")
                ->execute([':km' => $nuevoKm, ':id' => $vehiculoId]);
            $vehiculo['KilometrajeUltimo'] = $nuevoKm;
            $message = 'Kilometraje actualizado correctamente.';
        } else {
            $error = 'Ingresa un kilometraje válido mayor a cero.';
        }
    } elseif ($action === 'add_mantenimiento') {
        $tipo = trim($_POST['tipo_mantenimiento'] ?? '');
        $kmRealizado = (int)($_POST['km_realizado'] ?? $vehiculo['KilometrajeUltimo'] ?? 0);
        $kmProximo = (int)($_POST['km_proximo'] ?? 0) ?: null;
        $fechaProxima = trim($_POST['fecha_proxima'] ?? '') ?: null;
        $notas = trim($_POST['notas'] ?? '');

        if ($tipo !== '') {
            $stmtM = $pdo->prepare("
                INSERT INTO historialmantenimiento
                    (VehiculoID, TipoMantenimiento, KilometrajeRealizado, KilometrajeProximo, FechaProxima, Estado, Notas, UsuarioID)
                VALUES
                    (:vid, :tipo, :kmr, :kmp, :fp, 'Vigente', :notas, :uid)
            ");
            $stmtM->execute([
                ':vid' => $vehiculoId,
                ':tipo' => $tipo,
                ':kmr' => $kmRealizado,
                ':kmp' => $kmProximo,
                ':fp' => $fechaProxima,
                ':notas' => $notas ?: null,
                ':uid' => $user['id']
            ]);
            $message = 'Registro de mantenimiento preventivo guardado con éxito.';
        } else {
            $error = 'Especifica el tipo de mantenimiento.';
        }
    } elseif ($action === 'eliminar_mantenimiento') {
        $maintId = (int)($_POST['mantenimiento_id'] ?? 0);
        $pdo->prepare("DELETE FROM historialmantenimiento WHERE MantenimientoID = :mid AND VehiculoID = :vid")
            ->execute([':mid' => $maintId, ':vid' => $vehiculoId]);
        $message = 'Registro eliminado.';
    }
}

// 1. Mantenimientos y alertas
$stmtMaint = $pdo->prepare("
    SELECT hm.*, u.Nombre AS UsuarioNombre
    FROM historialmantenimiento hm
    LEFT JOIN usuarios u ON hm.UsuarioID = u.UsuarioID
    WHERE hm.VehiculoID = :id
    ORDER BY hm.FechaRealizado DESC
");
$stmtMaint->execute([':id' => $vehiculoId]);
$mantenimientos = $stmtMaint->fetchAll();

// Evaluar estados de alerta según kilometraje actual y fechas
$alertasVencidas = 0;
$alertasProximas = 0;
$kmActual = (int)($vehiculo['KilometrajeUltimo'] ?? 0);

foreach ($mantenimientos as &$m) {
    $estado = 'Vigente';
    $diferenciaKm = $m['KilometrajeProximo'] ? ($m['KilometrajeProximo'] - $kmActual) : null;
    $diasRestantes = $m['FechaProxima'] ? (int)round((strtotime($m['FechaProxima']) - time()) / 86400) : null;

    if (($diferenciaKm !== null && $diferenciaKm <= 0) || ($diasRestantes !== null && $diasRestantes < 0)) {
        $estado = 'Vencido';
        $alertasVencidas++;
    } elseif (($diferenciaKm !== null && $diferenciaKm <= 1000) || ($diasRestantes !== null && $diasRestantes <= 20)) {
        $estado = 'Proximo';
        $alertasProximas++;
    }
    $m['EstadoCalculado'] = $estado;
    $m['DiferenciaKm'] = $diferenciaKm;
    $m['DiasRestantes'] = $diasRestantes;
}
unset($m);

// 2. Historial de Órdenes de Trabajo de este auto
$stmtOts = $pdo->prepare("
    SELECT ot.*, u.Nombre AS UsuarioNombre,
           (SELECT SUM(pd.Subtotal) FROM presupuestodetalle pd JOIN presupuestos p ON pd.PresupuestoID = p.PresupuestoID WHERE p.OrdenTrabajoID = ot.OrdenTrabajoID AND pd.Aprobado = 1) AS TotalPresupuesto
    FROM ordenestrabajo ot
    LEFT JOIN usuarios u ON ot.UsuarioID = u.UsuarioID
    WHERE ot.VehiculoID = :id
    ORDER BY ot.OrdenTrabajoID DESC
");
$stmtOts->execute([':id' => $vehiculoId]);
$ordenes = $stmtOts->fetchAll();

// Para cada OT, cargar sus líneas aprobadas de repuestos y mano de obra
$detallesPorOT = [];
if (!empty($ordenes)) {
    $otIds = array_column($ordenes, 'OrdenTrabajoID');
    $placeholders = implode(',', array_fill(0, count($otIds), '?'));
    $stmtDet = $pdo->prepare("
        SELECT pd.*, p.OrdenTrabajoID
        FROM presupuestodetalle pd
        JOIN presupuestos p ON pd.PresupuestoID = p.PresupuestoID
        WHERE p.OrdenTrabajoID IN ($placeholders) AND pd.Aprobado = 1
        ORDER BY pd.TipoLinea ASC, pd.PresupuestoDetalleID ASC
    ");
    $stmtDet->execute($otIds);
    while ($row = $stmtDet->fetch()) {
        $detallesPorOT[$row['OrdenTrabajoID']][] = $row;
    }
}

// 3. Repuestos Compatibles ("¿Qué necesita este auto?")
$stmtComp = $pdo->prepare("
    SELECT cr.*, p.ProductoID, p.CodigoBarras, p.Nombre AS ProductoNombre, p.Descripcion AS ProductoDesc,
           p.MarcaRepuesto, p.NumeroParteOEM, p.NumeroParteAlternativo, p.ViscosidadAceite, p.TipoRepuesto,
           p.PrecioVenta, p.Stock, cat.Nombre AS CategoriaNombre
    FROM compatibilidadrepuestos cr
    JOIN productos p ON cr.ProductoID = p.ProductoID
    LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
    WHERE (LOWER(cr.MarcaVehiculo) = LOWER(:marca) AND LOWER(cr.ModeloVehiculo) = LOWER(:modelo))
       OR LOWER(p.Nombre) LIKE LOWER(:likeModelo)
    ORDER BY p.TipoRepuesto ASC, p.Nombre ASC
");
$stmtComp->execute([
    ':marca' => $vehiculo['Marca'],
    ':modelo' => $vehiculo['Modelo'],
    ':likeModelo' => '%' . $vehiculo['Modelo'] . '%'
]);
$repuestosCompatibles = $stmtComp->fetchAll();

// Lo último que se le puso a ESTE auto (por tipo de repuesto), según sus órdenes entregadas.
$stmtUsados = $pdo->prepare("
    SELECT p.ProductoID, p.Nombre, p.TipoRepuesto, p.ViscosidadAceite, p.MarcaRepuesto, p.PrecioVenta, p.Stock,
           ot.OrdenTrabajoID, ot.FechaEntrega, ot.KilometrajeIngreso
    FROM presupuestodetalle pd
    JOIN presupuestos pr ON pd.PresupuestoID = pr.PresupuestoID
    JOIN ordenestrabajo ot ON pr.OrdenTrabajoID = ot.OrdenTrabajoID
    JOIN productos p ON pd.ProductoID = p.ProductoID
    WHERE ot.VehiculoID = :v AND ot.Estado = 'Entregado' AND pd.Aprobado = 1
      AND pd.TipoLinea = 'Repuesto' AND p.TipoRepuesto <> 'General'
    ORDER BY ot.FechaEntrega DESC
");
$stmtUsados->execute([':v' => $vehiculo['VehiculoID']]);
$usadosEnEsteAuto = [];
foreach ($stmtUsados->fetchAll() as $u) {
    $usadosEnEsteAuto[$u['TipoRepuesto']] ??= $u;
}

// Lo ya mostrado como "usado en este auto" no se repite en la lista del modelo.
$idsUsados = array_column($usadosEnEsteAuto, 'ProductoID');
$repuestosCompatibles = array_values(array_filter($repuestosCompatibles, fn($r) => !in_array($r['ProductoID'], $idsUsados)));

include __DIR__ . '/views/ficha_vehiculo.view.php';
require_once __DIR__ . '/includes/footer.php';
