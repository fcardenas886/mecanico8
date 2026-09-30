<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/core/auth.php';
require_once __DIR__ . '/../includes/integraciones/revision_tecnica_helper.php';

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$vehiculoId = (int)($_POST['vehiculo_id'] ?? $_GET['vehiculo_id'] ?? 0);
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

if ($vehiculoId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Vehículo ID inválido']);
    exit;
}

$pdo = getDB();

switch ($action) {
    case 'marcar_renovada':
        $res = marcarPRTRenovada($vehiculoId, $pdo);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    case 'cambiar_regimen':
        $nuevoRegimen = strtolower(trim($_POST['regimen'] ?? ''));
        if (!in_array($nuevoRegimen, ['anual', 'semestral', 'cuatrimestral'], true)) {
            $nuevoRegimen = !empty($_POST['es_transporte']) ? 'semestral' : 'anual';
        }
        try {
            $esTp = ($nuevoRegimen !== 'anual') ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE vehiculos SET RevisionTecnicaRegimen = :reg, EsTransportePublico = :tp WHERE VehiculoID = :id");
            $stmt->execute([':reg' => $nuevoRegimen, ':tp' => $esTp, ':id' => $vehiculoId]);
            $cal = sincronizarVehiculoPRT($vehiculoId, $pdo, $nuevoRegimen);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Régimen actualizado exitosamente.',
                'revision_tecnica' => $cal
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'guardar_fecha_manual':
        $fechaVenc = trim($_POST['vencimiento'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVenc)) {
            echo json_encode(['success' => false, 'error' => 'Fecha de vencimiento inválida. Formato esperado: AAAA-MM-DD']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE vehiculos SET
                    RevisionTecnicaVencimiento = :venc,
                    RevisionTecnicaActualizadoEn = NOW()
                WHERE VehiculoID = :id
            ");
            $stmt->execute([':venc' => $fechaVenc, ':id' => $vehiculoId]);
            $cal = sincronizarVehiculoPRT($vehiculoId, $pdo);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Fecha de vencimiento guardada exitosamente.',
                'revision_tecnica' => $cal
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no soportada']);
        break;
}
