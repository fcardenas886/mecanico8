<?php
/**
 * Lógica unificada para marcar una Orden de Trabajo como Entregada.
 * Utilizada por ejecucion.php (POST) y api/entregar_ot.php (AJAX desde POS).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/repuestos_aprendizaje.php';

function entregarOrdenTrabajo(PDO $pdo, int $otId, int $usuarioId): array {
    if ($otId <= 0) {
        return ['success' => false, 'error' => 'ID de Orden de Trabajo inválido.'];
    }

    // 1. Cargar datos de la OT
    $stmt = $pdo->prepare("
        SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.VehiculoID, c.Nombre AS ClienteNombre
        FROM ordenestrabajo ot
        JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
        JOIN clientes c ON ot.ClienteID = c.ClienteID
        WHERE ot.OrdenTrabajoID = :id
    ");
    $stmt->execute([':id' => $otId]);
    $ot = $stmt->fetch();

    if (!$ot) {
        return ['success' => false, 'error' => 'Orden de Trabajo no encontrada.'];
    }

    if ($ot['Estado'] === 'Entregado') {
        return ['success' => true, 'ya_entregado' => true, 'mensaje' => 'La orden ya estaba marcada como entregada.'];
    }

    // 2. Cargar líneas del presupuesto para verificar saldo y mantenimientos
    $stmtP = $pdo->prepare("SELECT PresupuestoID FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
    $stmtP->execute([':id' => $otId]);
    $presupuesto = $stmtP->fetch();

    $lineas = [];
    $totalPresupuesto = 0.0;
    if ($presupuesto) {
        $stmtL = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid AND Aprobado = 1");
        $stmtL->execute([':pid' => $presupuesto['PresupuestoID']]);
        $lineas = $stmtL->fetchAll();
        $totalPresupuesto = (float)array_sum(array_column($lineas, 'Subtotal'));
    }

    // 3. Validar cobro: debe estar pagada en caja o tener total 0
    $estaCobrado = ($totalPresupuesto <= 0 || !empty($ot['VentaID']) || !empty($ot['ManoObraCobrada']));
    if (!$estaCobrado) {
        return ['success' => false, 'error' => 'Antes de entregar el vehículo, la orden debe estar cobrada en caja.'];
    }

    // 4. Cambiar estado a Entregado y registrar fecha/hora
    $stmtUpd = $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Entregado', FechaEntrega = NOW() WHERE OrdenTrabajoID = :id");
    $stmtUpd->execute([':id' => $otId]);

    // 5. Aprendizaje de compatibilidad de repuestos para este modelo
    $aprendidos = [];
    try {
        $aprendidos = aprenderRepuestosOT($pdo, $otId);
    } catch (Exception $e) {
        $aprendidos = [];
    }

    // 6. Registro automático en historialmantenimiento si no fue registrado previamente
    try {
        $kmIngreso = (int)($ot['KilometrajeIngreso'] ?? 0);
        $vehiculoId = (int)$ot['VehiculoID'];

        $stmtChkHm = $pdo->prepare("SELECT COUNT(*) FROM historialmantenimiento WHERE OrdenTrabajoID = :ot");
        $stmtChkHm->execute([':ot' => $otId]);
        if ((int)$stmtChkHm->fetchColumn() === 0 && !empty($lineas)) {
            $stmtEstCfg = $pdo->prepare("SELECT AceiteIntervaloKm, AceiteIntervaloMeses, FrenosIntervaloKm, FrenosIntervaloMeses FROM estacionservicio_ot WHERE OrdenTrabajoID = :id");
            $stmtEstCfg->execute([':id' => $otId]);
            $estCfg = $stmtEstCfg->fetch();

            $aceiteKmInterval = (!empty($estCfg['AceiteIntervaloKm']) && (int)$estCfg['AceiteIntervaloKm'] > 0) ? (int)$estCfg['AceiteIntervaloKm'] : 10000;
            $aceiteMesesInterval = (!empty($estCfg['AceiteIntervaloMeses']) && (int)$estCfg['AceiteIntervaloMeses'] > 0) ? (int)$estCfg['AceiteIntervaloMeses'] : 6;

            $frenosKmInterval = (!empty($estCfg['FrenosIntervaloKm']) && (int)$estCfg['FrenosIntervaloKm'] > 0) ? (int)$estCfg['FrenosIntervaloKm'] : 25000;
            $frenosMesesInterval = (!empty($estCfg['FrenosIntervaloMeses']) && (int)$estCfg['FrenosIntervaloMeses'] > 0) ? (int)$estCfg['FrenosIntervaloMeses'] : 12;

            $stmtInsHm = $pdo->prepare("
                INSERT INTO historialmantenimiento
                    (VehiculoID, OrdenTrabajoID, TipoMantenimiento, KilometrajeRealizado, FechaRealizado,
                     KilometrajeProximo, FechaProxima, Estado, Notas, UsuarioID)
                VALUES
                    (:vid, :ot, :tipo, :kmr, NOW(), :kmp, :fp, 'Vigente', :notas, :uid)
            ");

            $tieneAceite = false;
            $tieneFrenos = false;
            $tieneDistribucion = false;

            foreach ($lineas as $l) {
                $desc = mb_strtolower($l['Descripcion']);
                if (str_contains($desc, 'aceite') || str_contains($desc, 'lubricante')) $tieneAceite = true;
                if (str_contains($desc, 'freno') || str_contains($desc, 'pastilla')) $tieneFrenos = true;
                if (str_contains($desc, 'distribucion') || str_contains($desc, 'correa') || str_contains($desc, 'distribución')) $tieneDistribucion = true;
            }

            if ($tieneAceite) {
                $stmtInsHm->execute([
                    ':vid' => $vehiculoId,
                    ':ot' => $otId,
                    ':tipo' => 'Cambio de Aceite y Filtro de Motor',
                    ':kmr' => $kmIngreso,
                    ':kmp' => $kmIngreso ? ($kmIngreso + $aceiteKmInterval) : null,
                    ':fp' => date('Y-m-d', strtotime("+{$aceiteMesesInterval} months")),
                    ':notas' => "Registrado automáticamente al entregar OT " . formatFolioOT($otId) . " (Intervalo: " . number_format($aceiteKmInterval, 0, ',', '.') . " km / {$aceiteMesesInterval} meses)",
                    ':uid' => $usuarioId
                ]);
            }
            if ($tieneFrenos) {
                $stmtInsHm->execute([
                    ':vid' => $vehiculoId,
                    ':ot' => $otId,
                    ':tipo' => 'Mantenimiento de Frenos',
                    ':kmr' => $kmIngreso,
                    ':kmp' => $kmIngreso ? ($kmIngreso + $frenosKmInterval) : null,
                    ':fp' => date('Y-m-d', strtotime("+{$frenosMesesInterval} months")),
                    ':notas' => "Registrado automáticamente al entregar OT " . formatFolioOT($otId) . " (Intervalo: " . number_format($frenosKmInterval, 0, ',', '.') . " km / {$frenosMesesInterval} meses)",
                    ':uid' => $usuarioId
                ]);
            }
            if ($tieneDistribucion) {
                $stmtInsHm->execute([
                    ':vid' => $vehiculoId,
                    ':ot' => $otId,
                    ':tipo' => 'Cambio de Kit de Distribución',
                    ':kmr' => $kmIngreso,
                    ':kmp' => $kmIngreso ? ($kmIngreso + 60000) : null,
                    ':fp' => date('Y-m-d', strtotime('+3 years')),
                    ':notas' => 'Registrado automáticamente al entregar OT ' . formatFolioOT($otId),
                    ':uid' => $usuarioId
                ]);
            }
        }
    } catch (Exception $e) {
        // Fallo no crítico en bitácora de historial
    }

    return ['success' => true, 'aprendidos' => $aprendidos, 'folio' => formatFolioOT($otId)];
}
