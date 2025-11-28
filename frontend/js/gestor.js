/* =======================
   Utilidades
   ======================= */
const getTokenSafe = () => (typeof obtenerToken === "function" ? obtenerToken() : (typeof getToken === "function" ? getToken() : localStorage.getItem("auth_token")));
const logoutSafe = async () => (typeof cerrarSesion === "function" ? cerrarSesion() : (localStorage.removeItem("auth_token"), window.location.href = "../index.html"));

const apiBaseTickets = (() => {
  if (window.API_CONFIG?.tickets?.base) return API_CONFIG.tickets.base;
  if (window.API_CONFIG?.TICKETS_API) return API_CONFIG.TICKETS_API;
  return "http://localhost:8001";
})();

async function http(path, options = {}) {
  const headers = {
    "Content-Type": "application/json",
    ...(options.headers || {})
  };
  const token = getTokenSafe();
  if (!options.skipAuth && token) headers.Authorization = token;

  const res = await fetch(`${apiBaseTickets}${path}`, { ...options, headers });
  if (res.status === 401 && !options.skipAuth) {
    await logoutSafe();
    throw new Error("Sesión expirada");
  }
  const data = await res.json().catch(() => null);
  if (!res.ok) throw new Error(data?.error || `Error ${res.status}`);
  return data;
}

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

function toggle(el, show) {
  if (!el) return;
  el.style.display = show ? "" : "none";
}

/* =======================
   Navegación
   ======================= */
function setupNavigation() {
  document.querySelectorAll("[data-seccion]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const target = btn.getAttribute("data-seccion");
      document.querySelectorAll(".seccion").forEach(s => s.classList.remove("visible"));
      document.querySelectorAll(".opcion-menu").forEach(b => b.classList.remove("activa"));
      document.getElementById(`seccion-${target}`).classList.add("visible");
      btn.classList.add("activa");
      if (target === "listado") cargarMisTickets();
    });
  });
}

/* =======================
   Tickets: crear y listar
   ======================= */
async function crearTicketSubmit(e) {
  e.preventDefault();
  const titulo = document.getElementById("titulo").value.trim();
  const descripcion = document.getElementById("descripcion").value.trim();
  const mensaje = document.getElementById("mensajeCreacion");

  if (!titulo || !descripcion) {
    mensaje.textContent = "Completa todos los campos";
    mensaje.className = "mensaje-alerta error";
    toggle(mensaje, true);
    setTimeout(() => toggle(mensaje, false), 3000);
    return;
  }

  try {
    await http("/tickets", {
      method: "POST",
      body: JSON.stringify({ titulo, descripcion })
    });
    mensaje.textContent = "Ticket creado correctamente";
    mensaje.className = "mensaje-alerta success";
    toggle(mensaje, true);
    (document.getElementById("formCrearTicket")).reset();
    setTimeout(() => {
      toggle(mensaje, false);
      document.querySelector('[data-seccion="listado"]').click();
    }, 1200);
  } catch (err) {
    mensaje.textContent = err.message || "Error al crear el ticket";
    mensaje.className = "mensaje-alerta error";
    toggle(mensaje, true);
    setTimeout(() => toggle(mensaje, false), 3000);
  }
}

async function cargarMisTickets() {
  const loader = document.getElementById("cargandoTickets");
  const grid = document.getElementById("contenedorTickets");
  const empty = document.getElementById("sinTickets");

  toggle(loader, true);
  toggle(grid, false);
  toggle(empty, false);

  try {
    const data = await http("/tickets/my", { method: "GET" });
    grid.innerHTML = "";
    if (!data || data.length === 0) {
      toggle(empty, true);
      return;
    }

    data.forEach(t => {
      const card = document.createElement("div");
      card.className = "ticket-card";
      card.innerHTML = `
        <h4 class="ticket-titulo">${t.titulo}</h4>
        <p class="ticket-estado"><b>Estado:</b> ${t.estado}</p>
        <p class="ticket-fecha"><b>Fecha:</b> ${new Date(t.fecha).toLocaleString()}</p>
        <div class="ticket-acciones">
          <button class="boton-secundario" data-open="${t.id}">Ver detalles</button>
        </div>
      `;
      grid.appendChild(card);
    });

    grid.querySelectorAll("[data-open]").forEach(b => {
      b.addEventListener("click", () => abrirModalTicket(parseInt(b.getAttribute("data-open"), 10)));
    });

    toggle(grid, true);
  } catch (err) {
    grid.innerHTML = `<div class="mensaje-alerta error">Error al cargar tus tickets</div>`;
    toggle(grid, true);
  } finally {
    toggle(loader, false);
  }
}

/* =======================
   Tickets: modal y comentarios
   ======================= */
async function abrirModalTicket(id) {
  try {
    const detalle = await http(`/tickets/${id}`, { method: "GET" });
    const historial = await http(`/tickets/${id}/history`, { method: "GET" });

    document.getElementById("detalleTicket").innerHTML = `
      <div class="detalle">
        <p><b>ID:</b> ${detalle.id}</p>
        <p><b>Título:</b> ${detalle.titulo}</p>
        <p><b>Estado:</b> ${detalle.estado}</p>
        <p><b>Fecha:</b> ${new Date(detalle.fecha).toLocaleString()}</p>
        <p><b>Descripción:</b> ${detalle.descripcion || ""}</p>
      </div>
    `;

    const hist = document.getElementById("historialTicket");
    hist.innerHTML = (historial || []).map(h => `
      <div class="historial-item">
        <div class="historial-meta">
          <span>${new Date(h.fecha).toLocaleString()}</span>
          <span>${h.usuario}</span>
        </div>
        <div class="historial-msg">${h.mensaje}</div>
      </div>
    `).join("");

    const modal = document.getElementById("modalTicket");
    modal.classList.add("abierto");
    modal.setAttribute("data-current", String(id));
  } catch (err) {
    const grid = document.getElementById("contenedorTickets");
    grid.insertAdjacentHTML("afterbegin", `<div class="mensaje-alerta error">No fue posible cargar el ticket</div>`);
  }
}

function cerrarModalTicket() {
  const modal = document.getElementById("modalTicket");
  modal.classList.remove("abierto");
  modal.removeAttribute("data-current");
}

async function enviarComentario(e) {
  e.preventDefault();
  const modal = document.getElementById("modalTicket");
  const id = parseInt(modal.getAttribute("data-current") || "0", 10);
  const mensaje = document.getElementById("comentario").value.trim();
  if (!id || !mensaje) return;

  try {
    await http(`/tickets/${id}/comments`, {
      method: "POST",
      body: JSON.stringify({ mensaje })
    });
    document.getElementById("comentario").value = "";
    await abrirModalTicket(id);
  } catch (err) {
    const hist = document.getElementById("historialTicket");
    hist.insertAdjacentHTML("afterbegin", `<div class="mensaje-alerta error">No se pudo agregar el comentario</div>`);
  }
}

/* =======================
   Sesión y encabezado
   ======================= */
function setupHeader() {
  const name = localStorage.getItem("nombre_usuario") || localStorage.getItem("user_name") || "Gestor";
  setText("nombreUsuario", name);
  const btnSalir = document.getElementById("btnSalir");
  btnSalir.addEventListener("click", logoutSafe);
}

/* =======================
   Inicialización
   ======================= */
function initGestor() {
  if (typeof verificarSesion === "function" && !verificarSesion()) return;
  if (typeof verificarRol === "function" && !verificarRol("gestor")) return;

  setupHeader();
  setupNavigation();

  document.getElementById("formCrearTicket").addEventListener("submit", crearTicketSubmit);
  document.getElementById("cerrarModal").addEventListener("click", cerrarModalTicket);
  document.getElementById("modalTicket").addEventListener("click", (e) => {
    if (e.target.id === "modalTicket") cerrarModalTicket();
  });
  document.getElementById("formComentario").addEventListener("submit", enviarComentario);

  cargarMisTickets();
}

window.addEventListener("DOMContentLoaded", initGestor);