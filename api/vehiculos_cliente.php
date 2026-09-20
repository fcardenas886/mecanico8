<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$clienteId = (int)($_GET['cliente_id'] ?? 0);
if ($clienteId <= 0) {
    echo json_encode(['vehiculos' => []]);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT VehiculoID, Patente, Marca, Modelo, Anio, Color, KilometrajeUltimo
        FROM vehiculos
        WHERE ClienteID = :cid AND Activo = TRUE
        ORDER BY Patente ASC
    ");
    $stmt->execute([':cid' => $clienteId]);
    echo json_encode(['vehiculos' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al buscar vehículos: ' . $e->getMessage()]);
}
