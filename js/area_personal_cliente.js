// js/area_personal_cliente.js
// Este script gestiona la lógica de cálculo y disponibilidad en la página area_personal_cliente.php

document.addEventListener('DOMContentLoaded', function () {
    const newOrderForm = document.getElementById('newOrderForm');
    const lugarOrigenInput = document.getElementById('lugar_origen');
    const lugarDestinoInput = document.getElementById('lugar_destino');
    const kmInput = document.getElementById('km');
    const weightInput = document.getElementById('weight');
    const lengthInput = document.getElementById('length');
    const widthInput = document.getElementById('width');
    const heightInput = document.getElementById('height');
    const descripcionInput = document.getElementById('descripcion');
    const serviceSelect = document.getElementById('id_servicio');
    const fechaEnvioInput = document.getElementById('fecha_envio'); 
    const costoEstimadoDisplay = document.getElementById('costo_estimado_display');
    const costoEstimadoHidden = document.getElementById('costo_estimado_hidden');
    const selectedServiceMainIdHidden = document.getElementById('selected_service_main_id_hidden');
    
    const selectedTransportDisplay = document.getElementById('selected_transport_display');
    const selectedTransportIcon = document.getElementById('selected_transport_icon');
    const selectedTransportName = document.getElementById('selected_transport_name');
    const availabilityMessageDiv = document.createElement('div');
    availabilityMessageDiv.className = 'alert mt-2';
    if (fechaEnvioInput && fechaEnvioInput.parentNode && fechaEnvioInput.nextSibling) {
        fechaEnvioInput.parentNode.insertBefore(availabilityMessageDiv, fechaEnvioInput.nextSibling);
    } else if (fechaEnvioInput && fechaEnvioInput.parentNode) {
        fechaEnvioInput.parentNode.appendChild(availabilityMessageDiv);
    }

    // Función para calcular y actualizar el costo
    const calculateAndUpdateCost = () => {
        const currentKm = parseFloat(kmInput.value) || 0;
        const currentWeight = parseFloat(weightInput.value) || 0;
        const currentLength = parseFloat(lengthInput.value) || 0;
        const currentWidth = parseFloat(widthInput.value) || 0;
        const currentHeight = parseFloat(heightInput.value) || 0;
        const currentServiceId = serviceSelect.value; 

        const selectedService = SERVICES_CALCULATOR_DATA.find(s => s.main_servicio_id == currentServiceId);

        let totalCost = 0;
        let currentServiceName = '';
        let currentIconClass = 'fas fa-box'; 

        // Validar que TODOS los inputs numéricos necesarios para el cálculo estén > 0
        const hasAllRequiredNumericalInputs = currentKm > 0 && currentWeight > 0 && currentLength > 0 && currentWidth > 0 && currentHeight > 0;
        
        if (selectedService && hasAllRequiredNumericalInputs && currentServiceId !== "") {
            const baseCost = selectedService.costo_base || 0;
            const costPerKm = selectedService.costo_por_km || 0;
            const costPerKg = selectedService.costo_por_kg || 0;

            const distanceComponent = currentKm * costPerKm;
            const weightComponent = currentWeight * costPerKg;
            
            totalCost = baseCost + distanceComponent + weightComponent;
            currentServiceName = selectedService.nombre_servicio;
            currentIconClass = selectedService.icon_class;

            const maxPeso = selectedService.capacidades.max_peso_kg;
            const maxVolumen = selectedService.capacidades.max_volumen_m3;
            const currentVolumeM3 = (currentLength * currentWidth * currentHeight) / 1000000; // Convertir cm³ a m³

            const pesoCumple = (maxPeso === null || currentWeight <= maxPeso);
            const volumenCumple = (maxVolumen === null || currentVolumeM3 <= maxVolumen);

            // Si los límites de capacidad se exceden O el costo calculado es 0/negativo
            if (!pesoCumple || !volumenCumple || totalCost <= 0) { 
                costoEstimadoDisplay.value = 'N/A (Excede límites/Inválido)'; // Mensaje más específico
                costoEstimadoDisplay.style.color = 'red';
                costoEstimadoHidden.value = ''; 
                selectedServiceMainIdHidden.value = ''; 
                
                if (selectedTransportDisplay) {
                    selectedTransportDisplay.style.display = 'block';
                    if (selectedTransportIcon) {
                        selectedTransportIcon.className = 'fas fa-exclamation-triangle fa-2x me-3 text-danger';
                    }
                    if (selectedTransportName) {
                        selectedTransportName.textContent = currentServiceName ? currentServiceName + " (Excede límites)" : "Paquete inválido para servicio";
                        selectedTransportName.style.color = 'red';
                    }
                }
                newOrderForm.querySelector('button[type="submit"]').disabled = true; // Desactivar botón
            } else {
                costoEstimadoDisplay.value = `$${totalCost.toFixed(2)}`;
                costoEstimadoDisplay.style.color = 'inherit'; 
                costoEstimadoHidden.value = totalCost.toFixed(2);
                selectedServiceMainIdHidden.value = currentServiceId;
                
                if (selectedTransportDisplay) {
                    selectedTransportDisplay.style.display = 'block';
                    if (selectedTransportIcon) {
                        selectedTransportIcon.className = currentIconClass + ' fa-2x me-3';
                    }
                    if (selectedTransportName) {
                        selectedTransportName.textContent = currentServiceName;
                        selectedTransportName.style.color = 'inherit';
                    }
                }
            }
        } else { // Si no hay servicio seleccionado o inputs numéricos no válidos (<=0)
            costoEstimadoDisplay.value = "$0.00";
            costoEstimadoDisplay.style.color = 'inherit';
            costoEstimadoHidden.value = "0";
            selectedServiceMainIdHidden.value = "";
            if (selectedTransportDisplay) {
                selectedTransportDisplay.style.display = 'none';
            }
            newOrderForm.querySelector('button[type="submit"]').disabled = true; // Desactivar botón
        }
        // Siempre llamar a la verificación de disponibilidad después de actualizar el costo
        // para que la disponibilidad se verifique cuando cambian los inputs de costo.
        checkVehicleAvailability();
    };

    // Función para verificar la disponibilidad del vehículo (AJAX)
    const checkVehicleAvailability = () => {
        const currentServiceId = serviceSelect.value;
        const currentKm = parseFloat(kmInput.value) || 0;
        const currentFechaEnvio = fechaEnvioInput.value; 
        const currentCost = parseFloat(costoEstimadoHidden.value) || 0; // Se espera > 0 si el cálculo fue exitoso

        // Si los campos obligatorios para el cálculo de disponibilidad no son válidos (incluyendo el costo)
        if (!currentFechaEnvio || !currentServiceId || currentKm <= 0 || currentCost <= 0 || costoEstimadoDisplay.value.includes('N/A')) {
            availabilityMessageDiv.innerHTML = 'Por favor, complete todos los campos de paquete/envío requeridos.';
            availabilityMessageDiv.className = 'alert alert-warning mt-2';
            newOrderForm.querySelector('button[type="submit"]').disabled = true;
            // No deshabilitar opciones aquí, ya que calculateAndUpdateCost las manejará
            return; 
        }

        newOrderForm.querySelector('button[type="submit"]').disabled = true; // Desactivar mientras se verifica
        availabilityMessageDiv.className = 'alert alert-info mt-2';
        availabilityMessageDiv.innerHTML = 'Verificando disponibilidad de transporte...';

        const queryParams = new URLSearchParams({
            service_id: currentServiceId,
            km: currentKm,
            fecha: currentFechaEnvio
        }).toString();

        fetch(`php_scripts/get_available_services_for_date.php?${queryParams}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Error desconocido.'); }).catch(() => { throw new Error('Respuesta no JSON válida.'); });
                }
                return response.json();
            })
            .then(data => {
                // Actualiza las opciones del SELECT de servicio basándose en la disponibilidad.
                updateServiceOptionsAvailability(data.available_services_ids);

                // Comprobar si el servicio actualmente seleccionado sigue estando disponible DESPUÉS de actualizar las opciones.
                // Esto es crucial si el usuario lo seleccionó antes de que se deshabilitara.
                const selectedOptionIsNowAvailable = data.available_services_ids.includes(parseInt(currentServiceId));

                if (selectedOptionIsNowAvailable) {
                    availabilityMessageDiv.className = 'alert alert-success mt-2';
                    availabilityMessageDiv.innerHTML = "Transporte disponible para esta fecha y tipo de envío.";
                    newOrderForm.querySelector('button[type="submit"]').disabled = false; // Habilitar si está disponible
                } else {
                    availabilityMessageDiv.className = 'alert alert-danger mt-2';
                    availabilityMessageDiv.innerHTML = "El tipo de transporte seleccionado NO está disponible para esta fecha/distancia. Por favor, seleccione otra opción.";
                    newOrderForm.querySelector('button[type="submit"]').disabled = true; // Deshabilitar
                    serviceSelect.value = ""; // Deseleccionar el servicio si ya no es válido
                    calculateAndUpdateCost(); // Recalcular para actualizar el estado del formulario
                }
            })
            .catch(error => {
                availabilityMessageDiv.className = 'alert alert-danger mt-2';
                availabilityMessageDiv.innerHTML = `Error al verificar disponibilidad: ${error.message}`;
                newOrderForm.querySelector('button[type="submit"]').disabled = true;
                console.error('Error en checkVehicleAvailability:', error);
            });
    };

    // Función para actualizar las opciones del select de servicio (habilita/deshabilita visualmente)
    const updateServiceOptionsAvailability = (availableServiceIds) => {
        const currentSelectedServiceId = serviceSelect.value;
        let isCurrentServiceStillAvailable = false;

        Array.from(serviceSelect.options).forEach(option => {
            if (option.value === "") { // Siempre permitir la opción vacía
                option.disabled = false;
                return;
            }
            const serviceId = parseInt(option.value);
            // Si el servicio está en la lista de disponibles O si su costo_base/por_km/por_kg es 0 (no se puede calcular)
            // se considera "no disponible" a nivel de cálculo o capacidad.
            const serviceData = SERVICES_CALCULATOR_DATA.find(s => s.main_servicio_id == serviceId);
            const isCalculable = serviceData && (serviceData.costo_base > 0 || serviceData.costo_por_km > 0 || serviceData.costo_por_kg > 0);

            // Validar capacidad para el servicio si los inputs de paquete son válidos
            const currentWeight = parseFloat(weightInput.value) || 0;
            const currentLength = parseFloat(lengthInput.value) || 0;
            const currentWidth = parseFloat(widthInput.value) || 0;
            const currentHeight = parseFloat(heightInput.value) || 0;
            const currentVolumeM3 = (currentLength * currentWidth * currentHeight) / 1000000;

            const pesoCumple = (serviceData.capacidades.max_peso_kg === null || currentWeight <= serviceData.capacidades.max_peso_kg);
            const volumenCumple = (serviceData.capacidades.max_volumen_m3 === null || currentVolumeM3 <= serviceData.capacidades.max_volumen_m3);
            const meetsCapacity = (currentWeight > 0 && currentLength > 0 && currentWidth > 0 && currentHeight > 0) ? (pesoCumple && volumenCumple) : false; // Sólo si las dimensiones son válidas

            // Un servicio es "visualmente disponible" si:
            // 1. Es calculable (tiene costos definidos)
            // 2. Cumple con los límites de peso/volumen del paquete O no se han introducido todos los datos del paquete aún.
            // 3. Está en la lista de IDs disponibles devueltos por el backend (disponibilidad por KM/fecha)
            const isTrulyAvailable = isCalculable && (hasSufficientNumericalInputsForCapacity() ? meetsCapacity : true) && availableServiceIds.includes(serviceId);

            if (isTrulyAvailable) {
                option.disabled = false;
                option.classList.remove('text-muted');
                if (serviceId === parseInt(currentSelectedServiceId)) {
                    isCurrentServiceStillAvailable = true;
                }
            } else {
                option.disabled = true; 
                option.classList.add('text-muted'); 
                if (!isCalculable) {
                    option.textContent = option.textContent + " (Sin tarifa)";
                } else if (!meetsCapacity && hasSufficientNumericalInputsForCapacity()) {
                     option.textContent = option.textContent + " (Paquete excede límites)";
                }
            }
        });

        function hasSufficientNumericalInputsForCapacity() {
            return parseFloat(weightInput.value) > 0 && parseFloat(lengthInput.value) > 0 && parseFloat(widthInput.value) > 0 && parseFloat(heightInput.value) > 0;
        }

        if (!isCurrentServiceStillAvailable && currentSelectedServiceId !== "") {
            availabilityMessageDiv.className = 'alert alert-warning mt-2';
            availabilityMessageDiv.innerHTML = "El tipo de envío previamente seleccionado ya NO está disponible para estas condiciones. Por favor, seleccione otra opción o fecha.";
            serviceSelect.value = "";
            calculateAndUpdateCost(); 
        }
        
        if (serviceSelect.value === "" || !isCurrentServiceStillAvailable || costoEstimadoDisplay.value.includes('N/A')) {
            newOrderForm.querySelector('button[type="submit"]').disabled = true;
        } else {
            
        }
    };


    if (CLEAR_CALCULATOR_DATA_FLAG) {
        sessionStorage.removeItem('calculatorData'); 
    } else {
        const savedDataJSON = sessionStorage.getItem('calculatorData');
        if (savedDataJSON) {
            try {
                const savedData = JSON.parse(savedDataJSON);

                lugarOrigenInput.value = savedData.lugar_origen || '';
                lugarDestinoInput.value = savedData.lugar_destino || '';
                kmInput.value = savedData.km || '';
                weightInput.value = savedData.weight || '';
                lengthInput.value = savedData.length || '';
                widthInput.value = savedData.width || '';
                heightInput.value = savedData.height || '';
                descripcionInput.value = savedData.descripcion || ''; 

            } catch (e) {
                console.error("Error al parsear datos de sessionStorage:", e);
                sessionStorage.removeItem('calculatorData');
            }
        }
    }

    if (initialServiceId) {
        serviceSelect.value = initialServiceId;
    }
    
    const isAnyRelevantFieldFilled = (lugarOrigenInput.value || lugarDestinoInput.value || (kmInput.value > 0) || (weightInput.value > 0) || (lengthInput.value > 0) || (widthInput.value > 0) || (heightInput.value > 0) || (serviceSelect.value !== ""));

    if (initialServiceId || isAnyRelevantFieldFilled) {
        if (initialServiceId) {
            selectedServiceMainIdHidden.value = initialServiceId;
        }
        calculateAndUpdateCost(); // Recalcular con los datos iniciales o guardados
    } else {
        // Si no hay nada inicial, asegurar que el costo sea $0.00 y los displays estén ocultos
        costoEstimadoDisplay.value = "$0.00";
        costoEstimadoHidden.value = "0";
        if (selectedTransportDisplay) {
            selectedTransportDisplay.style.display = 'none';
        }
        newOrderForm.querySelector('button[type="submit"]').disabled = true; 
    }

    // Añadir listeners para recalcular y verificar disponibilidad
    kmInput.addEventListener('input', calculateAndUpdateCost);
    weightInput.addEventListener('input', calculateAndUpdateCost);
    lengthInput.addEventListener('input', calculateAndUpdateCost);
    widthInput.addEventListener('input', calculateAndUpdateCost);
    heightInput.addEventListener('input', calculateAndUpdateCost);
    serviceSelect.addEventListener('change', calculateAndUpdateCost); 
    fechaEnvioInput.addEventListener('change', checkVehicleAvailability); // La fecha afecta solo a la disponibilidad, no al costo directo


    newOrderForm.addEventListener('submit', (e) => {
        
        if (newOrderForm.querySelector('button[type="submit"]').disabled) {
            alert("No se puede crear el pedido. Por favor, asegúrese de que todos los campos obligatorios estén completos, los valores sean válidos y que el transporte esté disponible para la fecha y condiciones seleccionadas.");
            e.preventDefault();
            return;
        }
        // Si todas las validaciones pasan y el botón no está deshabilitado, el formulario se envía.
    });

    calculateAndUpdateCost();


    // --- Lógica de Eliminación de Pedido  ---
    document.querySelectorAll('.delete-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const envioId = this.dataset.envioId; // Obtener el ID del pedido del atributo data-envio-id

            if (confirm(`¿Estás seguro de que quieres eliminar el pedido #${envioId}? Esta acción no se puede deshacer.`)) {
                fetch('php_scripts/delete_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ envio_id: envioId })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'Error desconocido del servidor al eliminar.');
                        }).catch(() => {
                            return response.text().then(text => {
                                throw new Error('Respuesta del servidor no es JSON válida o hubo un error: ' + text);
                            });
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('Error al eliminar el pedido: ' + error.message);
                    console.error('Error al eliminar el pedido:', error);
                });
            } else {
                alert('Eliminación del pedido cancelada.');
            }
        });
    });
});