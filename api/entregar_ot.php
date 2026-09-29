<?php
/**
 * API para marcar una Orden de Trabajo como Entregada desde Caja POS o interfaces AJAX.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/core/auth.php';
require_once __DIR__ . '/../includes/dominio/ot_entrega.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
verifyCsrfApi();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$otId = (int)($input['ot_id'] ?? 0);

if ($otId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Falta ot_id.']);
    exit;
}

try {
    $pdo = getDB();
    $userId = (int)($_SESSION['usuario']['UsuarioID'] ?? $_SESSION['usuario']['id'] ?? 1);
    $res = entregarOrdenTrabajo($pdo, $otId, $userId);
    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
