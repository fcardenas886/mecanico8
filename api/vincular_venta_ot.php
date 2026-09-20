<?php
// Se llama desde pos.js justo después de registrar_venta.php cuando el carrito
// se cargó desde una OT: deja la venta real vinculada a la OT, sin que
// registrar_venta.php necesite saber nada de Ordenes de Trabajo.
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
verifyCsrfApi();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$otId = (int)($input['ot_id'] ?? 0);
$ventaId = (int)($input['venta_id'] ?? 0);

if ($otId <= 0 || $ventaId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Faltan ot_id o venta_id.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE ordenestrabajo SET VentaID = :vid WHERE OrdenTrabajoID = :id AND VentaID IS NULL");
    $stmt->execute([':vid' => $ventaId, ':id' => $otId]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
