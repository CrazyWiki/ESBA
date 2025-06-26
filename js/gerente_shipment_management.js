// public/js/gerente_shipment_management.js
// Propósito: Gestiona la visualización y edición de detalles de envíos para el Gerente de Ventas.
// Este script espera que las variables JS 'ESTADOS_DISPONIBLES' y 'VEHICULOS_DISPONIBLES'
// sean definidas globalmente en el HTML antes de que este script se cargue.

document.addEventListener('DOMContentLoaded', function () {
    // Referencias a elementos del modal
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const modalEnvioIdDisplay = document.getElementById('modalEnvioId'); // Span para mostrar ID en título del modal

    // Referencias a los campos del formulario de edición dentro del modal
    const editOrderForm = document.getElementById('editOrderForm');
    const editEnvioIdInput = document.getElementById('editEnvioId');       // Hidden input para ID de envío
    const editIdClienteInput = document.getElementById('editIdCliente');   // Hidden input para ID de cliente
    const editIdDetalleEnvioInput = document.getElementById('editIdDetalleEnvio'); // Hidden input para ID de DetalleEnvio

    // Campos editables de Envios
    const editFechaEnvioInput = document.getElementById('editFechaEnvio');
    const editLugarOrigenInput = document.getElementById('editLugarOrigen');
    const editLugarDestinoInput = document.getElementById('editLugarDestino');
    const editKmInput = document.getElementById('editKm');
    const editTipoVehiculoSelect = document.getElementById('editTipoVehiculo');
    const editEstadoSelect = document.getElementById('editEstado');

    // Campos editables de Clientes
    const editNombreClienteInput = document.getElementById('editNombreCliente');
    const editApellidoClienteInput = document.getElementById('editApellidoCliente');
    const editDocumentoClienteInput = document.getElementById('editDocumentoCliente');
    const editTelefonoClienteInput = document.getElementById('editTelefonoCliente');

    // Campos editables de DetalleEnvio
    const editPesoKgInput = document.getElementById('editPesoKg');
    const editLargoCmInput = document.getElementById('editLargoCm');
    const editAnchoCmInput = document.getElementById('editAnchoCm');
    const editAltoCmInput = document.getElementById('editAltoCm');
    const editDescripcionAdicionalInput = document.getElementById('editDescripcionAdicional');

    // Elementos de visualización (no editables o para mostrar valores calculados)
    const displayEmailCliente = document.getElementById('displayEmailCliente');
    const displayCostoEstimado = document.getElementById('displayCostoEstimado');

    const modalResponseStatusDiv = document.getElementById('modalResponseStatus'); // Div para mensajes de respuesta en el modal


    // --- 1. Rellenar Dropdowns (SELECTs) al cargar el DOM ---
    // Rellenar el select de Estados
    if (ESTADOS_DISPONIBLES && ESTADOS_DISPONIBLES.length > 0) {
        ESTADOS_DISPONIBLES.forEach(estado => {
            const option = document.createElement('option');
            option.value = estado.id;
            option.textContent = estado.descripcion;
            editEstadoSelect.appendChild(option);
        });
    } else {
        console.warn('ESTADOS_DISPONIBLES no está definido o está vacío. El select de estados no se llenará.');
    }

    // Rellenar el select de Vehículos
    if (VEHICULOS_DISPONIBLES && VEHICULOS_DISPONIBLES.length > 0) {
        VEHICULOS_DISPONIBLES.forEach(vehiculo => {
            const option = document.createElement('option');
            option.value = vehiculo.id;
            option.textContent = vehiculo.display_text;
            editTipoVehiculoSelect.appendChild(option);
        });
    } else {
        console.warn('VEHICULOS_DISPONIBLES no está definido o está vacío. El select de vehículos no se llenará.');
    }


    // --- 2. Listener para el botón "Ver Detalles" (Abrir y Llenar Modal) ---
    document.querySelectorAll('.view-order-details').forEach(button => {
        button.addEventListener('click', function () {
            const envioId = this.dataset.envioId; // Obtener ID del botón

            // Limpiar y preparar el modal para nueva información
            modalEnvioIdDisplay.textContent = envioId; // Mostrar ID en título del modal
            editEnvioIdInput.value = envioId;         // Asignar ID al input oculto del formulario
            modalResponseStatusDiv.innerHTML = '';    // Limpiar mensajes de respuesta anteriores en el modal
            editOrderForm.reset();                    // Limpiar todos los campos del formulario de edición

            // Cargar los detalles del pedido vía AJAX
            fetch(`php_scripts/get_order_details.php?envio_id=${envioId}`)
                .then(async response => {
                    const rawText = await response.text(); // читаем тело только один раз
                    let data;

                    try {
                        data = JSON.parse(rawText); // пробуем распарсить JSON
                    } catch (e) {
                        throw new Error('Respuesta no es JSON válido: ' + rawText);
                    }

                    if (!response.ok) {
                        throw new Error(data.message || 'Error desconocido del servidor.');
                    }

                    return data;
                })

                .then(data => {
                    if (data.error) {
                        // Si el servidor envía un error dentro del JSON
                        modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    } else if (data.details) {
                        const d = data.details; // Alias para los datos de detalles del pedido

                        // Rellenar campos ocultos de IDs de otras tablas
                        editIdClienteInput.value = d.id_cliente || '';
                        editIdDetalleEnvioInput.value = d.id_detalle_envio || '';

                        // Rellenar los campos editables del formulario (Envios)
                        editFechaEnvioInput.value = d.fecha_envio; // Formato YYYY-MM-DD ya viene de PHP
                        editLugarOrigenInput.value = d.lugar_origen;
                        editLugarDestinoInput.value = d.lugar_distinto;
                        editKmInput.value = d.km;
                        editTipoVehiculoSelect.value = d.Vehiculos_vehiculos_id || ''; // Pre-seleccionar vehículo por ID
                        editEstadoSelect.value = d.estado_actual_id; // Pre-seleccionar estado por ID

                        // Rellenar campos editables del Cliente (Clientes)
                        editNombreClienteInput.value = d.nombre_cliente || '';
                        editApellidoClienteInput.value = d.apellido_cliente || '';
                        editDocumentoClienteInput.value = d.numero_documento || '';
                        editTelefonoClienteInput.value = d.telefono || '';
                        displayEmailCliente.textContent = d.cliente_email || 'N/A'; // Email no editable

                        // Rellenar campos editables del Paquete (DetalleEnvio)
                        editPesoKgInput.value = d.peso_kg || '';
                        editLargoCmInput.value = d.largo_cm || '';
                        editAnchoCmInput.value = d.ancho_cm || '';
                        editAltoCmInput.value = d.alto_cm || '';
                        editDescripcionAdicionalInput.value = d.descripcion_adicional_usuario || '';

                        // Campos de visualización (no editables) del paquete
                        displayCostoEstimado.textContent = d.costo_estimado || 'N/A';

                    } else {
                        // Si no hay errores reportados pero 'data.details' está vacío
                        modalResponseStatusDiv.innerHTML = `<div class="alert alert-warning">No se encontraron detalles para este pedido.</div>`;
                    }
                })
                .catch(error => {
                    // Capturar y mostrar cualquier error ocurrido durante el proceso de fetch
                    modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error al cargar detalles: ${error.message}</div>`;
                    console.error('Error cargando detalles del pedido:', error);
                    editOrderForm.reset(); // Limpiar el formulario en caso de un error grave
                });

            orderDetailsModal.show(); // Finalmente, mostrar el modal
        });
    });

    // --- 3. Listener para el envío del formulario del Modal (Guardar Cambios) ---
    editOrderForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevenir el envío tradicional del formulario HTML

        modalResponseStatusDiv.innerHTML = `<div class="alert alert-info">Guardando cambios...</div>`; // Mostrar mensaje de progreso

        // Recolectar IDs
        const envioId = editEnvioIdInput.value;
        const idCliente = editIdClienteInput.value;
        const idDetalleEnvio = editIdDetalleEnvioInput.value;

        // Objeto para todos los datos actualizados, organizado por tabla
        const updatedData = {
            Envios: {},
            Clientes: {},
            DetalleEnvio: {}
        };

        // Datos de Envios
        updatedData.Envios.fecha_envio = editFechaEnvioInput.value;
        updatedData.Envios.lugar_origen = editLugarOrigenInput.value;
        updatedData.Envios.lugar_distinto = editLugarDestinoInput.value;
        updatedData.Envios.km = editKmInput.value;
        updatedData.Envios.Vehiculos_vehiculos_id = editTipoVehiculoSelect.value;
        updatedData.Envios.EstadoEnvio_estado_envio_id1 = editEstadoSelect.value;

        // Datos de Clientes
        updatedData.Clientes.nombre_cliente = editNombreClienteInput.value;
        updatedData.Clientes.apellido_cliente = editApellidoClienteInput.value;
        updatedData.Clientes.numero_documento = editDocumentoClienteInput.value;
        updatedData.Clientes.telefono = editTelefonoClienteInput.value;
        // No se incluye email ya que no es editable en este formulario

        // Datos de DetalleEnvio
        updatedData.DetalleEnvio.peso_kg = editPesoKgInput.value;
        updatedData.DetalleEnvio.largo_cm = editLargoCmInput.value;
        updatedData.DetalleEnvio.ancho_cm = editAnchoCmInput.value;
        updatedData.DetalleEnvio.alto_cm = editAltoCmInput.value;
        updatedData.DetalleEnvio.descripcion_adicional_usuario = editDescripcionAdicionalInput.value; // Este campo va dentro del JSON

        // =========================================================
        // === VALIDACIÓN DE DATOS DEL LADO DEL CLIENTE (Frontend) ===
        // =========================================================
        if (!envioId || parseInt(envioId) <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: ID de envío inválido.</div>`; return; }
        if (!idCliente || parseInt(idCliente) <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: ID de cliente inválido.</div>`; return; }
        if (!idDetalleEnvio || parseInt(idDetalleEnvio) <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: ID de detalles de envío inválido.</div>`; return; }

        // Validación para Envios
        if (!updatedData.Envios.fecha_envio || !/^\d{4}-\d{2}-\d{2}$/.test(updatedData.Envios.fecha_envio)) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Fecha de envío inválida. Use YYYY-MM-DD.</div>`; return; }
        if (!updatedData.Envios.lugar_origen || updatedData.Envios.lugar_origen.trim() === '') { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Origen no puede estar vacío.</div>`; return; }
        if (!updatedData.Envios.lugar_distinto || updatedData.Envios.lugar_distinto.trim() === '') { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Destino no puede estar vacío.</div>`; return; }
        const parsedKm = parseFloat(updatedData.Envios.km);
        if (isNaN(parsedKm) || parsedKm <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: KM debe ser un número positivo.</div>`; return; }
        if (!updatedData.Envios.Vehiculos_vehiculos_id || !VEHICULOS_DISPONIBLES.some(v => v.id == updatedData.Envios.Vehiculos_vehiculos_id)) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Seleccione un tipo de vehículo válido.</div>`; return; }
        if (!updatedData.Envios.EstadoEnvio_estado_envio_id1 || !ESTADOS_DISPONIBLES.some(e => e.id == updatedData.Envios.EstadoEnvio_estado_envio_id1)) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Seleccione un estado válido.</div>`; return; }

        // Validación para Clientes
        if (!updatedData.Clientes.nombre_cliente || updatedData.Clientes.nombre_cliente.trim() === '') { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Nombre del cliente no puede estar vacío.</div>`; return; }
        if (!updatedData.Clientes.apellido_cliente || updatedData.Clientes.apellido_cliente.trim() === '') { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Apellido del cliente no puede estar vacío.</div>`; return; }
        if (!updatedData.Clientes.numero_documento || updatedData.Clientes.numero_documento.trim() === '' || !/^\d+$/.test(updatedData.Clientes.numero_documento.trim())) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Documento del cliente inválido (solo números).</div>`; return; }
        if (!updatedData.Clientes.telefono || updatedData.Clientes.telefono.trim() === '' || !/^\+?\d{8,15}$/.test(updatedData.Clientes.telefono.trim())) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Teléfono del cliente inválido.</div>`; return; }

        // Validación para DetalleEnvio
        const parsedPeso = parseFloat(updatedData.DetalleEnvio.peso_kg);
        if (isNaN(parsedPeso) || parsedPeso <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Peso debe ser un número positivo.</div>`; return; }
        const parsedLargo = parseFloat(updatedData.DetalleEnvio.largo_cm);
        if (isNaN(parsedLargo) || parsedLargo <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Largo debe ser un número positivo.</div>`; return; }
        const parsedAncho = parseFloat(updatedData.DetalleEnvio.ancho_cm);
        if (isNaN(parsedAncho) || parsedAncho <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Ancho debe ser un número positivo.</div>`; return; }
        const parsedAlto = parseFloat(updatedData.DetalleEnvio.alto_cm);
        if (isNaN(parsedAlto) || parsedAlto <= 0) { modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de validación: Alto debe ser un número positivo.</div>`; return; }


        // =========================================================
        // === ENVÍO DE DATOS AL SERVIDOR VÍA AJAX (Backend) ===
        // =========================================================
        fetch('php_scripts/update_shipment_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                envio_id: envioId,
                id_cliente: idCliente,
                id_detalle_envio: idDetalleEnvio,
                data: updatedData // Contiene datos agrupados por tabla
            })
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Error desconocido del servidor.');
                    }).catch(() => {
                        return response.text().then(text => {
                            throw new Error('Respuesta del servidor no es JSON válido o hubo un error: ' + text);
                        });
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    modalResponseStatusDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => {
                        orderDetailsModal.hide();
                        location.reload();
                    }, 1500);
                } else {
                    modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error al guardar cambios: ${error.message}</div>`;
                console.error('Error al guardar cambios:', error);
            });
    });
});