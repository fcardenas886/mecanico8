<?php
/**
 * Al entregar una orden, recuerda qué repuestos aprobados se usaron en ese
 * vehículo (marca, modelo, año y motor) para sugerirlos la próxima vez.
 * Solo aprende repuestos con tipo definido (aceite, filtros, frenos, bujías...),
 * no los de tipo "General".
 *
 * Devuelve la lista de nombres de repuestos que quedaron registrados.
 */
function aprenderRepuestosOT(PDO $pdo, int $otId): array {
    $stmtV = $pdo->prepare("
        SELECT v.Marca, v.Modelo, v.Anio, v.Motor
        FROM ordenestrabajo ot JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
        WHERE ot.OrdenTrabajoID = :id
    ");
    $stmtV->execute([':id' => $otId]);
    $veh = $stmtV->fetch();
    if (!$veh || trim((string)$veh['Marca']) === '' || trim((string)$veh['Modelo']) === '') {
        return [];
    }
    $marca = trim($veh['Marca']);
    $modelo = trim($veh['Modelo']);
    $anio = (int)$veh['Anio'] ?: null;
    $motor = trim((string)$veh['Motor']);

    $stmtR = $pdo->prepare("
        SELECT DISTINCT p.ProductoID, p.Nombre
        FROM presupuestos pr
        JOIN presupuestodetalle pd ON pd.PresupuestoID = pr.PresupuestoID
        JOIN productos p ON pd.ProductoID = p.ProductoID
        WHERE pr.OrdenTrabajoID = :id AND pd.TipoLinea = 'Repuesto' AND pd.Aprobado = 1
          AND p.TipoRepuesto <> 'General'
    ");
    $stmtR->execute([':id' => $otId]);
    $repuestos = $stmtR->fetchAll();

    $stmtBuscar = $pdo->prepare("
        SELECT CompatibilidadID FROM compatibilidadrepuestos
        WHERE ProductoID = :p AND LOWER(MarcaVehiculo) = LOWER(:m) AND LOWER(ModeloVehiculo) = LOWER(:mo)
          AND LOWER(COALESCE(Motor, '')) = LOWER(:motor)
        LIMIT 1
    ");
    $stmtSubir = $pdo->prepare("
        UPDATE compatibilidadrepuestos
        SET VecesUsado = VecesUsado + 1, UltimoUso = NOW(),
            AnioDesde = CASE WHEN :a1 IS NULL THEN AnioDesde ELSE LEAST(COALESCE(AnioDesde, :a2), :a3) END,
            AnioHasta = CASE WHEN :a4 IS NULL THEN AnioHasta ELSE GREATEST(COALESCE(AnioHasta, :a5), :a6) END
        WHERE CompatibilidadID = :id
    ");
    $stmtNuevo = $pdo->prepare("
        INSERT INTO compatibilidadrepuestos
            (ProductoID, MarcaVehiculo, ModeloVehiculo, AnioDesde, AnioHasta, Motor, Notas, Origen, VecesUsado, UltimoUso)
        VALUES (:p, :m, :mo, :a1, :a2, :motor, :notas, 'Aprendido', 1, NOW())
    ");

    $aprendidos = [];
    foreach ($repuestos as $r) {
        $stmtBuscar->execute([':p' => $r['ProductoID'], ':m' => $marca, ':mo' => $modelo, ':motor' => $motor]);
        $existente = $stmtBuscar->fetchColumn();
        if ($existente) {
            $stmtSubir->execute([
                ':a1' => $anio, ':a2' => $anio, ':a3' => $anio, ':a4' => $anio, ':a5' => $anio, ':a6' => $anio,
                ':id' => $existente,
            ]);
        } else {
            $stmtNuevo->execute([
                ':p' => $r['ProductoID'], ':m' => $marca, ':mo' => $modelo,
                ':a1' => $anio, ':a2' => $anio, ':motor' => $motor !== '' ? $motor : null,
                ':notas' => 'Aprendido de ' . formatFolioOT($otId),
            ]);
        }
        $aprendidos[] = $r['Nombre'];
    }
    return $aprendidos;
}
