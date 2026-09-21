<?php
// Búsqueda de repuestos por número de parte (referencia). Se compara sin espacios,
// guiones, barras ni puntos: "W 67/1", "w671" y "W67-1" encuentran lo mismo.

function normalizarReferencia(string $s): string {
    return preg_replace('/[\s\-\/\.]+/', '', mb_strtoupper(trim($s)));
}

/** Expresión SQL que normaliza una columna igual que normalizarReferencia(). */
function sqlRefNormalizada(string $columna): string {
    return "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($columna, ''), ' ', ''), '-', ''), '/', ''), '.', ''))";
}

/**
 * Patrón LIKE para buscar por referencia, o null si el texto es muy corto
 * (con 1-2 caracteres casi todo coincidiría).
 */
function patronReferencia(string $busqueda): ?string {
    $n = normalizarReferencia($busqueda);
    return mb_strlen($n) >= 3 ? '%' . $n . '%' : null;
}

/** Cláusula SQL (sin parámetros de búsqueda de texto) que compara OEM y N° del fabricante. */
function sqlCoincideReferencia(string $alias, string $param1, string $param2): string {
    return '(' . sqlRefNormalizada("$alias.NumeroParteOEM") . " LIKE $param1 OR " . sqlRefNormalizada("$alias.NumeroParteAlternativo") . " LIKE $param2)";
}

/** Tipos de repuesto (valor en la base => texto para el usuario). */
function tiposRepuesto(): array {
    return [
        'General' => 'Otro / no es repuesto de motor', 'Aceite' => 'Aceite de motor', 'FiltroAceite' => 'Filtro de aceite',
        'FiltroAire' => 'Filtro de aire', 'FiltroCabina' => 'Filtro de cabina', 'FiltroCombustible' => 'Filtro de combustible',
        'Frenos' => 'Frenos (pastillas, discos)', 'Bujias' => 'Bujías', 'Distribucion' => 'Distribución (correas, kits)',
    ];
}
