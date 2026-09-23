<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDB();

$otId = (int)($_GET['ot'] ?? 0);
$mantenimientoId = (int)($_GET['mantenimiento_id'] ?? 0);
$vehiculoId = (int)($_GET['vehiculo_id'] ?? 0);

$ot = null;
$vehiculo = null;
$cliente = null;
$estacion = null;
$mantenimiento = null;

if ($otId > 0) {
    $stmt = $pdo->prepare("
        SELECT ot.*, v.VehiculoID, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.KilometrajeUltimo,
               c.ClienteID, c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono
        FROM ordenestrabajo ot
        JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
        JOIN clientes c ON ot.ClienteID = c.ClienteID
        WHERE ot.OrdenTrabajoID = :id
    ");
    $stmt->execute([':id' => $otId]);
    $ot = $stmt->fetch();

    if ($ot) {
        $vehiculoId = (int)$ot['VehiculoID'];

        // Revisar chequeo de fluidos
        $stmtE = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :id");
        $stmtE->execute([':id' => $otId]);
        $estacion = $stmtE->fetch();

        // Revisar historial de mantenimiento generado por esta OT
        $stmtM = $pdo->prepare("SELECT * FROM historialmantenimiento WHERE OrdenTrabajoID = :id AND TipoMantenimiento LIKE '%Aceite%' ORDER BY MantenimientoID DESC LIMIT 1");
        $stmtM->execute([':id' => $otId]);
        $mantenimiento = $stmtM->fetch();
    }
} elseif ($mantenimientoId > 0) {
    $stmtM = $pdo->prepare("SELECT * FROM historialmantenimiento WHERE MantenimientoID = :id");
    $stmtM->execute([':id' => $mantenimientoId]);
    $mantenimiento = $stmtM->fetch();

    if ($mantenimiento) {
        $vehiculoId = (int)$mantenimiento['VehiculoID'];
    }
}

if ($vehiculoId > 0 && !$ot) {
    $stmtV = $pdo->prepare("
        SELECT v.*, c.ClienteID, c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono
        FROM vehiculos v
        JOIN clientes c ON v.ClienteID = c.ClienteID
        WHERE v.VehiculoID = :id
    ");
    $stmtV->execute([':id' => $vehiculoId]);
    $rowV = $stmtV->fetch();
    if ($rowV) {
        $ot = [
            'OrdenTrabajoID' => $mantenimiento['OrdenTrabajoID'] ?? null,
            'Patente' => $rowV['Patente'],
            'Marca' => $rowV['Marca'],
            'Modelo' => $rowV['Modelo'],
            'Anio' => $rowV['Anio'],
            'Color' => $rowV['Color'],
            'KilometrajeIngreso' => $mantenimiento['KilometrajeRealizado'] ?? $rowV['KilometrajeUltimo'],
            'ClienteNombre' => $rowV['ClienteNombre'],
            'ClienteTelefono' => $rowV['ClienteTelefono']
        ];
    }
}

if (!$ot) {
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Vehículo u Orden no encontrados</h2><button onclick='window.close()'>Cerrar</button></div>");
}

// Determinar datos del cambio de aceite
$kmRealizado = (int)($mantenimiento['KilometrajeRealizado'] ?? $ot['KilometrajeIngreso'] ?? 0);
$fechaRealizado = !empty($mantenimiento['FechaRealizado']) ? date('d/m/Y', strtotime($mantenimiento['FechaRealizado'])) : date('d/m/Y');

// Próximo KM
$intervaloKm = (int)($estacion['AceiteIntervaloKm'] ?? 10000);
if ($intervaloKm <= 0) $intervaloKm = 10000;
$kmProximo = !empty($mantenimiento['KilometrajeProximo']) ? (int)$mantenimiento['KilometrajeProximo'] : ($kmRealizado ? ($kmRealizado + $intervaloKm) : null);

// Próxima Fecha
$intervaloMeses = (int)($estacion['AceiteIntervaloMeses'] ?? 6);
if ($intervaloMeses <= 0) $intervaloMeses = 6;
$fechaProxima = !empty($mantenimiento['FechaProxima']) ? date('d/m/Y', strtotime($mantenimiento['FechaProxima'])) : date('d/m/Y', strtotime("+{$intervaloMeses} months"));

// Buscar notas de lubricante / filtro
$lubricanteUtilizado = $mantenimiento['Notas'] ?? '';
if (empty($lubricanteUtilizado) && !empty($ot['OrdenTrabajoID'])) {
    // Buscar en lineas de presupuesto si hay algún lubricante o aceite mencionado
    $stmtLines = $pdo->prepare("
        SELECT Descripcion FROM presupuestodetalle pd
        JOIN presupuestos p ON pd.PresupuestoID = p.PresupuestoID
        WHERE p.OrdenTrabajoID = :ot AND (pd.Descripcion LIKE '%aceite%' OR pd.Descripcion LIKE '%5w%' OR pd.Descripcion LIKE '%10w%' OR pd.Descripcion LIKE '%15w%' OR pd.Descripcion LIKE '%20w%' OR pd.Descripcion LIKE '%sintetico%' OR pd.Descripcion LIKE '%sintético%')
        LIMIT 1
    ");
    $stmtLines->execute([':ot' => $ot['OrdenTrabajoID']]);
    $descAceite = $stmtLines->fetchColumn();
    if ($descAceite) {
        $lubricanteUtilizado = $descAceite;
    }
}
if (empty($lubricanteUtilizado) && !empty($estacion['Observaciones'])) {
    $lubricanteUtilizado = $estacion['Observaciones'];
}
if (empty($lubricanteUtilizado)) {
    $lubricanteUtilizado = "Aceite de Motor + Filtro";
}

// Configuraciones del taller
$configStmt = $pdo->query("SELECT Clave, Valor FROM configuraciones");
$configs = [];
while ($r = $configStmt->fetch()) {
    $configs[$r['Clave']] = $r['Valor'];
}

$nombreEmpresa = $configs['EMPRESA_NOMBRE'] ?? 'TALLER MECÁNICO PRO';
$telefonoEmpresa = $configs['EMPRESA_TELEFONO'] ?? '';
$direccionEmpresa = $configs['EMPRESA_DIRECCION'] ?? '';

include __DIR__ . '/views/sticker_aceite.view.php';
