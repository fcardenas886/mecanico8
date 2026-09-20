<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$user = currentUser();
$error = '';

$otId = (int)($_GET['id'] ?? $_POST['ot_id'] ?? 0);

function cargarOT(PDO $pdo, int $otId) {
    $stmt = $pdo->prepare("
        SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, c.Nombre AS ClienteNombre,
               m.Nombre AS MecanicoNombre
        FROM ordenestrabajo ot
        JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
        JOIN clientes c ON ot.ClienteID = c.ClienteID
        LEFT JOIN usuarios m ON ot.MecanicoID = m.UsuarioID
        WHERE ot.OrdenTrabajoID = :id
    ");
    $stmt->execute([':id' => $otId]);
    return $stmt->fetch();
}

$ot = cargarOT($pdo, $otId);
if (!$ot) {
    http_response_code(404);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Orden de Trabajo no encontrada</h2><a href='ordenestrabajo.php'>Volver al listado</a></div>");
}

$estadosValidos = ['Presupuesto aprobado', 'Presupuesto rechazado', 'En reparación', 'Listo para entregar', 'Entregado'];
if (!in_array($ot['Estado'], $estadosValidos, true)) {
    http_response_code(400);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Esta OT todavía no tiene un presupuesto decidido</h2><p>Primero registra la decisión del cliente en el Presupuesto.</p><a href='presupuesto.php?id=$otId'>Ir al Presupuesto</a></div>");
}

$stmtP = $pdo->prepare("SELECT PresupuestoID FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
$stmtP->execute([':id' => $otId]);
$presupuesto = $stmtP->fetch();

function cargarLineasAprobadas(PDO $pdo, int $presupuestoId) {
    $stmt = $pdo->prepare("SELECT * FROM presupuestodetalle WHERE PresupuestoID = :pid AND Aprobado = 1 ORDER BY PresupuestoDetalleID ASC");
    $stmt->execute([':pid' => $presupuestoId]);
    return $stmt->fetchAll();
}

$lineas = $presupuesto ? cargarLineasAprobadas($pdo, $presupuesto['PresupuestoID']) : [];
$lineasRepuesto = array_filter($lineas, fn($l) => $l['TipoLinea'] === 'Repuesto');
$lineasManoObra = array_filter($lineas, fn($l) => $l['TipoLinea'] !== 'Repuesto');
$totalRepuestos = array_sum(array_column($lineasRepuesto, 'Subtotal'));
$totalManoObra = array_sum(array_column($lineasManoObra, 'Subtotal'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'asignar_mecanico') {
        $mecanicoId = (int)($_POST['mecanico_id'] ?? 0) ?: null;
        $pdo->prepare("UPDATE ordenestrabajo SET MecanicoID = :m WHERE OrdenTrabajoID = :id")
            ->execute([':m' => $mecanicoId, ':id' => $otId]);
        if ($mecanicoId && $ot['Estado'] === 'Presupuesto aprobado') {
            $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En reparación' WHERE OrdenTrabajoID = :id")
                ->execute([':id' => $otId]);
        }
    } elseif ($action === 'marcar_listo') {
        $totalPresupuesto = $totalRepuestos + $totalManoObra;
        // ManoObraCobrada cubre OTs antiguas que usaron el mecanismo previo (movimiento de caja aparte).
        if ($totalPresupuesto <= 0 || !empty($ot['VentaID']) || $ot['ManoObraCobrada']) {
            $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Listo para entregar' WHERE OrdenTrabajoID = :id")
                ->execute([':id' => $otId]);
        } else {
            $error = 'Todavía falta cobrar la OT en caja antes de marcarla como lista.';
        }
    } elseif ($action === 'marcar_entregado') {
        $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Entregado', FechaEntrega = NOW() WHERE OrdenTrabajoID = :id")
            ->execute([':id' => $otId]);

        // Registrar automáticamente en historialmantenimiento si se realizaron tareas preventivas clave
        $kmIngreso = (int)($ot['KilometrajeIngreso'] ?? 0);
        $vehiculoId = (int)$ot['VehiculoID'];

        $stmtChkHm = $pdo->prepare("SELECT COUNT(*) FROM historialmantenimiento WHERE OrdenTrabajoID = :ot");
        $stmtChkHm->execute([':ot' => $otId]);
        if ((int)$stmtChkHm->fetchColumn() === 0 && !empty($lineas)) {
            // Consultar si el mecánico definió intervalos específicos en la inspección/chequeo
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
                if (str_contains($desc, 'aceite') || str_contains($desc, 'lubricante')) {
                    $tieneAceite = true;
                }
                if (str_contains($desc, 'freno') || str_contains($desc, 'pastilla')) {
                    $tieneFrenos = true;
                }
                if (str_contains($desc, 'distribucion') || str_contains($desc, 'correa') || str_contains($desc, 'distribución')) {
                    $tieneDistribucion = true;
                }
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
                    ':uid' => $user['id']
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
                    ':uid' => $user['id']
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
                    ':uid' => $user['id']
                ]);
            }
        }
    }

    $ot = cargarOT($pdo, $otId);
}

$mecanicos = $pdo->query("SELECT UsuarioID, Nombre FROM usuarios WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

include __DIR__ . '/views/ejecucion.view.php';
require_once __DIR__ . '/includes/footer.php';
