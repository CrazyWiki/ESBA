// js/calculator.js

document.addEventListener('DOMContentLoaded', () => {
    const calculatorForm = document.querySelector('.calculator-form'); // Получаем саму форму
    const calculateBtn = document.getElementById('calculateBtn');
    const resultDiv = document.getElementById('calculationResult');
    let servicesData = [];

    // 1. Загрузка сервисов (без изменений)
    fetch('php_scripts/get_services.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            servicesData = data;
        })
        .catch(error => {
            console.error('Error fetching services:', error);
            resultDiv.innerHTML = `<div class="alert alert-danger">No se pudieron cargar las opciones de envío. Causa: ${error.message}</div>`;
        });

    // 2. Логика расчета (без изменений)
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
        const availableServices = servicesData.filter(service => {
            const maxPeso = parseFloat(service.max_peso_kg);
            const maxVolumen = parseFloat(service.max_volumen_m3);
            return weight <= maxPeso && volumeM3 <= maxVolumen;
        });

        resultDiv.innerHTML = '';
        if (availableServices.length === 0) {
            resultDiv.innerHTML = `<div class="alert alert-warning">Lo sentimos, no hay servicios disponibles para el peso o tamaño de su paquete.</div>`;
            return;
        }

        let resultsHTML = `<h4>Seleccione una opción de envío:</h4><div class="services-options">`;
        availableServices.forEach(service => {
            const distanceCost = km * parseFloat(service.costo_km);
            const weightCost = weight * parseFloat(service.costo_kg);
            const totalCost = parseFloat(service.tarifa_base) + distanceCost + weightCost;

            resultsHTML += `
                <div class="service-card" data-service-id="${service.id_servicio}" data-cost="${totalCost.toFixed(2)}">
                    <i class="${service.icon_class} service-icon"></i>
                    <span class="service-name">${service.nombre}</span>
                    <span class="service-price">$${totalCost.toFixed(2)}</span>
                </div>
            `;
        });
        resultsHTML += `</div>`;
        resultDiv.innerHTML = resultsHTML;
    });


    // ===============================================================
    // === НОВАЯ, УЛУЧШЕННАЯ ЛОГИКА ОБРАБОТКИ КЛИКА                ===
    // ===============================================================

    /**
     * Функция для сбора и сохранения данных формы в sessionStorage.
     */
    const saveCalculatorData = () => {
        if (!calculatorForm.checkValidity()) {
            calculatorForm.reportValidity();
            return false; // Сообщаем, что сохранение не удалось
        }
        const formData = new FormData(calculatorForm);
        const dataToSave = {};
        formData.forEach((value, key) => {
            dataToSave[key] = value;
        });

        sessionStorage.setItem('calculatorData', JSON.stringify(dataToSave));
        console.log('Данные калькулятора сохранены:', dataToSave);
        return true; // Сохранение успешно
    };

    // --- Обработка клика по карточке сервиса ---
    resultDiv.addEventListener('click', (e) => {
        const selectedCard = e.target.closest('.service-card');
        if (!selectedCard) return;

        // 1. Сначала сохраняем данные формы. Если форма невалидна, прерываемся.
        if (!saveCalculatorData()) {
            return;
        }

        // 2. Получаем данные о выбранном сервисе
        const serviceId = selectedCard.dataset.serviceId;
        const cost = selectedCard.dataset.cost;

        // 3. Формируем URL для конечной страницы (личного кабинета)
        const targetUrl = `area-personal.php?service_id=${serviceId}&costo_estimado=${cost}`;

        // 4. Проверяем, залогинен ли пользователь
        if (IS_USER_LOGGED_IN) {
            // Если да, просто переходим на конечную страницу
            window.location.href = targetUrl;
        } else {
            // Если нет, формируем ссылку на логин с параметром редиректа
            // Мы кодируем конечный URL, чтобы он безопасно передался как параметр
            const redirectParam = encodeURIComponent(targetUrl);
            window.location.href = `login.php?redirect=${redirectParam}`;
        }
    });
});