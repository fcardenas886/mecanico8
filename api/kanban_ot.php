<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
if (!csrfMatches($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Token de seguridad inválido o expirado. Refresca la página.']);
    exit;
}
$action = $input['action'] ?? '';
$otId = (int)($input['ot_id'] ?? 0);

if ($otId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Orden de trabajo inválida']);
    exit;
}

$pdo = getDB();

// Cargar la OT actual
$stmtOT = $pdo->prepare("SELECT * FROM ordenestrabajo WHERE OrdenTrabajoID = :id");
$stmtOT->execute([':id' => $otId]);
$ot = $stmtOT->fetch();

if (!$ot) {
    echo json_encode(['success' => false, 'error' => 'Orden de trabajo no encontrada']);
    exit;
}

try {
    if ($action === 'mover_columna') {
        $columna = $input['columna'] ?? '';
        $columnasValidas = ['ingresado', 'diagnostico', 'esperando', 'reparacion', 'listo', 'entregado'];

        if (!in_array($columna, $columnasValidas, true)) {
            echo json_encode(['success' => false, 'error' => 'Columna destino no válida']);
            exit;
        }

        $nuevoEstado = $ot['Estado'];
        $mensaje = '';

        switch ($columna) {
            case 'ingresado':
                $nuevoEstado = 'Ingresado';
                $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Ingresado' WHERE OrdenTrabajoID = :id")
                    ->execute([':id' => $otId]);
                $mensaje = 'OT marcada como Ingresada';
                break;

            case 'diagnostico':
                if (in_array($ot['Estado'], ['Diagnosticado', 'Diagnóstico no aplica'], true)) {
                    $nuevoEstado = $ot['Estado'];
                } else {
                    $nuevoEstado = 'En diagnóstico';
                    $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En diagnóstico' WHERE OrdenTrabajoID = :id")
                        ->execute([':id' => $otId]);
                }
                $mensaje = 'OT en etapa de Diagnóstico';
                break;

            case 'esperando':
                // Si tiene presupuesto, dejar la decisión como pendiente
                $stmtP = $pdo->prepare("SELECT PresupuestoID FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
                $stmtP->execute([':id' => $otId]);
                $presupuesto = $stmtP->fetch();
                if ($presupuesto) {
                    $pdo->prepare("UPDATE presupuestos SET DecisionCliente = 'Pendiente' WHERE PresupuestoID = :pid")
                        ->execute([':pid' => $presupuesto['PresupuestoID']]);
                }
                if ($ot['Estado'] === 'Ingresado' || $ot['Estado'] === 'En diagnóstico') {
                    $nuevoEstado = 'Diagnosticado';
                    $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Diagnosticado' WHERE OrdenTrabajoID = :id")
                        ->execute([':id' => $otId]);
                }
                $mensaje = 'OT en Espera de Respuesta del Cliente';
                break;

            case 'reparacion':
                // Si el presupuesto estaba pendiente, se auto-aprueba al moverlo a reparación
                $stmtP = $pdo->prepare("SELECT PresupuestoID, DecisionCliente FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
                $stmtP->execute([':id' => $otId]);
                $presupuesto = $stmtP->fetch();
                if ($presupuesto && $presupuesto['DecisionCliente'] !== 'Aprobado') {
                    $pdo->prepare("UPDATE presupuestos SET DecisionCliente = 'Aprobado' WHERE PresupuestoID = :pid")
                        ->execute([':pid' => $presupuesto['PresupuestoID']]);
                }

                $nuevoEstado = 'En reparación';
                $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En reparación' WHERE OrdenTrabajoID = :id")
                    ->execute([':id' => $otId]);
                $mensaje = 'OT en Reparación activa';
                break;

            case 'listo':
                $nuevoEstado = 'Listo para entregar';
                $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Listo para entregar' WHERE OrdenTrabajoID = :id")
                    ->execute([':id' => $otId]);
                $mensaje = 'OT marcada como Lista para Retiro';
                break;

            case 'entregado':
                $nuevoEstado = 'Entregado';
                $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Entregado', FechaEntrega = COALESCE(FechaEntrega, NOW()) WHERE OrdenTrabajoID = :id")
                    ->execute([':id' => $otId]);
                $mensaje = 'OT marcada como Entregada';
                break;
        }

        echo json_encode([
            'success' => true,
            'ot_id' => $otId,
            'columna' => $columna,
            'nuevo_estado' => $nuevoEstado,
            'mensaje' => $mensaje
        ]);
        exit;

    } elseif ($action === 'asignar_mecanico') {
        $mecanicoId = (int)($input['mecanico_id'] ?? 0);
        $mecanicoVal = $mecanicoId > 0 ? $mecanicoId : null;

        $pdo->prepare("UPDATE ordenestrabajo SET MecanicoID = :m WHERE OrdenTrabajoID = :id")
            ->execute([':m' => $mecanicoVal, ':id' => $otId]);

        $mecanicoNombre = 'Sin asignar';
        if ($mecanicoVal) {
            $stmtM = $pdo->prepare("SELECT Nombre FROM usuarios WHERE UsuarioID = :id");
            $stmtM->execute([':id' => $mecanicoVal]);
            $mecanicoNombre = $stmtM->fetchColumn() ?: 'Mecánico';

            if ($ot['Estado'] === 'Presupuesto aprobado') {
                $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En reparación' WHERE OrdenTrabajoID = :id")
                    ->execute([':id' => $otId]);
            }
        }

        echo json_encode([
            'success' => true,
            'ot_id' => $otId,
            'mecanico_id' => $mecanicoVal,
            'mecanico_nombre' => $mecanicoNombre,
            'mensaje' => 'Mecánico actualizado a ' . $mecanicoNombre
        ]);
        exit;

    } else {
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos: ' . $e->getMessage()]);
    exit;
}
