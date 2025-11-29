// ===========================
// Registro – Proyecto Gestor
// ===========================

document.addEventListener('DOMContentLoaded', () => {

    // Si hay sesión activa → redirigir
    if (isAuthenticated()) {
        redirectByRole();
        return;
    }

    const UI = {
        form: document.getElementById('registerForm'),
        name: document.getElementById('name'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        role: document.getElementById('role'),
        submit: document.getElementById('submitBtn'),
        message: document.getElementById('message'),
    };

    UI.form.addEventListener('submit', onRegisterSubmit);

    // =================================================
    // Evento principal: procesar registro de usuario
    // =================================================
    async function onRegisterSubmit(e) {
        e.preventDefault();
        resetMessage();

        const userData = {
            name: UI.name.value.trim(),
            email: UI.email.value.trim(),
            password: UI.password.value.trim(),
            role: UI.role.value
        };

        // Validaciones
        if (!validateNotEmpty(userData))
            return showMessage('Por favor completa todos los campos', 'error');

        if (!validateEmail(userData.email))
            return showMessage('Por favor ingresa un email válido', 'error');

        if (userData.password.length < 6)
            return showMessage('La contraseña debe tener al menos 6 caracteres', 'error');

        if (!['admin', 'gestor'].includes(userData.role))
            return showMessage('Por favor selecciona un rol válido', 'error');

        setLoading(true);

        try {
            await register(userData);

            showMessage('Registro exitoso 😎 Redirigiendo...', 'success');

            setTimeout(() => {
                window.location.href = '../index.html';
            }, 1200);

        } catch (err) {
            console.error('Error en registro:', err);
            showMessage(err.message || 'Error al registrar usuario.', 'error');
            setLoading(false);
        }
    }

    // =====================================
    // Redirección según rol si ya está logueado
    // =====================================
    function redirectByRole() {
        const role = getUserRole();
        if (role === 'gestor')
            window.location.href = 'gestor-dashboard.html';
        else if (role === 'admin')
            window.location.href = 'admin-dashboard.html';
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

    // ===========================
    // Helpers de validación
    // ===========================
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validateNotEmpty(obj) {
        return Object.values(obj).every(value => value !== '');
    }
});
