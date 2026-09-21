<?php
$clientePreId = $vehiculoPrecargado['ClienteID'] ?? null;
$vehiculoPreId = $vehiculoPrecargado['VehiculoID'] ?? null;
?>
<div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
  <h1 style="font-size: 1.5rem; font-weight: 700;">Recibir un vehículo</h1>
  <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-arrow-left"></i> Órdenes de trabajo</a>
</div>
<?php otStepper(null, 1); ?>
<?php tallerAyuda('<strong>Qué hago aquí:</strong> elige el cliente y el vehículo, anota qué pide el cliente y en qué estado llega el auto (accesorios, golpes, combustible). Al terminar, el cliente firma y se imprime el comprobante. <strong>Después:</strong> se hace el diagnóstico.'); ?>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<style>
  .oi-section { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
  .oi-section h2 { font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
  .oi-section h2 .oi-num { background: var(--primary); color: #fff; width: 1.6rem; height: 1.6rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; }
  .oi-modo { display: flex; gap: 1rem; margin-bottom: 1rem; }
  .oi-modo label { font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem; cursor: pointer; color: var(--text-muted); }
  
  /* Operaciones Solicitadas Cards */
  .ops-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 0.6rem; margin-bottom: 0.75rem; }
  .op-card {
    background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 8px;
    padding: 0.6rem 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem;
    transition: all 0.15s ease; user-select: none;
  }
  .op-card:hover { border-color: var(--primary); background: rgba(59, 130, 246, 0.08); }
  .op-card input[type="checkbox"] { width: 1.15rem; height: 1.15rem; accent-color: var(--primary); cursor: pointer; }
  .op-card span { font-size: 0.85rem; font-weight: 600; color: #e2e8f0; }

  /* Checklist rows */
  .oi-cat-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin: 1rem 0 0.5rem; letter-spacing: 0.03em; }
  .oi-cat-title:first-child { margin-top: 0; }
  .oi-item-row { display: grid; grid-template-columns: 1fr auto 1fr; gap: 0.75rem; align-items: center; padding: 0.45rem 0; border-bottom: 1px dashed var(--border-dark); }
  .oi-item-row:last-child { border-bottom: none; }
  .oi-item-name { font-size: 0.9rem; }
  .oi-pill-group { display: flex; gap: 0.35rem; }
  .oi-pill { position: relative; }
  .oi-pill input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; }
  .oi-pill span { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid var(--border-dark); color: var(--text-muted); }
  .oi-pill input:checked + span { background: var(--primary); border-color: var(--primary); color: #fff; }
  .oi-detalle-input { font-size: 0.82rem; padding: 0.35rem 0.6rem; }

  /* Diagrama de Carrocería Simplificado y Fácil */
  .danio-box { display: grid; grid-template-columns: 1fr 1.1fr; gap: 1.25rem; align-items: flex-start; }
  .zonas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 0.5rem; }
  .btn-zona {
    background: rgba(255,255,255,0.04); border: 1px solid var(--border-dark); color: #e2e8f0;
    padding: 0.45rem 0.65rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; text-align: left;
    display: flex; align-items: center; justify-content: space-between; transition: all 0.15s ease;
  }
  .btn-zona:hover { border-color: #3b82f6; background: rgba(59, 130, 246, 0.15); }
  .btn-zona i { color: #64748b; font-size: 0.75rem; }

  .car-visual-panel {
    background: #ffffff; border: 2px solid #cbd5e1; border-radius: 8px; padding: 12px;
    position: relative; display: flex; flex-direction: column; align-items: center;
  }
  .car-svg-clean { width: 100%; max-width: 380px; height: auto; display: block; cursor: crosshair; }
  .pin-clean {
    position: absolute; width: 22px; height: 22px; border-radius: 50%;
    color: white; font-weight: 800; font-size: 11px; display: flex; align-items: center; justify-content: center;
    transform: translate(-50%, -50%); box-shadow: 0 2px 5px rgba(0,0,0,0.4); border: 2px solid #fff; pointer-events: none;
  }
  .pin-clean.rayon { background: #ef4444; }
  .pin-clean.golpe { background: #f59e0b; }

  .danio-item-row {
    display: flex; justify-content: space-between; align-items: center;
    background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 6px;
    padding: 0.4rem 0.65rem; margin-bottom: 0.35rem; font-size: 0.82rem;
  }

  /* Firma */
  #firmaCanvas { background: #fff; border-radius: 8px; border: 1px solid var(--border-dark); touch-action: none; cursor: crosshair; width: 100%; max-width: 480px; height: 160px; }

  @media (max-width: 768px) {
    .oi-item-row { grid-template-columns: 1fr; }
    .danio-box { grid-template-columns: 1fr; }
  }
</style>

<form method="POST" action="ordeningreso.php" id="formOrdenIngreso">
  <?= csrfField() ?>

  <!-- 1. Vehículo y Patente (Flujo Patente-First Asistido por API) -->
  <div class="oi-section">
    <h2><span class="oi-num">1</span> Vehículo (Ingreso por Patente)</h2>
    
    <!-- Caja Buscadora Rápida por Patente -->
    <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 12px; padding: 1.1rem; margin-bottom: 1.25rem;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; flex-wrap: wrap; gap: 0.5rem;">
        <label style="font-size: 0.82rem; color: #38bdf8; font-weight: 700; letter-spacing: 0.04em;">
          <i class="fa-solid fa-bolt"></i> BUSCADOR DIRECTO DE PATENTE (HISTORIAL LOCAL + CONSULTA API)
        </label>
        <span style="font-size: 0.75rem; color: var(--text-muted);">Ingresa la patente para autocompletar</span>
      </div>

      <div style="display: flex; gap: 0.6rem; align-items: stretch;">
        <div style="position: relative; flex: 1; min-width: 180px;">
          <input type="text" id="patenteUniversalInput" class="form-control" placeholder="Ej: ABCD12, BBCL20..." 
                 style="text-transform: uppercase; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.08em; color: #38bdf8; background: #0f172a; padding-left: 2.6rem; height: 46px;"
                 autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault(); buscarVehiculoUniversal();}">
          <i class="fa-solid fa-car" style="position: absolute; left: 0.95rem; top: 50%; transform: translateY(-50%); color: #38bdf8; font-size: 1.1rem;"></i>
        </div>
        <button type="button" id="btnBuscarPatenteUniversal" onclick="buscarVehiculoUniversal()" class="btn btn-primary" style="padding: 0 1.25rem; font-weight: 700; height: 46px; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;">
          <i class="fa-solid fa-magnifying-glass" id="iconBusquedaUniversal"></i>
          <span id="txtBusquedaUniversal">Buscar Auto</span>
        </button>
      </div>

      <!-- Banner dinámico de resultado -->
      <div id="resultadoPatenteBanner" style="display: none; margin-top: 0.85rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.88rem; align-items: center; justify-content: space-between; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.6rem;">
          <i id="resultadoPatenteIcon" class="fa-solid fa-circle-check"></i>
          <span id="resultadoPatenteTexto"></span>
        </div>
        <span id="resultadoPatenteBadge" class="badge"></span>
      </div>
    </div>

    <!-- Modos de Vehículo (Existente / Nuevo) -->
    <div class="oi-modo">
      <label><input type="radio" name="vehiculo_modo" id="radioVehiculoExistente" value="existente" checked onchange="toggleVehiculoModo()"> Vehículo registrado en taller</label>
      <label><input type="radio" name="vehiculo_modo" id="radioVehiculoNuevo" value="nuevo" onchange="toggleVehiculoModo()"> Vehículo nuevo</label>
    </div>

    <div id="vehiculoExistenteBlock">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">SELECCIONA EL VEHÍCULO REGISTRADO</label>
      <select name="vehiculo_id" id="vehiculoSelect" class="form-control" onchange="onSelectVehiculoExistente(this.value)">
        <option value="">Primero selecciona un cliente o busca por patente arriba...</option>
      </select>
    </div>

    <div id="vehiculoNuevoBlock" style="display: none;">

      <div style="border: 1px dashed #38bdf8; border-radius: 8px; padding: 0.6rem 0.75rem; margin-bottom: 0.75rem; background: rgba(56,189,248,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
          <span style="font-size: 0.78rem; font-weight: 700; color: #38bdf8;"><i class="fa-solid fa-paste"></i> Pegar datos de la consulta</span>
          <button type="button" class="btn btn-secondary" style="padding: 2px 10px; font-size: 0.75rem;" onclick="PegarVehiculo.abrirAutoRiesgo(document.getElementById('vn_patente').value) || alert('Escribe primero la patente')">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir AutoRiesgo
          </button>
        </div>
        <textarea id="vn_pegar_texto" class="form-control" rows="2" placeholder="Copia el resultado de la consulta y pégalo aquí (Ctrl+V)" style="font-size: 0.8rem;" oninput="pegarVehiculoAplicar('vn')"></textarea>
        <div id="vn_pegar_estado" style="display: none; font-size: 0.78rem; margin-top: 4px;"></div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PATENTE / PLACA *</label>
          <input type="text" name="vehiculo_patente_nuevo" id="vn_patente" class="form-control" placeholder="Ej: ABCD12" style="text-transform: uppercase;" required>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">AÑO</label>
          <input type="number" name="vehiculo_anio_nuevo" id="vn_anio" class="form-control" placeholder="2020">
        </div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MARCA *</label>
          <input type="text" name="vehiculo_marca_nuevo" id="vn_marca" class="form-control" placeholder="Ej: Toyota" list="listaMarcasPopulares" oninput="actualizarModelosSugeridos(this.value, 'listaModelosPopulares')" required autocomplete="off">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MODELO *</label>
          <input type="text" name="vehiculo_modelo_nuevo" id="vn_modelo" class="form-control" placeholder="Ej: Yaris" list="listaModelosPopulares" required autocomplete="off">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">COLOR</label>
          <input type="text" name="vehiculo_color_nuevo" id="vn_color" class="form-control" placeholder="Ej: Blanco">
        </div>
      </div>

      <datalist id="listaMarcasPopulares">
        <option value="Toyota">
        <option value="Chevrolet">
        <option value="Hyundai">
        <option value="Kia">
        <option value="Nissan">
        <option value="Suzuki">
        <option value="Ford">
        <option value="Peugeot">
        <option value="Volkswagen">
        <option value="Mitsubishi">
        <option value="Chery">
        <option value="MG">
        <option value="Mazda">
        <option value="Subaru">
        <option value="Honda">
        <option value="Renault">
        <option value="Citroën">
        <option value="BMW">
        <option value="Mercedes-Benz">
      </datalist>
      <datalist id="listaModelosPopulares"></datalist>

      <!-- Ficha Mecánica Avanzada (Combustible, Motor, Transmisión, Tipo, VIN) -->
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TIPO DE COMBUSTIBLE</label>
          <select name="vehiculo_combustible_nuevo" id="vn_combustible" class="form-control">
            <option value="Bencina">Bencina (Gasolina)</option>
            <option value="Diésel">Diésel</option>
            <option value="Híbrido">Híbrido</option>
            <option value="Eléctrico">Eléctrico</option>
            <option value="Gas GLP/GNC">Gas (GLP / GNC)</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MOTOR / CILINDRADA</label>
          <input type="text" name="vehiculo_motor_nuevo" id="vn_motor" class="form-control" placeholder="Ej: 1.5L, 2.0 CRDi">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TRANSMISIÓN</label>
          <select name="vehiculo_transmision_nueva" id="vn_transmision" class="form-control">
            <option value="Manual">Manual</option>
            <option value="Automática">Automática</option>
            <option value="CVT">CVT</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TIPO DE CARROCERÍA</label>
          <input type="text" name="vehiculo_tipo_nuevo" id="vn_tipo" class="form-control" placeholder="Ej: Sedán, SUV, Camioneta, Hatchback">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">VIN / N° CHASIS</label>
          <input type="text" name="vehiculo_vin_nuevo" id="vn_vin" class="form-control" placeholder="17 caracteres alfanuméricos" style="text-transform: uppercase;">
        </div>
      </div>
    </div>

    <div style="margin-top: 1rem;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">KILOMETRAJE DE INGRESO *</label>
      <input type="number" name="kilometraje_ingreso" id="kmIngresoInput" class="form-control" placeholder="Ej: 45000" style="max-width: 240px;">
    </div>
  </div>

  <!-- 2. Cliente / Propietario -->
  <div class="oi-section">
    <h2><span class="oi-num">2</span> Cliente / Propietario</h2>
    <div class="oi-modo">
      <label><input type="radio" name="cliente_modo" id="radioClienteExistente" value="existente" checked onchange="toggleClienteModo()"> Cliente existente</label>
      <label><input type="radio" name="cliente_modo" id="radioClienteNuevo" value="nuevo" onchange="toggleClienteModo()"> Cliente nuevo</label>
    </div>

    <div id="clienteExistenteBlock">
      <select name="cliente_id" id="clienteSelect" class="form-control" onchange="fetchVehiculos(this.value)">
        <option value="">Selecciona un cliente...</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= $c['ClienteID'] ?>" <?= $clientePreId == $c['ClienteID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['Nombre']) ?><?= $c['Telefono'] ? ' — ' . htmlspecialchars($c['Telefono']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="clienteNuevoBlock" style="display: none; display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem;">
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">NOMBRE COMPLETO *</label>
        <input type="text" name="cliente_nombre_nuevo" id="cn_nombre" class="form-control" placeholder="Nombre completo">
      </div>
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TELÉFONO</label>
        <input type="text" name="cliente_telefono_nuevo" id="cn_telefono" class="form-control" placeholder="+56 9 1234 5678">
      </div>
    </div>
  </div>

  <!-- 3. Operaciones Solicitadas (Catálogo Frecuente - Imagen 1) -->
  <div class="oi-section">
    <h2><span class="oi-num">3</span> Operaciones Solicitadas por el Cliente</h2>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.75rem;">Marca con un clic los trabajos que pide el cliente al momento de llegar:</p>
    
    <div class="ops-grid">
      <?php foreach ($operacionesCatalogo as $op): ?>
        <label class="op-card">
          <input type="checkbox" name="operaciones[]" value="<?= $op['OperacionID'] ?>">
          <span><?= htmlspecialchars($op['Nombre']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <div style="margin-top: 0.75rem;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">OTRAS OPERACIONES O SÍNTOMAS REPORTADOS POR EL CLIENTE:</label>
      <textarea name="operaciones_solicitadas_texto" class="form-control" rows="2" placeholder="Ej: Ruido al frenar en rueda delantera, testigo de check engine encendido, aire no enfría..."></textarea>
    </div>
  </div>

  <!-- 4. Checklist de Accesorios y Pertenencias (Custodia) -->
  <div class="oi-section">
    <h2><span class="oi-num">4</span> Checklist de Accesorios y Pertenencias</h2>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">Constancia de objetos dejados dentro del auto para resguardo legal:</p>

    <?php foreach ($checklistPorCategoria as $categoria => $items): ?>
      <?php if ($categoria !== 'Daño'): ?>
        <div class="oi-cat-title"><?= htmlspecialchars($categoria) ?></div>
        <?php foreach ($items as $item):
          $id = $item['ChecklistItemID'];
        ?>
          <div class="oi-item-row">
            <div class="oi-item-name"><?= htmlspecialchars($item['Nombre']) ?></div>
            <div class="oi-pill-group">
              <label class="oi-pill">
                <input type="radio" name="checklist[<?= $id ?>][valor]" value="Si" checked>
                <span>Sí</span>
              </label>
              <label class="oi-pill">
                <input type="radio" name="checklist[<?= $id ?>][valor]" value="No">
                <span>No</span>
              </label>
            </div>
            <input type="text" name="checklist[<?= $id ?>][detalle]" class="form-control oi-detalle-input" placeholder="Detalle opcional (ej: rueda en buen estado)...">
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- 5. Daños Visibles de Carrocería (Simplificado y Fácil de Usar) -->
  <div class="oi-section">
    <h2><span class="oi-num">5</span> Daños Visibles de Carrocería (Rayones y Golpes)</h2>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.75rem;">
      Toca un botón de zona o haz clic directo en la silueta para marcar daños preexistentes y evitar reclamos:
    </p>

    <div class="danio-box">
      <!-- Columna Izquierda: Botones de Selección Rápida por Zona -->
      <div>
        <!-- Selector de Tipo -->
        <div style="background: rgba(0,0,0,0.2); padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--border-dark); display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
          <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">TIPO:</span>
          <label style="cursor: pointer; display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem;">
            <input type="radio" name="tipo_danio_radio" value="rayon" checked onchange="cambiarTipoDanio('rayon')">
            <span style="color: #ef4444; font-weight: bold;">🔴 Rayón</span>
          </label>
          <label style="cursor: pointer; display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem;">
            <input type="radio" name="tipo_danio_radio" value="golpe" onchange="cambiarTipoDanio('golpe')">
            <span style="color: #f59e0b; font-weight: bold;">🟡 Golpe / Abolladura</span>
          </label>
        </div>

        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">
          Toca una zona para agregar rápido:
        </div>
        
        <div class="zonas-grid">
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Parachoques Delantero', 50, 7)"><span>Parachoques Delantero</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Capó / Frente', 50, 16)"><span>Capó / Frente</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Faro / Espejo Izquierdo', 26, 17)"><span>Faro / Espejo Izq.</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Faro / Espejo Derecho', 74, 17)"><span>Faro / Espejo Der.</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Puerta Delantera Izquierda', 31, 35)"><span>Puerta Del. Izquierda</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Puerta Delantera Derecha', 69, 35)"><span>Puerta Del. Derecha</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Puerta Trasera Izquierda', 31, 50)"><span>Puerta Tras. Izquierda</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Puerta Trasera Derecha', 69, 50)"><span>Puerta Tras. Derecha</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Techo', 50, 40)"><span>Techo</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Maletero / Portalón', 50, 63)"><span>Maletero / Portalón</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Parachoques Trasero', 50, 71)"><span>Parachoques Trasero</span> <i class="fa-solid fa-plus"></i></button>
          <button type="button" class="btn-zona" onclick="agregarDanioZona('Costado / Perfil Lateral', 50, 87)"><span>Costado / Perfil Lateral</span> <i class="fa-solid fa-plus"></i></button>
        </div>

        <!-- Lista de daños marcados -->
        <div style="margin-top: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
            <span style="font-size: 0.8rem; font-weight: 700; color: #fff;">Daños en Carrocería (<span id="danioCount">0</span>)</span>
            <button type="button" onclick="limpiarTodosDanios()" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.72rem;">Limpiar todo</button>
          </div>
          <div id="danioListContainer" style="max-height: 180px; overflow-y: auto;">
            <div style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Sin daños marcados. Si el auto no tiene rayones ni golpes, déjalo vacío.</div>
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Silueta Visual Clara (Estilo Hoja de Taller) -->
      <div class="car-visual-panel">
        <div style="font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;">
          O haz clic directamente sobre la silueta:
        </div>
        
        <div style="position: relative; display: inline-block;">
          <!-- Silueta clara de alta legibilidad -->
          <svg class="car-svg-clean" id="carSvgClean" viewBox="0 0 340 380" xmlns="http://www.w3.org/2000/svg">
            <!-- Fondo de contraste -->
            <rect width="340" height="380" fill="#f8fafc" rx="8" stroke="#e2e8f0" stroke-width="1"/>
            
            <!-- Silueta Auto Superior -->
            <g transform="translate(90, 20)">
              <!-- Carrocería principal -->
              <rect x="10" y="20" width="140" height="240" rx="30" fill="#e2e8f0" stroke="#1e293b" stroke-width="2.5"/>
              
              <!-- Frente / Capó -->
              <path d="M 22 25 Q 80 18 138 25 L 138 65 Q 80 60 22 65 Z" fill="#cbd5e1" stroke="#475569" stroke-width="1.5"/>
              <text x="80" y="45" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">CAPÓ / FRENTE</text>
              
              <!-- Parabrisas delantero -->
              <path d="M 24 70 Q 80 65 136 70 L 130 95 Q 80 90 30 95 Z" fill="#94a3b8" stroke="#334155" stroke-width="1.5"/>
              
              <!-- Techo -->
              <rect x="30" y="100" width="100" height="85" rx="10" fill="#cbd5e1" stroke="#334155" stroke-width="1.5"/>
              <text x="80" y="145" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">TECHO</text>
              
              <!-- Vidrio trasero -->
              <path d="M 30 190 Q 80 195 130 190 L 136 215 Q 80 220 24 215 Z" fill="#94a3b8" stroke="#334155" stroke-width="1.5"/>
              
              <!-- Maletero / Atrás -->
              <path d="M 22 220 Q 80 225 138 220 L 138 255 Q 80 262 22 255 Z" fill="#cbd5e1" stroke="#475569" stroke-width="1.5"/>
              <text x="80" y="242" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">MALETERO</text>
              
              <!-- Espejos -->
              <rect x="-2" y="70" width="12" height="18" rx="4" fill="#334155"/>
              <rect x="150" y="70" width="12" height="18" rx="4" fill="#334155"/>
              
              <!-- Ruedas -->
              <rect x="2" y="38" width="8" height="32" rx="3" fill="#0f172a"/>
              <rect x="150" y="38" width="8" height="32" rx="3" fill="#0f172a"/>
              <rect x="2" y="200" width="8" height="32" rx="3" fill="#0f172a"/>
              <rect x="150" y="200" width="8" height="32" rx="3" fill="#0f172a"/>
            </g>
            
            <!-- Silueta Auto Lateral (Inferior) -->
            <g transform="translate(45, 290)">
              <text x="125" y="10" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">VISTA DE PERFIL</text>
              <path d="M 15 65 L 30 50 L 60 45 L 85 20 L 165 20 L 195 45 L 235 50 L 240 65 
                       L 210 65 A 16 16 0 0 0 175 65 L 85 65 A 16 16 0 0 0 50 65 Z" 
                    fill="#cbd5e1" stroke="#1e293b" stroke-width="2"/>
              <!-- Ruedas -->
              <circle cx="68" cy="65" r="14" fill="#0f172a" stroke="#fff" stroke-width="2"/>
              <circle cx="192" cy="65" r="14" fill="#0f172a" stroke="#fff" stroke-width="2"/>
            </g>
          </svg>
          <!-- Capa de pines superpuestos -->
          <div id="pinsCleanLayer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
        </div>

        <input type="hidden" name="danios_carroceria_json" id="danios_carroceria_json" value="[]">
      </div>
    </div>
  </div>

  <!-- 6. Combustible y Autorizaciones Legales -->
  <div class="oi-section">
    <h2><span class="oi-num">6</span> Combustible y Autorizaciones Legales</h2>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.75rem; margin-bottom: 1rem;">
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">NIVEL DE COMBUSTIBLE EN TABLERO</label>
        <select name="nivel_combustible" class="form-control">
          <option value="Vacio">Vacío (0)</option>
          <option value="1/4">1/4 de Estanque</option>
          <option value="1/2" selected>1/2 de Estanque</option>
          <option value="3/4">3/4 de Estanque</option>
          <option value="Lleno">Lleno (F)</option>
        </select>
      </div>
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">OBJETOS DE VALOR DECLARADOS</label>
        <input type="text" name="objetos_valor" class="form-control" placeholder="Ej: Ninguno / Lentes en la guantera">
      </div>
    </div>

    <div style="background: rgba(0,0,0,0.2); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.85rem; display: flex; flex-direction: column; gap: 0.6rem;">
      <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
        <input type="checkbox" name="autoriza_presupuesto_previo" checked style="width: 1.1rem; height: 1.1rem;">
        <strong>El cliente solicita presupuesto previo antes de autorizar las reparaciones</strong>
      </label>
      <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
        <input type="checkbox" name="autoriza_prueba_manejo" style="width: 1.1rem; height: 1.1rem;">
        <strong>El cliente autoriza conducir el vehículo en pruebas de ruta</strong>
      </label>
    </div>
  </div>

  <!-- 7. Firma de Conformidad del Cliente -->
  <div class="oi-section">
    <h2><span class="oi-num">7</span> Firma de Conformidad del Cliente</h2>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">
      Aceptación expresa de las condiciones de custodia. Se imprimirán dos copias al guardar:
    </p>
    <canvas id="firmaCanvas" width="480" height="160"></canvas>
    <input type="hidden" name="firma_data" id="firma_data">
    <div style="margin-top: 0.5rem;">
      <button type="button" onclick="limpiarFirma()" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">
        <i class="fa-solid fa-eraser"></i> Limpiar firma
      </button>
    </div>
  </div>

  <div style="display: flex; gap: 0.75rem; margin-bottom: 2rem;">
    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem; font-size: 1rem; font-weight: bold;">
      <i class="fa-solid fa-file-signature"></i> Generar Orden de Ingreso
    </button>
    <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">Cancelar</a>
  </div>
</form>

<script src="assets/js/pegar_vehiculo.js?v=<?= defined('APP_VERSION') ? APP_VERSION : 1 ?>"></script>
<script>

function pegarVehiculoAplicar(prefijo) {
  const ta = document.getElementById(prefijo + '_pegar_texto');
  const st = document.getElementById(prefijo + '_pegar_estado');
  const datos = PegarVehiculo.parsearTextoVehiculo(ta.value);
  const hechos = PegarVehiculo.rellenarVehiculo(prefijo, datos, { tipo_vehiculo: 'tipo' });
  st.style.display = 'block';
  if (hechos.length) {
    st.style.color = '#34d399';
    st.innerHTML = '<i class="fa-solid fa-check"></i> Completado: ' + hechos.join(', ');
  } else if (ta.value.trim()) {
    st.style.color = '#f87171';
    st.textContent = 'No pude reconocer datos en ese texto. Revisa que copiaste el resultado completo.';
  } else {
    st.style.display = 'none';
  }
}
const VEHICULO_PRECARGADO_ID = <?= $vehiculoPreId ? (int)$vehiculoPreId : 'null' ?>;

function toggleClienteModo() {
  const modo = document.querySelector('input[name="cliente_modo"]:checked').value;
  document.getElementById('clienteExistenteBlock').style.display = modo === 'existente' ? 'block' : 'none';
  document.getElementById('clienteNuevoBlock').style.display = modo === 'nuevo' ? 'grid' : 'none';
}

function toggleVehiculoModo() {
  const modo = document.querySelector('input[name="vehiculo_modo"]:checked').value;
  document.getElementById('vehiculoExistenteBlock').style.display = modo === 'existente' ? 'block' : 'none';
  document.getElementById('vehiculoNuevoBlock').style.display = modo === 'nuevo' ? 'block' : 'none';
  // Los campos obligatorios ocultos bloquean el envío sin avisar: solo son requeridos en modo nuevo.
  ['vn_patente', 'vn_marca', 'vn_modelo'].forEach(id => { document.getElementById(id).required = (modo === 'nuevo'); });
}

function onSelectVehiculoExistente(vid) {
  const sel = document.getElementById('vehiculoSelect');
  if (sel.selectedIndex > 0) {
    const txt = sel.options[sel.selectedIndex].text;
    const parts = txt.split(' — ');
    if (parts.length > 0) {
      document.getElementById('patenteUniversalInput').value = parts[0].trim();
    }
  }
}

async function buscarVehiculoUniversal() {
  const input = document.getElementById('patenteUniversalInput');
  const patente = (input.value || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (!patente) {
    input.focus();
    input.style.borderColor = '#ef4444';
    setTimeout(() => input.style.borderColor = '', 1500);
    return;
  }

  const btn = document.getElementById('btnBuscarPatenteUniversal');
  const icon = document.getElementById('iconBusquedaUniversal');
  const txt = document.getElementById('txtBusquedaUniversal');
  const banner = document.getElementById('resultadoPatenteBanner');
  const bannerIcon = document.getElementById('resultadoPatenteIcon');
  const bannerTexto = document.getElementById('resultadoPatenteTexto');
  const bannerBadge = document.getElementById('resultadoPatenteBadge');

  btn.disabled = true;
  icon.className = 'fa-solid fa-spinner fa-spin';
  txt.textContent = 'Buscando...';
  banner.style.display = 'none';

  try {
    const res = await fetch(`api/consultar_vehiculo_api.php?patente=${encodeURIComponent(patente)}`);
    const data = await res.json();

    if (!data.success) {
      banner.style.display = 'flex';
      banner.style.background = 'rgba(239, 68, 68, 0.15)';
      banner.style.border = '1px solid #ef4444';
      banner.style.color = '#fca5a5';
      bannerIcon.className = 'fa-solid fa-circle-exclamation';
      bannerTexto.textContent = data.error || 'No se encontraron datos para la patente ' + patente;
      bannerBadge.className = 'badge badge-danger';
      bannerBadge.textContent = 'Sin datos';

      document.getElementById('radioVehiculoNuevo').checked = true;
      toggleVehiculoModo();
      document.getElementById('vn_patente').value = patente;
      return;
    }

    const d = data.datos;

    if (data.fuente === 'local') {
      // VEHÍCULO EXISTENTE EN EL TALLER
      banner.style.display = 'flex';
      banner.style.background = 'rgba(56, 189, 248, 0.15)';
      banner.style.border = '1px solid #38bdf8';
      banner.style.color = '#bae6fd';
      bannerIcon.className = 'fa-solid fa-circle-check';
      
      const kmAnteriorTxt = d.kilometraje ? `${Number(d.kilometraje).toLocaleString('es-CL')} km` : 'Sin registro';
      let alertaHtml = '';
      if (d.alertas_mantenimiento && d.alertas_mantenimiento.length > 0) {
        alertaHtml = `<div style="margin-top: 6px; padding: 4px 8px; background: rgba(245, 158, 11, 0.2); border-left: 3px solid #f59e0b; border-radius: 4px; color: #fde047; font-weight: 600; font-size: 0.8rem;">
          <i class="fa-solid fa-triangle-exclamation"></i> Mantenimiento preventivo pendiente: ${d.alertas_mantenimiento.join(' • ')}
        </div>`;
      }
      bannerTexto.innerHTML = `<div><strong>Vehículo del taller:</strong> ${d.marca} ${d.modelo} (${d.patente}) • Dueño: <strong>${d.cliente_nombre}</strong> • Último km: ${kmAnteriorTxt} • Visitas: ${d.total_ordenes}${alertaHtml}</div>`;
      bannerBadge.className = 'badge badge-primary';
      bannerBadge.textContent = 'Historial Taller';

      // Seleccionar cliente
      document.getElementById('radioClienteExistente').checked = true;
      toggleClienteModo();
      const cliSelect = document.getElementById('clienteSelect');
      cliSelect.value = d.cliente_id;

      // Seleccionar vehículo
      document.getElementById('radioVehiculoExistente').checked = true;
      toggleVehiculoModo();
      await fetchVehiculos(d.cliente_id, d.vehiculo_id);

      if (d.kilometraje) {
        document.getElementById('kmIngresoInput').value = d.kilometraje;
      }
    } else {
      // VEHÍCULO NUEVO EXTRAÍDO DESDE API EXTERNA O DEMO
      banner.style.display = 'flex';
      banner.style.background = 'rgba(16, 185, 129, 0.15)';
      banner.style.border = '1px solid #10b981';
      banner.style.color = '#a7f3d0';
      bannerIcon.className = 'fa-solid fa-wand-magic-sparkles';
      
      const fuenteNombre = data.fuente === 'findatos' ? 'Findatos Chile' : (data.fuente === 'boostr' ? 'Boostr Chile' : (data.fuente === 'getapi' ? 'GetAPI' : (data.fuente === 'nhtsa' ? 'NHTSA' : 'API Automotriz')));
      bannerTexto.innerHTML = `<strong>Datos técnicos extraídos con éxito:</strong> ${d.marca} ${d.modelo} ${d.anio || ''} (${d.combustible || ''} ${d.motor || ''})`;
      bannerBadge.className = 'badge badge-success';
      bannerBadge.textContent = fuenteNombre;

      document.getElementById('radioVehiculoNuevo').checked = true;
      toggleVehiculoModo();

      document.getElementById('vn_patente').value = d.patente || patente;
      document.getElementById('vn_marca').value = d.marca || '';
      document.getElementById('vn_modelo').value = d.modelo || '';
      document.getElementById('vn_anio').value = d.anio || '';
      document.getElementById('vn_color').value = d.color || '';
      if (d.combustible) document.getElementById('vn_combustible').value = d.combustible;
      if (d.motor) document.getElementById('vn_motor').value = d.motor;
      if (d.transmision) document.getElementById('vn_transmision').value = d.transmision;
      if (d.tipo_vehiculo) document.getElementById('vn_tipo').value = d.tipo_vehiculo;
      if (d.vin) document.getElementById('vn_vin').value = d.vin;

      if (d.propietario) {
        document.getElementById('radioClienteNuevo').checked = true;
        toggleClienteModo();
        document.getElementById('cn_nombre').value = d.propietario;
      }
    }
  } catch (err) {
    banner.style.display = 'flex';
    banner.style.background = 'rgba(239, 68, 68, 0.15)';
    banner.style.border = '1px solid #ef4444';
    banner.style.color = '#fca5a5';
    bannerIcon.className = 'fa-solid fa-triangle-exclamation';
    bannerTexto.textContent = 'Error al comunicarse con el servicio de vehículos.';
    bannerBadge.className = 'badge badge-danger';
    bannerBadge.textContent = 'Error';
  } finally {
    btn.disabled = false;
    icon.className = 'fa-solid fa-magnifying-glass';
    txt.textContent = 'Buscar Auto';
  }
}

const MODELOS_POR_MARCA = {
  'Toyota': ['Yaris', 'Corolla', 'Hilux', 'RAV4', 'Rush', 'Etios', 'Land Cruiser', 'Fortuner', 'Prius', 'Urban Cruiser'],
  'Chevrolet': ['Sail', 'Onix', 'Tracker', 'D-Max', 'Silverado', 'Spark', 'Captiva', 'Aveo', 'Cruze', 'Groove', 'Montana'],
  'Hyundai': ['Tucson', 'Accent', 'Grand i10', 'Santa Fe', 'Creta', 'Elantra', 'Porter', 'Kona', 'Venue', 'H-1'],
  'Kia': ['Sportage', 'Rio', 'Morning', 'Soluto', 'Cerato', 'Sorento', 'Frontier', 'Seltos', 'Sonet', 'Carnival'],
  'Nissan': ['Navara', 'Versa', 'Kicks', 'Qashqai', 'X-Trail', 'NP300', 'Sentra', 'March', 'Terrano', 'Tiida'],
  'Suzuki': ['Swift', 'Baleno', 'Vitara', 'Jimny', 'S-Presso', 'Celerio', 'Alto', 'Grand Nomade', 'Dzire', 'Ertiga'],
  'Ford': ['Ranger', 'EcoSport', 'F-150', 'Explorer', 'Focus', 'Territory', 'Escape', 'Fiesta', 'Transit'],
  'Peugeot': ['208', '2008', '301', '3008', 'Partner', '5008', 'Boxer', 'Rifter', 'Expert'],
  'Volkswagen': ['Gol', 'Amarok', 'Polo', 'Taos', 'T-Cross', 'Tiguan', 'Vento', 'Nivus', 'Saveiro', 'Voyage'],
  'Mitsubishi': ['L200', 'Outlander', 'ASX', 'Montero', 'Eclipse Cross', 'Mirage', 'Katana'],
  'Chery': ['Tiggo 2', 'Tiggo 2 Pro', 'Tiggo 3', 'Tiggo 7 Pro', 'Tiggo 8 Pro', 'Arrizo 5'],
  'MG': ['MG3', 'ZS', 'RX5', 'MG5', 'ZX', 'MG6', 'HS', 'One'],
  'Mazda': ['Mazda 2', 'Mazda 3', 'Mazda 6', 'CX-3', 'CX-5', 'CX-30', 'BT-50', 'CX-9'],
  'Subaru': ['Forester', 'XV', 'Impreza', 'Outback', 'Legacy', 'Evoltis', 'Crosstrek'],
  'Honda': ['Civic', 'CR-V', 'HR-V', 'Accord', 'Fit', 'City', 'Pilot', 'WR-V']
};

function actualizarModelosSugeridos(marca, datalistId) {
  const dl = document.getElementById(datalistId);
  if (!dl) return;
  dl.innerHTML = '';
  // Buscar coincidencia exacta o insensible a mayúsculas
  let lista = MODELOS_POR_MARCA[marca] || [];
  if (lista.length === 0) {
    const k = Object.keys(MODELOS_POR_MARCA).find(m => m.toLowerCase() === (marca || '').trim().toLowerCase());
    if (k) lista = MODELOS_POR_MARCA[k];
  }
  lista.forEach(m => {
    const opt = document.createElement('option');
    opt.value = m;
    dl.appendChild(opt);
  });
}

async function fetchVehiculos(clienteId, seleccionar) {
  const select = document.getElementById('vehiculoSelect');
  if (!clienteId) {
    select.innerHTML = '<option value="">Primero selecciona un cliente...</option>';
    return;
  }
  select.innerHTML = '<option value="">Cargando vehículos...</option>';
  try {
    const res = await fetch(`api/vehiculos_cliente.php?cliente_id=${clienteId}`);
    const data = await res.json();
    const vehiculos = data.vehiculos || [];
    if (vehiculos.length === 0) {
      select.innerHTML = '<option value="">Este cliente no tiene vehículos. Elige "Vehículo nuevo".</option>';
      return;
    }
    let html = '<option value="">Selecciona un vehículo...</option>';
    for (const v of vehiculos) {
      const selected = (seleccionar && seleccionar === v.VehiculoID) ? 'selected' : '';
      const kmTxt = v.KilometrajeUltimo ? ` — ${Number(v.KilometrajeUltimo).toLocaleString('es-CL')} km` : '';
      html += `<option value="${v.VehiculoID}" ${selected}>${v.Patente} — ${v.Marca} ${v.Modelo}${kmTxt}</option>`;
    }
    select.innerHTML = html;
  } catch (e) {
    select.innerHTML = '<option value="">Error al cargar vehículos</option>';
  }
}

// ----------------------------------------------------
// LÓGICA DE DAÑOS DE CARROCERÍA (FÁCIL Y VISUAL)
// ----------------------------------------------------
let daniosList = [];
let tipoDanioActual = 'rayon';

function cambiarTipoDanio(tipo) {
  tipoDanioActual = tipo;
}

function agregarDanioZona(nombreZona, defX, defY) {
  const tipoTxt = tipoDanioActual === 'rayon' ? 'Rayón' : 'Golpe';
  daniosList.push({
    id: daniosList.length + 1,
    zona: nombreZona,
    tipo: tipoDanioActual,
    tipoTxt: tipoTxt,
    x: defX,
    y: defY
  });
  renderDanios();
}

const carSvg = document.getElementById('carSvgClean');
const pinsLayer = document.getElementById('pinsCleanLayer');
const daniosInput = document.getElementById('danios_carroceria_json');
const danioListContainer = document.getElementById('danioListContainer');
const danioCount = document.getElementById('danioCount');

carSvg.addEventListener('click', function(e) {
  const rect = carSvg.getBoundingClientRect();
  const x = ((e.clientX - rect.left) / rect.width) * 100;
  const y = ((e.clientY - rect.top) / rect.height) * 100;
  
  const tipoTxt = tipoDanioActual === 'rayon' ? 'Rayón' : 'Golpe';
  let zonaAuto = 'Carrocería';
  if (y < 11) {
    zonaAuto = 'Parachoques Delantero';
  } else if (y < 24) {
    if (x < 35) zonaAuto = 'Faro / Espejo Izquierdo';
    else if (x > 65) zonaAuto = 'Faro / Espejo Derecho';
    else zonaAuto = 'Capó / Frente';
  } else if (y < 43) {
    if (x < 38) zonaAuto = 'Puerta Delantera Izquierda';
    else if (x > 62) zonaAuto = 'Puerta Delantera Derecha';
    else zonaAuto = 'Techo / Parabrisas';
  } else if (y < 58) {
    if (x < 38) zonaAuto = 'Puerta Trasera Izquierda';
    else if (x > 62) zonaAuto = 'Puerta Trasera Derecha';
    else zonaAuto = 'Techo';
  } else if (y < 67) {
    zonaAuto = 'Maletero / Portalón';
  } else if (y < 76) {
    zonaAuto = 'Parachoques Trasero';
  } else {
    zonaAuto = 'Costado / Perfil Lateral';
  }

  daniosList.push({
    id: daniosList.length + 1,
    zona: zonaAuto,
    tipo: tipoDanioActual,
    tipoTxt: tipoTxt,
    x: parseFloat(x.toFixed(1)),
    y: parseFloat(y.toFixed(1))
  });

  renderDanios();
});

function renderDanios() {
  pinsLayer.innerHTML = '';
  if (daniosList.length === 0) {
    danioListContainer.innerHTML = '<div style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Sin daños marcados. Si el auto no tiene rayones ni golpes, déjalo vacío.</div>';
    danioCount.textContent = '0';
    daniosInput.value = '[]';
    return;
  }

  let listHtml = '';
  daniosList.forEach((d, idx) => {
    // Pin en silueta
    const pin = document.createElement('div');
    pin.className = `pin-clean ${d.tipo}`;
    pin.style.left = `${d.x}%`;
    pin.style.top = `${d.y}%`;
    pin.textContent = (d.tipo === 'rayon' ? 'R' : 'G') + (idx + 1);
    pinsLayer.appendChild(pin);

    const badgeColor = d.tipo === 'rayon' ? '#ef4444' : '#f59e0b';
    listHtml += `
      <div class="danio-item-row">
        <span>
          <strong style="color: ${badgeColor};">#${idx + 1} ${d.tipoTxt}</strong> en <strong>${d.zona}</strong>
        </span>
        <button type="button" onclick="eliminarDanio(${idx})" style="background:none; border:none; color:#f87171; cursor:pointer; font-size:1.1rem; padding:0 4px;" title="Eliminar">&times;</button>
      </div>
    `;
  });

  danioListContainer.innerHTML = listHtml;
  danioCount.textContent = daniosList.length;
  daniosInput.value = JSON.stringify(daniosList);
}

function eliminarDanio(index) {
  daniosList.splice(index, 1);
  renderDanios();
}

function limpiarTodosDanios() {
  daniosList = [];
  renderDanios();
}

// ----------------------------------------------------
// LÓGICA DEL CANVAS DE FIRMA
// ----------------------------------------------------
const canvas = document.getElementById('firmaCanvas');
const ctx = canvas.getContext('2d');
let dibujando = false;
let tieneFirma = false;

function iniciarDibujo(e) {
  dibujando = true;
  tieneFirma = true;
  ctx.beginPath();
  const pos = obtenerPos(e);
  ctx.moveTo(pos.x, pos.y);
  e.preventDefault();
}

function dibujar(e) {
  if (!dibujando) return;
  const pos = obtenerPos(e);
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#0f172a';
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();
  e.preventDefault();
}

function pararDibujo() {
  dibujando = false;
}

function obtenerPos(e) {
  const rect = canvas.getBoundingClientRect();
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;
  return {
    x: (clientX - rect.left) * scaleX,
    y: (clientY - rect.top) * scaleY
  };
}

canvas.addEventListener('mousedown', iniciarDibujo);
canvas.addEventListener('mousemove', dibujar);
window.addEventListener('mouseup', pararDibujo);

canvas.addEventListener('touchstart', iniciarDibujo, { passive: false });
canvas.addEventListener('touchmove', dibujar, { passive: false });
window.addEventListener('touchend', pararDibujo);

function limpiarFirma() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  tieneFirma = false;
  document.getElementById('firma_data').value = '';
}

document.getElementById('formOrdenIngreso').addEventListener('submit', function(e) {
  if (tieneFirma) {
    document.getElementById('firma_data').value = canvas.toDataURL('image/png');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  toggleVehiculoModo();
  const clienteIdInit = document.getElementById('clienteSelect').value;
  if (clienteIdInit) {
    fetchVehiculos(clienteIdInit, VEHICULO_PRECARGADO_ID);
  }
  <?php if (!empty($vehiculoPrecargado)): ?>
    document.getElementById('patenteUniversalInput').value = <?= json_encode($vehiculoPrecargado['Patente']) ?>;
    <?php if (!empty($vehiculoPrecargado['KilometrajeUltimo'])): ?>
      document.getElementById('kmIngresoInput').value = <?= (int)$vehiculoPrecargado['KilometrajeUltimo'] ?>;
    <?php endif; ?>
  <?php endif; ?>
});
</script>
