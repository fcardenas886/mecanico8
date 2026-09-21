<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$user = currentUser();
$error = '';
$message = '';

$otId = (int)($_GET['id'] ?? $_POST['ot_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ot.*, v.Patente, v.Marca, v.Modelo, v.Anio, v.Color, v.KilometrajeUltimo, c.Nombre AS ClienteNombre
    FROM ordenestrabajo ot
    JOIN vehiculos v ON ot.VehiculoID = v.VehiculoID
    JOIN clientes c ON ot.ClienteID = c.ClienteID
    WHERE ot.OrdenTrabajoID = :id
");
$stmt->execute([':id' => $otId]);
$ot = $stmt->fetch();

if (!$ot) {
    http_response_code(404);
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Orden de Trabajo no encontrada</h2><a href='ordenestrabajo.php'>Volver al listado</a></div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_hallazgo') {
        $area = $_POST['area'] ?? 'Mecánica';
        $hallazgo = trim($_POST['hallazgo'] ?? '');
        if ($hallazgo !== '' && in_array($area, ['Mecánica', 'Electricidad', 'Carrocería'], true)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO diagnosticoot (OrdenTrabajoID, Area, Hallazgo, UsuarioID)
                    VALUES (:ot, :area, :hallazgo, :uid)
                ");
                $stmt->execute([':ot' => $otId, ':area' => $area, ':hallazgo' => $hallazgo, ':uid' => $user['id']]);

                if ($ot['Estado'] === 'Ingresado') {
                    $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'En diagnóstico' WHERE OrdenTrabajoID = :id")
                        ->execute([':id' => $otId]);
                    $ot['Estado'] = 'En diagnóstico';
                }
                $message = 'Hallazgo agregado correctamente.';
            } catch (Exception $e) {
                $error = 'Error al registrar el hallazgo: ' . $e->getMessage();
            }
        } else {
            $error = 'Escribe el hallazgo antes de agregarlo.';
        }
    } elseif ($action === 'finalizar') {
        $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Diagnosticado' WHERE OrdenTrabajoID = :id")
            ->execute([':id' => $otId]);
        $ot['Estado'] = 'Diagnosticado';
        $message = 'Diagnóstico finalizado. Ya puedes generar el Presupuesto.';
    } elseif ($action === 'omitir') {
        $pdo->prepare("UPDATE ordenestrabajo SET Estado = 'Diagnóstico no aplica' WHERE OrdenTrabajoID = :id")
            ->execute([':id' => $otId]);
        $ot['Estado'] = 'Diagnóstico no aplica';
        $message = 'Diagnóstico marcado como "no aplica".';
    } elseif ($action === 'eliminar_hallazgo') {
        $diagId = (int)($_POST['diagnostico_id'] ?? 0);
        $pdo->prepare("DELETE FROM diagnosticoot WHERE DiagnosticoID = :id AND OrdenTrabajoID = :ot")
            ->execute([':id' => $diagId, ':ot' => $otId]);
        $message = 'Hallazgo eliminado.';
    } elseif ($action === 'guardar_estacion' || $action === 'guardar_y_cotizar_fluidos' || $action === 'cargar_fluidos_presupuesto') {
        if ($action === 'guardar_estacion' || $action === 'guardar_y_cotizar_fluidos') {
            $est = $_POST['estacion'] ?? [];
            $motorNivel = in_array($est['motor_nivel'] ?? '', ['Normal', 'Bajo', 'No revisado'], true) ? $est['motor_nivel'] : 'Normal';
            $motorCambio = !empty($est['motor_cambio']) ? 1 : 0;
            $filtroCambio = !empty($est['filtro_cambio']) ? 1 : 0;
            $dhNivel = in_array($est['dh_nivel'] ?? '', ['Normal', 'Bajo', 'No aplica'], true) ? $est['dh_nivel'] : 'Normal';
            $cajaNivel = in_array($est['caja_nivel'] ?? '', ['Normal', 'Bajo', 'No revisado'], true) ? $est['caja_nivel'] : 'Normal';
            $cajaCambio = !empty($est['caja_cambio']) ? 1 : 0;
            $frenosNivel = in_array($est['frenos_nivel'] ?? '', ['Normal', 'Bajo', 'Contaminado'], true) ? $est['frenos_nivel'] : 'Normal';
            $frenosCambio = !empty($est['frenos_cambio']) ? 1 : 0;
            $radiadorNivel = in_array($est['radiador_nivel'] ?? '', ['Normal', 'Bajo', 'No revisado'], true) ? $est['radiador_nivel'] : 'Normal';
            $radiadorAnticong = !empty($est['radiador_anticongelante']) ? 1 : 0;
            $lavVidrioCarga = !empty($est['lav_vidrio_carga']) ? 1 : 0;
            $lavadoCarroceria = in_array($est['lavado_carroceria'] ?? '', ['No', 'Basico', 'Completo'], true) ? $est['lavado_carroceria'] : 'No';
            $obsEstacion = trim($est['observaciones'] ?? '');
            
            $aceiteKm = (int)($est['aceite_intervalo_km'] ?? 10000);
            if ($aceiteKm <= 0) $aceiteKm = 10000;
            $aceiteMeses = (int)($est['aceite_intervalo_meses'] ?? 6);
            if ($aceiteMeses <= 0) $aceiteMeses = 6;

            $frenosKm = (int)($est['frenos_intervalo_km'] ?? 25000);
            if ($frenosKm <= 0) $frenosKm = 25000;
            $frenosMeses = (int)($est['frenos_intervalo_meses'] ?? 12);
            if ($frenosMeses <= 0) $frenosMeses = 12;

            $stmtEst = $pdo->prepare("
                INSERT INTO estacionservicio_ot
                    (OrdenTrabajoID, MotorNivel, MotorCambio, FiltroCambio, AceiteIntervaloKm, AceiteIntervaloMeses,
                     DHNivel, CajaNivel, CajaCambio, FrenosNivel, FrenosCambio, FrenosIntervaloKm, FrenosIntervaloMeses,
                     RadiadorNivel, RadiadorAnticongelante, LavVidrioCarga, LavadoCarroceria, Observaciones)
                VALUES
                    (:ot, :mniv, :mcamb, :fcamb, :akm, :ames,
                     :dhniv, :cniv, :ccamb, :fniv, :fcamb2, :fkm, :fmes,
                     :rniv, :rantic, :lvcarga, :lav, :obs)
                ON DUPLICATE KEY UPDATE
                    MotorNivel = VALUES(MotorNivel),
                    MotorCambio = VALUES(MotorCambio),
                    FiltroCambio = VALUES(FiltroCambio),
                    AceiteIntervaloKm = VALUES(AceiteIntervaloKm),
                    AceiteIntervaloMeses = VALUES(AceiteIntervaloMeses),
                    DHNivel = VALUES(DHNivel),
                    CajaNivel = VALUES(CajaNivel),
                    CajaCambio = VALUES(CajaCambio),
                    FrenosNivel = VALUES(FrenosNivel),
                    FrenosCambio = VALUES(FrenosCambio),
                    FrenosIntervaloKm = VALUES(FrenosIntervaloKm),
                    FrenosIntervaloMeses = VALUES(FrenosIntervaloMeses),
                    RadiadorNivel = VALUES(RadiadorNivel),
                    RadiadorAnticongelante = VALUES(RadiadorAnticongelante),
                    LavVidrioCarga = VALUES(LavVidrioCarga),
                    LavadoCarroceria = VALUES(LavadoCarroceria),
                    Observaciones = VALUES(Observaciones)
            ");
            $stmtEst->execute([
                ':ot' => $otId,
                ':mniv' => $motorNivel, ':mcamb' => $motorCambio, ':fcamb' => $filtroCambio,
                ':akm' => $aceiteKm, ':ames' => $aceiteMeses,
                ':dhniv' => $dhNivel, ':cniv' => $cajaNivel, ':ccamb' => $cajaCambio,
                ':fniv' => $frenosNivel, ':fcamb2' => $frenosCambio,
                ':fkm' => $frenosKm, ':fmes' => $frenosMeses,
                ':rniv' => $radiadorNivel, ':rantic' => $radiadorAnticong,
                ':lvcarga' => $lavVidrioCarga, ':lav' => $lavadoCarroceria,
                ':obs' => $obsEstacion ?: null
            ]);
            $message = 'Chequeo de niveles y fluidos guardado correctamente.';
        }

        // Si se solicitó cargar al presupuesto
        if ($action === 'guardar_y_cotizar_fluidos' || $action === 'cargar_fluidos_presupuesto') {
            // Asegurar que exista presupuesto para esta OT
            $stmtP = $pdo->prepare("SELECT PresupuestoID FROM presupuestos WHERE OrdenTrabajoID = :id ORDER BY PresupuestoID DESC LIMIT 1");
            $stmtP->execute([':id' => $otId]);
            $presupuestoId = $stmtP->fetchColumn();
            if (!$presupuestoId) {
                $pdo->prepare("INSERT INTO presupuestos (OrdenTrabajoID, UsuarioID) VALUES (:ot, :uid)")
                    ->execute([':ot' => $otId, ':uid' => $user['id']]);
                $presupuestoId = (int)$pdo->lastInsertId();
            }

            // Consultar estación de servicio
            $stmtEst = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :id");
            $stmtEst->execute([':id' => $otId]);
            $es = $stmtEst->fetch();

            if ($es) {
                $itemsACotizar = [];
                if ($es['MotorCambio']) {
                    $itemsACotizar[] = ['desc' => 'Mano de Obra: Cambio de Aceite y Filtro de Motor', 'precio' => 15000];
                }
                if ($es['FrenosCambio']) {
                    $itemsACotizar[] = ['desc' => 'Mano de Obra: Cambio y Purga de Líquido de Frenos', 'precio' => 20000];
                }
                if ($es['RadiadorAnticongelante']) {
                    $itemsACotizar[] = ['desc' => 'Mano de Obra: Cambio/Carga de Refrigerante Anticongelante', 'precio' => 12000];
                }
                if ($es['CajaCambio']) {
                    $itemsACotizar[] = ['desc' => 'Mano de Obra: Cambio de Aceite de Caja de Velocidades', 'precio' => 25000];
                }

                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM presupuestodetalle WHERE PresupuestoID = :pid AND Descripcion = :desc");
                $stmtIns = $pdo->prepare("
                    INSERT INTO presupuestodetalle (PresupuestoID, TipoLinea, Descripcion, Cantidad, PrecioUnitario, Subtotal, Aprobado)
                    VALUES (:pid, 'ManoObra', :desc, 1, :precio, :subtotal, 1)
                ");

                $agregados = 0;
                foreach ($itemsACotizar as $item) {
                    $stmtCheck->execute([':pid' => $presupuestoId, ':desc' => $item['desc']]);
                    if ((int)$stmtCheck->fetchColumn() === 0) {
                        $stmtIns->execute([
                            ':pid' => $presupuestoId,
                            ':desc' => $item['desc'],
                            ':precio' => $item['precio'],
                            ':subtotal' => $item['precio']
                        ]);
                        $agregados++;
                    }
                }

                if ($agregados > 0) {
                    $message = "¡Chequeo guardado y se agregaron $agregados servicios de fluidos al Presupuesto!";
                } elseif (empty($itemsACotizar)) {
                    $message = "Chequeo guardado. No había fluidos marcados con 'Requiere cambio'.";
                } else {
                    $message = "Chequeo guardado. Los fluidos marcados ya se encontraban incluidos en el Presupuesto.";
                }
            }
        }
    }
}

// Hallazgos por área
$stmtHallazgos = $pdo->prepare("
    SELECT d.*, u.Nombre AS UsuarioNombre
    FROM diagnosticoot d
    JOIN usuarios u ON d.UsuarioID = u.UsuarioID
    WHERE d.OrdenTrabajoID = :id
    ORDER BY d.Fecha ASC
");
$stmtHallazgos->execute([':id' => $otId]);
$hallazgos = $stmtHallazgos->fetchAll();
$hallazgosPorArea = ['Mecánica' => [], 'Electricidad' => [], 'Carrocería' => []];
foreach ($hallazgos as $h) {
    $hallazgosPorArea[$h['Area']][] = $h;
}

// Estación de Servicio
$stmtEst = $pdo->prepare("SELECT * FROM estacionservicio_ot WHERE OrdenTrabajoID = :id");
$stmtEst->execute([':id' => $otId]);
$estacion = $stmtEst->fetch() ?: null;

// Operaciones Solicitadas
$stmtOps = $pdo->prepare("SELECT NombreOperacion FROM orden_operaciones_solicitadas WHERE OrdenTrabajoID = :id");
$stmtOps->execute([':id' => $otId]);
$operacionesSolicitadas = $stmtOps->fetchAll(PDO::FETCH_COLUMN);

// Daños decodificados
$daniosCarroceria = json_decode($ot['DaniosCarroceriaJson'] ?? '[]', true) ?: [];

include __DIR__ . '/views/diagnostico.view.php';
require_once __DIR__ . '/includes/footer.php';
