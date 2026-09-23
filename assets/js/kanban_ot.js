/**
 * Tablero Kanban de Órdenes de Trabajo - Taller Mecánico
 * Manejo de Drag & Drop, actualización AJAX en tiempo real, filtros y cambio de vista.
 */

let draggedOtId = null;

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    inicializarModoVista();
    inicializarDragAndDrop();
    inicializarFiltros();
    actualizarContadoresKanban();
});

/**
 * Control del modo de vista (Kanban vs Lista)
 */
function inicializarModoVista() {
    const urlParams = new URLSearchParams(window.location.search);
    const vistaParam = urlParams.get('vista');
    const vistaGuardada = localStorage.getItem('taller_vista_ot') || 'kanban';
    const modoFinal = vistaParam || vistaGuardada;

    cambiarModoVista(modoFinal, false);
}

function cambiarModoVista(modo, guardar = true) {
    const contenedorKanban = document.getElementById('vistaKanbanContainer');
    const contenedorLista = document.getElementById('vistaListaContainer');
    const btnKanban = document.getElementById('btnModoKanban');
    const btnLista = document.getElementById('btnModoLista');

    if (!contenedorKanban || !contenedorLista) return;

    if (modo === 'lista') {
        contenedorKanban.style.display = 'none';
        contenedorLista.style.display = 'block';
        if (btnLista) {
            btnLista.classList.add('active', 'btn-primary');
            btnLista.classList.remove('btn-outline-secondary');
        }
        if (btnKanban) {
            btnKanban.classList.remove('active', 'btn-primary');
            btnKanban.classList.add('btn-outline-secondary');
        }
    } else {
        contenedorLista.style.display = 'none';
        contenedorKanban.style.display = 'block';
        if (btnKanban) {
            btnKanban.classList.add('active', 'btn-primary');
            btnKanban.classList.remove('btn-outline-secondary');
        }
        if (btnLista) {
            btnLista.classList.remove('active', 'btn-primary');
            btnLista.classList.add('btn-outline-secondary');
        }
    }

    if (guardar) {
        localStorage.setItem('taller_vista_ot', modo);
    }
}

/**
 * Configuración de Drag & Drop HTML5
 */
function inicializarDragAndDrop() {
    const cards = document.querySelectorAll('.kanban-card');
    const dropzones = document.querySelectorAll('.kanban-col-cards');

    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    dropzones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('dragleave', handleDragLeave);
        zone.addEventListener('drop', handleDrop);
    });
}

function handleDragStart(e) {
    draggedOtId = this.getAttribute('data-ot-id');
    this.classList.add('dragging');
    e.dataTransfer.setData('text/plain', draggedOtId);
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd() {
    this.classList.remove('dragging');
    document.querySelectorAll('.kanban-col-cards').forEach(zone => {
        zone.classList.remove('drag-over');
    });
    draggedOtId = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    // Evitar parpadeo cuando entra a un hijo de la zona
    if (!this.contains(e.relatedTarget)) {
        this.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    const otId = e.dataTransfer.getData('text/plain') || draggedOtId;
    const columnaDestino = this.getAttribute('data-col');

    if (!otId || !columnaDestino) return;

    const card = document.getElementById('kanban-card-' + otId);
    if (!card) return;

    const columnaOrigen = card.getAttribute('data-col');
    if (columnaOrigen === columnaDestino) return; // Mismo lugar, sin cambios

    moverColumnaKanban(otId, columnaDestino);
}

/**
 * Mover una orden de trabajo a una nueva columna vía AJAX
 */
async function moverColumnaKanban(otId, columnaDestino) {
    const card = document.getElementById('kanban-card-' + otId);
    if (!card) return;

    const columnaOrigen = card.getAttribute('data-col');
    const targetZone = document.querySelector(`.kanban-col-cards[data-col="${columnaDestino}"]`);
    if (!targetZone) return;

    // Indicador visual de guardando
    card.classList.add('saving');

    try {
        const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('api/kanban_ot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                action: 'mover_columna',
                ot_id: parseInt(otId, 10),
                columna: columnaDestino
            })
        });

        const data = await response.json();
        card.classList.remove('saving');

        if (!data.success) {
            mostrarToast('Error: ' + (data.error || 'No se pudo mover la orden'), 'error');
            return;
        }

        // Mover nodo en el DOM
        targetZone.prepend(card);
        card.setAttribute('data-col', columnaDestino);

        // Actualizar badges de columna
        actualizarContadoresKanban();

        // Notificación de éxito
        mostrarToast(`✓ OT-${String(otId).padStart(6, '0')}: ${data.mensaje}`, 'success');

        // Si se movió a 'listo', resaltar opción de WhatsApp
        if (columnaDestino === 'listo') {
            const btnWA = card.querySelector('.btn-wa-auto');
            if (btnWA) {
                btnWA.classList.add('pulse-highlight');
                setTimeout(() => btnWA.classList.remove('pulse-highlight'), 4000);
            }
        }

    } catch (err) {
        card.classList.remove('saving');
        mostrarToast('Error de conexión al mover la orden', 'error');
    }
}

/**
 * Asignar o cambiar mecánico directamente desde la tarjeta
 */
async function asignarMecanicoKanban(otId, mecanicoId) {
    const card = document.getElementById('kanban-card-' + otId);
    const selectElem = card ? card.querySelector('.select-mecanico-inline') : null;

    try {
        const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('api/kanban_ot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                action: 'asignar_mecanico',
                ot_id: parseInt(otId, 10),
                mecanico_id: parseInt(mecanicoId, 10) || 0
            })
        });

        const data = await response.json();
        if (!data.success) {
            mostrarToast('Error al asignar mecánico: ' + (data.error || ''), 'error');
            return;
        }

        if (card) {
            card.setAttribute('data-mecanico-id', data.mecanico_id || '');
        }

        mostrarToast(`✓ OT-${String(otId).padStart(6, '0')}: ${data.mensaje}`, 'success');

    } catch (err) {
        mostrarToast('Error de conexión al asignar mecánico', 'error');
    }
}

/**
 * Filtros rápidos en tiempo real (Búsqueda y Mecánico)
 */
function inicializarFiltros() {
    const searchInput = document.getElementById('kanbanSearchLive');
    const selectMecanico = document.getElementById('kanbanFiltroMecanico');

    if (searchInput) {
        searchInput.addEventListener('input', aplicarFiltrosKanban);
    }
    if (selectMecanico) {
        selectMecanico.addEventListener('change', aplicarFiltrosKanban);
    }
}

function aplicarFiltrosKanban() {
    const query = (document.getElementById('kanbanSearchLive')?.value || '').toLowerCase().trim();
    const mecanicoId = document.getElementById('kanbanFiltroMecanico')?.value || '';

    const cards = document.querySelectorAll('.kanban-card');

    cards.forEach(card => {
        const patente = (card.getAttribute('data-patente') || '').toLowerCase();
        const cliente = (card.getAttribute('data-cliente') || '').toLowerCase();
        const folio = (card.getAttribute('data-folio') || '').toLowerCase();
        const vehiculo = (card.getAttribute('data-vehiculo') || '').toLowerCase();
        const cardMecId = card.getAttribute('data-mecanico-id') || '';

        // Coincidencia de texto
        const matchText = !query || patente.includes(query) || cliente.includes(query) || folio.includes(query) || vehiculo.includes(query);

        // Coincidencia de mecánico
        let matchMec = true;
        if (mecanicoId === 'sin_asignar') {
            matchMec = !cardMecId || cardMecId === '0';
        } else if (mecanicoId !== '') {
            matchMec = cardMecId === mecanicoId;
        }

        if (matchText && matchMec) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });

    actualizarContadoresKanban();
}

/**
 * Actualizar los números de vehículos en cada columna
 */
function actualizarContadoresKanban() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const colCards = col.querySelectorAll('.kanban-card');
        let visibles = 0;
        colCards.forEach(c => {
            if (c.style.display !== 'none') visibles++;
        });

        const badge = col.querySelector('.kanban-col-count');
        if (badge) {
            badge.textContent = visibles;
        }

        // Mostrar u ocultar mensaje vacío
        const emptyMsg = col.querySelector('.kanban-col-empty');
        if (emptyMsg) {
            emptyMsg.style.display = visibles === 0 ? 'block' : 'none';
        }
    });
}

/**
 * Sistema de Toasts flotantes
 */
function mostrarToast(mensaje, tipo = 'info') {
    let toastContainer = document.getElementById('tallerToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'tallerToastContainer';
        toastContainer.className = 'kanban-toast-container';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `kanban-toast ${tipo}`;
    toast.innerHTML = `
        <div class="toast-content">${mensaje}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}
