<?php
require_once __DIR__ . '/includes/header.php';
checkRole(['Administrador', 'Supervisor']);

$pdo = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $accion = $_POST['action'] ?? 'crear';

    if ($accion === 'eliminar') {
        $promoID = (int)($_POST['promo_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM promociones WHERE PromocionID = ?");
            $stmt->execute([$promoID]);
            $message = 'Promoción eliminada con éxito.';
        } catch (Exception $e) {
            $error = 'Error al eliminar la promoción: ' . $e->getMessage();
        }
    } elseif ($accion === 'eliminar_combo') {
        $comboID = (int)($_POST['combo_id'] ?? 0);
        try {
            $pdo->prepare("DELETE FROM promociones_combos WHERE ComboID = ?")->execute([$comboID]);
            $message = 'Combo eliminado con éxito.';
        } catch (Exception $e) {
            $error = 'Error al eliminar el combo: ' . $e->getMessage();
        }
    } elseif ($accion === 'activar_combo') {
        $comboID = (int)($_POST['combo_id'] ?? 0);
        $pdo->prepare("UPDATE promociones_combos SET Activa = NOT Activa WHERE ComboID = ?")->execute([$comboID]);
    } elseif ($accion === 'crear_combo') {
        $nombreCombo = trim($_POST['combo_nombre'] ?? '');
        $tipoDescuento = in_array($_POST['combo_tipo_descuento'] ?? '', ['PORCENTAJE', 'MONTO_FIJO', 'PRECIO_FIJO'], true)
            ? $_POST['combo_tipo_descuento'] : 'PORCENTAJE';
        $valorDescuento = (float)($_POST['combo_valor_descuento'] ?? 0);
        $fechaInicioCombo = $_POST['combo_fecha_inicio'] ?? date('Y-m-d');
        $fechaFinCombo = $_POST['combo_fecha_fin'] ?? date('Y-m-d', strtotime('+30 days'));

        $cupoModos = $_POST['cupo_modo'] ?? [];
        $cupoTipos = $_POST['cupo_tipo'] ?? [];
        $cupoProductos = $_POST['cupo_producto'] ?? [];
        $cupoCantidades = $_POST['cupo_cantidad'] ?? [];

        $cupos = [];
        foreach ($cupoModos as $i => $modo) {
            $modo = in_array($modo, ['TIPO_REPUESTO', 'PRODUCTO_ESPECIFICO'], true) ? $modo : 'TIPO_REPUESTO';
            $cantidad = (float)($cupoCantidades[$i] ?? 1);
            if ($cantidad <= 0) continue;
            if ($modo === 'PRODUCTO_ESPECIFICO') {
                $pid = (int)($cupoProductos[$i] ?? 0);
                if ($pid <= 0) continue;
                $cupos[] = ['modo' => $modo, 'tipo' => null, 'producto' => $pid, 'cantidad' => $cantidad];
            } else {
                $tipoRep = array_key_exists($cupoTipos[$i] ?? '', tiposRepuesto()) ? $cupoTipos[$i] : null;
                if (!$tipoRep) continue;
                $cupos[] = ['modo' => $modo, 'tipo' => $tipoRep, 'producto' => null, 'cantidad' => $cantidad];
            }
        }

        if ($nombreCombo === '') {
            $error = 'Ponle un nombre al combo (ej. "Pack Mantención Aceite + Filtro").';
        } elseif ($valorDescuento <= 0) {
            $error = 'Indica el valor del descuento del combo.';
        } elseif (count($cupos) < 2) {
            $error = 'Un combo necesita al menos 2 cupos (ej. Aceite + Filtro de Aceite).';
        } else {
            try {
                $pdo->beginTransaction();
                $stmtCombo = $pdo->prepare("
                    INSERT INTO promociones_combos (Nombre, TipoDescuento, ValorDescuento, FechaInicio, FechaFin, Activa)
                    VALUES (:nombre, :tipo, :valor, :inicio, :fin, TRUE)
                ");
                $stmtCombo->execute([
                    ':nombre' => $nombreCombo,
                    ':tipo' => $tipoDescuento,
                    ':valor' => $valorDescuento,
                    ':inicio' => date('Y-m-d 00:00:00', strtotime($fechaInicioCombo)),
                    ':fin' => date('Y-m-d 23:59:59', strtotime($fechaFinCombo)),
                ]);
                $comboID = (int)$pdo->lastInsertId();

                $stmtCupo = $pdo->prepare("
                    INSERT INTO promociones_combo_items (ComboID, ModoSeleccion, TipoRepuesto, ProductoID, CantidadRequerida)
                    VALUES (:combo, :modo, :tipo, :producto, :cantidad)
                ");
                foreach ($cupos as $c) {
                    $stmtCupo->execute([
                        ':combo' => $comboID, ':modo' => $c['modo'], ':tipo' => $c['tipo'],
                        ':producto' => $c['producto'], ':cantidad' => $c['cantidad'],
                    ]);
                }
                $pdo->commit();
                $message = 'Combo "' . $nombreCombo . '" creado con ' . count($cupos) . ' cupos.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error al crear el combo: ' . $e->getMessage();
            }
        }
    } else {
        $productoID = (int)($_POST['producto_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'DESCUENTO_UNIT';

        // Configurar valores basados en el tipo de oferta
        if ($tipo === 'DESCUENTO_UNIT') {
            $cantidadMinima = 1.000;
            $descuentoPorcentaje = (float)($_POST['descuento_porcentaje'] ?? 0);
            $precioOferta = 0;
        } else { // MULTIBUY
            $cantidadMinima = (float)($_POST['cantidad_minima'] ?? 3);
            $descuentoPorcentaje = 0.00;
            $precioOferta = (int)($_POST['precio_oferta'] ?? 0);
        }

        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+30 days'));

        if ($productoID > 0) {
            try {
                // Formatear fechas para DATETIME de MySQL
                $inicioFormatted = date('Y-m-d 00:00:00', strtotime($fechaInicio));
                $finFormatted = date('Y-m-d 23:59:59', strtotime($fechaFin));

                $stmt = $pdo->prepare("
                    INSERT INTO promociones (ProductoID, Tipo, CantidadMinima, DescuentoPorcentaje, PrecioOferta, FechaInicio, FechaFin, Activa)
                    VALUES (:pid, :tipo, :cant_min, :desc_porc, :precio_of, :inicio, :fin, TRUE)
                ");
                $stmt->execute([
                    ':pid' => $productoID,
                    ':tipo' => $tipo,
                    ':cant_min' => $cantidadMinima,
                    ':desc_porc' => $descuentoPorcentaje,
                    ':precio_of' => $precioOferta,
                    ':inicio' => $inicioFormatted,
                    ':fin' => $finFormatted
                ]);
                $message = 'Promoción creada exitosamente.';
            } catch (Exception $e) {
                $error = 'Error al crear la promoción: ' . $e->getMessage();
            }
        } else {
            $error = 'Selecciona un producto.';
        }
    }
}

// Cargar promociones con joins a Productos
try {
    $stmtPromo = $pdo->query("
        SELECT pr.*, p.Nombre AS ProductoName, p.PrecioVenta 
        FROM promociones pr
        JOIN productos p ON pr.ProductoID = p.ProductoID
        ORDER BY pr.PromocionID DESC
    ");
    $promociones = $stmtPromo->fetchAll();
} catch (Exception $e) {
    $promociones = [];
}

$productosList = $pdo->query("SELECT ProductoID, Nombre, PrecioVenta FROM productos WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

// Cargar combos con sus cupos (uno o dos queries en vez de N+1)
$combos = $pdo->query("SELECT * FROM promociones_combos ORDER BY ComboID DESC")->fetchAll();
if (!empty($combos)) {
    $stmtCupos = $pdo->prepare("
        SELECT ci.*, p.Nombre AS ProductoNombre
        FROM promociones_combo_items ci
        LEFT JOIN productos p ON ci.ProductoID = p.ProductoID
        WHERE ci.ComboID = :id
        ORDER BY ci.ComboItemID ASC
    ");
    foreach ($combos as &$c) {
        $stmtCupos->execute([':id' => $c['ComboID']]);
        $c['cupos'] = $stmtCupos->fetchAll();
    }
    unset($c);
}
$tiposRepuestoList = tiposRepuesto();

include __DIR__ . '/views/promociones.view.php';
require_once __DIR__ . '/includes/footer.php';
