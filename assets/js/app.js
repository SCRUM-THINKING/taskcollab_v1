/**
 * Lógica principal de la aplicación TaskCollab
 * 
 * Este archivo maneja toda la interactividad del cliente, incluyendo:
 * - Gestión de tareas (crear, editar, eliminar, marcar como completada)
 * - Actualizaciones de la interfaz de usuario
 * - Comunicación con el servidor mediante API REST
 * - Manejo de notificaciones y estados
 */

// ===== VARIABLES GLOBALES =====
// Referencias a elementos del DOM que se usan frecuentemente
let taskForm, taskInput, taskList, emptyState, totalTasks, completedTasks, pendingTasks;

/**
 * Carga las tareas del servidor
 * Realiza una petición GET a la API y actualiza la interfaz
 */
async function loadTasks() {
    try {
        const response = await fetch(`api/tasks.php?usuario_id=${USUARIO_ID}`);
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        const tasks = await response.json();

        updateStats(tasks);
        updateTaskList(tasks);
    } catch (error) {
        console.error('Error:', error);
        showErrorToast('Error al cargar las tareas');
    }
}

/**
 * Actualiza las estadísticas de tareas
 * @param {Array} tasks - Array de tareas
 */
function updateStats(tasks) {
    const total = tasks.length;
    const completed = tasks.filter(t => t.estado === 'completada').length;
    
    totalTasks.textContent = total;
    completedTasks.textContent = completed;
    pendingTasks.textContent = total - completed;
}

/**
 * Actualiza la lista de tareas en la interfaz
 * @param {Array} tasks - Array de tareas a mostrar
 */
function updateTaskList(tasks) {
    // Mostrar/ocultar estado vacío según corresponda
    emptyState.style.display = tasks.length === 0 ? 'block' : 'none';

    // Limpiar y renderizar lista de tareas
    taskList.innerHTML = '';
    tasks.forEach(task => {
        const li = createTaskElement(task);
        taskList.appendChild(li);
    });
}

/**
 * Crea el elemento HTML para una tarea
 * @param {Object} task - Objeto con los datos de la tarea
 * @returns {HTMLElement} Elemento li con la estructura de la tarea
 */
function createTaskElement(task) {
    const li = document.createElement('li');
    li.className = `task-item ${task.estado === 'completada' ? 'completed' : ''}`;
    li.dataset.id = task.id;
    
    li.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div class="form-check flex-grow-1">
                <input class="form-check-input" type="checkbox" 
                       ${task.estado === 'completada' ? 'checked' : ''}
                       onchange="updateTaskStatus(${task.id}, this.checked)">
                <label class="form-check-label task-text"
                       ondblclick="enableEditMode(this, ${task.id})">
                    ${task.titulo}
                </label>
                <small class="text-muted d-block">${task.descripcion || ''}</small>
            </div>
            <div class="task-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="enableEditMode(this.parentElement.previousElementSibling.querySelector('label'), ${task.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(${task.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    return li;
}

// ===== INICIALIZACIÓN DE LA APLICACIÓN =====
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar referencias a elementos del DOM
    taskForm = document.getElementById('task-form');
    taskInput = document.getElementById('task-input');
    taskList = document.getElementById('task-list');
    emptyState = document.getElementById('empty-state');
    totalTasks = document.getElementById('total-tasks');
    completedTasks = document.getElementById('completed-tasks');
    pendingTasks = document.getElementById('pending-tasks');

    // Cargar tareas existentes al iniciar
    loadTasks();

    /**
     * Manejador del formulario de nueva tarea
     * Previene el envío tradicional y usa AJAX para crear tareas
     */
    taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const titulo = taskInput.value.trim();
        
        if (titulo) {
            try {
                const response = await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        titulo,
                        usuario_id: USUARIO_ID
                    })
                });

                if (response.ok) {
                    taskInput.value = '';
                    await loadTasks();
                    showSuccessToast('Tarea creada correctamente');
                } else {
                    const error = await response.json();
                    showErrorToast(error.error || 'Error al crear la tarea');
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorToast('Error al crear la tarea');
            }
        }
    });
});

/**
 * Habilita el modo de edición para una tarea
 * @param {HTMLElement} label - Elemento label que contiene el texto de la tarea
 * @param {number} taskId - ID de la tarea a editar
 */
function enableEditMode(label, taskId) {
    const currentText = label.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.value = currentText;
    input.className = 'form-control form-control-sm';
    
    label.style.display = 'none';
    label.parentNode.insertBefore(input, label);
    input.focus();

    // Manejar la finalización de la edición
    input.addEventListener('blur', async () => {
        const newText = input.value.trim();
        if (newText !== currentText && newText !== '') {
            await updateTask(taskId, { titulo: newText });
        }
        input.remove();
        label.style.display = '';
    });

    input.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            input.blur();
        }
    });
}

/**
 * Actualiza una tarea en el servidor
 * @param {number} taskId - ID de la tarea a actualizar
 * @param {Object} data - Datos a actualizar
 */
async function updateTask(taskId, data) {
    const loadingToast = showLoadingToast('Actualizando tarea...');
    try {
        const response = await fetch('api/tasks.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: taskId,
                ...data
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Error al actualizar la tarea');
        }

        await loadTasks();
        hideLoadingToast(loadingToast);
        showSuccessToast('Tarea actualizada correctamente');
    } catch (error) {
        console.error('Error:', error);
        hideLoadingToast(loadingToast);
        showErrorToast(error.message || 'Error al actualizar la tarea');
    }
}

/**
 * Actualiza el estado de una tarea (completada/pendiente)
 * @param {number} taskId - ID de la tarea
 * @param {boolean} completed - Estado de completado
 */
async function updateTaskStatus(taskId, completed) {
    await updateTask(taskId, {
        estado: completed ? 'completada' : 'pendiente'
    });
}

/**
 * Elimina una tarea
 * @param {number} taskId - ID de la tarea a eliminar
 */
async function deleteTask(taskId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta tarea?')) {
        return;
    }

    const loadingToast = showLoadingToast('Eliminando tarea...');
    try {
        const response = await fetch(`api/tasks.php?id=${taskId}`, {
            method: 'DELETE'
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Error al eliminar la tarea');
        }

        await loadTasks();
        hideLoadingToast(loadingToast);
        showSuccessToast('Tarea eliminada correctamente');
    } catch (error) {
        console.error('Error:', error);
        hideLoadingToast(loadingToast);
        showErrorToast(error.message || 'Error al eliminar la tarea');
    }
}

// ===== SISTEMA DE NOTIFICACIONES =====

/**
 * Muestra una notificación de carga
 * @param {string} message - Mensaje a mostrar
 * @returns {HTMLElement} Elemento toast creado
 */
function showLoadingToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-primary show';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.minWidth = '250px';
    toast.style.zIndex = '9999';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <span class="spinner-border spinner-border-sm me-2"></span>
                ${message}
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    return toast;
}

/**
 * Oculta una notificación con animación
 * @param {HTMLElement} toast - Elemento toast a ocultar
 */
function hideLoadingToast(toast) {
    if (toast && toast.parentNode) {
        toast.style.transition = 'opacity 0.5s ease-out';
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 500);
    }
}

/**
 * Muestra una notificación de éxito
 * @param {string} message - Mensaje a mostrar
 */
function showSuccessToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white show';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.minWidth = '250px';
    toast.style.zIndex = '9999';
    toast.style.backgroundColor = '#28a745';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body" style="display: flex; align-items: center;">
                <i class="fas fa-check-circle me-2" style="color: #fff;"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Animación de entrada
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'transform 0.5s ease-out';
    setTimeout(() => toast.style.transform = 'translateX(0)', 10);
    
    // Auto cerrar después de 3 segundos
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => hideLoadingToast(toast), 500);
    }, 3000);
}

/**
 * Muestra una notificación de error
 * @param {string} message - Mensaje de error a mostrar
 */
function showErrorToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white show';
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.minWidth = '250px';
    toast.style.zIndex = '9999';
    toast.style.backgroundColor = '#dc3545';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body" style="display: flex; align-items: center;">
                <i class="fas fa-exclamation-circle me-2" style="color: #fff;"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Animación de entrada
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'transform 0.5s ease-out';
    setTimeout(() => toast.style.transform = 'translateX(0)', 10);
    
    // Auto cerrar después de 5 segundos
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => hideLoadingToast(toast), 500);
    }, 5000);
} 