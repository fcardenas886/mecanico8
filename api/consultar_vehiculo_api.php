<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/vehiculo_api_helper.php';

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$patente = strtoupper(trim($_GET['patente'] ?? $_POST['patente'] ?? ''));
$patente = preg_replace('/[^A-Z0-9]/', '', $patente);

$vin = strtoupper(trim($_GET['vin'] ?? $_POST['vin'] ?? ''));
$vin = preg_replace('/[^A-Z0-9]/', '', $vin);

$forzarApi = !empty($_GET['forzar_api']) || !empty($_POST['forzar_api']);
$buscarLocal = empty($_GET['sin_local']) && empty($_POST['sin_local']);

if (empty($patente) && empty($vin)) {
    echo json_encode(['success' => false, 'error' => 'Debes ingresar una Patente o un número VIN/Chasis.']);
    exit;
}

$pdo = getDB();

// 1. Búsqueda en Base de Datos Local del Taller
if ($buscarLocal && !$forzarApi && !empty($patente)) {
    try {
        $stmt = $pdo->prepare("
            SELECT v.VehiculoID, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color,
                   v.Combustible, v.Motor, v.Transmision, v.TipoVehiculo, v.VIN,
                   v.KilometrajeUltimo, v.ClienteID,
                   c.Nombre AS ClienteNombre, c.Telefono AS ClienteTelefono, c.Email AS ClienteEmail,
                   CONCAT(COALESCE(c.RutCuerpo, ''), IF(c.RutDv IS NOT NULL, CONCAT('-', c.RutDv), '')) AS ClienteRUT,
                   (SELECT COUNT(*) FROM ordenestrabajo ot WHERE ot.VehiculoID = v.VehiculoID) AS TotalOrdenes,
                   (SELECT MAX(FechaIngreso) FROM ordenestrabajo ot WHERE ot.VehiculoID = v.VehiculoID) AS UltimaVisita
            FROM vehiculos v
            JOIN clientes c ON v.ClienteID = c.ClienteID
            WHERE v.Patente = :pat AND v.Activo = TRUE
            LIMIT 1
        ");
        $stmt->execute([':pat' => $patente]);
        $vehiculoLocal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($vehiculoLocal) {
            // Buscar si tiene alertas de mantenimiento pendientes o vencidas
            $alertasMantenimiento = [];
            try {
                $stmtM = $pdo->prepare("
                    SELECT TipoMantenimiento, KilometrajeProximo, FechaProxima
                    FROM historialmantenimiento
                    WHERE VehiculoID = :vid
                    ORDER BY FechaRealizado DESC
                    LIMIT 4
                ");
                $stmtM->execute([':vid' => $vehiculoLocal['VehiculoID']]);
                $kmAct = (int)($vehiculoLocal['KilometrajeUltimo'] ?? 0);
                foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $m) {
                    if ($m['KilometrajeProximo'] && $kmAct >= $m['KilometrajeProximo']) {
                        $alertasMantenimiento[] = "Toca " . $m['TipoMantenimiento'] . " (vencido a los " . number_format($m['KilometrajeProximo'], 0, ',', '.') . " km)";
                    } elseif ($m['FechaProxima'] && strtotime($m['FechaProxima']) <= time()) {
                        $alertasMantenimiento[] = "Toca " . $m['TipoMantenimiento'] . " (vencido el " . date('d/m/Y', strtotime($m['FechaProxima'])) . ")";
                    }
                }
            } catch (Exception $em) {}

            echo json_encode([
                'success' => true,
                'fuente' => 'local',
                'mensaje' => 'Vehículo registrado en el historial del taller.',
                'datos' => [
                    'vehiculo_id' => (int)$vehiculoLocal['VehiculoID'],
                    'patente' => $vehiculoLocal['Patente'],
                    'marca' => $vehiculoLocal['Marca'],
                    'modelo' => $vehiculoLocal['Modelo'],
                    'anio' => $vehiculoLocal['Anio'] ? (int)$vehiculoLocal['Anio'] : null,
                    'color' => $vehiculoLocal['Color'] ?? '',
                    'combustible' => $vehiculoLocal['Combustible'] ?? '',
                    'motor' => $vehiculoLocal['Motor'] ?? '',
                    'transmision' => $vehiculoLocal['Transmision'] ?? '',
                    'tipo_vehiculo' => $vehiculoLocal['TipoVehiculo'] ?? '',
                    'vin' => $vehiculoLocal['VIN'] ?? '',
                    'kilometraje' => $vehiculoLocal['KilometrajeUltimo'] ? (int)$vehiculoLocal['KilometrajeUltimo'] : null,
                    'cliente_id' => (int)$vehiculoLocal['ClienteID'],
                    'cliente_nombre' => $vehiculoLocal['ClienteNombre'],
                    'cliente_telefono' => $vehiculoLocal['ClienteTelefono'] ?? '',
                    'cliente_rut' => $vehiculoLocal['ClienteRUT'] ?? '',
                    'total_ordenes' => (int)$vehiculoLocal['TotalOrdenes'],
                    'ultima_visita' => $vehiculoLocal['UltimaVisita'] ?? null,
                    'alertas_mantenimiento' => $alertasMantenimiento
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        // En caso de error local, continúa con la búsqueda externa
    }
}

// 2. Obtener Parámetros de Configuración
$configs = [];
try {
    $stmtCfg = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave LIKE 'VEHICULO_API_%'");
    $configs = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

// 3. Ejecutar Consulta Externa
$resultado = consultarVehiculoPorPatenteOVin($patente, $vin, $configs);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
