// public/js/gerente_shipment_management.js
document.addEventListener('DOMContentLoaded', function () {
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const modalEnvioIdDisplay = document.getElementById('modalEnvioId');
    const editOrderForm = document.getElementById('editOrderForm');
    const modalResponseStatusDiv = document.getElementById('modalResponseStatus');
    const displayEmailCliente = document.getElementById('displayEmailCliente');

    // --- НАЧАЛО: НОВЫЙ БЛОК ДЛЯ ДИНАМИЧЕСКОГО ОБНОВЛЕНИЯ ТРАНСПОРТА ---
    
    // Получаем ссылки на элементы, которые будут вызывать обновление
    const editFechaEnvioInput = document.getElementById('editFechaEnvio');
    const editKmInput = document.getElementById('editKm');
    const editTipoVehiculoSelect = document.getElementById('editTipoVehiculo');
    const editEnvioIdInput = document.getElementById('editEnvioId');

    // Функция для обновления выпадающего списка транспорта
    function updateVehicleSelect(availableVehicles) {
        const currentlySelectedId = editTipoVehiculoSelect.value;
        editTipoVehiculoSelect.innerHTML = ''; // Очищаем список

        if (availableVehicles.length === 0) {
            const option = document.createElement('option');
            option.textContent = 'No hay vehículos disponibles';
            editTipoVehiculoSelect.appendChild(option);
            return;
        }

        let isCurrentSelectionStillAvailable = false;
        availableVehicles.forEach(vehicle => {
            const option = document.createElement('option');
            option.value = vehicle.id;
            option.textContent = vehicle.display_text;
            editTipoVehiculoSelect.appendChild(option);
            if (vehicle.id == currentlySelectedId) {
                isCurrentSelectionStillAvailable = true;
            }
        });
        
        // Если ранее выбранный автомобиль все еще доступен, оставляем его выбранным
        if (isCurrentSelectionStillAvailable) {
            editTipoVehiculoSelect.value = currentlySelectedId;
        }
    }

    // Функция, которая запрашивает доступные автомобили у сервера
    function updateAvailableVehicles() {
        const fecha = editFechaEnvioInput.value;
        const km = editKmInput.value;
        const envioId = editEnvioIdInput.value;

        if (!fecha || !km || km <= 0) {
            updateVehicleSelect([]); // Очищаем, если данных недостаточно
            return;
        }

        const url = `php_scripts/get_available_vehicles.php?fecha=${fecha}&km=${km}&current_envio_id=${envioId}`;
        
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Error de red al buscar vehículos.');
                return response.json();
            })
            .then(data => {
                updateVehicleSelect(data);
            })
            .catch(err => {
                console.error("Error al actualizar vehículos:", err);
                updateVehicleSelect([]);
            });
    }

    // Назначаем слушателей событий на поля даты и КМ
    editFechaEnvioInput.addEventListener('change', updateAvailableVehicles);
    editKmInput.addEventListener('input', updateAvailableVehicles);

    // --- КОНЕЦ НОВОГО БЛОКА ---


    // Ваш рабочий код для заполнения статических списков
    if (typeof ESTADOS_DISPONIBLES !== 'undefined' && ESTADOS_DISPONIBLES.length > 0) {
        const editEstadoSelect = document.getElementById('editEstado');
        ESTADOS_DISPONIBLES.forEach(estado => {
            const option = document.createElement('option');
            option.value = estado.id;
            option.textContent = estado.descripcion;
            editEstadoSelect.appendChild(option);
        });
    }

    if (typeof VEHICULOS_DISPONIBLES !== 'undefined' && VEHICULOS_DISPONIBLES.length > 0) {
        VEHICULOS_DISPONIBLES.forEach(vehiculo => {
            const option = document.createElement('option');
            option.value = vehiculo.id;
            option.textContent = vehiculo.display_text;
            editTipoVehiculoSelect.appendChild(option);
        });
    }

    // Ваш рабочий слушатель для кнопки "Ver Detalles"
    document.querySelectorAll('.view-order-details').forEach(button => {
        button.addEventListener('click', function () {
            const envioId = this.dataset.envioId;
            modalEnvioIdDisplay.textContent = envioId;
            editOrderForm.querySelector('#editEnvioId').value = envioId;
            modalResponseStatusDiv.innerHTML = '';
            
            fetch(`php_scripts/get_order_details.php?envio_id=${envioId}`)
                .then(response => {
                    if (!response.ok) { throw new Error('Error de red o del servidor.'); }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    const d = data.details;
                    
                    editOrderForm.querySelector('#editIdCliente').value = d.id_cliente || '';
                    editOrderForm.querySelector('#editIdDetalleEnvio').value = d.id_detalle_envio || '';
                    editOrderForm.querySelector('#editFechaEnvio').value = d.fecha_envio;
                    editOrderForm.querySelector('#editLugarOrigen').value = d.lugar_origen;
                    editOrderForm.querySelector('#editLugarDestino').value = d.lugar_distinto;
                    editOrderForm.querySelector('#editKm').value = d.km;
                    editOrderForm.querySelector('#editTipoVehiculo').value = d.Vehiculos_vehiculos_id || '';
                    editOrderForm.querySelector('#editEstado').value = d.estado_actual_id;
                    editOrderForm.querySelector('#editNombreCliente').value = d.nombre_cliente || '';
                    editOrderForm.querySelector('#editApellidoCliente').value = d.apellido_cliente || '';
                    editOrderForm.querySelector('#editDocumentoCliente').value = d.numero_documento || '';
                    editOrderForm.querySelector('#editTelefonoCliente').value = d.telefono || '';
                    displayEmailCliente.textContent = d.cliente_email || 'N/A';
                    editOrderForm.querySelector('#editPesoKg').value = d.peso_kg || '';
                    editOrderForm.querySelector('#editLargoCm').value = d.largo_cm || '';
                    editOrderForm.querySelector('#editAnchoCm').value = d.ancho_cm || '';
                    editOrderForm.querySelector('#editAltoCm').value = d.alto_cm || '';
                    editOrderForm.querySelector('#editDescripcionAdicional').value = d.descripcion_adicional_usuario || '';
                    editOrderForm.querySelector('#editCostoFinal').value = parseFloat(d.costo_final_corregido || 0).toFixed(2);

                    // --- ВЫЗОВ НОВОЙ ФУНКЦИИ ---
                    // Сразу после заполнения данных, обновляем список доступных автомобилей
                    updateAvailableVehicles();

                })
                .catch(error => {
                    modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error al cargar detalles: ${error.message}</div>`;
                });

            orderDetailsModal.show();
        });
    });

    // Ваш рабочий слушатель для сохранения формы
    editOrderForm.addEventListener('submit', function (e) {
        e.preventDefault();
        modalResponseStatusDiv.innerHTML = `<div class="alert alert-info">Guardando cambios...</div>`;
        const formData = new FormData(editOrderForm);
        fetch('php_scripts/update_shipment_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
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
            modalResponseStatusDiv.innerHTML = `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
        });
    });
});