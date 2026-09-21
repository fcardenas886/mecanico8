<style>
  .sv-card { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1.25rem; }
  .sv-table { width: 100%; border-collapse: collapse; }
  .sv-table th { text-align: left; font-size: .75rem; color: var(--text-muted); padding: .4rem .5rem; }
  .sv-table td { padding: .4rem .5rem; border-top: 1px solid var(--border-dark); vertical-align: middle; }
  .sv-table input, .sv-table select { width: 100%; }
  .sv-off { opacity: .5; }
</style>
<div style="max-width: 1000px; margin: 0 auto;">
  <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: .25rem;"><i class="fa-solid fa-tags"></i> Servicios y precios</h1>
  <p style="color: var(--text-muted); margin-bottom: 1.25rem; font-size: .9rem;">
    Lista de la mano de obra del taller con su precio ya definido. Al armar un presupuesto se elige de aquí y el precio se completa solo.
    Los precios cargados son de ejemplo: ajústalos a los del taller.
  </p>

  <?php if ($error): ?><div class="badge badge-danger" style="display:block; padding:.7rem; margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($mensaje): ?><div class="badge badge-success" style="display:block; padding:.7rem; margin-bottom:1rem;"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

  <div class="sv-card" style="border-color: rgba(56,189,248,.35);">
    <strong><i class="fa-solid fa-circle-info"></i> Cómo funciona el diagnóstico</strong>
    <p style="font-size:.85rem; color: var(--text-muted); margin-top:.35rem;">
      El <em>Diagnóstico general</em> se agrega solo a cada presupuesto. Si el cliente aprueba la reparación <strong>no se cobra</strong>;
      si no aprueba, <strong>se cobra</strong> como servicio. En cualquier presupuesto se puede cambiar a "cobrar siempre" para un caso puntual.
    </p>
  </div>

  <div class="sv-card">
    <div style="overflow-x:auto;">
    <table class="sv-table">
      <thead><tr><th>Servicio</th><th style="width:150px;">Grupo</th><th style="width:120px;">Precio ($)</th><th style="width:250px;">Cuándo se cobra</th><th style="width:150px;"></th></tr></thead>
      <tbody>
      <?php foreach ($servicios as $s): ?>
        <tr class="<?= $s['Activo'] ? '' : 'sv-off' ?>">
          <td colspan="5" style="padding:0; border:0;">
          <form method="POST" action="servicios.php" style="display:flex; gap:0; align-items:center;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="guardar">
            <input type="hidden" name="id" value="<?= $s['OperacionID'] ?>">
            <div style="flex:1; padding:.4rem .5rem; border-top:1px solid var(--border-dark);"><input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($s['Nombre']) ?>" required></div>
            <div style="width:150px; padding:.4rem .5rem; border-top:1px solid var(--border-dark);"><input type="text" name="categoria" class="form-control" value="<?= htmlspecialchars($s['Categoria']) ?>"></div>
            <div style="width:120px; padding:.4rem .5rem; border-top:1px solid var(--border-dark);"><input type="number" name="precio" class="form-control" value="<?= (int)$s['PrecioBase'] ?>" min="1"></div>
            <div style="width:250px; padding:.4rem .5rem; border-top:1px solid var(--border-dark);">
              <select name="politica" class="form-control">
                <?php foreach ($politicas as $k => $t): ?><option value="<?= $k ?>" <?= $s['PoliticaCobro'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div style="width:150px; padding:.4rem .5rem; border-top:1px solid var(--border-dark); white-space:nowrap;">
              <button type="submit" class="btn btn-primary" style="padding:.3rem .6rem; font-size:.8rem;">Guardar</button>
              <?php if (!$s['EsDiagnosticoBase']): ?>
                <button type="submit" name="action" value="activar" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.8rem;"><?= $s['Activo'] ? 'Ocultar' : 'Mostrar' ?></button>
              <?php endif; ?>
            </div>
          </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <div class="sv-card">
    <h2 style="font-size:1rem; font-weight:700; margin-bottom:.75rem;">Agregar un servicio nuevo</h2>
    <form method="POST" action="servicios.php" style="display:flex; gap:.6rem; flex-wrap:wrap; align-items:flex-end;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="crear">
      <input type="hidden" name="politica" value="Siempre">
      <div style="flex:1; min-width:200px;"><label>NOMBRE</label><input type="text" name="nombre" class="form-control" placeholder="Ej: Cambio de correa de distribución" required></div>
      <div style="width:150px;"><label>GRUPO</label><input type="text" name="categoria" class="form-control" placeholder="Ej: Motor"></div>
      <div style="width:120px;"><label>PRECIO ($)</label><input type="number" name="precio" class="form-control" min="1" required></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
    </form>
  </div>
</div>
