///////CARGA Y VISUALIZACIÓN DE USUARIOS

async function loadUsers() {
    const UI = getElements(['usersLoading', 'usersTableContainer', 'usersTableBody']);

    showLoadingUI(UI);

    try {
        allUsers = await listUsers();
        renderUsersTable(allUsers, UI);
        attachUserEventListeners();
    } catch (error) {
        handleError('cargar usuarios', error, UI.usersLoading, UI.usersTableBody, 6);
    }
}

function renderUsersTable(users, UI) {
    UI.usersLoading.style.display = 'none';
    UI.usersTableContainer.style.display = 'block';
    UI.usersTableBody.innerHTML = users.map(createUserRow).join('');
}

function createUserRow(u) {
    const badge = `<span class="badge ${u.role === 'admin' ? 'badge-danger' : 'badge-primary'}">
        ${u.role}
    </span>`;

    return `
        <tr>
            <td>${u.id}</td>
            <td>${escapeHtml(u.name)}</td>
            <td>${escapeHtml(u.email)}</td>
            <td>${badge}</td>
            <td>${formatDate(u.created_at)}</td>
            <td>
                ${createUserActionBtn('edit', u.id, 'btn-primary', 'Editar')}
                ${createUserActionBtn('delete', u.id, 'btn-danger', 'Eliminar')}
            </td>
        </tr>
    `;
}

function createUserActionBtn(type, id, style, label) {
    return `<button class="btn ${style} btn-icon ${type}-user" data-user-id="${id}">${label}</button>`;
}

function attachUserEventListeners() {
    delegateClick('.edit-user', btn => openEditUserModal(btn.dataset.userId));
    delegateClick('.delete-user', btn => handleDeleteUser(btn.dataset.userId));
}

function delegateClick(selector, handler) {
    document.querySelectorAll(selector).forEach(btn =>
        btn.addEventListener('click', () => handler(btn))
    );
}

function showLoadingUI(UI) {
    UI.usersLoading.style.display = 'flex';
    UI.usersTableContainer.style.display = 'none';
}

///////EDICIÓN DE USUARIOS

function openEditUserModal(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;

    setModalFormValues(user);
    document.getElementById('userModal').classList.add('active');
}

function setModalFormValues(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserName').value = user.name;
    document.getElementById('editUserEmail').value = user.email;
    document.getElementById('editUserRole').value = user.role;
}

async function handleEditUser(e) {
    e.preventDefault();

    const userId = getInputVal('editUserId');
    const data = {
        name: getInputVal('editUserName').trim(),
        email: getInputVal('editUserEmail').trim(),
        role: getInputVal('editUserRole')
    };

    if (Object.values(data).some(v => !v))
        return alert('Completa todos los campos');

    try {
        await updateUser(userId, { name: data.name, email: data.email });

        const oldUser = allUsers.find(u => u.id == userId);

        if (oldUser.role !== data.role)
            await changeUserRole(userId, data.role);

        alert('Usuario actualizado correctamente');
        closeUserModal();
        loadUsers();
    } catch (error) {
        alert('Error al actualizar usuario: ' + error.message);
    }
}

function getInputVal(id) {
    return document.getElementById(id).value;
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
    document.getElementById('editUserForm').reset();
}

//////ELIMINACIÓN DE USUARIOS

async function handleDeleteUser(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;

    if (!confirm(`¿Eliminar a "${user.name}"?`)) return;

    try {
        await deleteUser(userId);
        alert('Usuario eliminado correctamente');
        loadUsers();
    } catch (error) {
        alert('Error al eliminar usuario: ' + error.message);
    }
}

/////CREACIÓN DE USUARIOS

async function handleCreateUser(e) {
    e.preventDefault();
    hideCreateUserMessage();

    const data = getFormData(['newUserName', 'newUserEmail', 'newUserPassword', 'newUserRole']);

    if (!validateNewUser(data)) return;

    setCreateUserLoading(true);

    try {
        await register(data);
        showCreateUserMessage('Usuario creado exitosamente', 'success');
        e.target.reset();

        setTimeout(() => {
            loadUsers();
            hideCreateUserMessage();
        }, 1500);
    } catch (error) {
        showCreateUserMessage(error.message || 'Error al crear usuario', 'error');
    } finally {
        setCreateUserLoading(false);
    }
}

function getFormData(fields) {
    return Object.fromEntries(
        fields.map(f => [f.replace('newUser','').toLowerCase(), getInputVal(f).trim()])
    );
}

function validateNewUser(data) {
    if (!data.name || !data.email || !data.password || !data.role)
        return showCreateUserMessage('Completa todos los campos', 'error'), false;

    if (!isValidEmail(data.email))
        return showCreateUserMessage('Email inválido', 'error'), false;

    if (data.password.length < 6)
        return showCreateUserMessage('Contraseña mínimo 6 caracteres', 'error'), false;

    if (!['gestor', 'admin'].includes(data.role))
        return showCreateUserMessage('Rol inválido', 'error'), false;

    return true;
}
