<?php
// No incluye includes/header.php todavía: si el POST termina en éxito, redirige
// con header('Location...') y eso falla si ya se envió HTML antes (mismo patrón
// que usa login.php).
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDB();
$user = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $clienteModo = $_POST['cliente_modo'] ?? 'existente';
    $vehiculoModo = $_POST['vehiculo_modo'] ?? 'existente';

    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $clienteNombreNuevo = trim($_POST['cliente_nombre_nuevo'] ?? '');
    $clienteTelefonoNuevo = trim($_POST['cliente_telefono_nuevo'] ?? '');

    $vehiculoId = (int)($_POST['vehiculo_id'] ?? 0);
    $patenteNueva = strtoupper(trim($_POST['vehiculo_patente_nuevo'] ?? ''));
    $marcaNueva = trim($_POST['vehiculo_marca_nuevo'] ?? '');
    $modeloNuevo = trim($_POST['vehiculo_modelo_nuevo'] ?? '');
    $anioNuevo = (int)($_POST['vehiculo_anio_nuevo'] ?? 0) ?: null;
    $colorNuevo = trim($_POST['vehiculo_color_nuevo'] ?? '');
    $combustibleNuevo = trim($_POST['vehiculo_combustible_nuevo'] ?? '') ?: null;
    $motorNuevo = trim($_POST['vehiculo_motor_nuevo'] ?? '') ?: null;
    $transmisionNueva = trim($_POST['vehiculo_transmision_nueva'] ?? '') ?: null;
    $tipoVehiculoNuevo = trim($_POST['vehiculo_tipo_nuevo'] ?? '') ?: null;
    $vinNuevo = strtoupper(trim($_POST['vehiculo_vin_nuevo'] ?? '')) ?: null;

    $kilometrajeIngreso = (int)($_POST['kilometraje_ingreso'] ?? 0) ?: null;
    $nivelCombustible = $_POST['nivel_combustible'] ?? '1/2';
    $objetosValor = trim($_POST['objetos_valor'] ?? '');
    $autorizaPresupuesto = isset($_POST['autoriza_presupuesto_previo']) ? 1 : 0;
    $autorizaPrueba = isset($_POST['autoriza_prueba_manejo']) ? 1 : 0;
    $firmaData = trim($_POST['firma_data'] ?? '');
    $checklistPost = $_POST['checklist'] ?? [];

    // Nuevos campos de Estación de Servicio, Operaciones Rápidas y Diagrama de Daños
    $operacionesSeleccionadas = $_POST['operaciones'] ?? [];
    $operacionesTextoLibre = trim($_POST['operaciones_solicitadas_texto'] ?? '');
    $daniosJson = trim($_POST['danios_carroceria_json'] ?? '');
    $estacion = $_POST['estacion'] ?? [];

    $valido = true;
    if ($clienteModo === 'nuevo' && $clienteNombreNuevo === '') { $valido = false; $error = 'Ingresa el nombre del cliente nuevo.'; }
    if ($clienteModo === 'existente' && $clienteId <= 0) { $valido = false; $error = 'Selecciona un cliente.'; }
    if ($vehiculoModo === 'nuevo' && ($patenteNueva === '' || $marcaNueva === '' || $modeloNuevo === '')) { $valido = false; $error = 'Completa patente, marca y modelo del vehículo nuevo.'; }
    if ($vehiculoModo === 'existente' && $vehiculoId <= 0) { $valido = false; $error = 'Selecciona un vehículo.'; }

    if ($valido) {
        try {
            $pdo->beginTransaction();

            if ($clienteModo === 'nuevo') {
                $stmt = $pdo->prepare("INSERT INTO clientes (Nombre, Telefono) VALUES (:nombre, :tel)");
                $stmt->execute([':nombre' => $clienteNombreNuevo, ':tel' => $clienteTelefonoNuevo]);
                $clienteId = (int)$pdo->lastInsertId();
            }

            if ($vehiculoModo === 'nuevo') {
                $stmt = $pdo->prepare("
                    INSERT INTO vehiculos (ClienteID, Patente, Marca, Modelo, Anio, Color, Combustible, Motor, Transmision, TipoVehiculo, VIN, KilometrajeUltimo)
                    VALUES (:cid, :pat, :marca, :modelo, :anio, :color, :combustible, :motor, :transmision, :tipo, :vin, :km)
                ");
                $stmt->execute([
                    ':cid' => $clienteId, ':pat' => $patenteNueva, ':marca' => $marcaNueva, ':modelo' => $modeloNuevo,
                    ':anio' => $anioNuevo, ':color' => $colorNuevo,
                    ':combustible' => $combustibleNuevo, ':motor' => $motorNuevo,
                    ':transmision' => $transmisionNueva, ':tipo' => $tipoVehiculoNuevo,
                    ':vin' => $vinNuevo, ':km' => $kilometrajeIngreso
                ]);
                $vehiculoId = (int)$pdo->lastInsertId();
            } else {
                // Actualiza el kilometraje del vehículo con el dato de este ingreso.
                if ($kilometrajeIngreso) {
                    $stmt = $pdo->prepare("UPDATE vehiculos SET KilometrajeUltimo = :km WHERE VehiculoID = :id");
                    $stmt->execute([':km' => $kilometrajeIngreso, ':id' => $vehiculoId]);
                }
            }

            // Insertar Orden de Trabajo con daños de carrocería y texto libre
            $stmt = $pdo->prepare("
                INSERT INTO ordenestrabajo
                    (VehiculoID, ClienteID, UsuarioID, KilometrajeIngreso, NivelCombustible, ObjetosValor,
                     DaniosCarroceriaJson, OperacionesTextoLibre,
                     AutorizaPresupuestoPrevio, AutorizaPruebaManejo, FirmaClienteBase64, Estado)
                VALUES (:vid, :cid, :uid, :km, :nivel, :objetos, :danios, :optexto, :autpres, :autprueba, :firma, 'Ingresado')
            ");
            $stmt->execute([
                ':vid' => $vehiculoId, ':cid' => $clienteId, ':uid' => $user['id'],
                ':km' => $kilometrajeIngreso, ':nivel' => $nivelCombustible, ':objetos' => $objetosValor ?: null,
                ':danios' => $daniosJson ?: null, ':optexto' => $operacionesTextoLibre ?: null,
                ':autpres' => $autorizaPresupuesto, ':autprueba' => $autorizaPrueba, ':firma' => $firmaData ?: null
            ]);
            $otId = (int)$pdo->lastInsertId();

            // 1. Guardar Checklist de Recepción
            $stmtItems = $pdo->query("SELECT ChecklistItemID FROM checklistitems WHERE Activo = TRUE");
            $itemIds = $stmtItems->fetchAll(PDO::FETCH_COLUMN);

            $stmtChk = $pdo->prepare("
                INSERT INTO checklistrecepcion (OrdenTrabajoID, ChecklistItemID, Valor, Detalle)
                VALUES (:ot, :item, :valor, :detalle)
            ");
            foreach ($itemIds as $itemId) {
                $valor = ($checklistPost[$itemId]['valor'] ?? 'Si') === 'No' ? 'No' : 'Si';
                $detalle = trim($checklistPost[$itemId]['detalle'] ?? '');
                $stmtChk->execute([
                    ':ot' => $otId, ':item' => $itemId, ':valor' => $valor, ':detalle' => $detalle ?: null
                ]);
            }

            // 2. Guardar Estación de Servicio y Fluidos (si fue provista en la recepción)
            if (!empty($estacion)) {
                $motorNivel = in_array($estacion['motor_nivel'] ?? '', ['Normal', 'Bajo', 'No revisado'], true) ? $estacion['motor_nivel'] : 'Normal';
                $motorCambio = !empty($estacion['motor_cambio']) ? 1 : 0;
                $filtroCambio = !empty($estacion['filtro_cambio']) ? 1 : 0;
                $dhNivel = in_array($estacion['dh_nivel'] ?? '', ['Normal', 'Bajo', 'No aplica'], true) ? $estacion['dh_nivel'] : 'Normal';
                $cajaNivel = in_array($estacion['caja_nivel'] ?? '', ['Normal', 'Bajo', 'No revisado'], true) ? $estacion['caja_nivel'] : 'Normal';
                $cajaCambio = !empty($estacion['caja_cambio']) ? 1 : 0;
                $frenosNivel = in_array($estacion['frenos_nivel'] ?? '', ['Normal', 'Bajo', 'Contaminado'], true) ? $estacion['frenos_nivel'] : 'Normal';
                $frenosCambio = !empty($estacion['frenos_cambio']) ? 1 : 0;
                $radiadorNivel = in_array($estacion['radiador_nivel'] ?? '', ['Normal', 'Bajo', 'No revisado'], true) ? $estacion['radiador_nivel'] : 'Normal';
                $radiadorAnticong = !empty($estacion['radiador_anticongelante']) ? 1 : 0;
                $lavVidrioCarga = !empty($estacion['lav_vidrio_carga']) ? 1 : 0;
                $lavadoCarroceria = in_array($estacion['lavado_carroceria'] ?? '', ['No', 'Basico', 'Completo'], true) ? $estacion['lavado_carroceria'] : 'No';
                $obsEstacion = trim($estacion['observaciones'] ?? '');

                $stmtEst = $pdo->prepare("
                    INSERT INTO estacionservicio_ot
                        (OrdenTrabajoID, MotorNivel, MotorCambio, FiltroCambio, DHNivel, CajaNivel, CajaCambio,
                         FrenosNivel, FrenosCambio, RadiadorNivel, RadiadorAnticongelante, LavVidrioCarga,
                         LavadoCarroceria, Observaciones)
                    VALUES
                        (:ot, :mniv, :mcamb, :fcamb, :dhniv, :cniv, :ccamb, :fniv, :fcamb2, :rniv, :rantic, :lvcarga, :lav, :obs)
                ");
                $stmtEst->execute([
                    ':ot' => $otId,
                    ':mniv' => $motorNivel, ':mcamb' => $motorCambio, ':fcamb' => $filtroCambio,
                    ':dhniv' => $dhNivel, ':cniv' => $cajaNivel, ':ccamb' => $cajaCambio,
                    ':fniv' => $frenosNivel, ':fcamb2' => $frenosCambio,
                    ':rniv' => $radiadorNivel, ':rantic' => $radiadorAnticong,
                    ':lvcarga' => $lavVidrioCarga, ':lav' => $lavadoCarroceria,
                    ':obs' => $obsEstacion ?: null
                ]);
            }

            // 3. Guardar Operaciones Solicitadas
            if (!empty($operacionesSeleccionadas) && is_array($operacionesSeleccionadas)) {
                $stmtGetOp = $pdo->prepare("SELECT Nombre FROM operacionessolicitadas WHERE OperacionID = :id");
                $stmtInsOp = $pdo->prepare("
                    INSERT INTO orden_operaciones_solicitadas (OrdenTrabajoID, OperacionID, NombreOperacion)
                    VALUES (:ot, :opid, :nombre)
                ");
                foreach ($operacionesSeleccionadas as $opId) {
                    $opId = (int)$opId;
                    if ($opId > 0) {
                        $stmtGetOp->execute([':id' => $opId]);
                        $opNombre = $stmtGetOp->fetchColumn();
                        if ($opNombre) {
                            $stmtInsOp->execute([
                                ':ot' => $otId, ':opid' => $opId, ':nombre' => $opNombre
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();
            header('Location: comprobante_ot.php?id=' . $otId);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = str_contains($e->getMessage(), 'UQ_Vehiculos_Patente')
                ? "Ya existe un vehículo registrado con esa patente. Búscalo como 'Vehículo existente'."
                : 'Error al generar la Orden de Ingreso: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$clientes = $pdo->query("SELECT ClienteID, Nombre, Telefono FROM clientes WHERE Activo = TRUE ORDER BY Nombre ASC")->fetchAll();

$checklistItems = $pdo->query("
    SELECT ChecklistItemID, Nombre, Categoria FROM checklistitems WHERE Activo = TRUE ORDER BY Orden ASC, Nombre ASC
")->fetchAll();
$checklistPorCategoria = [];
foreach ($checklistItems as $item) {
    $checklistPorCategoria[$item['Categoria']][] = $item;
}

// Catálogo de Operaciones Solicitadas Rápidas (Imagen 1)
$operacionesCatalogo = $pdo->query("
    SELECT OperacionID, Nombre, Categoria FROM operacionessolicitadas WHERE Activo = TRUE AND EsDiagnosticoBase = 0 ORDER BY Orden ASC
")->fetchAll();

// Si se llega desde la Ficha de Vehículo con un vehículo puntual, precargarlo.
$vehiculoPrecargado = null;
$vehiculoIdParam = (int)($_GET['vehiculo_id'] ?? 0);
if ($vehiculoIdParam > 0) {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE VehiculoID = :id AND Activo = TRUE");
    $stmt->execute([':id' => $vehiculoIdParam]);
    $vehiculoPrecargado = $stmt->fetch();
}

include __DIR__ . '/views/ordeningreso.view.php';
require_once __DIR__ . '/includes/footer.php';
