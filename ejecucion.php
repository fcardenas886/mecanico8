<?php
require_once __DIR__ . '/includes/layout/header.php';

$pdo = getDB();
$user = currentUser();
$error = '';

$otId = (int)($_GET['id'] ?? $_POST['ot_id'] ?? 0);

function cargarOT(PDO $pdo, int $otId) {
    $stmt = $pdo->prepare("
        SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, c.Nombre AS ClienteNombre,
               c.Telefono AS ClienteTelefono, m.Nombre AS MecanicoNombre
        FROM ordenestrabajo ot
        JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
        JOIN clientes c ON ot.ClienteID = c.ClienteID
        LEFT JOIN usuarios m ON ot.MecanicoID = m.UsuarioID
        WHERE ot.OrdenTrabajoID = :id
    ");
    $stmt->execute([':id' => $otId]);
    return $stmt->fetch();
}

$ot = cargarOT($pdo, $otId);
if (!$ot) {
    http_response_code(404);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Orden de Trabajo no encontrada</h2><a href='ordenestrabajo.php'>Volver al listado</a></div>");
}

$estadosValidos = ['Presupuesto aprobado', 'Presupuesto rechazado', 'En reparación', 'Listo para entregar', 'Entregado'];
if (!in_array($ot['Estado'], $estadosValidos, true)) {
    http_response_code(400);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Esta OT todavía no tiene un presupuesto decidido</h2><p>Primero registra la decisión del cliente en el Presupuesto.</p><a href='presupuesto.php?id=$otId'>Ir al Presupuesto</a></div>");
}

$stmtP = $pdo->prepare("SELECT PresupuestoID FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
$stmtP->execute([':id' => $otId]);
$presupuesto = $stmtP->fetch();

function cargarLineasAprobadas(PDO $pdo, int $presupuestoId) {
    $stmt = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid AND Aprobado = 1 ORDER BY PresupuestoDetalleID ASC");
    $stmt->execute([':pid' => $presupuestoId]);
    return $stmt->fetchAll();
}

$lineas = $presupuesto ? cargarLineasAprobadas($pdo, $presupuesto['PresupuestoID']) : [];
$lineasRepuesto = array_filter($lineas, fn($l) => $l['TipoLinea'] === 'Repuesto');
$lineasManoObra = array_filter($lineas, fn($l) => $l['TipoLinea'] !== 'Repuesto');
$totalRepuestos = array_sum(array_column($lineasRepuesto, 'Subtotal'));
$totalManoObra = array_sum(array_column($lineasManoObra, 'Subtotal'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'asignar_mecanico') {
        $mecanicoId = (int)($_POST['mecanico_id'] ?? 0) ?: null;
        $pdo->prepare("UPDATE ordenestrabajo SET MecanicoID = :m WHERE OrdenTrabajoID = :id")
            ->execute([':m' => $mecanicoId, ':id' => $otId]);
        if ($mecanicoId && $ot['Estado'] === 'Presupuesto aprobado') {
            $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En reparación' WHERE OrdenTrabajoID = :id")
                ->execute([':id' => $otId]);
        }
    } elseif ($action === 'marcar_listo') {
        $totalPresupuesto = $totalRepuestos + $totalManoObra;
        // ManoObraCobrada cubre OTs antiguas que usaron el mecanismo previo (movimiento de caja aparte).
        if ($totalPresupuesto <= 0 || !empty($ot['VentaID']) || $ot['ManoObraCobrada']) {
            $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Listo para entregar' WHERE OrdenTrabajoID = :id")
                ->execute([':id' => $otId]);
        } else {
            $error = 'Todavía falta cobrar la OT en caja antes de marcarla como lista.';
        }
    } elseif ($action === 'marcar_entregado') {
        require_once __DIR__ . '/includes/dominio/ot_entrega.php';
        $userId = (int)($user['UsuarioID'] ?? $user['id'] ?? 1);
        $resEntrega = entregarOrdenTrabajo($pdo, $otId, $userId);
        if (!$resEntrega['success']) {
            $error = $resEntrega['error'];
        } else {
            $aprendidos = $resEntrega['aprendidos'] ?? [];
        }
    }

    $ot = cargarOT($pdo, $otId);
}

$mecanicos = $pdo->query("SELECT UsuarioID, Nombre FROM usuarios WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

include __DIR__ . '/views/ejecucion.view.php';
require_once __DIR__ . '/includes/layout/footer.php';
