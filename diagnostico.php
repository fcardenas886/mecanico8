<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDB();
$user = currentUser();
$error = '';
$message = '';

$otId = (int)($_GET['id'] ?? $_POST['ot_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.KilometrajeUltimo, c.Nombre AS ClienteNombre
    FROM ordenestrabajo ot
    JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
    JOIN clientes c ON ot.ClienteID = c.ClienteID
    WHERE ot.OrdenTrabajoID = :id
");
$stmt->execute([':id' => $otId]);
$ot = $stmt->fetch();

if (!$ot) {
    http_response_code(404);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Orden de Trabajo no encontrada</h2><a href='ordenestrabajo.php'>Volver al listado</a></div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

    if ($action === 'add_hallazgo') {
        $area = $_POST['area'] ?? 'Mecánica';
        $hallazgo = trim($_POST['hallazgo'] ?? '');
        if ($hallazgo !== '' && in_array($area, ['Mecánica', 'Electricidad', 'Carrocería'], true)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO diagnosticoot (OrdenTrabajoID, Area, Hallazgo, UsuarioID)
                    VALUES (:ot, :area, :hallazgo, :uid)
                ");
                $stmt->execute([':ot' => $otId, ':area' => $area, ':hallazgo' => $hallazgo, ':uid' => $user['id']]);
                $nuevoId = (int)$pdo->lastInsertId();

                if ($ot['Estado'] === 'Ingresado') {
                    $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En diagnóstico' WHERE OrdenTrabajoID = :id")
                        ->execute([':id' => $otId]);
                    $ot['Estado'] = 'En diagnóstico';
                }
                $message = 'Hallazgo agregado correctamente.';

                if ($isAjax) {
                    $areaKeyMap = ['Mecánica' => 'mecanica', 'Electricidad' => 'electricidad', 'Carrocería' => 'carroceria'];
                    $areaKey = $areaKeyMap[$area] ?? strtolower($area);

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message' => $message,
                        'item' => [
                            'id' => $nuevoId,
                            'area' => $area,
                            'area_key' => $areaKey,
                            'hallazgo' => $hallazgo,
                            'usuario' => $user['nombre'] ?? 'Mecánico',
                            'fecha' => date('d/m/Y H:i'),
                        ]
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Error al registrar el hallazgo: ' . $e->getMessage();
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        } else {
            $error = 'Escribe el hallazgo antes de agregarlo.';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    } elseif ($action === 'finalizar' || $action === 'omitir') {
        // Blindaje: si vienen observaciones en el formulario, guardarlas antes de cerrar
        if (isset($_POST['observaciones'])) {
            $obs = trim($_POST['observaciones']);
            $stmtEst = $pdo->prepare("
                INSERT INTO estacionservicio_ot (OrdenTrabajoID, Observaciones)
                VALUES (:ot, :obs)
                ON DUPLICATE KEY UPDATE Observaciones = VALUES(Observaciones)
            ");
            $stmtEst->execute([':ot' => $otId, ':obs' => $obs ?: null]);
        }

        $nuevoEstado = $action === 'finalizar' ? 'Diagnosticado' : 'Diagnóstico no aplica';
        $pdo->prepare("UPDATE ordenestrabajo SET Estado = :estado WHERE OrdenTrabajoID = :id")
            ->execute([':estado' => $nuevoEstado, ':id' => $otId]);
        $ot['Estado'] = $nuevoEstado;

        // Asegurar que el Presupuesto quede creado e inicializado
        require_once __DIR__ . '/includes/servicios.php';
        $stmtP = $pdo->prepare("SELECT PresupuestoID FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
        $stmtP->execute([':id' => $otId]);
        $presupuestoId = $stmtP->fetchColumn();
        if (!$presupuestoId) {
            $stmtNewP = $pdo->prepare("INSERT INTO presupuestos (OrdenTrabajoID, UsuarioID) VALUES (:ot, :uid)");
            $stmtNewP->execute([':ot' => $otId, ':uid' => $user['id']]);
            $presupuestoId = (int)$pdo->lastInsertId();
            agregarDiagnosticoAlPresupuesto($pdo, $presupuestoId, $nuevoEstado);
        }

        // Redirigir de inmediato al Presupuesto sin pantalla intermedia innecesaria
        header("Location: presupuesto.php?id=" . $otId);
        exit;
    } elseif ($action === 'eliminar_hallazgo') {
        $diagId = (int)($_POST['diagnostico_id'] ?? 0);
        $pdo->prepare("DELETE FROM diagnosticoot WHERE DiagnosticoID = :id AND OrdenTrabajoID = :ot")
            ->execute([':id' => $diagId, ':ot' => $otId]);
        $message = 'Hallazgo eliminado.';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }
    } elseif ($action === 'guardar_estacion') {
        $obs = trim($_POST['observaciones'] ?? $_POST['estacion']['observaciones'] ?? '');
        $stmtEst = $pdo->prepare("
            INSERT INTO estacionservicio_ot (OrdenTrabajoID, Observaciones)
            VALUES (:ot, :obs)
            ON DUPLICATE KEY UPDATE Observaciones = VALUES(Observaciones)
        ");
        $stmtEst->execute([':ot' => $otId, ':obs' => $obs ?: null]);
        $message = 'Observaciones guardadas correctamente.';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }
    }
}

// Hallazgos por área
$stmtHallazgos = $pdo->prepare("
    SELECT d.*, u.Nombre AS UsuarioNombre
    FROM diagnosticoot d
    JOIN usuarios u ON d.UsuarioID = u.UsuarioID
    WHERE d.OrdenTrabajoID = :id
    ORDER BY d.Fecha ASC
");
$stmtHallazgos->execute([':id' => $otId]);
$hallazgos = $stmtHallazgos->fetchAll();
$hallazgosPorArea = ['Mecánica' => [], 'Electricidad' => [], 'Carrocería' => []];
foreach ($hallazgos as $h) {
    $hallazgosPorArea[$h['Area']][] = $h;
}

// Estación de Servicio
$stmtEst = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :id");
$stmtEst->execute([':id' => $otId]);
$estacion = $stmtEst->fetch() ?: null;

// Operaciones Solicitadas
$stmtOps = $pdo->prepare("SELECT NombreOperacion FROM orden_operaciones_solicitadas WHERE OrdenTrabajoID = :id");
$stmtOps->execute([':id' => $otId]);
$operacionesSolicitadas = $stmtOps->fetchAll(PDO::FETCH_COLUMN);

// Daños decodificados
$daniosCarroceria = json_decode($ot['DaniosCarroceriaJson'] ?? '[]', true) ?: [];

require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/diagnostico.view.php';
require_once __DIR__ . '/includes/footer.php';
