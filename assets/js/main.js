// Script Global - Minimarket POS Web PHP
console.log('Minimarket POS Web (PHP + Laragon) inicializado correctamente.');

// Abre el catálogo de Mann-Filter en otra pestaña con el VIN ya copiado.
// Mann-Filter no permite pasar el VIN por la dirección, así que se pega con Ctrl+V.
async function buscarEnMannFilterConVin(vin) {
  vin = (vin || '').trim().toUpperCase();
  if (!vin) return;
  let copiado = false;
  try { await navigator.clipboard.writeText(vin); copiado = true; } catch (e) {}
  window.open('https://www.mann-filter.com/es/catalogo.html', '_blank', 'noopener');

  const aviso = document.createElement('div');
  aviso.style.cssText = 'position:fixed; right:1.25rem; bottom:1.25rem; z-index:3000; max-width:340px; background:#0f172a; border:1px solid #16a34a; color:#e2e8f0; padding:0.85rem 1rem; border-radius:10px; font-size:0.88rem; box-shadow:0 10px 30px rgba(0,0,0,0.5);';
  aviso.innerHTML = copiado
    ? '<strong style="color:#4ade80;">VIN copiado:</strong> <code>' + vin + '</code><br>En Mann-Filter elige <strong>Buscar: VIN</strong>, haz clic en la casilla y pega con <strong>Ctrl+V</strong>.'
    : 'No se pudo copiar automáticamente. Copia este VIN: <code>' + vin + '</code>';
  document.body.appendChild(aviso);
  setTimeout(() => aviso.remove(), 9000);
}
