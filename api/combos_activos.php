<?php
// Combos vigentes con sus cupos, para que la Caja los detecte en el carrito y muestre
// la etiqueta antes de cobrar. Es solo una vista previa: el cobro real siempre lo
// calcula y revalida el servidor en api/registrar_venta.php (includes/promociones_combos.php).
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$pdo = getDB();

$combos = $pdo->query("
    SELECT ComboID, Nombre, TipoDescuento, ValorDescuento
    FROM promociones_combos
    WHERE Activa = 1 AND FechaInicio <= NOW() AND FechaFin >= NOW()
    ORDER BY ComboID ASC
")->fetchAll();

$stmtCupos = $pdo->prepare("SELECT ModoSeleccion, TipoRepuesto, ProductoID, CantidadRequerida FROM promociones_combo_items WHERE ComboID = :id");
foreach ($combos as &$c) {
    $stmtCupos->execute([':id' => $c['ComboID']]);
    $c['cupos'] = $stmtCupos->fetchAll();
}
unset($c);

echo json_encode(['success' => true, 'combos' => $combos]);
