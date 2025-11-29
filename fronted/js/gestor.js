let selectedTicketId = null;

// Inicialización al cargar
document.addEventListener('DOMContentLoaded', () => {
    if (!checkAuth()) return;
    if (!checkRole('gestor')) return;
    initGestorPanel();
});

function initGestorPanel() {
    const userName = getUserName();
    if (userName) document.getElementById('userName').textContent = userName;
    
    bindUIEvents();
    loadTicketsFromGestor();
}

function bindUIEvents() {
    document.querySelectorAll('.nav-item').forEach(item => 
        item.addEventListener('click', () => showSection(item.dataset.section))
    );

    document.getElementById('logoutBtn').addEventListener('click', logout);
    document.getElementById('createTicketForm').addEventListener('submit', onCreateTicket);
    document.getElementById('addCommentForm').addEventListener('submit', onAddComment);
    
    const modal = document.getElementById('ticketModal');
    document.getElementById('closeModal').addEventListener('click', closeTicketView);
    modal.addEventListener('click', e => e.target === modal && closeTicketView());
}

function showSection(name) {
    document.querySelectorAll('.nav-item').forEach(item => 
        item.classList.toggle('active', item.dataset.section === name)
    );

    document.querySelectorAll('.content-section').forEach(section => 
        section.classList.remove('active')
    );

    document.getElementById(`section-${name}`).classList.add('active');

    if (name === 'mis-tickets') loadTicketsFromGestor();
}

async function onCreateTicket(e) {
    e.preventDefault();

    const titulo = document.getElementById('titulo').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    const btn = document.getElementById('createBtn');
    const msg = document.getElementById('createMessage');

    if (!titulo || !descripcion)
        return showMessage(msg, 'Debes rellenar todos los campos', 'error');

    setButtonLoading(btn, true);

    try {
        await createTicket({ titulo, descripcion });
        showMessage(msg, 'Ticket creado correctamente', 'success');
        document.getElementById('createTicketForm').reset();
        setTimeout(() => showSection('mis-tickets'), 1200);
    } catch (err) {
        console.error(err);
        showMessage(msg, err.message || 'Error creando ticket', 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}

async function loadTicketsFromGestor() {
    const UI = getElements(['ticketsLoading', 'ticketsContainer', 'noTickets']);

    UI.ticketsLoading.style.display = 'flex';
    UI.ticketsContainer.innerHTML = '';
    UI.noTickets.style.display = 'none';

    try {
        const tickets = await listMyTickets();
        UI.ticketsLoading.style.display = 'none';

        if (!tickets?.length) {
            UI.noTickets.style.display = 'block';
            return;
        }

        UI.ticketsContainer.innerHTML = tickets.map(generateTicketCard).join('');
        attachCardListeners();

    } catch (err) {
        UI.ticketsLoading.style.display = 'none';
        UI.ticketsContainer.innerHTML = `
            <div class="alert alert-error">
                Error cargando tickets: ${err.message}
            </div>
        `;
    }
}

function generateTicketCard(ticket) {
    return `
        <div class="ticket-card estado-${ticket.estado}" data-ticket-id="${ticket.id}">
            <div class="ticket-header">
                <span>#${ticket.id}</span>
                ${getEstadoBadge(ticket.estado)}
            </div>
            <h3>${escapeHtml(ticket.titulo)}</h3>
            <p>${escapeHtml(ticket.descripcion)}</p>
            <div class="ticket-footer">
                <span>📅 ${formatDate(ticket.created_at)}</span>
            </div>
        </div>
    `;
}

function attachCardListeners() {
    document.querySelectorAll('.ticket-card').forEach(card => 
        card.addEventListener('click', () => viewTicket(card.dataset.ticketId))
    );
}

async function viewTicket(ticketId) {
    selectedTicketId = ticketId;
    
    const modal = document.getElementById('ticketModal');
    const details = document.getElementById('ticketDetails');
    const history = document.getElementById('ticketHistory');

    modal.classList.add('active');
    details.innerHTML = '<div class="loading-spinner"></div>';

    try {
        const [ticket, events] = await Promise.all([
            getTicketDetails(ticketId),
            getTicketHistory(ticketId)
        ]);

        details.innerHTML = ticketDetailsView(ticket);
        history.innerHTML = events.map(historyView).join('');

    } catch (err) {
        details.innerHTML = `<div class="alert alert-error">Error: ${err.message}</div>`;
    }
}

function ticketDetailsView(ticket) {
    return `
        <div class="ticket-detail-header">
            <h3>${escapeHtml(ticket.titulo)}</h3>
            ${getEstadoBadge(ticket.estado)}
        </div>
        <div class="ticket-meta">
            ${createMetaItem('ID', ticket.id)}
            ${createMetaItem('Creado', formatDate(ticket.created_at))}
            ${createMetaItem('Estado', formatEstado(ticket.estado))}
        </div>
        <div class="ticket-detail-description">
            ${escapeHtml(ticket.descripcion)}
        </div>
    `;
}

function historyView(item) {
    return `
        <div class="history-item">
            <div class="history-header">
                <span>👤 Usuario #${item.user_id}</span>
                <span>${formatDate(item.created_at)}</span>
            </div>
            <div>${escapeHtml(item.mensaje)}</div>
        </div>
    `;
}

async function onAddComment(e) {
    e.preventDefault();
    if (!selectedTicketId) return;

    const comentario = document.getElementById('comentario').value.trim();
    if (!comentario) return;

    try {
        await addComment(selectedTicketId, comentario);
        e.target.reset();

        const hist = await getTicketHistory(selectedTicketId);
        document.getElementById('ticketHistory').innerHTML = hist.map(historyView).join('');

    } catch (err) {
        alert('Error comentando: ' + err.message);
    }
}

function closeTicketView() {
    document.getElementById('ticketModal').classList.remove('active');
    selectedTicketId = null;
    document.getElementById('addCommentForm').reset();
}
