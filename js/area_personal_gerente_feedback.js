// Archivo: public/js/area_personal_gerente_feedback.js
// Propósito: Gestiona la visualización, filtrado y edición de mensajes de feedback para el Gerente de Ventas,
//           así como la lógica de cálculo de envíos y verificación de disponibilidad.

document.addEventListener('DOMContentLoaded', function() {
    const feedbackTableBody = document.querySelector('#feedbackTable tbody');
    const addCommentModal = new bootstrap.Modal(document.getElementById('addCommentModal'));
    const modalFeedbackIdSpan = document.getElementById('modalFeedbackId');
    const clientMessageContentSpan = document.getElementById('clientMessageContent');
    const clientMessageSenderSpan = document.getElementById('clientMessageSender');
    const commentForm = document.getElementById('commentForm');
    const commentFeedbackIdInput = document.getElementById('commentFeedbackId');
    const employeeCommentTextInput = document.getElementById('employeeCommentText');
    const commentResponseDiv = document.getElementById('commentResponse');

    // Elementos del formulario de filtrado de Feedback
    const feedbackFilterForm = document.getElementById('feedbackFilterForm');
    const filtroFeedbackFechaInput = document.getElementById('filtro_feedback_fecha');
    const filtroFeedbackNombreClienteInput = document.getElementById('filtro_feedback_nombre_cliente');
    const filtroFeedbackEmailClienteInput = document.getElementById('filtro_feedback_email_cliente');


    // Función auxiliar para escapar HTML (necesaria para innerHTML)
    function htmlspecialchars(str) {
        if (typeof str !== 'string' && typeof str !== 'number') {
            return str; 
        }
        str = String(str);
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // --- Lógica para la sección de Gestión de Feedback ---

    // Carga y muestra los mensajes de feedback en la tabla
    const loadFeedbackMessages = () => {
        feedbackTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Cargando mensajes...</td></tr>';
        
        // Obtiene valores de los filtros del formulario
        const filtroFecha = filtroFeedbackFechaInput.value;
        const filtroNombre = filtroFeedbackNombreClienteInput.value;
        const filtroEmail = filtroFeedbackEmailClienteInput.value;

        const queryParams = new URLSearchParams();
        if (filtroFecha) queryParams.append('fecha', filtroFecha);
        if (filtroNombre) queryParams.append('nombre_cliente', filtroNombre);
        if (filtroEmail) queryParams.append('email_cliente', filtroEmail);

        fetch(`php_scripts/get_all_feedback_messages.php?${queryParams.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red al cargar feedback: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    feedbackTableBody.innerHTML = `<tr><td colspan="7" class="text-center alert alert-danger">${data.error}</td></tr>`;
                } else if (data.feedback && data.feedback.length > 0) {
                    let html = '';
                    data.feedback.forEach(msg => {
                        html += `
                            <tr>
                                <td>${htmlspecialchars(msg.idFeedback)}</td>
                                <td>${htmlspecialchars(msg.name)}</td>
                                <td>${htmlspecialchars(msg.email)}</td>
                                <td>${htmlspecialchars(msg.fecha_envio)}</td>
                                <td>${htmlspecialchars(msg.message)}</td>
                                <td>${htmlspecialchars(msg.employee_comment || 'Ninguno')}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary add-comment-btn" 
                                            data-feedback-id="${htmlspecialchars(msg.idFeedback)}"
                                            data-client-name="${htmlspecialchars(msg.name)}"
                                            data-client-email="${htmlspecialchars(msg.email)}"
                                            data-client-message="${htmlspecialchars(msg.message)}"
                                            data-current-comment="${htmlspecialchars(msg.employee_comment || '')}">
                                        Añadir/Editar Comentario
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    feedbackTableBody.innerHTML = html;
                } else {
                    feedbackTableBody.innerHTML = `<tr><td colspan="7" class="text-center">No hay mensajes de feedback.</td></tr>`;
                }
            })
            .catch(error => {
                feedbackTableBody.innerHTML = `<tr><td colspan="7" class="text-center alert alert-danger">Error al cargar mensajes de feedback: ${error.message}</td></tr>`;
                console.error('Error cargando mensajes de feedback:', error);
            });
    };

    // Listener para abrir el modal de comentario
    feedbackTableBody.addEventListener('click', function(e) {
        const addCommentBtn = e.target.closest('.add-comment-btn');
        if (addCommentBtn) {
            const feedbackId = addCommentBtn.dataset.feedbackId;
            const clientName = addCommentBtn.dataset.clientName;
            const clientEmail = addCommentBtn.dataset.clientEmail;
            const clientMessage = addCommentBtn.dataset.clientMessage;
            const currentComment = addCommentBtn.dataset.currentComment;
            
            modalFeedbackIdSpan.textContent = feedbackId;
            clientMessageContentSpan.textContent = clientMessage;
            clientMessageSenderSpan.textContent = `${clientName} (${clientEmail})`;
            commentFeedbackIdInput.value = feedbackId;
            employeeCommentTextInput.value = currentComment; 

            commentResponseDiv.innerHTML = ''; 
            commentResponseDiv.className = '';

            addCommentModal.show();
        }
    });

    // Listener para enviar el comentario del empleado
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        commentResponseDiv.innerHTML = 'Guardando comentario...';
        commentResponseDiv.className = 'alert alert-info';

        const feedbackId = commentFeedbackIdInput.value;
        const newCommentText = employeeCommentTextInput.value.trim();

        const employeeId = typeof MANAGER_USER_ID !== 'undefined' ? MANAGER_USER_ID : 'UNKNOWN'; 
        
        const finalComment = `[ID:${employeeId}] ${newCommentText}`; 

        fetch('php_scripts/add_feedback_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id_feedback: feedbackId,
                employee_comment: finalComment 
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message || 'Error desconocido'); }).catch(() => { throw new Error('Respuesta del servidor no es JSON válido.'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                commentResponseDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                loadFeedbackMessages(); // Recargar la tabla para ver el cambio
                setTimeout(() => addCommentModal.hide(), 1000); 
            } else {
                commentResponseDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            commentResponseDiv.innerHTML = `<div class="alert alert-danger">Error inesperado al añadir comentario: ${error.message}</div>`;
            console.error('Error añadiendo comentario:', error);
        });
    });

    if (feedbackFilterForm) {
        feedbackFilterForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            loadFeedbackMessages(); // Recargar los mensajes con los nuevos filtros
        });
    }

    loadFeedbackMessages();

 
});