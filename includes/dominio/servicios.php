<?php
// Catálogo de servicios (mano de obra) con precio y política de cobro.
// Usa la tabla operacionessolicitadas, la misma lista que se marca al recibir el vehículo.

function politicasCobro(): array {
    return [
        'Siempre' => 'Se cobra cuando el cliente aprueba',
        'SoloSiNoAprueba' => 'No se cobra si aprueba; se cobra si no aprueba',
    ];
}

/**
 * Agrega la línea de "Diagnóstico general" al presupuesto, con su política de cobro,
 * salvo que la orden haya saltado el diagnóstico ("no aplica"), no haya diagnóstico
 * en el catálogo o ya esté agregado. Devuelve true si la agregó.
 */
function agregarDiagnosticoAlPresupuesto(PDO $pdo, int $presupuestoId, string $estadoOT): bool {
    if ($estadoOT === 'Diagnóstico no aplica') return false;

    $diag = $pdo->query("
        SELECT OperacionID, Nombre, PrecioBase, PoliticaCobro FROM operacionessolicitadas
        WHERE EsDiagnosticoBase = 1 AND Activo = TRUE AND PrecioBase > 0 LIMIT 1
    ")->fetch();
    if (!$diag) return false;

    $ya = $pdo->prepare("SELECT COUNT(*) FROM presupuestodetalle WHERE PresupuestoID = :p AND ServicioID = :s");
    $ya->execute([':p' => $presupuestoId, ':s' => $diag['OperacionID']]);
    if ((int)$ya->fetchColumn() > 0) return false;

    $pdo->prepare("
        INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, ServicioID, PoliticaCobro)
        VALUES (:p, 'ManoObra', :d, 1, :precio, :precio2, :s, :pol)
    ")->execute([
        ':p' => $presupuestoId, ':d' => $diag['Nombre'], ':precio' => $diag['PrecioBase'], ':precio2' => $diag['PrecioBase'],
        ':s' => $diag['OperacionID'], ':pol' => $diag['PoliticaCobro'],
    ]);
    return true;
}
