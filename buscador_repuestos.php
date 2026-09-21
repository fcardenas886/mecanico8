<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$mensaje = '';

// Quitar una compatibilidad aprendida por error (las cargadas a mano no se borran desde aquí)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quitar_compat') {
    verifyCsrf();
    $del = $pdo->prepare("DELETE FROM compatibilidadrepuestos WHERE CompatibilidadID = :id AND Origen = 'Aprendido'");
    $del->execute([':id' => (int)($_POST['compat_id'] ?? 0)]);
    $mensaje = $del->rowCount() ? 'Se quitó la sugerencia aprendida.' : '';
}

// Obtener lista de vehículos registrados
$vehiculos = $pdo->query("
    SELECT v.VehiculoID, v.Patente, v.Marca, v.Modelo, v.Anio, c.Nombre AS ClienteNombre
    FROM vehiculos v
    JOIN clientes c ON v.ClienteID = c.ClienteID
    WHERE v.Activo = TRUE
    ORDER BY v.Marca ASC, v.Modelo ASC
")->fetchAll();

// Obtener marcas y modelos disponibles en la matriz de compatibilidad
$marcasModelos = $pdo->query("
    SELECT DISTINCT MarcaVehiculo, ModeloVehiculo
    FROM compatibilidadrepuestos
    ORDER BY MarcaVehiculo ASC, ModeloVehiculo ASC
")->fetchAll();

// Parámetros de búsqueda
$vehiculoId = (int)($_GET['vehiculo_id'] ?? 0);
$marcaParam = trim($_GET['marca'] ?? '');
$modeloParam = trim($_GET['modelo'] ?? '');

$vehiculoSeleccionado = null;
if ($vehiculoId > 0) {
    $stmtV = $pdo->prepare("
        SELECT v.*, c.Nombre AS ClienteNombre
        FROM vehiculos v
        JOIN clientes c ON v.ClienteID = c.ClienteID
        WHERE v.VehiculoID = :id
    ");
    $stmtV->execute([':id' => $vehiculoId]);
    $vehiculoSeleccionado = $stmtV->fetch();
    if ($vehiculoSeleccionado) {
        $marcaParam = $vehiculoSeleccionado['Marca'];
        $modeloParam = $vehiculoSeleccionado['Modelo'];
    }
}

// Búsqueda de repuestos y aceites compatibles
$repuestosEncontrados = [];
if ($marcaParam !== '' || $modeloParam !== '') {
    $stmtRep = $pdo->prepare("
        SELECT cr.*, p.ProductoID, p.CodigoBarras, p.Nombre AS ProductoNombre, p.Descripcion AS ProductoDesc,
               p.MarcaRepuesto, p.NumeroParteOEM, p.NumeroParteAlternativo, p.ViscosidadAceite, p.TipoRepuesto,
               p.PrecioVenta, p.Stock, cat.Nombre AS CategoriaNombre
        FROM compatibilidadrepuestos cr
        JOIN productos p ON cr.ProductoID = p.ProductoID
        LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
        WHERE (LOWER(cr.MarcaVehiculo) LIKE LOWER(:marca) AND LOWER(cr.ModeloVehiculo) LIKE LOWER(:modelo))
           OR (LOWER(p.Nombre) LIKE LOWER(:likeModelo))
        ORDER BY 
          CASE p.TipoRepuesto
            WHEN 'Aceite' THEN 1
            WHEN 'FiltroAceite' THEN 2
            WHEN 'FiltroAire' THEN 3
            WHEN 'FiltroCabina' THEN 4
            WHEN 'FiltroCombustible' THEN 5
            WHEN 'Frenos' THEN 6
            WHEN 'Bujias' THEN 7
            ELSE 8
          END,
          p.Stock DESC, p.Nombre ASC
    ");
    $stmtRep->execute([
        ':marca' => "%$marcaParam%",
        ':modelo' => "%$modeloParam%",
        ':likeModelo' => "%$modeloParam%"
    ]);
    $repuestosEncontrados = $stmtRep->fetchAll();

    // Si hay un vehículo concreto, avisa cuando la compatibilidad es de otro motor o de otros años,
    // y deja esas opciones al final. Lo más comprobado por el taller va primero.
    // Motores distintos = cilindrada sin coincidencia (1.4 vs 1.6) o combustible distinto (diésel vs gasolina).
    $motorDistinto = function (string $veh, string $rep): bool {
        preg_match_all('/\d\.\d/', $veh, $a);
        preg_match_all('/\d\.\d/', $rep, $b);
        if ($a[0] && $b[0] && !array_intersect($a[0], $b[0])) return true;
        $comb = fn($s) => preg_match('/di[eé]sel|petrol/i', $s) ? 'd' : (preg_match('/gasolina|bencina/i', $s) ? 'g' : '');
        if ($comb($veh) && $comb($rep) && $comb($veh) !== $comb($rep)) return true;
        if (!$a[0] && !$b[0] && !$comb($veh) && !$comb($rep)) {
            $x = strtolower(preg_replace('/\s+/', '', $veh));
            $y = strtolower(preg_replace('/\s+/', '', $rep));
            return !str_contains($x, $y) && !str_contains($y, $x);
        }
        return false;
    };
    $motorVeh = $vehiculoSeleccionado ? trim((string)$vehiculoSeleccionado['Motor']) : '';
    $anioVeh = $vehiculoSeleccionado ? (int)$vehiculoSeleccionado['Anio'] : 0;
    foreach ($repuestosEncontrados as &$rep) {
        $avisos = [];
        $motorRep = trim((string)($rep['Motor'] ?? ''));
        if ($motorVeh !== '' && $motorRep !== '' && $motorDistinto($motorVeh, $motorRep)) {
            $avisos[] = 'Es para motor ' . $motorRep;
        }
        if ($anioVeh > 0 && (($rep['AnioDesde'] && $anioVeh < (int)$rep['AnioDesde']) || ($rep['AnioHasta'] && $anioVeh > (int)$rep['AnioHasta']))) {
            $rango = ($rep['AnioDesde'] ?: '?') . ((int)$rep['AnioDesde'] === (int)$rep['AnioHasta'] ? '' : '-' . ($rep['AnioHasta'] ?: '?'));
            $avisos[] = ($rep['Origen'] ?? '') === 'Aprendido' ? 'Solo comprobado en un ' . $rango : 'Es para años ' . $rango;
        }
        $rep['_avisos'] = $avisos;
    }
    unset($rep);
    usort($repuestosEncontrados, fn($a, $b) => [count($a['_avisos']) > 0, -(int)$a['VecesUsado']] <=> [count($b['_avisos']) > 0, -(int)$b['VecesUsado']]);
}

// Memoria del vehículo: lo último que se le puso en órdenes ya entregadas, por tipo de repuesto.
$usadosEnEsteAuto = [];
if ($vehiculoSeleccionado) {
    $stmtHist = $pdo->prepare("
        SELECT p.ProductoID, p.Nombre, p.TipoRepuesto, p.ViscosidadAceite, p.MarcaRepuesto, p.PrecioVenta, p.Stock,
               ot.OrdenTrabajoID, ot.FechaEntrega, ot.KilometrajeIngreso
        FROM presupuestodetalle pd
        JOIN presupuestos pr ON pd.PresupuestoID = pr.PresupuestoID
        JOIN ordenestrabajo ot ON pr.OrdenTrabajoID = ot.OrdenTrabajoID
        JOIN productos p ON pd.ProductoID = p.ProductoID
        WHERE ot.VehiculoID = :v AND ot.Estado = 'Entregado' AND pd.Aprobado = 1
          AND pd.TipoLinea = 'Repuesto' AND p.TipoRepuesto <> 'General'
        ORDER BY ot.FechaEntrega DESC
    ");
    $stmtHist->execute([':v' => $vehiculoSeleccionado['VehiculoID']]);
    foreach ($stmtHist->fetchAll() as $h) {
        $usadosEnEsteAuto[$h['TipoRepuesto']] ??= $h;
    }
}

// Agrupar repuestos por tipo
$repuestosPorTipo = [
    'Aceite' => [],
    'FiltroAceite' => [],
    'FiltroAire' => [],
    'FiltrosOtros' => [],
    'Frenos' => [],
    'Bujias' => [],
    'Otros' => []
];

foreach ($repuestosEncontrados as $r) {
    $t = $r['TipoRepuesto'];
    if ($t === 'Aceite') {
        $repuestosPorTipo['Aceite'][] = $r;
    } elseif ($t === 'FiltroAceite') {
        $repuestosPorTipo['FiltroAceite'][] = $r;
    } elseif ($t === 'FiltroAire') {
        $repuestosPorTipo['FiltroAire'][] = $r;
    } elseif (in_array($t, ['FiltroCabina', 'FiltroCombustible'], true)) {
        $repuestosPorTipo['FiltrosOtros'][] = $r;
    } elseif ($t === 'Frenos') {
        $repuestosPorTipo['Frenos'][] = $r;
    } elseif ($t === 'Bujias') {
        $repuestosPorTipo['Bujias'][] = $r;
    } else {
        $repuestosPorTipo['Otros'][] = $r;
    }
}

include __DIR__ . '/views/buscador_repuestos.view.php';
require_once __DIR__ . '/includes/footer.php';
