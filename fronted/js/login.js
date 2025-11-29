// =======================
// Login – Proyecto Gestor
// =======================

document.addEventListener('DOMContentLoaded', () => {
    
    // Si existe sesión previa → redirigir
    if (isAuthenticated()) {
        redirectBasedOnRole();
        return;
    }

    const UI = {
        loginForm: document.getElementById('loginForm'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        submit: document.getElementById('submitBtn'),
        message: document.getElementById('message'),
    };

    UI.loginForm.addEventListener('submit', onSubmitLogin);

    // =====================================
    // EVENTO PRINCIPAL: user hace login
    // =====================================
    async function onSubmitLogin(e) {
        e.preventDefault();
        resetMessage();

        const email = UI.email.value.trim();
        const password = UI.password.value.trim();

        // Validaciones básicas
        if (!email || !password)
            return showMessage('Por favor completa todos los campos', 'error');

        if (!validateEmail(email))
            return showMessage('Por favor ingresa un email válido', 'error');

        setLoading(true);

        try {
            const result = await login(email, password);

            if (result.token) saveToken(result.token);
            if (result.user) saveUserInfo(result.user);

            showMessage('Inicio de sesión exitoso 👍', 'success');

            setTimeout(() => redirectBasedOnRole(), 800);

        } catch (err) {
            console.error('Login error:', err);
            showMessage(err.message || 'Credenciales incorrectas', 'error');
            setLoading(false);
        }
    }

    // ===========================
    // Redirección según el rol
    // ===========================
    function redirectBasedOnRole() {
        const role = getUserRole();

        switch (role) {
            case 'gestor':
                window.location.href = 'front/gestor-dashboard.html';
                break;
            case 'admin':
                window.location.href = 'front/admin-dashboard.html';
                break;
            default:
                showMessage('Rol no permitido', 'error');
                break;
        }
    }

    // ===========================
    // Helpers UI
    // ===========================
    function showMessage(text, type) {
        UI.message.textContent = text;
        UI.message.className = `auth-message ${type} show`;
    }

    function resetMessage() {
        UI.message.className = 'auth-message';
        UI.message.textContent = '';
    }

    function setLoading(active) {
        UI.submit.disabled = active;
        UI.submit.classList.toggle('btn-loading', active);
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
