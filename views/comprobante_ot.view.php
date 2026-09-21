<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
$fechaIngreso = date('d/m/Y H:i', strtotime($ot['FechaIngreso']));
$nombreTaller = $nombreEmpresa ?? 'Taller Mecánico';
$rutCliente = $ot['RutCuerpo'] ? $ot['RutCuerpo'] . '-' . $ot['RutDv'] : 'Sin registrar';

function chkLabel($valor) {
    return $valor === 'Si'
        ? '<span style="color:#10b981; font-weight:700;">Sí</span>'
        : '<span style="color:#ef4444; font-weight:700;">No</span>';
}
?>
<style>
  .comprobante-wrap { max-width: 780px; margin: 0 auto; }
  .comprobante-paper { background: #fff; color: #111827; border-radius: 10px; padding: 1.75rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-lg); border: 1px solid #e5e7eb; }
  .comprobante-paper h3 { color: #111827; }
  .comprobante-label { text-align: center; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem; }
  .cp-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 0.75rem; margin-bottom: 0.75rem; }
  .cp-folio { font-size: 1.2rem; font-weight: 800; color: #1e40af; }
  .cp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem; font-size: 0.85rem; margin-bottom: 0.85rem; }
  .cp-grid .k { font-size: 0.68rem; text-transform: uppercase; color: #6b7280; font-weight: 700; }
  
  .cp-section-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #1e3a8a; margin: 0.85rem 0 0.35rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.2rem; }
  
  .cp-checklist-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.2rem 1.5rem; font-size: 0.8rem; }
  .cp-checklist-item { display: flex; justify-content: space-between; padding: 0.15rem 0; border-bottom: 1px dotted #e5e7eb; }
  
  /* Estación de servicio table */
  .cp-es-table { width: 100%; border-collapse: collapse; font-size: 0.78rem; margin: 0.4rem 0 0.75rem; }
  .cp-es-table th { background: #f3f4f6; color: #374151; padding: 4px 6px; border: 1px solid #d1d5db; text-align: left; }
  .cp-es-table td { padding: 4px 6px; border: 1px solid #e5e7eb; color: #1f2937; }

  /* Diagrama de daños en comprobante */
  .cp-diagram-wrap { display: flex; gap: 1rem; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem; margin-bottom: 0.75rem; }
  .cp-car-svg { width: 280px; height: auto; position: relative; }
  .cp-pin { position: absolute; width: 16px; height: 16px; border-radius: 50%; color: white; font-size: 8.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; transform: translate(-50%, -50%); border: 1.5px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.35); }

  .cp-legal { font-size: 0.68rem; color: #6b7280; margin-top: 0.75rem; border-top: 1px solid #e5e7eb; padding-top: 0.5rem; line-height: 1.35; }
  .cp-firma-box { margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
  .cp-firma-img { border-bottom: 1px solid #111827; height: 65px; }
  .cp-reducido { display: flex; justify-content: space-between; gap: 1.5rem; }
  .cp-reducido .cp-folio-lg { font-size: 1.4rem; font-weight: 800; color: #1e40af; }

  @media print {
    body * { visibility: hidden !important; }
    .comprobante-print, .comprobante-print * { visibility: visible !important; }
    .comprobante-print { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; margin: 0 !important; }
    .comprobante-paper { box-shadow: none !important; page-break-after: always; padding: 1.2rem; }
    .comprobante-label { display: none !important; }
    .no-print { display: none !important; }
    @page { size: A4; margin: 10mm; }
  }
</style>

<div class="no-print" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Comprobante de recepción</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;"><strong><?= htmlspecialchars($folio) ?></strong> · <?= htmlspecialchars($ot['Patente']) ?> <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> — <?= htmlspecialchars($ot['ClienteNombre']) ?></p>
  </div>
  <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-arrow-left"></i> Órdenes de trabajo</a>
</div>
<?php otStepper($ot, 1); ?>
<div class="ts-help no-print" style="justify-content: space-between; align-items: center; flex-wrap: wrap;">
  <div><i class="fa-solid fa-circle-check" style="color:#34d399;"></i> <strong>Vehículo recibido.</strong> Imprime el comprobante: se genera una copia para el cliente y otra reducida para dejar dentro del auto.</div>
  <div style="display: flex; gap: 0.5rem;">
    <button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Imprimir</button>
    <a href="diagnostico.php?id=<?= $otId ?>" class="btn btn-primary">Siguiente: diagnóstico <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</div>

<div class="comprobante-wrap comprobante-print">

  <!-- ======================================================== -->
  <!-- COPIA 1: CLIENTE (Ficha Completa de Custodia)           -->
  <!-- ======================================================== -->
  <div class="comprobante-label">Copia Cliente</div>
  <div class="comprobante-paper">
    <div class="cp-header">
      <div>
        <div style="font-weight: 800; font-size: 1.2rem; color: #0f172a;"><?= htmlspecialchars($nombreTaller) ?></div>
        <div style="font-size: 0.8rem; color: #4b5563;">SERVICIO TÉCNICO AUTOMOTRIZ — COMPROBANTE DE CUSTODIA</div>
      </div>
      <div style="text-align: right;">
        <div class="cp-folio"><?= htmlspecialchars($folio) ?></div>
        <div style="font-size: 0.8rem; color: #6b7280;"><?= $fechaIngreso ?></div>
      </div>
    </div>

    <!-- Datos Cliente y Vehículo -->
    <div class="cp-grid">
      <div><div class="k">Cliente</div><strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong></div>
      <div><div class="k">RUT / DNI</div><?= htmlspecialchars($rutCliente) ?></div>
      <div><div class="k">Teléfono</div><?= htmlspecialchars($ot['ClienteTelefono'] ?: '-') ?></div>
      <div><div class="k">Asesor que recibe</div><?= htmlspecialchars($ot['UsuarioNombre']) ?></div>

      <div><div class="k">Vehículo</div><?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> <?= $ot['Anio'] ?: '' ?></div>
      <div><div class="k">Patente / Placa</div><strong style="font-size: 1.05rem;"><?= htmlspecialchars($ot['Patente']) ?></strong></div>
      <div><div class="k">Color / VIN</div><?= htmlspecialchars($ot['Color'] ?: '-') ?> &nbsp;•&nbsp; <?= htmlspecialchars($ot['VIN'] ?: 'Sin VIN') ?></div>
      <div><div class="k">Kilometraje de Ingreso</div><strong><?= $ot['KilometrajeIngreso'] ? number_format($ot['KilometrajeIngreso'], 0, ',', '.') . ' km' : '-' ?></strong></div>

      <div><div class="k">Nivel Combustible</div><strong><?= htmlspecialchars($ot['NivelCombustible']) ?></strong></div>
      <div><div class="k">Objetos de Valor Declarados</div><?= htmlspecialchars($ot['ObjetosValor'] ?: 'Ninguno declarado') ?></div>
    </div>

    <!-- Operaciones Solicitadas (Imagen 1) -->
    <?php if (!empty($operacionesSolicitadas) || !empty($ot['OperacionesTextoLibre'])): ?>
      <div class="cp-section-title">Operaciones Solicitadas por el Cliente</div>
      <div style="font-size: 0.8rem; margin-bottom: 0.6rem; background: #f8fafc; padding: 0.4rem 0.6rem; border-radius: 4px; border: 1px solid #e2e8f0;">
        <?php if (!empty($operacionesSolicitadas)): ?>
          <div style="margin-bottom: 0.2rem;">
            <strong>Servicios solicitados:</strong> <?= htmlspecialchars(implode(' &nbsp;•&nbsp; ', $operacionesSolicitadas)) ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($ot['OperacionesTextoLibre'])): ?>
          <div style="color: #4b5563;">
            <strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($ot['OperacionesTextoLibre'])) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Estación de Servicio y Fluidos (Imagen 2) -->
    <?php if ($estacion): ?>
      <div class="cp-section-title">Niveles y fluidos</div>
      <table class="cp-es-table">
        <thead>
          <tr>
            <th>Sistema / Fluido</th>
            <th>Nivel</th>
            <th>Acción Requerida</th>
            <th>Sistema / Fluido</th>
            <th>Nivel</th>
            <th>Acción Requerida</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Motor</strong></td>
            <td><?= htmlspecialchars($estacion['MotorNivel']) ?></td>
            <td><?= $estacion['MotorCambio'] ? '<span style="color:#d97706;font-weight:bold;">Cambiar Aceite</span>' : 'Correcto' ?></td>
            <td><strong>Líquido Frenos</strong></td>
            <td><?= htmlspecialchars($estacion['FrenosNivel']) ?></td>
            <td><?= $estacion['FrenosCambio'] ? '<span style="color:#d97706;font-weight:bold;">Purgar/Cambio</span>' : 'Correcto' ?></td>
          </tr>
          <tr>
            <td><strong>Filtro Aceite</strong></td>
            <td>-</td>
            <td><?= $estacion['FiltroCambio'] ? '<span style="color:#d97706;font-weight:bold;">Cambiar Filtro</span>' : 'Correcto' ?></td>
            <td><strong>Radiador</strong></td>
            <td><?= htmlspecialchars($estacion['RadiadorNivel']) ?></td>
            <td><?= $estacion['RadiadorAnticongelante'] ? '<span style="color:#d97706;font-weight:bold;">Cargar Anticong.</span>' : 'Correcto' ?></td>
          </tr>
          <tr>
            <td><strong>Dir. Hidráulica</strong></td>
            <td><?= htmlspecialchars($estacion['DHNivel']) ?></td>
            <td>-</td>
            <td><strong>Lavavidrios</strong></td>
            <td>-</td>
            <td><?= $estacion['LavVidrioCarga'] ? '<span style="color:#2563eb;font-weight:bold;">Cargar Líquido</span>' : 'Correcto' ?></td>
          </tr>
          <tr>
            <td><strong>Caja Velocidad</strong></td>
            <td><?= htmlspecialchars($estacion['CajaNivel']) ?></td>
            <td><?= $estacion['CajaCambio'] ? '<span style="color:#d97706;font-weight:bold;">Cambiar Aceite</span>' : 'Correcto' ?></td>
            <td><strong>Lavado</strong></td>
            <td colspan="2"><?= $estacion['LavadoCarroceria'] !== 'No' ? 'Lavado ' . htmlspecialchars($estacion['LavadoCarroceria']) : 'No requerido' ?></td>
          </tr>
        </tbody>
      </table>
      <?php if (!empty($estacion['Observaciones'])): ?>
        <div style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.5rem;">
          <strong>Notas fluidos:</strong> <?= htmlspecialchars($estacion['Observaciones']) ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Diagrama de Daños de Carrocería (si tiene puntos marcados) -->
    <?php if (!empty($daniosCarroceria)): ?>
      <div class="cp-section-title">Inspección de Carrocería (Daños y Rayaduras Preexistentes)</div>
      <div class="cp-diagram-wrap">
        <div style="position: relative; display: inline-block;">
          <!-- Mini SVG silueta exacta y limpia -->
          <svg style="width: 150px; height: 168px; display: block; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;" viewBox="0 0 340 380">
            <!-- Fondo de contraste -->
            <rect width="340" height="380" fill="#f8fafc" rx="8"/>
            
            <!-- Silueta Auto Superior -->
            <g transform="translate(90, 20)">
              <rect x="10" y="20" width="140" height="240" rx="30" fill="#e2e8f0" stroke="#1e293b" stroke-width="2.5"/>
              <path d="M 22 25 Q 80 18 138 25 L 138 65 Q 80 60 22 65 Z" fill="#cbd5e1" stroke="#475569" stroke-width="1.5"/>
              <text x="80" y="45" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">CAPÓ / FRENTE</text>
              <path d="M 24 70 Q 80 65 136 70 L 130 95 Q 80 90 30 95 Z" fill="#94a3b8" stroke="#334155" stroke-width="1.5"/>
              <rect x="30" y="100" width="100" height="85" rx="10" fill="#cbd5e1" stroke="#334155" stroke-width="1.5"/>
              <text x="80" y="145" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">TECHO</text>
              <path d="M 30 190 Q 80 195 130 190 L 136 215 Q 80 220 24 215 Z" fill="#94a3b8" stroke="#334155" stroke-width="1.5"/>
              <path d="M 22 220 Q 80 225 138 220 L 138 255 Q 80 262 22 255 Z" fill="#cbd5e1" stroke="#475569" stroke-width="1.5"/>
              <text x="80" y="242" fill="#475569" font-size="9" font-weight="bold" text-anchor="middle">MALETERO</text>
              <rect x="-2" y="70" width="12" height="18" rx="4" fill="#334155"/>
              <rect x="150" y="70" width="12" height="18" rx="4" fill="#334155"/>
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
              <circle cx="68" cy="65" r="14" fill="#0f172a" stroke="#fff" stroke-width="2"/>
              <circle cx="192" cy="65" r="14" fill="#0f172a" stroke="#fff" stroke-width="2"/>
            </g>
          </svg>
          <!-- Pines dibujados sobre el SVG con prefijos R o G -->
          <?php foreach ($daniosCarroceria as $idx => $d): 
            $pinBg = ($d['tipo'] ?? '') === 'rayon' ? '#ef4444' : '#f59e0b';
            $pref = ($d['tipo'] ?? '') === 'rayon' ? 'R' : 'G';
          ?>
            <div class="cp-pin" style="left: <?= (float)$d['x'] ?>%; top: <?= (float)$d['y'] ?>%; background: <?= $pinBg ?>;">
              <?= $pref . ($idx + 1) ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="font-size: 0.78rem; flex: 1;">
          <strong>Detalle de marcas de carrocería (<?= count($daniosCarroceria) ?>):</strong>
          <ul style="margin: 0.35rem 0 0 1rem; padding: 0; line-height: 1.5;">
            <?php foreach ($daniosCarroceria as $idx => $d): 
              $pref = ($d['tipo'] ?? '') === 'rayon' ? 'R' : 'G';
              $badgeColor = ($d['tipo'] ?? '') === 'rayon' ? '#dc2626' : '#d97706';
            ?>
              <li>
                <strong style="color: <?= $badgeColor ?>;">#<?= $pref . ($idx + 1) ?>:</strong>
                <?= htmlspecialchars($d['tipoTxt'] ?? ucfirst($d['tipo'] ?? 'Marca')) ?>
                <?= !empty($d['zona']) ? ' en <strong>' . htmlspecialchars($d['zona']) . '</strong>' : '' ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Checklist de Recepción (Inventario de Accesorios) -->
    <div class="cp-section-title">Inventario de Accesorios en Recepción</div>
    <div class="cp-checklist-grid">
      <?php foreach ($checklist as $it): ?>
        <div class="cp-checklist-item">
          <span><?= htmlspecialchars($it['Nombre']) ?><?= $it['Detalle'] ? ' (' . htmlspecialchars($it['Detalle']) . ')' : '' ?></span>
          <span><?= chkLabel($it['Valor']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Autorizaciones y Firma -->
    <div style="font-size: 0.78rem; margin: 0.75rem 0 0.5rem; background: #f8fafc; padding: 0.4rem 0.6rem; border-radius: 4px; border: 1px solid #e2e8f0;">
      <strong>Autorizaciones del cliente:</strong> &nbsp;
      Presupuesto previo antes de reparar: <?= $ot['AutorizaPresupuestoPrevio'] ? chkLabel('Si') : chkLabel('No') ?> &nbsp;·&nbsp;
      Prueba de manejo en ruta: <?= $ot['AutorizaPruebaManejo'] ? chkLabel('Si') : chkLabel('No') ?>
    </div>

    <div class="cp-firma-box">
      <div style="flex: 1;">
        <?php if (!empty($ot['FirmaClienteBase64'])): ?>
          <img src="<?= htmlspecialchars($ot['FirmaClienteBase64']) ?>" class="cp-firma-img" alt="Firma del cliente">
        <?php else: ?>
          <div class="cp-firma-img"></div>
        <?php endif; ?>
        <div style="font-size: 0.75rem; color: #4b5563; font-weight: 600;">Firma de conformidad del cliente</div>
      </div>
      <div style="font-size: 0.75rem; color: #4b5563; text-align: right;">
        <div>_________________________________</div>
        <div style="font-weight: 600; margin-top: 2px;">Firma Recepcionista / Taller</div>
      </div>
    </div>

    <div class="cp-legal">
      La empresa y/o sus dependientes no se responsabilizan por pérdidas o deterioros de objetos de valor no declarados e inventariados al momento del ingreso. El cliente autoriza la custodia del vehículo bajo las condiciones técnicas y estéticas descritas en el presente comprobante.
    </div>
  </div>

  <!-- ======================================================== -->
  <!-- COPIA 2: PARABRISAS / RETROVISOR (Reducida para Bahía)   -->
  <!-- ======================================================== -->
  <div class="comprobante-label">Copia Parabrisas / Retrovisor</div>
  <div class="comprobante-paper" style="border: 2px dashed #94a3b8;">
    <div class="cp-reducido">
      <div>
        <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a;"><?= htmlspecialchars($nombreTaller) ?></div>
        <div style="font-size: 1.25rem; margin-top: 0.25rem;">
          <strong style="background: #1e293b; color: #fff; padding: 2px 8px; border-radius: 4px;"><?= htmlspecialchars($ot['Patente']) ?></strong>
          <span style="font-size: 1rem; margin-left: 0.5rem;"><?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> (<?= $ot['Anio'] ?: '' ?>)</span>
        </div>
        <div style="font-size: 0.85rem; margin-top: 0.25rem; color: #374151;">
          Cliente: <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong> &nbsp;•&nbsp; Tel: <?= htmlspecialchars($ot['ClienteTelefono'] ?: '-') ?>
        </div>
        
        <?php if (!empty($operacionesSolicitadas)): ?>
          <div style="margin-top: 0.5rem; font-size: 0.82rem; background: #eff6ff; padding: 0.35rem 0.5rem; border-radius: 4px; border: 1px solid #bfdbfe;">
            <strong style="color: #1e40af;">TAREAS SOLICITADAS:</strong> <?= htmlspecialchars(implode(' • ', $operacionesSolicitadas)) ?>
          </div>
        <?php endif; ?>

        <?php if ($estacion && ($estacion['MotorCambio'] || $estacion['FrenosCambio'] || $estacion['RadiadorAnticongelante'])): ?>
          <div style="margin-top: 0.35rem; font-size: 0.8rem; color: #b45309;">
            <strong>FLUIDOS URGENTES:</strong>
            <?= $estacion['MotorCambio'] ? '[Cambio Aceite] ' : '' ?>
            <?= $estacion['FrenosCambio'] ? '[Líquido Frenos] ' : '' ?>
            <?= $estacion['RadiadorAnticongelante'] ? '[Anticongelante] ' : '' ?>
          </div>
        <?php endif; ?>

        <div style="font-size: 0.78rem; color: #6b7280; margin-top: 0.4rem;">
          Ingreso: <?= $fechaIngreso ?> &nbsp;•&nbsp; Km: <?= $ot['KilometrajeIngreso'] ? number_format($ot['KilometrajeIngreso'], 0, ',', '.') : '-' ?>
        </div>
      </div>

      <div style="text-align: right; min-width: 140px;">
        <div class="cp-folio-lg"><?= htmlspecialchars($folio) ?></div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">Combustible: <strong><?= htmlspecialchars($ot['NivelCombustible']) ?></strong></div>
        <div style="font-size: 0.78rem; color: #6b7280; margin-top: 0.25rem;">Asesor: <?= htmlspecialchars($ot['UsuarioNombre']) ?></div>
      </div>
    </div>
  </div>

</div>
