// js/calculator_precio.js
// Este código gestiona toda la lógica en la página calculadora.php

document.addEventListener('DOMContentLoaded', () => {
    const calculatorForm = document.querySelector('.calculator-form');
    const calculateBtn = document.getElementById('calculateBtn');
    const resultDiv = document.getElementById('calculationResult');
    
    const transportImages = {
        'motocicleta': 'img/moto.png', 
        'furgoneta': 'img/furgoneta.png',
        'camión': 'img/camion.png',
        'pickup': 'img/pickup.png',
        'default': 'img/default.png' 
    };

    let servicesCalculatedData = []; 
    fetch('php_scripts/get_calculation_data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error de red o del servidor: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            servicesCalculatedData = data; 
            console.log('--- LOG: Datos de servicios cargados y agrupados (desde PHP) ---', servicesCalculatedData);
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger">No se pudieron cargar las opciones de envío. Causa: ${error.message}</div>`;
            console.error('--- LOG: Error cargando datos de servicios ---', error);
        });

    calculateBtn.addEventListener('click', () => {
        if (!calculatorForm.checkValidity()) {
            calculatorForm.reportValidity();
            return;
        }

        const km = parseFloat(document.getElementById('km').value);
        const weight = parseFloat(document.getElementById('weight').value);
        const length = parseFloat(document.getElementById('length').value);
        const width = parseFloat(document.getElementById('width').value);
        const height = parseFloat(document.getElementById('height').value);

        console.log('--- LOG: Valores de entrada del usuario ---', { km, weight, length, width, height });
        
        if (isNaN(km) || isNaN(weight) || isNaN(length) || isNaN(width) || isNaN(height) || km <= 0 || weight <= 0) {
            resultDiv.innerHTML = `<div class="alert alert-danger">Por favor, complete todos los campos con valores numéricos válidos y mayores que cero para KM y Peso.</div>`;
            return;
        }

        const volumeM3 = (length * width * height) / 1000000;
        
        const finalAvailableServices = servicesCalculatedData.filter(service => {
            const maxPeso = service.capacidades.max_peso_kg;
            const maxVolumen = service.capacidades.max_volumen_m3;
            
            const pesoCumple = (maxPeso === null || weight <= maxPeso);
            const volumenCumple = (maxVolumen === null || volumeM3 <= maxVolumen);
            
            if (!pesoCumple || !volumenCumple) {
                return false;
            }

            const baseCost = service.costo_base || 0;
            const costPerKm = service.costo_por_km || 0;
            const costPerKg = service.costo_por_kg || 0;
            
            const distanceComponent = km * costPerKm;
            const weightComponent = weight * costPerKg;
            
            service.totalCost = baseCost + distanceComponent + weightComponent; 

            return service.totalCost > 0 || (baseCost > 0 && costPerKm === 0 && costPerKg === 0);
        });

        console.log('--- LOG: Servicios disponibles después del filtro (JS) ---', finalAvailableServices);

        resultDiv.innerHTML = '';
        if (finalAvailableServices.length === 0) {
            resultDiv.innerHTML = `<div class="alert alert-warning">Lo sentimos, no hay servicios disponibles para el peso o tamaño de su paquete.</div>`;
            return;
        }
        
        let resultsHTML = `<h4>Seleccione una opción de envío:</h4><div class="services-options">`;
        let servicesDisplayedCount = 0;

        finalAvailableServices.forEach(service => {
            servicesDisplayedCount++;

            const iconClass = service.icon_class || 'fas fa-box';
            
            resultsHTML += `
                <div class="service-card" 
                     data-service-id="${service.main_servicio_id}" 
                     data-cost="${service.totalCost.toFixed(2)}"
                     data-service-name="${service.nombre_servicio}" 
                     data-icon-class="${iconClass}">
                    <i class="${iconClass} service-icon"></i> 
                    <span class="service-name">${service.nombre_servicio}</span> 
                    <span class="service-price">$${service.totalCost.toFixed(2)}</span> </div>
            `;
        });
        resultsHTML += `</div>`;
        resultDiv.innerHTML = resultsHTML;

        if (servicesDisplayedCount === 0) {
            resultDiv.innerHTML = `<div class="alert alert-warning">No se pudieron calcular costos válidos para los servicios disponibles.</div>`;
        }
    });

   
    const saveCalculatorData = () => {
        if (!calculatorForm.checkValidity()) {
            calculatorForm.reportValidity();
            return false;
        }
        const formData = new FormData(calculatorForm);
        const dataToSave = {};
        formData.forEach((value, key) => {
            dataToSave[key] = value;
        });

        console.log('--- LOG: Datos guardados en sessionStorage ---', dataToSave);
        sessionStorage.setItem('calculatorData', JSON.stringify(dataToSave));
        return true;
    };

    // Manejar el clic en una tarjeta de servicio
    resultDiv.addEventListener('click', (e) => {
        const selectedCard = e.target.closest('.service-card');
        if (!selectedCard) return;

        if (!saveCalculatorData()) {
            return;
        }

        const serviceId = selectedCard.dataset.serviceId;
        const costoEstimado = selectedCard.dataset.cost;
        const serviceName = selectedCard.dataset.serviceName; 
        const iconClass = selectedCard.dataset.iconClass; 

        console.log('--- LOG: Datos de servicio seleccionado para redirección ---', { serviceId, costoEstimado, serviceName, iconClass });
        
        const targetUrl = `area_personal_cliente.php?service_id=${serviceId}&costo_estimado=${costoEstimado}&service_name=${encodeURIComponent(serviceName)}&icon_class=${encodeURIComponent(iconClass)}`;
        console.log('--- LOG: URL para redirección ---', targetUrl);

        if (typeof IS_USER_LOGGED_IN !== 'undefined' && IS_USER_LOGGED_IN) {
            window.location.href = targetUrl;
        } else {
            const redirectParam = encodeURIComponent(targetUrl);
            window.location.href = `login.php?redirect=${redirectParam}`;
        }
    });
});