let currentTicketId = null;
let allUsers = [];
let adminUsers = [];

// ====================
//  CARGA DE TICKETS
// ====================
async function loadAllTickets() {
    const ui = getElements(['ticketsLoading', 'ticketsTableContainer', 'noTickets', 'ticketsTableBody']);
    showLoading(ui.ticketsLoading, ui.ticketsTableContainer, ui.noTickets);

    try {
        const [tickets] = await Promise.all([listAllTickets(), loadUsersForFilters()]);
        renderTickets(tickets, ui);
    } catch (error) {
        handleError('cargar tickets', error, ui.ticketsLoading, ui.ticketsTableBody, 7);
    }
}

function renderTickets(tickets, ui) {
    ui.ticketsLoading.style.display = 'none';

    if (!tickets || tickets.length === 0) {
        ui.noTickets.style.display = 'block';
        return;
    }

    ui.ticketsTableContainer.style.display = 'block';
    ui.ticketsTableBody.innerHTML = tickets.map(createTicketRow).join('');
    attachTicketEventListeners();
}

// ====================
//     FILTROS
// ====================
async function handleFilter(e) {
    e.preventDefault();
    const ui = getElements(['ticketsLoading', 'ticketsTableContainer', 'noTickets', 'ticketsTableBody']);
    showLoading(ui.ticketsLoading, ui.ticketsTableContainer, ui.noTickets);

    try {
        const filters = getFilterValues(['filterEstado', 'filterGestor', 'filterAdmin'], ['estado', 'gestor_id', 'admin_id']);
        const tickets = Object.keys(filters).length ? await searchTickets(filters) : await listAllTickets();
        renderTickets(tickets, ui);
    } catch (error) {
        handleError('filtrar tickets', error, ui.ticketsLoading);
    }
}

function clearFilters() {
    ['filterEstado', 'filterGestor', 'filterAdmin'].forEach(id =>
        document.getElementById(id).value = ''
    );
    loadAllTickets();
}

async function loadUsersForFilters() {
    try {
        const users = await listUsers();
        allUsers = users || [];
        adminUsers = allUsers.filter(u => u.role === 'admin');

        populateSelect('filterGestor', allUsers.filter(u => u.role === 'gestor'));
        populateSelect('filterAdmin', adminUsers);
        populateSelect('assignAdmin', adminUsers, 'Sin asignar');
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
}

// ====================
//  MODAL DETALLE
// ====================
async function openTicketModal(ticketId) {
    currentTicketId = ticketId;
    const modal = document.getElementById('ticketModal');

    updateModalUI({ detail: '<div class="loading-spinner"></div>', history: '' });
    modal.classList.add('active');

    try {
        const [ticket, history] = await Promise.all([getTicketDetails(ticketId), getTicketHistory(ticketId)]);
        updateModalData(ticket, history);
    } catch (error) {
        updateModalUI({ detail: `<div class="alert alert-error">Error: ${error.message}</div>` });
    }
}

function updateModalUI({ detail = null, history = null }) {
    if (detail !== null) document.getElementById('ticketDetails').innerHTML = detail;
    if (history !== null) document.getElementById('ticketHistory').innerHTML = history;
}

function updateModalData(ticket, history) {
    document.getElementById('ticketDetails').innerHTML = createTicketDetails(ticket);
    document.getElementById('ticketEstado').value = ticket.estado;
    document.getElementById('assignAdmin').value = ticket.admin_id || '';
    document.getElementById('ticketHistory').innerHTML = history.map(createHistoryItem).join('');
}

function closeTicketModal() {
    document.getElementById('ticketModal').classList.remove('active');
    currentTicketId = null;
    document.getElementById('addCommentForm').reset();
}

// ====================
//  ACCIONES TICKET
// ====================
async function handleUpdateStatus() {
    if (!currentTicketId) return;
    await processTicketAction(
        () => updateTicketStatus(currentTicketId, document.getElementById('ticketEstado').value),
        'Estado actualizado correctamente'
    );
}

async function handleAssignTicket() {
    if (!currentTicketId) return;

    const adminId = document.getElementById('assignAdmin').value;
    if (!adminId) return alert('Selecciona un administrador');

    await processTicketAction(
        () => assignTicket(currentTicketId, adminId),
        'Ticket asignado correctamente'
    );
}

async function handleAddComment(e) {
    e.preventDefault();
    if (!currentTicketId) return;

    const comentario = document.getElementById('comentario').value.trim();
    if (!comentario) return alert('Escribe un comentario');

    try {
        await addComment(currentTicketId, comentario);
        e.target.reset();
        reloadHistory();
    } catch (error) {
        alert('Error al agregar comentario: ' + error.message);
    }
}

// ====================
//  FUNCIONES REUTILIZABLES
// ====================
async function processTicketAction(actionFn, successMsg) {
    try {
        await actionFn();
        alert(successMsg);
        await reloadTicketModal();
        loadAllTickets();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function reloadHistory() {
    const history = await getTicketHistory(currentTicketId);
    document.getElementById('ticketHistory').innerHTML = history.map(createHistoryItem).join('');
}

async function reloadTicketModal() {
    const [ticket, history] = await Promise.all([
        getTicketDetails(currentTicketId),
        getTicketHistory(currentTicketId)
    ]);
    updateModalData(ticket, history);
}
