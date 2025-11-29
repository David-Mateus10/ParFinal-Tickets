document.addEventListener('DOMContentLoaded', () => {
    if (!checkAuth() || !checkRole('admin')) return;
    init();
});

function init() {
    const userName = getUserName();
    if (userName) document.getElementById('userName').textContent = userName;
    setupEventListeners();
    loadAllTickets();
    loadUsers();
}

function setupEventListeners() {

    // TABS
    document.querySelectorAll('.tab-btn').forEach(btn =>
        btn.addEventListener('click', () => switchTab(btn.dataset.tab))
    );

    // ACCIONES GENERALES
    document.getElementById('logoutBtn').addEventListener('click', logout);
    document.getElementById('filterForm').addEventListener('submit', handleFilter);
    document.getElementById('clearFilters').addEventListener('click', clearFilters);

    // MODALES
    ['ticketModal', 'userModal'].forEach(id => {
        const modal = document.getElementById(id);
        const closeBtn = document.getElementById(id === 'ticketModal' ? 'closeTicketModal' : 'closeUserModal');
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', e => e.target === modal && modal.classList.remove('active'));
    });

    // TICKETS
    document.getElementById('updateStatusBtn').addEventListener('click', handleUpdateStatus);
    document.getElementById('assignTicketBtn').addEventListener('click', handleAssignTicket);
    document.getElementById('addCommentForm').addEventListener('submit', handleAddComment);

    // USUARIOS
    document.getElementById('editUserForm').addEventListener('submit', handleEditUser);
    document.getElementById('cancelEditUser').addEventListener('click', closeUserModal);
    document.getElementById('createUserForm')?.addEventListener('submit', handleCreateUser);
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn =>
        btn.classList.toggle('active', btn.dataset.tab === tabName)
    );
    document.querySelectorAll('.tab-content').forEach(content =>
        content.classList.remove('active')
    );
    document.getElementById(`tab-${tabName}`).classList.add('active');

    if (tabName === 'tickets') loadAllTickets();
    else if (tabName === 'usuarios') loadUsers();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
