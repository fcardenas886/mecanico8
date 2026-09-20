/**
 * Pegar datos de vehículo: interpreta el texto copiado desde una consulta de patente
 * (ej. la vista previa de autoriesgo.cl) y devuelve los campos del vehículo.
 * No consulta ningún servicio externo: solo procesa el texto que el usuario pega.
 */
(function () {
  const ETIQUETAS = {
    patente: ['patente', 'placa patente', 'placa', 'ppu'],
    marca: ['marca', 'make', 'fabricante'],
    modelo: ['modelo', 'model', 'version', 'versión'],
    anio: ['año', 'ano', 'anio', 'year', 'año de fabricación', 'año fabricación', 'año del vehículo'],
    color: ['color'],
    combustible: ['combustible', 'tipo de combustible', 'fuel'],
    motor: ['motor', 'n° motor', 'nº motor', 'numero de motor', 'número de motor', 'n motor', 'cilindrada'],
    transmision: ['transmisión', 'transmision', 'caja'],
    tipo_vehiculo: ['tipo de vehículo', 'tipo vehiculo', 'tipo de vehiculo', 'tipo', 'carrocería', 'carroceria'],
    vin: ['vin', 'chasis', 'n° chasis', 'nº chasis', 'numero de chasis', 'número de chasis', 'n chasis']
  };

  const sinTildes = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

  function campoDeEtiqueta(etiqueta) {
    const e = sinTildes(etiqueta).replace(/[:*]+$/, '').trim();
    for (const campo in ETIQUETAS) {
      if (ETIQUETAS[campo].some((a) => sinTildes(a) === e)) return campo;
    }
    return null;
  }

  function parsearTextoVehiculo(texto) {
    const out = {};
    const lineas = String(texto || '').split(/\r?\n/).map((l) => l.trim()).filter(Boolean);

    for (let i = 0; i < lineas.length; i++) {
      const l = lineas[i];
      // "Etiqueta: valor"  o  "Etiqueta - valor"
      let m = l.match(/^([^:\-–]{2,32})\s*[:\-–]\s*(.+)$/);
      if (m) {
        const campo = campoDeEtiqueta(m[1]);
        if (campo && !out[campo]) { out[campo] = m[2].trim(); continue; }
      }
      // "Etiqueta" en una línea y el valor en la siguiente
      const campo = campoDeEtiqueta(l);
      if (campo && !out[campo] && lineas[i + 1] && !campoDeEtiqueta(lineas[i + 1])) {
        out[campo] = lineas[i + 1];
        i++;
      }
    }

    // Respaldos cuando no hay etiquetas
    const anioSuelto = String(texto).match(/\b(19[89]\d|20[0-3]\d)\b/);
    if (!out.anio && anioSuelto) out.anio = anioSuelto[1];
    const vinSuelto = String(texto).toUpperCase().match(/\b[A-HJ-NPR-Z0-9]{17}\b/);
    if (!out.vin && vinSuelto) out.vin = vinSuelto[0];

    if (out.anio) out.anio = (String(out.anio).match(/\d{4}/) || [''])[0];
    if (out.vin) out.vin = out.vin.toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (out.patente) out.patente = out.patente.toUpperCase().replace(/[^A-Z0-9]/g, '');
    ['marca', 'modelo', 'color'].forEach((c) => { if (out[c]) out[c] = out[c].toUpperCase(); });
    return out;
  }

  /**
   * Rellena inputs con id `${prefijo}_${campo}`. Devuelve los nombres de campos completados.
   * mapaIds permite cambiar el sufijo de un campo (ej. tipo_vehiculo -> tipo).
   */
  function rellenarVehiculo(prefijo, datos, mapaIds) {
    const completados = [];
    Object.keys(datos).forEach((campo) => {
      if (campo === 'patente') return; // la patente ya la escribió el usuario
      const sufijo = (mapaIds && mapaIds[campo]) || campo;
      const el = document.getElementById(prefijo + '_' + sufijo);
      if (!el || !datos[campo]) return;
      el.value = datos[campo];
      // Si es un <select> y el valor no existe, no dejarlo vacío
      if (el.tagName === 'SELECT' && el.value !== datos[campo]) {
        const opt = Array.from(el.options).find((o) => sinTildes(o.value) === sinTildes(datos[campo]));
        if (opt) el.value = opt.value; else return;
      }
      el.dispatchEvent(new Event('input', { bubbles: true }));
      completados.push(campo);
    });
    return completados;
  }

  function abrirAutoRiesgo(patente) {
    const p = String(patente || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (!p) return false;
    window.open('https://autoriesgo.cl/?patente=' + encodeURIComponent(p), '_blank', 'noopener');
    return true;
  }

  window.PegarVehiculo = { parsearTextoVehiculo, rellenarVehiculo, abrirAutoRiesgo };
})();
