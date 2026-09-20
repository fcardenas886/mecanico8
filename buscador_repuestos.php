<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

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
