<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Vehículos y su historial</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Vehículos registrados, su dueño y el historial de órdenes de trabajo</p>
  </div>
  <div style="display: flex; gap: 0.5rem;">
    <button onclick="abrirNuevoVehiculo()" class="btn btn-primary">
      <i class="fa-solid fa-car-side"></i> Nuevo Vehículo
    </button>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
  <form method="GET" style="display: flex; gap: 0.5rem; max-width: 420px; flex: 1;">
    <input type="text" name="q" class="form-control" placeholder="Buscar por patente, marca, modelo o cliente..." value="<?= htmlspecialchars($q) ?>">
    <?php if (!empty($filtroRt)): ?>
      <input type="hidden" name="filtro_rt" value="<?= htmlspecialchars($filtroRt) ?>">
    <?php endif; ?>
    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>

  <!-- Filtros rápidos de Revisión Técnica (PRT) -->
  <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;"><i class="fa-solid fa-shield-halved"></i> Revisión Técnica:</span>
    <a href="vehiculos.php<?= !empty($q) ? '?q=' . urlencode($q) : '' ?>" class="btn <?= empty($filtroRt) ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.25rem 0.6rem; font-size: 0.78rem;">
      Todos (<?= $totalVehiculos ?>)
    </a>
    <a href="vehiculos.php?filtro_rt=por_vencer<?= !empty($q) ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $filtroRt === 'por_vencer' ? 'btn-warning' : 'btn-secondary' ?>" style="padding: 0.25rem 0.6rem; font-size: 0.78rem; <?= $filtroRt === 'por_vencer' ? 'background: #f59e0b; color: #000;' : 'color: #fbbf24;' ?>">
      <i class="fa-solid fa-triangle-exclamation"></i> Vencen este mes (<?= $totalPorVencer ?>)
    </a>
    <a href="vehiculos.php?filtro_rt=vencidos<?= !empty($q) ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $filtroRt === 'vencidos' ? 'btn-danger' : 'btn-secondary' ?>" style="padding: 0.25rem 0.6rem; font-size: 0.78rem; <?= $filtroRt === 'vencidos' ? 'background: #ef4444; color: #fff;' : 'color: #f87171;' ?>">
      <i class="fa-solid fa-circle-xmark"></i> Vencidos (<?= $totalVencidos ?>)
    </a>
  </div>
</div>

<div class="table-card">
  <div class="table-header">
    <h2 style="font-size: 1.1rem; font-weight: 600;">Vehículos Registrados</h2>
    <span style="color: var(--text-muted); font-size: 0.85rem;">Total: <?= count($vehiculos) ?></span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Patente</th>
        <th>Marca / Modelo</th>
        <th>Año</th>
        <th>Color</th>
        <th>Cliente</th>
        <th>Km Registrado</th>
        <th>Revisión Técnica</th>
        <th>Órdenes</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($vehiculos)): ?>
        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay vehículos registrados.</td></tr>
      <?php else: ?>
        <?php foreach ($vehiculos as $v): ?>
          <tr>
            <td>
              <a href="ficha_vehiculo.php?id=<?= $v['VehiculoID'] ?>" style="text-decoration: none;" title="Ver historial del vehículo">
                <code style="font-weight: 800; color: #38bdf8; font-size: 0.95rem;"><?= htmlspecialchars($v['Patente']) ?></code>
              </a>
            </td>
            <td style="font-weight: 600; color: #fff;">
              <?= htmlspecialchars($v['Marca']) ?> <?= htmlspecialchars($v['Modelo']) ?>
              <?php if (!empty($v['Combustible']) || !empty($v['Motor'])): ?>
                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: normal; margin-top: 2px; display: flex; align-items: center; gap: 0.4rem;">
                  <?php if (!empty($v['Combustible'])): ?>
                    <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; font-size: 0.68rem; padding: 1px 5px;"><?= htmlspecialchars($v['Combustible']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($v['Motor'])): ?>
                    <span style="color: #cbd5e1; font-size: 0.75rem;"><?= htmlspecialchars($v['Motor']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
            <td><?= $v['Anio'] ?: '-' ?></td>
            <td><?= htmlspecialchars($v['Color'] ?: '-') ?></td>
            <td><?= htmlspecialchars($v['ClienteNombre']) ?></td>
            <td><?= $v['KilometrajeUltimo'] ? number_format($v['KilometrajeUltimo'], 0, ',', '.') . ' km' : '-' ?></td>
            <td>
              <?php if (!empty($v['PRT']['valido'])): 
                $prt = $v['PRT'];
                $bg = $prt['badge_color'] === 'danger' ? 'rgba(239, 68, 68, 0.15)' : ($prt['badge_color'] === 'warning' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(16, 185, 129, 0.15)');
                $col = $prt['badge_color'] === 'danger' ? '#f87171' : ($prt['badge_color'] === 'warning' ? '#fbbf24' : '#34d399');
                $bor = $prt['badge_color'] === 'danger' ? 'rgba(239, 68, 68, 0.3)' : ($prt['badge_color'] === 'warning' ? 'rgba(245, 158, 11, 0.3)' : 'rgba(16, 185, 129, 0.3)');
              ?>
                <span class="badge" style="background: <?= $bg ?>; color: <?= $col ?>; border: 1px solid <?= $bor ?>; font-size: 0.73rem; padding: 3px 7px; display: inline-flex; align-items: center; gap: 4px;" title="<?= htmlspecialchars($prt['estado_label']) ?>">
                  <i class="<?= $prt['badge_icon'] ?>"></i> <?= htmlspecialchars($prt['meses_texto']) ?>
                  <?php if (!empty($prt['es_cuatrimestral'])): ?>
                    <i class="fa-solid fa-bus" style="margin-left: 2px; color: #f97316;" title="Régimen Cuatrimestral (Buses/Escolar)"></i>
                  <?php elseif (!empty($prt['es_semestral'])): ?>
                    <i class="fa-solid fa-truck" style="margin-left: 2px;" title="Régimen Semestral (Carga/Transporte)"></i>
                  <?php endif; ?>
                </span>
                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">
                  <?= $prt['vencimiento'] ? date('d/m/Y', strtotime($prt['vencimiento'])) : '' ?>
                </div>
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="ficha_vehiculo.php?id=<?= $v['VehiculoID'] ?>#ordenes" style="text-decoration: none;">
                <span class="badge badge-success"><?= (int)$v['TotalOrdenes'] ?></span>
              </a>
            </td>
            <td style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
              <a href="ficha_vehiculo.php?id=<?= $v['VehiculoID'] ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; background: rgba(59, 130, 246, 0.15); border-color: #3b82f6; color: #93c5fd;" title="Ver Hoja de Vida e Historial">
                <i class="fa-solid fa-file-waveform"></i> Historial
              </a>
              <button onclick='abrirEditarVehiculo(<?= json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" title="Editar datos del vehículo">
                <i class="fa-solid fa-pen"></i>
              </button>
              <a href="ordeningreso.php?vehiculo_id=<?= $v['VehiculoID'] ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" title="Crear Orden de Ingreso">
                <i class="fa-solid fa-right-to-bracket"></i> Ingreso
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Vehículo -->
<div id="vehiculoModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 1.5rem 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 460px; max-width: 95%; padding: 1.75rem; margin: auto; box-shadow: var(--shadow-lg);">
    <h2 id="vehiculoModalTitle" style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem;">Registrar Vehículo</h2>

    <form method="POST" action="vehiculos.php" style="display: flex; flex-direction: column; gap: 1rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_vehiculo">
      <input type="hidden" name="vehiculo_id" id="vf_vehiculo_id" value="">

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CLIENTE (DUEÑO) *</label>
        <select name="cliente_id" id="vf_cliente_id" class="form-control" required>
          <option value="">Selecciona un cliente...</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['ClienteID'] ?>"><?= htmlspecialchars($c['Nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
          <span>PATENTE *</span>
          <span style="color: #38bdf8; font-size: 0.75rem; cursor: pointer; font-weight: 600;" onclick="consultarApiVehiculosModal()">
            <i class="fa-solid fa-bolt"></i> Autocompletar con API
          </span>
        </label>
        <div style="display: flex; gap: 0.5rem;">
          <input type="text" name="patente" id="vf_patente" class="form-control" required placeholder="Ej: ABCD12" style="text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
          <button type="button" id="btnApiModalVehiculo" onclick="consultarApiVehiculosModal()" class="btn btn-secondary" style="padding: 0 0.85rem; font-size: 0.82rem; white-space: nowrap; border-color: #38bdf8; color: #38bdf8;" title="Buscar en API de vehículos">
            <i class="fa-solid fa-bolt" id="iconApiModal"></i> <span id="txtApiModal">API</span>
          </button>
        </div>
        <div id="apiModalStatus" style="display: none; font-size: 0.78rem; margin-top: 4px; padding: 3px 6px; border-radius: 4px;"></div>
      </div>

      <div style="border: 1px dashed #38bdf8; border-radius: 8px; padding: 0.6rem 0.75rem; margin-bottom: 0.75rem; background: rgba(56,189,248,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
          <span style="font-size: 0.78rem; font-weight: 700; color: #38bdf8;"><i class="fa-solid fa-paste"></i> Pegar datos de la consulta</span>
          <button type="button" class="btn btn-secondary" style="padding: 2px 10px; font-size: 0.75rem;" onclick="PegarVehiculo.abrirAutoRiesgo(document.getElementById('vf_patente').value) || alert('Escribe primero la patente')">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir AutoRiesgo
          </button>
        </div>
        <textarea id="vf_pegar_texto" class="form-control" rows="2" placeholder="Copia el resultado de la consulta y pégalo aquí (Ctrl+V)" style="font-size: 0.8rem;" oninput="pegarVehiculoAplicar('vf')"></textarea>
        <div id="vf_pegar_estado" style="display: none; font-size: 0.78rem; margin-top: 4px;"></div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MARCA *</label>
          <input type="text" name="marca" id="vf_marca" class="form-control" required placeholder="Ej: Toyota" list="listaMarcasPopularesModal" oninput="actualizarModelosSugeridos(this.value, 'listaModelosPopularesModal')" autocomplete="off">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MODELO *</label>
          <input type="text" name="modelo" id="vf_modelo" class="form-control" required placeholder="Ej: Yaris" list="listaModelosPopularesModal" autocomplete="off">
        </div>
      </div>

      <datalist id="listaMarcasPopularesModal">
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
      </datalist>
      <datalist id="listaModelosPopularesModal"></datalist>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">AÑO</label>
          <input type="number" name="anio" id="vf_anio" class="form-control" placeholder="2020">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">COLOR</label>
          <input type="text" name="color" id="vf_color" class="form-control" placeholder="Ej: Blanco">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">COMBUSTIBLE</label>
          <select name="combustible" id="vf_combustible" class="form-control">
            <option value="">Seleccionar...</option>
            <option value="Bencina">Bencina (Gasolina)</option>
            <option value="Diésel">Diésel</option>
            <option value="Híbrido">Híbrido</option>
            <option value="Eléctrico">Eléctrico</option>
            <option value="Gas GLP/GNC">Gas GLP/GNC</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MOTOR / CILINDRADA</label>
          <input type="text" name="motor" id="vf_motor" class="form-control" placeholder="Ej: 1.5L, 2.0 TDI">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TRANSMISIÓN</label>
          <select name="transmision" id="vf_transmision" class="form-control">
            <option value="">Seleccionar...</option>
            <option value="Manual">Manual</option>
            <option value="Automática">Automática</option>
            <option value="CVT">CVT</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CARROCERÍA</label>
          <input type="text" name="tipo_vehiculo" id="vf_tipo" class="form-control" placeholder="Ej: Sedán, SUV, Pickup">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">KILOMETRAJE</label>
          <input type="number" name="kilometraje" id="vf_km" class="form-control" placeholder="Ej: 45000">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">VIN / CHASIS</label>
          <input type="text" name="vin" id="vf_vin" class="form-control" placeholder="Opcional" style="text-transform: uppercase;">
        </div>
      </div>

      <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.75rem 0.95rem;">
        <label style="font-size: 0.8rem; color: #38bdf8; font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-shield-halved"></i> FRECUENCIA REVISIÓN TÉCNICA (PRT)
        </label>
        <div style="display: flex; gap: 0.85rem; flex-wrap: wrap;">
          <label style="font-size: 0.8rem; color: #f1f5f9; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; margin: 0;">
            <input type="radio" name="regimen_prt" id="vf_prt_12" value="anual" checked style="accent-color: #38bdf8;">
            <span><strong>Cada 12 meses</strong> <small style="color: var(--text-muted);">(Particular)</small></span>
          </label>
          <label style="font-size: 0.8rem; color: #f1f5f9; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; margin: 0;">
            <input type="radio" name="regimen_prt" id="vf_prt_6" value="semestral" style="accent-color: #a855f7;">
            <span><strong>Cada 6 meses</strong> <small style="color: var(--text-muted);">(Transporte / Carga / Taxi)</small></span>
          </label>
          <label style="font-size: 0.8rem; color: #f1f5f9; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; margin: 0;">
            <input type="radio" name="regimen_prt" id="vf_prt_4" value="cuatrimestral" style="accent-color: #f97316;">
            <span><strong>Cada 4 meses</strong> <small style="color: var(--text-muted);">(Buses antiguos / Escolar)</small></span>
          </label>
        </div>
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Guardar Vehículo</button>
        <button type="button" onclick="document.getElementById('vehiculoModal').style.display='none'" class="btn btn-secondary btn-block">Cancelar</button>
      </div>
    </form>
  </div>
</div>

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
function abrirNuevoVehiculo() {
  document.getElementById('vehiculoModalTitle').textContent = 'Registrar Vehículo';
  document.getElementById('vf_vehiculo_id').value = '';
  ['vf_cliente_id', 'vf_patente', 'vf_anio', 'vf_marca', 'vf_modelo', 'vf_color', 'vf_combustible', 'vf_motor', 'vf_transmision', 'vf_tipo', 'vf_km', 'vf_vin'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const r12 = document.getElementById('vf_prt_12');
  if (r12) r12.checked = true;
  document.getElementById('apiModalStatus').style.display = 'none';
  document.getElementById('vehiculoModal').style.display = 'flex';
}

function abrirEditarVehiculo(v) {
  document.getElementById('vehiculoModalTitle').textContent = 'Editar Vehículo — ' + v.Patente;
  document.getElementById('vf_vehiculo_id').value = v.VehiculoID;
  document.getElementById('vf_cliente_id').value = v.ClienteID;
  document.getElementById('vf_patente').value = v.Patente;
  document.getElementById('vf_anio').value = v.Anio || '';
  document.getElementById('vf_marca').value = v.Marca;
  document.getElementById('vf_modelo').value = v.Modelo;
  document.getElementById('vf_color').value = v.Color || '';
  document.getElementById('vf_combustible').value = v.Combustible || '';
  document.getElementById('vf_motor').value = v.Motor || '';
  document.getElementById('vf_transmision').value = v.Transmision || '';
  document.getElementById('vf_tipo').value = v.TipoVehiculo || '';
  document.getElementById('vf_km').value = v.KilometrajeUltimo || '';
  document.getElementById('vf_vin').value = v.VIN || '';

  const reg = (v.RevisionTecnicaRegimen || (Number(v.EsTransportePublico) ? 'semestral' : 'anual')).toLowerCase();
  if (reg === 'cuatrimestral') {
    const r4 = document.getElementById('vf_prt_4');
    if (r4) r4.checked = true;
  } else if (reg === 'semestral') {
    const r6 = document.getElementById('vf_prt_6');
    if (r6) r6.checked = true;
  } else {
    const r12 = document.getElementById('vf_prt_12');
    if (r12) r12.checked = true;
  }

  document.getElementById('apiModalStatus').style.display = 'none';
  document.getElementById('vehiculoModal').style.display = 'flex';
}

async function consultarApiVehiculosModal() {
  const input = document.getElementById('vf_patente');
  const patente = (input.value || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (!patente) {
    input.focus();
    input.style.borderColor = '#ef4444';
    setTimeout(() => input.style.borderColor = '', 1500);
    return;
  }

  const btn = document.getElementById('btnApiModalVehiculo');
  const icon = document.getElementById('iconApiModal');
  const txt = document.getElementById('txtApiModal');
  const status = document.getElementById('apiModalStatus');

  btn.disabled = true;
  icon.className = 'fa-solid fa-spinner fa-spin';
  txt.textContent = 'Buscando...';
  status.style.display = 'none';

  try {
    const res = await fetch(`api/consultar_vehiculo_api.php?patente=${encodeURIComponent(patente)}&sin_local=1`);
    const data = await res.json();

    if (!data.success) {
      status.style.display = 'block';
      status.style.background = 'rgba(239, 68, 68, 0.15)';
      status.style.color = '#f87171';
      status.textContent = data.error || 'No se obtuvieron datos para esta patente.';
      return;
    }

    const d = data.datos;
    if (d.marca) document.getElementById('vf_marca').value = d.marca;
    if (d.modelo) document.getElementById('vf_modelo').value = d.modelo;
    if (d.anio) document.getElementById('vf_anio').value = d.anio;
    if (d.color) document.getElementById('vf_color').value = d.color;
    if (d.combustible) document.getElementById('vf_combustible').value = d.combustible;
    if (d.motor) document.getElementById('vf_motor').value = d.motor;
    if (d.transmision) document.getElementById('vf_transmision').value = d.transmision;
    if (d.tipo_vehiculo) document.getElementById('vf_tipo').value = d.tipo_vehiculo;
    if (d.vin) document.getElementById('vf_vin').value = d.vin;
    if (d.revision_tecnica) {
      if (d.revision_tecnica.regimen === 'cuatrimestral') {
        const r4 = document.getElementById('vf_prt_4');
        if (r4) r4.checked = true;
      } else if (d.revision_tecnica.regimen === 'semestral' || d.revision_tecnica.es_semestral) {
        const r6 = document.getElementById('vf_prt_6');
        if (r6) r6.checked = true;
      } else {
        const r12 = document.getElementById('vf_prt_12');
        if (r12) r12.checked = true;
      }
    }

    status.style.display = 'block';
    status.style.background = 'rgba(16, 185, 129, 0.15)';
    status.style.color = '#34d399';
    const prtTxt = d.revision_tecnica ? ` • RT: ${d.revision_tecnica.meses_texto}` : '';
    status.innerHTML = `<i class="fa-solid fa-check"></i> Datos completados vía API (${d.marca} ${d.modelo} ${d.combustible || ''}${prtTxt})`;
  } catch (e) {
    status.style.display = 'block';
    status.style.background = 'rgba(239, 68, 68, 0.15)';
    status.style.color = '#f87171';
    status.textContent = 'Error al comunicarse con la API de vehículos.';
  } finally {
    btn.disabled = false;
    icon.className = 'fa-solid fa-bolt';
    txt.textContent = 'API';
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
</script>
