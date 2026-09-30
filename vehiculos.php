<?php
require_once __DIR__ . '/includes/layout/header.php';
require_once __DIR__ . '/includes/integraciones/revision_tecnica_helper.php';

$pdo = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'save_vehiculo';

    if ($action === 'save_vehiculo') {
        $vehiculoId = (int)($_POST['vehiculo_id'] ?? 0);
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $patente = strtoupper(trim($_POST['patente'] ?? ''));
        $marca = trim($_POST['marca'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $anio = (int)($_POST['anio'] ?? 0) ?: null;
        $color = trim($_POST['color'] ?? '');
        $combustible = trim($_POST['combustible'] ?? '') ?: null;
        $motor = trim($_POST['motor'] ?? '') ?: null;
        $transmision = trim($_POST['transmision'] ?? '') ?: null;
        $tipoVehiculo = trim($_POST['tipo_vehiculo'] ?? '') ?: null;
        $vin = strtoupper(trim($_POST['vin'] ?? ''));
        $km = (int)($_POST['kilometraje'] ?? 0) ?: null;
        $regimenPRT = strtolower(trim($_POST['regimen_prt'] ?? ''));
        if (!in_array($regimenPRT, ['anual', 'semestral', 'cuatrimestral'], true)) {
            $regimenPRT = !empty($_POST['es_transporte_publico']) ? 'semestral' : 'anual';
        }
        $esTransporte = ($regimenPRT !== 'anual') ? 1 : 0;

        if ($clienteId > 0 && $patente !== '' && $marca !== '' && $modelo !== '') {
            try {
                if ($vehiculoId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE vehiculos SET ClienteID = :cid, Patente = :pat, Marca = :marca, Modelo = :modelo,
                               Anio = :anio, Color = :color, Combustible = :comb, Motor = :mot,
                               Transmision = :trans, TipoVehiculo = :tipo, VIN = :vin, KilometrajeUltimo = :km,
                               RevisionTecnicaRegimen = :regimen, EsTransportePublico = :tp
                        WHERE VehiculoID = :id
                    ");
                    $stmt->execute([
                        ':cid' => $clienteId, ':pat' => $patente, ':marca' => $marca, ':modelo' => $modelo,
                        ':anio' => $anio, ':color' => $color, ':comb' => $combustible, ':mot' => $motor,
                        ':trans' => $transmision, ':tipo' => $tipoVehiculo,
                        ':vin' => $vin ?: null, ':km' => $km,
                        ':regimen' => $regimenPRT, ':tp' => $esTransporte, ':id' => $vehiculoId
                    ]);
                    sincronizarVehiculoPRT($vehiculoId, $pdo, $regimenPRT);
                    $message = "Vehículo $patente actualizado correctamente.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO vehiculos (ClienteID, Patente, Marca, Modelo, Anio, Color, Combustible, Motor, Transmision, TipoVehiculo, VIN, KilometrajeUltimo, RevisionTecnicaRegimen, EsTransportePublico)
                        VALUES (:cid, :pat, :marca, :modelo, :anio, :color, :comb, :mot, :trans, :tipo, :vin, :km, :regimen, :tp)
                    ");
                    $stmt->execute([
                        ':cid' => $clienteId, ':pat' => $patente, ':marca' => $marca, ':modelo' => $modelo,
                        ':anio' => $anio, ':color' => $color, ':comb' => $combustible, ':mot' => $motor,
                        ':trans' => $transmision, ':tipo' => $tipoVehiculo,
                        ':vin' => $vin ?: null, ':km' => $km,
                        ':regimen' => $regimenPRT, ':tp' => $esTransporte
                    ]);
                    $nuevoId = (int)$pdo->lastInsertId();
                    sincronizarVehiculoPRT($nuevoId, $pdo, $regimenPRT);
                    $message = "Vehículo $patente registrado correctamente.";
                }
            } catch (Exception $e) {
                $error = str_contains($e->getMessage(), 'UQ_Vehiculos_Patente')
                    ? "Ya existe un vehículo registrado con la patente $patente."
                    : 'Error al guardar el vehículo: ' . $e->getMessage();
            }
        } else {
            $error = 'Completa cliente, patente, marca y modelo.';
        }
    }
}

$clientes = $pdo->query("SELECT ClienteID, Nombre FROM clientes WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

$q = trim($_GET['q'] ?? '');
$filtroRt = trim($_GET['filtro_rt'] ?? '');

$sqlFiltroRt = '';
if ($filtroRt === 'vencidos') {
    $sqlFiltroRt = " AND v.RevisionTecnicaEstado = 'vencida'";
} elseif ($filtroRt === 'por_vencer') {
    $sqlFiltroRt = " AND v.RevisionTecnicaEstado = 'por_vencer'";
} elseif ($filtroRt === 'vigentes') {
    $sqlFiltroRt = " AND v.RevisionTecnicaEstado = 'vigente'";
}

$stmtV = $pdo->prepare("
    SELECT v.*, c.Nombre AS ClienteNombre,
           (SELECT COUNT(*) FROM ordenestrabajo ot WHERE ot.VehiculoID = v.VehiculoID) AS TotalOrdenes
    FROM vehiculos v
    JOIN clientes c ON v.ClienteID = c.ClienteID
    WHERE v.Activo = TRUE AND (:q = '' OR v.Patente LIKE :like OR v.Marca LIKE :like2 OR v.Modelo LIKE :like3 OR c.Nombre LIKE :like4)
    {$sqlFiltroRt}
    ORDER BY v.VehiculoID DESC
");
$stmtV->execute([':q' => $q, ':like' => "%$q%", ':like2' => "%$q%", ':like3' => "%$q%", ':like4' => "%$q%"]);
$vehiculos = $stmtV->fetchAll();

// Contadores rápidos para los filtros de Revisión Técnica
$totalVehiculos = (int)$pdo->query("SELECT COUNT(*) FROM vehiculos WHERE Activo = TRUE")->fetchColumn();
$totalVencidos = (int)$pdo->query("SELECT COUNT(*) FROM vehiculos WHERE Activo = TRUE AND RevisionTecnicaEstado = 'vencida'")->fetchColumn();
$totalPorVencer = (int)$pdo->query("SELECT COUNT(*) FROM vehiculos WHERE Activo = TRUE AND RevisionTecnicaEstado = 'por_vencer'")->fetchColumn();

// Enriquecer cada vehículo con su información PRT calculada
foreach ($vehiculos as &$veh) {
    $veh['PRT'] = calcularCalendarioPRT(
        $veh['Patente'],
        $veh['TipoVehiculo'] ?? '',
        $veh['RevisionTecnicaRegimen'] ?? (!empty($veh['EsTransportePublico']) ? 'semestral' : null),
        null,
        $veh['RevisionTecnicaUltima'] ?? null,
        $veh['RevisionTecnicaVencimiento'] ?? null,
        $veh['Anio'] ? (int)$veh['Anio'] : null
    );
}
unset($veh);

include __DIR__ . '/views/vehiculos.view.php';
require_once __DIR__ . '/includes/layout/footer.php';

