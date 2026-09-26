// Presupuesto de OT: buscador único, edición en línea, aprobación parcial y avisos.
(function () {
  const PR = window.PR || {};
  const fmtCLP = n => '$' + Math.round(n).toLocaleString('es-CL');
  const norm = s => (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  // ---------- Scroll: al recargar tras agregar algo, volver al mismo punto ----------
  const SCROLL_KEY = 'pr_scroll_' + PR.otId;
  const guardarScroll = () => { try { sessionStorage.setItem(SCROLL_KEY, String(window.scrollY)); } catch (e) {} };
  document.querySelectorAll('form').forEach(f => f.addEventListener('submit', guardarScroll));
  try {
    const y = sessionStorage.getItem(SCROLL_KEY);
    if (y !== null) {
      sessionStorage.removeItem(SCROLL_KEY);
      window.scrollTo({ top: parseInt(y, 10), behavior: 'instant' });
    }
  } catch (e) {}

  // ---------- Toast ----------
  function toast(texto, error) {
    document.querySelectorAll('.pr-toast').forEach(t => t.remove());
    const el = document.createElement('div');
    el.className = 'pr-toast ' + (error ? 'is-err' : 'is-ok');
    el.innerHTML = '<i class="fa-solid ' + (error ? 'fa-triangle-exclamation' : 'fa-circle-check') + '"></i> ';
    el.appendChild(document.createTextNode(texto));
    document.body.appendChild(el);
    ocultarToast(el, error ? 6000 : 3000);
  }
  function ocultarToast(el, ms) {
    setTimeout(() => { el.classList.add('is-hide'); setTimeout(() => el.remove(), 350); }, ms);
  }
  const toastInicial = document.getElementById('prToast');
  if (toastInicial) ocultarToast(toastInicial, toastInicial.classList.contains('is-err') ? 6000 : 3000);

  // ---------- Modal teléfono ----------
  window.prModal = abierto => {
    const m = document.getElementById('prModalTel');
    m.classList.toggle('is-open', abierto);
    if (abierto) m.querySelector('input[name=telefono]').focus();
  };

  // ---------- Filtro de la lista de repuestos ----------
  // Oculta las opciones que no calzan con lo escrito (nombre, marca, código o N° de parte)
  // y deja elegida la primera que coincide, para agregar con un solo clic.
  const filtro = document.getElementById('prFiltroRepuesto');
  const selRep = document.getElementById('prSelectRepuesto');
  if (filtro && selRep) {
    const opciones = [...selRep.options].slice(1).map(o => ({ o, txt: norm(o.dataset.busca + ' ' + o.text) }));
    filtro.addEventListener('input', () => {
      const terms = norm(filtro.value).split(/\s+/).filter(Boolean);
      let primera = null;
      opciones.forEach(({ o, txt }) => {
        const ok = terms.every(t => txt.includes(t));
        o.hidden = !ok;
        if (ok && !primera) primera = o;
      });
      selRep.value = terms.length && primera ? primera.value : '';
    });
    filtro.addEventListener('keydown', e => {
      if (e.key === 'Enter') { e.preventDefault(); if (selRep.value) { guardarScroll(); selRep.form.submit(); } }
    });
  }

  // ---------- Edición en línea (AJAX) ----------
  async function enviar(datos) {
    const fd = new FormData();
    fd.append('csrf_token', PR.csrf);
    fd.append('ajax', '1');
    Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
    const res = await fetch('presupuesto.php?id=' + PR.otId, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    return res.json();
  }

  function setEstadoGuardado(texto, modo) {
    document.querySelectorAll('.pr-save-indicator, .pr-save-pill').forEach(el => {
      if (modo === 'saving') {
        el.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:#38bdf8"></i> <span>' + texto + '</span>';
      } else if (modo === 'error') {
        el.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#ef4444"></i> <span>' + texto + '</span>';
      } else {
        el.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10b981"></i> <span>' + texto + '</span>';
      }
    });
  }

  function refrescarTotales(data) {
    const cont = document.getElementById('prTotales');
    if (cont && data.totales_html) cont.innerHTML = data.totales_html;
    document.querySelectorAll('.pr-total-val').forEach(el => { el.textContent = data.total_fmt; });
    Object.entries(data.grupos_fmt || {}).forEach(([tipo, val]) => {
      const celda = document.querySelector('[data-grupo="' + tipo + '"]');
      if (celda) celda.textContent = val;
    });
    const count = document.getElementById('prCount');
    if (count) count.textContent = data.count + (data.count === 1 ? ' ítem' : ' ítems');
    if (data.wa_url) {
      document.querySelectorAll('.pr-btn-wa').forEach(btn => { btn.href = data.wa_url; });
    }
  }

  document.querySelectorAll('.pr-input-num').forEach(inp => {
    inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
    inp.addEventListener('change', async () => {
      const row = inp.closest('tr');
      const cant = row.querySelector('[data-campo=cantidad]');
      const precio = row.querySelector('[data-campo=precio]');
      if (!(parseFloat(cant.value) > 0) || !(parseInt(precio.value, 10) > 0)) {
        inp.classList.add('is-err');
        toast('Precio y cantidad deben ser mayores a 0.', true);
        return;
      }
      inp.classList.remove('is-ok', 'is-err');
      setEstadoGuardado('Guardando...', 'saving');
      try {
        const data = await enviar({ action: 'editar_linea', linea_id: inp.dataset.linea, cantidad: cant.value, precio: precio.value });
        if (!data.ok) throw new Error(data.error);
        row.querySelector('.pr-subtotal').textContent = data.subtotal_fmt;
        row.dataset.subtotal = String(Math.round(parseFloat(cant.value) * parseInt(precio.value, 10)));
        refrescarTotales(data);
        actualizarParcial();
        inp.classList.add('is-ok');
        setEstadoGuardado('Guardado', 'saved');
        setTimeout(() => inp.classList.remove('is-ok'), 1200);
      } catch (e) {
        inp.classList.add('is-err');
        setEstadoGuardado('Error al guardar', 'error');
        toast(e.message || 'No se pudo guardar el cambio.', true);
      }
    });
  });

  document.querySelectorAll('[data-borrar]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const row = btn.closest('tr');
      const nombre = row.querySelector('.pr-desc').textContent.trim();
      if (!confirm('¿Quitar "' + nombre + '" del presupuesto?')) return;
      row.style.opacity = '0.3';
      try {
        const data = await enviar({ action: 'eliminar_linea', linea_id: btn.dataset.borrar });
        if (!data.ok) throw new Error(data.error);
        // Quitar ítems cambia la columna "Por cotizar" y los grupos: recargar en el mismo punto.
        guardarScroll();
        window.location.reload();
      } catch (e) {
        row.style.opacity = '';
        toast(e.message || 'No se pudo quitar el ítem.', true);
      }
    });
  });

  // ---------- Aprobación parcial ----------
  const root = document.getElementById('prRoot');
  function actualizarParcial() {
    const out = document.getElementById('prParcialTotal');
    if (!out) return;
    let suma = 0, n = 0;
    document.querySelectorAll('.pr-chk:checked').forEach(c => { suma += parseInt(c.closest('tr').dataset.subtotal, 10) || 0; n++; });
    out.textContent = n + (n === 1 ? ' ítem · ' : ' ítems · ') + fmtCLP(suma);
  }
  document.querySelectorAll('.pr-chk').forEach(c => c.addEventListener('change', actualizarParcial));
  window.prModoParcial = on => {
    root.classList.toggle('pr-modo-parcial', on);
    actualizarParcial();
    if (on) document.querySelector('.pr-table').scrollIntoView({ behavior: 'smooth', block: 'center' });
  };
})();
