const CONFIG = {
  apiBase: "http://localhost:8000",
  endpoints: {
    tickets: "/tickets",
    ticketsAssign: (id) => `/tickets/${id}/assign`,
    ticketsStatus: (id) => `/tickets/${id}/status`,
    ticketsHistory: (id) => `/tickets/${id}/history`,
    users: "/usuarios",
    userCreate: "/usuarios/crear",
    userUpdate: (id) => `/usuarios/${id}`,
    userRole: (id) => `/usuarios/${id}/rol`,
    userDelete: (id) => `/usuarios/${id}`,
  },
  pageSize: 10,
};

const STATE = {
  token: null,
  // Tickets
  tickets: [],
  ticketsPage: 1,
  ticketsTotal: 0,
  ticketsFilters: { estado: "", creador: "", asignado: "", q: "" },
  ticketsSort: { field: "fecha", dir: "desc" },
  // Users
  users: [],
  usersPage: 1,
  usersTotal: 0,
  usersSort: { field: "created_at", dir: "desc" },
  usersSearch: "",
  // UI
  loading: { tickets: false, users: false },
  modals: { ticket: false, user: false },
  currentTicket: null,
  currentUser: null,
};

/* =========================================================================
   Utilidades
   ========================================================================= */
const qs = (sel) => document.querySelector(sel);
const qsa = (sel) => Array.from(document.querySelectorAll(sel));
const on = (el, evt, fn) => el && el.addEventListener(evt, fn);

function debounce(fn, ms = 300) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

function showAlert(id, text, type = "info", timeout = 4000) {
  const el = document.getElementById(id);
  if (!el) return;
  el.style.display = "block";
  el.className = `mensaje-alerta ${type}`;
  el.textContent = text;
  if (timeout) setTimeout(() => (el.style.display = "none"), timeout);
}

function exportCSV(filename, rows, columns, formatters = {}) {
  const header = columns.join(",");
  const out = [header];
  rows.forEach((r) => {
    const line = columns
      .map((c) => {
        const val = formatters[c] ? formatters[c](r[c], r) : r[c];
        const safe = (val ?? "").toString().replace(/"/g, '""');
        return `"${safe}"`;
      })
      .join(",");
    out.push(line);
  });
  const blob = new Blob([out.join("\n")], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

function formatDate(value) {
  if (!value) return "";
  const d = new Date(value);
  return d.toLocaleString();
}

function guardToken() {
  STATE.token = localStorage.getItem("token");
  if (!STATE.token) {
    showAlert("mensajeUsuario", "Sesión inválida. Inicia sesión nuevamente.", "error", 6000);
  }
}

/* =========================================================================
   Capa de API
   ========================================================================= */
async function api(path, options = {}) {
  const headers = {
    "Content-Type": "application/json",
    ...(STATE.token ? { Authorization: `Bearer ${STATE.token}` } : {}),
    ...(options.headers || {}),
  };
  const res = await fetch(`${CONFIG.apiBase}${path}`, {
    ...options,
    headers,
  });

  let body = null;
  try {
    body = await res.json();
  } catch (_) {
    body = null;
  }

  if (!res.ok) {
    const message = body?.error || `Error ${res.status}`;
    const err = new Error(message);
    err.status = res.status;
    err.body = body;
    throw err;
  }
  return body;
}

/* =========================================================================
   Navegación
   ========================================================================= */
function setupNavigation() {
  qsa(".nav-opcion").forEach((btn) => {
    on(btn, "click", () => {
      qsa(".nav-opcion").forEach((b) => b.classList.remove("activa"));
      qsa(".admin-seccion").forEach((s) => s.classList.remove("visible"));
      btn.classList.add("activa");
      const target = btn.dataset.seccion;
      const secEl = document.getElementById(`seccion-${target}`);
      if (secEl) secEl.classList.add("visible");
      // Cargas iniciales por sección
      if (target === "tickets") loadTickets();
      if (target === "usuarios") loadUsers();
    });
  });
}

/* =========================================================================
   Tickets: carga, filtros, tabla
   ========================================================================= */
function renderTicketsTable() {
  const tbody = document.getElementById("cuerpoTickets");
  const { tickets } = STATE;
  tbody.innerHTML = "";
  tickets.forEach((t) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${t.id}</td>
      <td>${t.titulo}</td>
      <td>${t.estado}</td>
      <td>${t.creador}</td>
      <td>${t.asignado || "-"}</td>
      <td>${formatDate(t.fecha)}</td>
      <td class="acciones">
        <button class="boton-secundario" data-view="${t.id}">Ver</button>
        <button class="boton-principal" data-assign="${t.id}">Asignar</button>
        <button class="boton-secundario" data-status="${t.id}">Cambiar estado</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  // Bind actions
  qsa("[data-view]").forEach((b) =>
    on(b, "click", () => openTicketModal(parseInt(b.dataset.view, 10)))
  );
  qsa("[data-assign]").forEach((b) =>
    on(b, "click", () => openAssignModal(parseInt(b.dataset.assign, 10)))
  );
  qsa("[data-status]").forEach((b) =>
    on(b, "click", () => openStatusModal(parseInt(b.dataset.status, 10)))
  );
}

function renderTicketsPagination() {
  const container = document.getElementById("ticketsPagination");
  if (!container) return;
  const { ticketsTotal, ticketsPage } = STATE;
  const pages = Math.ceil(ticketsTotal / CONFIG.pageSize) || 1;
  container.innerHTML = "";
  for (let p = 1; p <= pages; p++) {
    const btn = document.createElement("button");
    btn.className = `paginador ${p === ticketsPage ? "activo" : ""}`;
    btn.textContent = p;
    on(btn, "click", () => {
      STATE.ticketsPage = p;
      loadTickets();
    });
    container.appendChild(btn);
  }
}

async function loadTickets() {
  guardToken();
  const loadingEl = document.getElementById("cargandoTickets");
  const tableEl = document.getElementById("tablaTickets");
  const emptyEl = document.getElementById("sinTickets");
  loadingEl.style.display = "block";
  tableEl.style.display = "none";
  emptyEl.style.display = "none";

  const { ticketsPage, ticketsSort, ticketsFilters } = STATE;
  const params = new URLSearchParams({
    page: ticketsPage,
    per_page: CONFIG.pageSize,
    sort: ticketsSort.field,
    dir: ticketsSort.dir,
    estado: ticketsFilters.estado || "",
    creador: ticketsFilters.creador || "",
    asignado: ticketsFilters.asignado || "",
    q: ticketsFilters.q || "",
  }).toString();

  try {
    const data = await api(`${CONFIG.endpoints.tickets}?${params}`);
    STATE.tickets = data.items || data; // soporta {items,total} o array plano
    STATE.ticketsTotal = data.total ?? STATE.tickets.length;

    if (!STATE.tickets.length) {
      emptyEl.style.display = "block";
    } else {
      renderTicketsTable();
      tableEl.style.display = "block";
    }
    renderTicketsPagination();
  } catch (err) {
    showAlert("mensajeUsuario", err.message || "Error al cargar tickets", "error");
  } finally {
    loadingEl.style.display = "none";
  }
}

function setupTicketFilters() {
  const form = document.getElementById("formFiltroTickets");
  on(form, "submit", (e) => {
    e.preventDefault();
    STATE.ticketsFilters.estado = document.getElementById("estadoTicket").value;
    STATE.ticketsFilters.creador = document.getElementById("creadorTicket").value;
    STATE.ticketsFilters.asignado = document.getElementById("asignadoTicket").value;
    STATE.ticketsPage = 1;
    loadTickets();
  });
  on(document.getElementById("limpiarFiltros"), "click", () => {
    form.reset();
    STATE.ticketsFilters = { estado: "", creador: "", asignado: "", q: "" };
    STATE.ticketsPage = 1;
    loadTickets();
  });
  const inputSearch = document.getElementById("searchTickets");
  if (inputSearch) {
    on(
      inputSearch,
      "input",
      debounce(() => {
        STATE.ticketsFilters.q = inputSearch.value.trim();
        STATE.ticketsPage = 1;
        loadTickets();
      }, 300)
    );
  }
}

function setupTicketSorting() {
  qsa("[data-sort-ticket]").forEach((th) =>
    on(th, "click", () => {
      const field = th.dataset.sortTicket;
      const dir = STATE.ticketsSort.dir === "asc" ? "desc" : "asc";
      STATE.ticketsSort = { field, dir };
      loadTickets();
    })
  );
}

function setupTicketsExport() {
  const btn = document.getElementById("exportTicketsBtn");
  if (!btn) return;
  on(btn, "click", async () => {
    try {
      const data = await api(CONFIG.endpoints.tickets);
      exportCSV("tickets.csv", data.items || data, ["id", "titulo", "estado", "creador", "asignado", "fecha"], {
        fecha: (v) => formatDate(v),
      });
      showAlert("mensajeUsuario", "Exportación de tickets completa", "success");
    } catch (err) {
      showAlert("mensajeUsuario", "No fue posible exportar tickets", "error");
    }
  });
}

/* =========================================================================
   Modal de Ticket (detalles, historial, cambio de estado, asignación)
   ========================================================================= */
async function openTicketModal(id) {
  try {
    const detail = await api(`${CONFIG.endpoints.tickets}/${id}`);
    const history = await api(CONFIG.endpoints.ticketsHistory(id));
    STATE.currentTicket = detail;
    const modal = document.getElementById("modalTicket");
    const body = modal.querySelector(".modal-cuerpo #detalleTicket");
    const hist = modal.querySelector("#historialTicket");
    body.innerHTML = `
      <div class="detalle">
        <p><b>ID:</b> ${detail.id}</p>
        <p><b>Título:</b> ${detail.titulo}</p>
        <p><b>Estado:</b> ${detail.estado}</p>
        <p><b>Creador:</b> ${detail.creador}</p>
        <p><b>Asignado:</b> ${detail.asignado || "-"}</p>
        <p><b>Fecha:</b> ${formatDate(detail.fecha)}</p>
        <p><b>Descripción:</b> ${detail.descripcion || ""}</p>
      </div>
    `;
    hist.innerHTML = history
      .map(
        (h) => `
      <div class="historial-item">
        <div class="historial-meta">
          <span>${formatDate(h.fecha)}</span>
          <span>${h.usuario}</span>
        </div>
        <div class="historial-msg">${h.mensaje}</div>
      </div>
    `
      )
      .join("");
    modal.classList.add("abierto");
  } catch (err) {
    showAlert("mensajeUsuario", "No fue posible cargar el detalle del ticket", "error");
  }
}

function closeTicketModal() {
  const modal = document.getElementById("modalTicket");
  modal.classList.remove("abierto");
  STATE.currentTicket = null;
}

function openAssignModal(id) {
  const targetId = id ?? STATE.currentTicket?.id;
  const nombre = prompt("Asignar a (email o nombre de admin):");
  if (!nombre) return;
  assignTicket(targetId, nombre);
}

async function assignTicket(id, adminNameOrEmail) {
  try {
    await api(CONFIG.endpoints.ticketsAssign(id), {
      method: "POST",
      body: JSON.stringify({ asignado: adminNameOrEmail }),
    });
    showAlert("mensajeUsuario", "Ticket asignado correctamente", "success");
    loadTickets();
  } catch (err) {
    showAlert("mensajeUsuario", err.message || "No se pudo asignar el ticket", "error");
  }
}

function openStatusModal(id) {
  const estados = ["abierto", "en_progreso", "resuelto", "cerrado"];
  const nuevo = prompt(`Nuevo estado (${estados.join(", ")}):`);
  if (!nuevo || !estados.includes(nuevo)) {
    showAlert("mensajeUsuario", "Estado inválido", "error");
    return;
  }
  updateTicketStatus(id, nuevo);
}

async function updateTicketStatus(id, estado) {
  try {
    await api(CONFIG.endpoints.ticketsStatus(id), {
      method: "PATCH",
      body: JSON.stringify({ estado }),
    });
    showAlert("mensajeUsuario", "Estado actualizado", "success");
    loadTickets();
  } catch (err) {
    showAlert("mensajeUsuario", err.message || "No se pudo actualizar el estado", "error");
  }
}

function setupTicketModalBindings() {
  const closeBtn = document.getElementById("cerrarModal");
  on(closeBtn, "click", closeTicketModal);
  on(document.getElementById("modalTicket"), "click", (e) => {
    if (e.target.id === "modalTicket") closeTicketModal();
  });

  const formComentario = document.getElementById("formComentario");
  on(formComentario, "submit", async (e) => {
    e.preventDefault();
    const comentario = document.getElementById("comentario").value.trim();
    if (!comentario || !STATE.currentTicket) return;
    try {
      await api(CONFIG.endpoints.ticketsHistory(STATE.currentTicket.id), {
        method: "POST",
        body: JSON.stringify({ mensaje: comentario }),
      });
      showAlert("mensajeUsuario", "Comentario agregado", "success");
      document.getElementById("comentario").value = "";
      openTicketModal(STATE.currentTicket.id); // recargar detalle + historial
    } catch (err) {
      showAlert("mensajeUsuario", "No se pudo agregar el comentario", "error");
    }
  });
}

/* =========================================================================
   Usuarios: carga, tabla, búsqueda, ordenamiento, paginación, CRUD
   ========================================================================= */
function renderUsersTable() {
  const tbody = document.getElementById("cuerpoUsuarios");
  const { users } = STATE;
  tbody.innerHTML = "";
  users.forEach((u) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${u.id}</td>
      <td>${u.name}</td>
      <td>${u.email}</td>
      <td>${u.role}</td>
      <td>${formatDate(u.created_at)}</td>
      <td class="acciones">
        <button class="boton-secundario" data-edit="${u.id}">Editar</button>
        <button class="boton-secundario" data-role="${u.id}">Cambiar rol</button>
        <button class="boton-error" data-delete="${u.id}">Eliminar</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  qsa("[data-edit]").forEach((b) => on(b, "click", () => openUserModal(parseInt(b.dataset.edit, 10))));
  qsa("[data-role]").forEach((b) =>
    on(b, "click", () => openRoleChangeModal(parseInt(b.dataset.role, 10)))
  );
  qsa("[data-delete]").forEach((b) =>
    on(b, "click", () => deleteUser(parseInt(b.dataset.delete, 10)))
  );
}

function renderUsersPagination() {
  const container = document.getElementById("usersPagination");
  if (!container) return;
  const { usersTotal, usersPage } = STATE;
  const pages = Math.ceil(usersTotal / CONFIG.pageSize) || 1;
  container.innerHTML = "";
  for (let p = 1; p <= pages; p++) {
    const btn = document.createElement("button");
    btn.className = `paginador ${p === usersPage ? "activo" : ""}`;
    btn.textContent = p;
    on(btn, "click", () => {
      STATE.usersPage = p;
      loadUsers();
    });
    container.appendChild(btn);
  }
}

async function loadUsers() {
  guardToken();
  const loader = document.getElementById("cargandoUsuarios");
  const table = document.getElementById("tablaUsuarios");
  loader.style.display = "block";
  table.style.display = "none";

  const { usersPage, usersSort, usersSearch } = STATE;
  const params = new URLSearchParams({
    page: usersPage,
    per_page: CONFIG.pageSize,
    sort: usersSort.field,
    dir: usersSort.dir,
    q: usersSearch || "",
  }).toString();

  try {
    const data = await api(`${CONFIG.endpoints.users}?${params}`);
    STATE.users = data.items || data;
    STATE.usersTotal = data.total ?? STATE.users.length;
    renderUsersTable();
    renderUsersPagination();
    table.style.display = "block";
  } catch (err) {
    showAlert("mensajeUsuario", err.message || "Error al cargar usuarios", "error");
  } finally {
    loader.style.display = "none";
  }
}

function setupUserSearch() {
  const input = document.getElementById("searchUsers");
  if (!input) return;
  on(
    input,
    "input",
    debounce(() => {
      STATE.usersSearch = input.value.trim();
      STATE.usersPage = 1;
      loadUsers();
    }, 300)
  );
}

function setupUserSorting() {
  qsa("[data-sort-user]").forEach((th) =>
    on(th, "click", () => {
      const field = th.dataset.sortUser;
      const dir = STATE.usersSort.dir === "asc" ? "desc" : "asc";
      STATE.usersSort = { field, dir };
      loadUsers();
    })
  );
}

async function deleteUser(id) {
  if (!confirm("¿Eliminar usuario? Esta acción no puede deshacerse.")) return;
  try {
    await api(CONFIG.endpoints.userDelete(id), { method: "DELETE" });
    showAlert("mensajeUsuario", "Usuario eliminado", "success");
    loadUsers();
  } catch (err) {
    showAlert("mensajeUsuario", err.message || "No se pudo eliminar el usuario", "error");
  }
}

/* =========================================================================
   Modales de Usuario (editar, cambio de rol)
   ========================================================================= */
async function openUserModal(id) {
  try {
    const data = await api(`${CONFIG.endpoints.users}/${id}`);
    STATE.currentUser = data;
    const modal = qs("#modalUsuario");
    qs("#editUserName").value = data.name || "";
    qs("#editUserEmail").value = data.email || "";
    qs("#editUserRole").value = data.role || "gestor";
    modal.classList.add("abierto");
  } catch (err) {
    showAlert("mensajeUsuario", "No fue posible cargar el usuario", "error");
  }
}

function closeUserModal() {
  qs("#modalUsuario").classList.remove("abierto");
  STATE.currentUser = null;
}

function setupUserModalBindings() {
  on(qs("#cerrarModalUsuario"), "click", closeUserModal);
  on(qs("#modalUsuario"), "click", (e) => {
    if (e.target.id === "modalUsuario") closeUserModal();
  });

  on(qs("#formEditarUsuario"), "submit", async (e) => {
    e.preventDefault();
    if (!STATE.currentUser) return;
    const name = qs("#editUserName").value.trim();
    const email = qs("#editUserEmail").value.trim();
    const role = qs("#editUserRole").value;

    if (!name || !email || !role) {
      showAlert("mensajeUsuario", "Completa todos los campos", "error");
      return;
    }

    try {
      await api(CONFIG.endpoints.userUpdate(STATE.currentUser.id), {
        method: "PUT",
        body: JSON.stringify({ name, email, role }),
      });
      showAlert("mensajeUsuario", "Usuario actualizado", "success");
      closeUserModal();
      loadUsers();
    } catch (err) {
      showAlert("mensajeUsuario", err.message || "No se pudo actualizar el usuario", "error");
    }
  });
}

function openRoleChangeModal(id) {
  const nuevo = prompt("Nuevo rol (gestor/admin):");
  if (!nuevo || !["gestor", "admin"].includes(nuevo)) {
    showAlert("mensajeUsuario", "Rol inválido", "error");
    return;
  }
  updateUserRole(id, nuevo);
}

async function updateUserRole(id, role) {
  try {
    await api(CONFIG.endpoints.userRole(id), {
      method: "PATCH",
      body: JSON.stringify({ role }),
    });
    showAlert("mensajeUsuario", "Rol actualizado", "success");
    loadUsers();
  } catch (err) {
    showAlert("mensajeUsuario", err.message || "No se pudo actualizar el rol", "error");
  }
}

/* =========================================================================
   Crear Usuario (formulario seccion-nuevo-usuario)
   ========================================================================= */
function setupCreateUser() {
  const form = document.getElementById("formCrearUsuario");
  on(form, "submit", async (e) => {
    e.preventDefault();
    const name = document.getElementById("nombreNuevo").value.trim();
    const email = document.getElementById("correoNuevo").value.trim();
    const password = document.getElementById("claveNuevo").value.trim();
    const role = document.getElementById("rolNuevo").value;

    if (!name || !email || !password || !role) {
      showAlert("mensajeUsuario", "Todos los campos son obligatorios", "error");
      return;
    }
    if (password.length < 6) {
      showAlert("mensajeUsuario", "La contraseña debe tener al menos 6 caracteres", "error");
      return;
    }

    try {
      await api(CONFIG.endpoints.userCreate, {
        method: "POST",
        body: JSON.stringify({ name, email, password, role }),
      });
      showAlert("mensajeUsuario", "Usuario creado correctamente", "success");
      form.reset();
      STATE.usersPage = 1;
      loadUsers();
    } catch (err) {
      showAlert("mensajeUsuario", err.body?.error || "No se pudo crear el usuario", "error");
    }
  });
}

/* =========================================================================
   Botón salir (logout) y seguridad
   ========================================================================= */
function setupLogout() {
  const btn = document.getElementById("btnSalir");
  on(btn, "click", () => {
    localStorage.removeItem("token");
    showAlert("mensajeUsuario", "Sesión cerrada", "success");
    setTimeout(() => (window.location.href = "index.html"), 500);
  });
}

/* =========================================================================
   Inicialización
   ========================================================================= */
function init() {
  guardToken();
  setupNavigation();
  setupTicketFilters();
  setupTicketSorting();
  setupTicketsExport();
  setupTicketModalBindings();

  setupUserSearch();
  setupUserSorting();
  setupUserModalBindings();

  setupCreateUser();
  setupLogout();

  // Cargas iniciales
  loadTickets();
  loadUsers();
}

window.addEventListener("DOMContentLoaded", init);
