/* =======================
   Configuración de APIs
   ======================= */

const API_CONFIG = {
    usuarios: {
        base: "http://localhost:8000",
        endpoints: {
            login: "/login",
            register: "/register",
            logout: "/logout",
            list: "/users",
            update: (id) => `/users/${id}`,
            role: (id) => `/users/${id}/role`,
            delete: (id) => `/users/${id}`
        }
    },
    tickets: {
        base: "http://localhost:8001",
        endpoints: {
            create: "/tickets",
            my: "/tickets/my",
            all: "/tickets",
            detail: (id) => `/tickets/${id}`,
            status: (id) => `/tickets/${id}/status`,
            assign: (id) => `/tickets/${id}/assign`,
            comments: (id) => `/tickets/${id}/comments`,
            history: (id) => `/tickets/${id}/history`,
            search: "/tickets/search"
        }
    }
};