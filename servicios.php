<?php
require_once __DIR__ . '/includes/auth.php';
checkRole(['Administrador', 'Supervisor']);
$pdo = getDB();
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '') ?: 'General';
    $precio = (int)($_POST['precio'] ?? 0);
    $politica = ($_POST['politica'] ?? '') === 'SoloSiNoAprueba' ? 'SoloSiNoAprueba' : 'Siempre';

    if ($action === 'crear') {
        if ($nombre === '' || $precio <= 0) {
            $error = 'Indica el nombre del servicio y un precio mayor a 0.';
        } else {
            $orden = (int)$pdo->query("SELECT COALESCE(MAX(Orden), 0) + 1 FROM operacionessolicitadas")->fetchColumn();
            $pdo->prepare("INSERT INTO operacionessolicitadas (Nombre, Categoria, Orden, Activo, PrecioBase, PoliticaCobro) VALUES (:n, :c, :o, 1, :p, :pol)")
                ->execute([':n' => $nombre, ':c' => $categoria, ':o' => $orden, ':p' => $precio, ':pol' => $politica]);
            $mensaje = 'Servicio agregado.';
        }
    } elseif ($action === 'guardar') {
        if ($nombre === '' || $precio <= 0) {
            $error = 'El nombre y un precio mayor a 0 son obligatorios.';
        } else {
            $pdo->prepare("UPDATE operacionessolicitadas SET Nombre = :n, Categoria = :c, PrecioBase = :p, PoliticaCobro = :pol WHERE OperacionID = :id")
                ->execute([':n' => $nombre, ':c' => $categoria, ':p' => $precio, ':pol' => $politica, ':id' => $id]);
            $mensaje = 'Cambios guardados. Los presupuestos ya armados conservan el precio con que se cotizaron.';
        }
    } elseif ($action === 'activar') {
        $pdo->prepare("UPDATE operacionessolicitadas SET Activo = NOT Activo WHERE OperacionID = :id AND EsDiagnosticoBase = 0")->execute([':id' => $id]);
    }
}

$servicios = $pdo->query("SELECT * FROM operacionessolicitadas ORDER BY EsDiagnosticoBase DESC, Activo DESC, Categoria, Orden")->fetchAll();
$politicas = politicasCobro();

require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/servicios.view.php';
require_once __DIR__ . '/includes/footer.php';
