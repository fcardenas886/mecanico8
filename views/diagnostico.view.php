<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
// El diagnóstico solo se edita mientras la OT está en sus primeros dos estados;
// cualquier estado posterior (presupuesto, ejecución, entrega) lo deja cerrado.
$estadosAbiertos = ['Ingresado', 'En diagnóstico'];
$esCerrado = !in_array($ot['Estado'], $estadosAbiertos, true);

$badgeClase = [
    'Ingresado' => 'badge-success',
    'En diagnóstico' => 'badge-warning',
    'Diagnosticado' => 'badge-success',
    'Diagnóstico no aplica' => 'badge-success',
][$ot['Estado']] ?? 'badge-success';
?>
<style>
  .dg-section { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
  .dg-area-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin: 1rem 0 0.5rem; letter-spacing: 0.03em; }
  .dg-area-title:first-child { margin-top: 0; }
  .dg-hallazgo { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px dashed var(--border-dark); font-size: 0.88rem; }
  .dg-hallazgo:last-child { border-bottom: none; }
  .dg-hallazgo .meta { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem; }
  .dg-empty { color: var(--text-muted); font-size: 0.85rem; padding: 0.5rem 0; }
  
  .dg-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
  .dg-info-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 8px; padding: 1rem; }
  .dg-info-card h3 { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: #93c5fd; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.4rem; }
  
  .badge-tag-op { display: inline-block; background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #93c5fd; padding: 2px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 600; margin: 2px; }
  .badge-danio { display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; margin: 2px; }
</style>

<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Diagnóstico del vehículo</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
      <strong><?= htmlspecialchars($folio) ?></strong> ·
      <code style="font-weight: 700;"><?= htmlspecialchars($ot['Patente']) ?></code>
      <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> — <?= htmlspecialchars($ot['ClienteNombre']) ?>
      <?= $ot['KilometrajeIngreso'] ? ' (' . number_format($ot['KilometrajeIngreso'], 0, ',', '.') . ' km)' : '' ?>
    </p>
  </div>
  <div style="display: flex; align-items: center; gap: 0.75rem;">
    <span class="badge <?= $badgeClase ?>"><?= htmlspecialchars(otEstadoInfo($ot)['etiqueta']) ?></span>
    <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
      <i class="fa-solid fa-arrow-left"></i> Órdenes de trabajo
    </a>
  </div>
</div>
<?php otStepper($ot, 2); ?>
<?php if (!$esCerrado): ?>
  <?php tallerAyuda('<strong>Qué hago aquí:</strong> anota lo que el mecánico encuentra en el vehículo (agrega cada hallazgo). Cuando termines, pulsa <strong>Finalizar diagnóstico</strong>. Si el cliente ya sabe exactamente qué necesita (por ejemplo, cambiar un foco), pulsa <strong>No aplica</strong> y pasa directo al presupuesto.'); ?>
<?php endif; ?>

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

<!-- Resumen de Recepción del Vehículo -->
<div class="dg-section" style="margin-bottom: 1.25rem;">
  <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
    <i class="fa-solid fa-clipboard-check" style="color: #60a5fa;"></i> Antecedentes de Recepción del Vehículo
  </h2>
  
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
    <!-- Operaciones solicitadas -->
    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.85rem 1rem;">
      <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.4rem;">
        Operaciones Solicitadas por el Cliente
      </div>
      <?php if (empty($operacionesSolicitadas) && empty($ot['OperacionesTextoLibre'])): ?>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">No se registraron operaciones puntuales en el ingreso.</p>
      <?php else: ?>
        <div>
          <?php foreach ($operacionesSolicitadas as $op): ?>
            <span class="badge-tag-op"><i class="fa-solid fa-check"></i> <?= htmlspecialchars($op) ?></span>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($ot['OperacionesTextoLibre'])): ?>
          <div style="margin-top: 0.5rem; font-size: 0.82rem; color: #cbd5e1; background: rgba(0,0,0,0.2); padding: 0.4rem 0.6rem; border-radius: 4px;">
            <strong>Nota:</strong> <?= nl2br(htmlspecialchars($ot['OperacionesTextoLibre'])) ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Daños de carrocería observados -->
    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.85rem 1rem;">
      <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.4rem;">
        Daños Registrados al Ingreso (Carrocería)
      </div>
      <?php if (empty($daniosCarroceria)): ?>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">Sin daños registrados al recibir el vehículo.</p>
      <?php else: ?>
        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
          <?php foreach ($daniosCarroceria as $idx => $d): 
            $bg = ($d['tipo'] ?? '') === 'rayon' ? '#ef4444' : '#f59e0b';
          ?>
            <span class="badge-danio" style="background: <?= $bg ?>; color: white;">
              #<?= $idx + 1 ?> <?= htmlspecialchars($d['zonaNombre'] ?? $d['tipoTxt'] ?? $d['tipo']) ?> (<?= htmlspecialchars($d['tipoTxt'] ?? $d['tipo']) ?>)
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<!-- Observaciones de Estación de Servicio -->
<div class="dg-section">
  <div style="margin-bottom: 0.6rem;">
    <h2 style="font-size: 0.95rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
      <i class="fa-solid fa-clipboard-list" style="color: #38bdf8;"></i> Observaciones de Estación de Servicio
    </h2>
    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
      Anotaciones de la inspección inicial o detalles técnicos generales del vehículo.
    </p>
  </div>

  <?php if (!$esCerrado): ?>
    <form method="POST" action="diagnostico.php?id=<?= $otId ?>" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
      <?= csrfField() ?>
      <div style="flex: 1; min-width: 260px;">
        <input type="text" name="estacion[observaciones]" class="form-control" placeholder="Ej: Fuga leve en manguera superior de radiador, aceite motor degradado..." value="<?= htmlspecialchars($estacion['Observaciones'] ?? '') ?>">
      </div>
      <button type="submit" name="action" value="guardar_estacion" class="btn btn-secondary">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Observación
      </button>
    </form>
  <?php else: ?>
    <?php if (!empty($estacion['Observaciones'])): ?>
      <div style="font-size: 0.85rem; color: #cbd5e1; background: rgba(255,255,255,0.02); padding: 0.6rem 0.8rem; border-radius: 6px; border: 1px solid var(--border-dark);">
        <?= htmlspecialchars($estacion['Observaciones']) ?>
      </div>
    <?php else: ?>
      <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0; font-style: italic;">Sin observaciones registradas.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if (in_array($ot['Estado'], ['Diagnosticado', 'Diagnóstico no aplica'], true)): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem;">
    <i class="fa-solid fa-circle-check"></i>
    <?= $ot['Estado'] === 'Diagnóstico no aplica'
        ? 'Diagnóstico marcado como "no aplica" — pedido directo del cliente.'
        : 'Diagnóstico finalizado.' ?>
    Siguiente paso: <a href="presupuesto.php?id=<?= $otId ?>" style="color: #34d399; text-decoration: underline; font-weight: bold;">Ir al Presupuesto de la OT</a>.
  </div>
<?php elseif ($esCerrado): ?>
  <div style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-dark); color: var(--text-muted); padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.85rem;">
    <i class="fa-solid fa-lock"></i> Diagnóstico de solo lectura — esta OT ya avanzó a <a href="presupuesto.php?id=<?= $otId ?>" style="color: var(--text-muted); text-decoration: underline;">Presupuesto</a> o etapas posteriores.
  </div>
<?php endif; ?>

<!-- Hallazgos por Área Técnica -->
<div class="dg-section">
  <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Hallazgos del mecánico</h2>

  <?php foreach (['Mecánica', 'Electricidad', 'Carrocería'] as $area): ?>
    <div class="dg-area-title"><?= $area ?></div>
    <?php if (empty($hallazgosPorArea[$area])): ?>
      <div class="dg-empty">Sin hallazgos registrados en esta área.</div>
    <?php else: ?>
      <?php foreach ($hallazgosPorArea[$area] as $h): ?>
        <div class="dg-hallazgo">
          <div>
            <div><?= htmlspecialchars($h['Hallazgo']) ?></div>
            <div class="meta"><?= htmlspecialchars($h['UsuarioNombre']) ?> — <?= date('d/m/Y H:i', strtotime($h['Fecha'])) ?></div>
          </div>
          <?php if (!$esCerrado): ?>
            <form method="POST" action="diagnostico.php?id=<?= $otId ?>" onsubmit="return confirm('¿Eliminar este hallazgo?');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="eliminar_hallazgo">
              <input type="hidden" name="diagnostico_id" value="<?= $h['DiagnosticoID'] ?>">
              <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Eliminar">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<?php if (!$esCerrado): ?>
  <!-- Formulario para agregar hallazgo -->
  <div class="dg-section">
    <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Agregar Nuevo Hallazgo</h2>
    <form method="POST" action="diagnostico.php?id=<?= $otId ?>" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_hallazgo">
      <div style="min-width: 180px;">
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">ÁREA</label>
        <select name="area" class="form-control">
          <option value="Mecánica">Mecánica</option>
          <option value="Electricidad">Electricidad</option>
          <option value="Carrocería">Carrocería</option>
        </select>
      </div>
      <div style="flex: 1; min-width: 280px;">
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">HALLAZGO TÉCNICO / DIAGNÓSTICO</label>
        <input type="text" name="hallazgo" class="form-control" placeholder="Ej: Fuga de aceite por retén de cigüeñal, pastillas delanteras al 10%..." required>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar Hallazgo</button>
    </form>
  </div>

  <!-- Cierre de Diagnóstico -->
  <div class="dg-section" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 style="margin-bottom: 0.25rem;">Pasar a Presupuesto</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Cuando termines la evaluación técnica, cierra el diagnóstico para valorizar en el Presupuesto.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
      <form method="POST" action="diagnostico.php?id=<?= $otId ?>" style="display: inline;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="omitir">
        <button type="submit" class="btn btn-secondary" title="Si el cliente vino por algo puntual y no requiere diagnóstico técnico">
          Omitir diagnóstico (Pedido puntual)
        </button>
      </form>
      <form method="POST" action="diagnostico.php?id=<?= $otId ?>" style="display: inline;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="finalizar">
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-circle-check"></i> Finalizar Diagnóstico
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>
