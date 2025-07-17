document.addEventListener('DOMContentLoaded', () => {
    const calculatorForm = document.querySelector('.calculator-form');
    const calculateBtn = document.getElementById('calculateBtn');
    const resultDiv = document.getElementById('calculationResult');
    
    let servicesCalculatedData = []; 
    fetch('php_scripts/get_calculation_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            servicesCalculatedData = data; 
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="alert alert-danger">No se pudieron cargar las opciones de envío. Causa: ${error.message}</div>`;
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
        
        if (isNaN(km) || isNaN(weight) || isNaN(length) || isNaN(width) || isNaN(height) || km <= 0 || weight <= 0) {
            resultDiv.innerHTML = `<div class="alert alert-danger">Por favor, complete todos los campos con valores numéricos válidos.</div>`;
            return;
        }

        const volumeM3 = (length * width * height) / 1000000;
        
        const finalAvailableServices = servicesCalculatedData.filter(service => {
            const maxPeso = service.capacidades.max_peso_kg;
            const maxVolumen = service.capacidades.max_volumen_m3;
            const pesoCumple = !maxPeso || weight <= maxPeso;
            const volumenCumple = !maxVolumen || volumeM3 <= maxVolumen;
            
            if (!pesoCumple || !volumenCumple) return false;

            const baseCost = service.costo_base || 0;
            const costPerKm = service.costo_por_km || 0;
            const costPerKg = service.costo_por_kg || 0;
            
            service.totalCost = baseCost + (km * costPerKm) + (weight * costPerKg); 
            return service.totalCost > 0;
        });

        if (finalAvailableServices.length === 0) {
            resultDiv.innerHTML = `<div class="alert alert-warning">Lo sentimos, no hay servicios disponibles para el peso o tamaño de su paquete.</div>`;
            return;
        }
        
        let resultsHTML = `<h4>Seleccione una opción de envío:</h4><div class="services-options">`;
        finalAvailableServices.forEach(service => {
            const iconClass = service.icon_class || 'fas fa-box';
            resultsHTML += `
                <div class="service-card" 
                     data-service-id="${service.main_servicio_id}" 
                     data-cost="${service.totalCost.toFixed(2)}"
                     data-service-name="${service.nombre_servicio}" 
                     data-icon-class="${iconClass}">
                    <i class="${iconClass} service-icon"></i> 
                    <span class="service-name">${service.nombre_servicio}</span> 
                    <span class="service-price">$${service.totalCost.toFixed(2)}</span>
                </div>`;
        });
        resultsHTML += `</div>`;
        resultDiv.innerHTML = resultsHTML;
    });

    const saveCalculatorData = () => {
        if (!calculatorForm.checkValidity()) {
            calculatorForm.reportValidity();
            return false;
        }
        const formData = new FormData(calculatorForm);
        const dataToSave = Object.fromEntries(formData.entries());
        sessionStorage.setItem('calculatorData', JSON.stringify(dataToSave));
        return true;
    };

    resultDiv.addEventListener('click', (e) => {
        const selectedCard = e.target.closest('.service-card');
        if (!selectedCard) return;

        if (!saveCalculatorData()) return;

        const { serviceId, cost, serviceName, iconClass } = selectedCard.dataset;
        
        const params = new URLSearchParams({
            service_id: serviceId,
            costo_estimado: cost,
            service_name: serviceName,
            icon_class: iconClass
        });

        const targetUrl = `area_personal_cliente.php?${params.toString()}`;
        
        if (typeof IS_USER_LOGGED_IN !== 'undefined' && IS_USER_LOGGED_IN) {
            window.location.href = targetUrl;
        } else {
            window.location.href = `login.php?redirect=${encodeURIComponent(targetUrl)}`;
        }
    });
});