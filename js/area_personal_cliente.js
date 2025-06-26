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

        const hasSufficientNumericalInputs = currentKm > 0 && currentWeight > 0 && currentLength > 0 && currentWidth > 0 && currentHeight > 0 && currentServiceId !== "";

        if (selectedService && hasSufficientNumericalInputs) {
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
            const currentVolumeM3 = (currentLength * currentWidth * currentHeight) / 1000000;

            const pesoCumple = (maxPeso === null || currentWeight <= maxPeso);
            const volumenCumple = (maxVolumen === null || currentVolumeM3 <= maxVolumen);

            if (!pesoCumple || !volumenCumple || totalCost <= 0) { 
                costoEstimadoDisplay.value = 'N/A (No disponible)';
                costoEstimadoDisplay.style.color = 'red';
                costoEstimadoHidden.value = ''; 
                selectedServiceMainIdHidden.value = ''; 
                
                if (selectedTransportDisplay) {
                    selectedTransportDisplay.style.display = 'block';
                    if (selectedTransportIcon) {
                        selectedTransportIcon.className = 'fas fa-exclamation-triangle fa-2x me-3 text-danger';
                    }
                    if (selectedTransportName) {
                        selectedTransportName.textContent = currentServiceName ? currentServiceName + " (No disponible)" : "Datos de paquete inválidos";
                        selectedTransportName.style.color = 'red';
                    }
                }
                newOrderForm.querySelector('button[type="submit"]').disabled = true; 
                return; 
            } else {
                costoEstimadoDisplay.style.color = 'inherit'; 
                if (selectedTransportName) selectedTransportName.style.color = 'inherit';
            }
        } else { // Si no hay servicio seleccionado o inputs numéricos no válidos
            costoEstimadoDisplay.value = "$0.00";
            costoEstimadoDisplay.style.color = 'inherit';
            costoEstimadoHidden.value = "0";
            selectedServiceMainIdHidden.value = "";
            if (selectedTransportDisplay) {
                selectedTransportDisplay.style.display = 'none';
            }
            newOrderForm.querySelector('button[type="submit"]').disabled = true;
            return;
        }
        
        costoEstimadoDisplay.value = `$${totalCost.toFixed(2)}`;
        costoEstimadoHidden.value = totalCost.toFixed(2);
        selectedServiceMainIdHidden.value = currentServiceId;

        if (selectedTransportDisplay) {
            if (selectedService && currentServiceId !== "" && totalCost > 0) {
                selectedTransportDisplay.style.display = 'block';
                if (selectedTransportIcon) {
                    selectedTransportIcon.className = currentIconClass + ' fa-2x me-3';
                }
                if (selectedTransportName) {
                    selectedTransportName.textContent = currentServiceName;
                }
            } else {
                selectedTransportDisplay.style.display = 'none';
            }
        }
        checkVehicleAvailability(); 
    };

    // Función para verificar la disponibilidad del vehículo
    const checkVehicleAvailability = () => {
        const currentServiceId = serviceSelect.value;
        const currentKm = parseFloat(kmInput.value) || 0;
        const currentFechaEnvio = fechaEnvioInput.value; 
        const currentCost = parseFloat(costoEstimadoHidden.value) || 0;

        // --- MODIFICACIÓN CLAVE AQUÍ ---
        if (!currentFechaEnvio || !currentServiceId || currentKm <= 0 || currentCost <= 0) {
            // Si la fecha, servicio, KM o costo no están válidos, simplemente no se puede verificar la disponibilidad.
            // Limpiamos el mensaje y deshabilitamos el botón.
            availabilityMessageDiv.innerHTML = 'Por favor, complete Distancia (KM), Tipo de Envío, Costo Estimado y Fecha de Recogida.';
            availabilityMessageDiv.className = 'alert alert-warning mt-2';
            newOrderForm.querySelector('button[type="submit"]').disabled = true;
            updateServiceOptionsAvailability([]); // Deshabilita todas las opciones de servicio
            return; // Salir de la función
        }
        // --- FIN MODIFICACIÓN ---

        newOrderForm.querySelector('button[type="submit"]').disabled = true;
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
                    throw new Error('Error de red al verificar disponibilidad: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                updateServiceOptionsAvailability(data.available_services_ids); 

                if (data.available_services_ids.includes(parseInt(currentServiceId))) {
                    availabilityMessageDiv.className = 'alert alert-success mt-2';
                    availabilityMessageDiv.innerHTML = "Transporte disponible para esta fecha y tipo de envío.";
                    newOrderForm.querySelector('button[type="submit"]').disabled = false; 
                } else {
                    availabilityMessageDiv.className = 'alert alert-danger mt-2';
                    availabilityMessageDiv.innerHTML = "El tipo de transporte seleccionado no está disponible para esta fecha/distancia.";
                    newOrderForm.querySelector('button[type="submit"]').disabled = true; 
                }
            })
            .catch(error => {
                availabilityMessageDiv.className = 'alert alert-danger mt-2';
                availabilityMessageDiv.innerHTML = `Error al verificar disponibilidad: ${error.message}`;
                newOrderForm.querySelector('button[type="submit"]').disabled = true; 
                console.error('Error en checkVehicleAvailability:', error);
            });
    };

    // Función para actualizar las opciones del select de servicio
    const updateServiceOptionsAvailability = (availableServiceIds) => {
        const currentSelectedServiceId = serviceSelect.value; 
        let isCurrentServiceStillAvailable = false;

        Array.from(serviceSelect.options).forEach(option => {
            if (option.value === "") { 
                option.disabled = false;
                return;
            }
            const serviceId = parseInt(option.value);
            if (availableServiceIds.includes(serviceId)) {
                option.disabled = false;
                option.classList.remove('text-muted');
                if (serviceId === parseInt(currentSelectedServiceId)) {
                    isCurrentServiceStillAvailable = true;
                }
            } else {
                option.disabled = true;
                option.classList.add('text-muted');
            }
        });

        if (!isCurrentServiceStillAvailable && currentSelectedServiceId !== "" && parseFloat(costoEstimadoHidden.value) > 0) {
            availabilityMessageDiv.className = 'alert alert-warning mt-2';
            availabilityMessageDiv.innerHTML = "El tipo de envío previamente seleccionado ya no está disponible para estas condiciones. Por favor, seleccione otra opción o fecha.";
            serviceSelect.value = ""; 
            calculateAndUpdateCost(); 
        }
    };


    // Lógica de autocompletado y limpieza al cargar la página
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

    // Autocompletar desde parámetros de URL y activar cálculo inicial
    if (initialServiceId) {
        serviceSelect.value = initialServiceId;
    }
    
    const isAnyRelevantFieldFilled = (lugarOrigenInput.value || lugarDestinoInput.value || (kmInput.value > 0) || (weightInput.value > 0) || (lengthInput.value > 0) || (widthInput.value > 0) || (heightInput.value > 0) || (serviceSelect.value !== ""));

    if (initialServiceId || isAnyRelevantFieldFilled) {
        if (initialServiceId) {
             selectedServiceMainIdHidden.value = initialServiceId;
        }
        calculateAndUpdateCost(); 
    } else {
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
    fechaEnvioInput.addEventListener('change', checkVehicleAvailability); 
    kmInput.addEventListener('input', checkVehicleAvailability); 
    weightInput.addEventListener('input', checkVehicleAvailability);
    lengthInput.addEventListener('input', checkVehicleAvailability);
    widthInput.addEventListener('input', checkVehicleAvailability);
    heightInput.addEventListener('input', checkVehicleAvailability);

    newOrderForm.addEventListener('submit', (e) => {
        if (costoEstimadoDisplay.value.includes('N/A')) {
            alert("No se puede crear el pedido. Los valores de peso o dimensiones exceden los límites del servicio seleccionado.");
            e.preventDefault();
            return;
        }
        if (parseFloat(costoEstimadoHidden.value) <= 0 && serviceSelect.value !== "") {
             alert("No se puede crear el pedido. El costo estimado es $0.00. Por favor, ajuste los valores.");
             e.preventDefault();
             return;
        }
        if (newOrderForm.querySelector('button[type="submit"]').disabled) {
            alert("No se puede crear el pedido. Por favor, asegúrese de que el transporte esté disponible para la fecha y condiciones seleccionadas.");
            e.preventDefault();
            return;
        }
    });
});