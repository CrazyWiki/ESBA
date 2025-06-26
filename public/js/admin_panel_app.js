document.addEventListener('DOMContentLoaded', () => {
    const tableSelector = document.getElementById('tableSelector');
    const tableContainer = document.getElementById('adminDynamicTableContainer');
    const addRowBtn = document.getElementById('adminAddNewRowBtn');
    const messageArea = document.getElementById('adminUserMessageArea');

    let currentActiveTable = '';
    let currentTableColumns = [];
    let currentTablePrimaryKey = '';

    const PREDEFINED_ADMIN_ROLES = ['Administrador', 'Conductor', 'Gerente de ventas'];

    function displayAdminMessage(text, type = 'success') {
        messageArea.innerHTML = `<div class="admin-user-message admin-type-${type}">${escapeHtml(text)}</div>`;
        setTimeout(() => { messageArea.innerHTML = ''; }, 7000);
    }

    fetch('php_scripts/list_tables.php')
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Ошибка сервера: ${response.status}`) });
            return response.json();
        })
        .then(data => {
            if (data.success && data.tables && data.tables.length > 0) {
                tableSelector.innerHTML = '<option value="">-- Выберите таблицу --</option>';
                data.tables.forEach(tableName => {
                    const option = document.createElement('option');
                    option.value = tableName; // Имя таблицы как есть из БД (может быть 'administradores')
                    option.textContent = tableName;
                    tableSelector.appendChild(option);
                });
            } else {
                tableSelector.innerHTML = '<option value="">Не удалось загрузить таблицы</option>';
                displayAdminMessage(data.message || 'Не удалось получить список таблиц.', 'error');
            }
        })
        .catch(error => {
            console.error('Критическая ошибка при загрузке списка таблиц:', error);
            tableSelector.innerHTML = '<option value="">Ошибка загрузки</option>';
            displayAdminMessage(`Критическая ошибка (список таблиц): ${error.message}`, 'error');
        });

    tableSelector.addEventListener('change', function() {
        currentActiveTable = this.value; // Здесь будет 'administradores' если так пришло из list_tables.php
        if (currentActiveTable) {
            fetchAndRenderTable(currentActiveTable);
            addRowBtn.classList.remove('admin-hidden');
        } else {
            tableContainer.innerHTML = '<p class="admin-info-text">Пожалуйста, выберите таблицу из выпадающего списка выше.</p>';
            addRowBtn.classList.add('admin-hidden');
            currentTableColumns = [];
            currentTablePrimaryKey = '';
        }
    });

    function fetchAndRenderTable(tableName) {
        tableContainer.innerHTML = '<p class="admin-info-text">Загрузка данных таблицы...</p>';
        fetch(`php_scripts/load_table.php?table=${encodeURIComponent(tableName)}`)
            .then(response => {
                if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Ошибка сервера: ${response.status}`) });
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    currentTableColumns = data.columns;
                    currentTablePrimaryKey = data.primaryKey;
                    renderHtmlTableUI(data.columns, data.rows, data.primaryKey);
                } else {
                    displayAdminMessage(data.message || 'Не удалось загрузить данные таблицы.', 'error');
                    tableContainer.innerHTML = `<p class="admin-info-text admin-type-error">${escapeHtml(data.message || 'Ошибка загрузки.')}</p>`;
                }
            })
            .catch(error => {
                console.error(`Ошибка при загрузке таблицы "${tableName}":`, error);
                displayAdminMessage(`Ошибка при загрузке таблицы "${tableName}": ${error.message}`, 'error');
                tableContainer.innerHTML = `<p class="admin-info-text admin-type-error">Ошибка: ${escapeHtml(error.message)}</p>`;
            });
    }

    function renderHtmlTableUI(columnsMeta, rowsData, pkField) {
        console.log("Rendering table for:", currentActiveTable, "PK:", pkField);
        console.log("Columns Meta:", columnsMeta);

        if (!columnsMeta || columnsMeta.length === 0) {
            tableContainer.innerHTML = '<p class="admin-info-text">Структура таблицы не определена или таблица пуста.</p>';
            return;
        }

        let tableHtml = '<table class="admin-data-display-table"><thead><tr>';
        columnsMeta.forEach(colMeta => {
            tableHtml += `<th>${escapeHtml(colMeta.Field)} ${colMeta.Field === pkField ? '<span class="pk-marker">(PK)</span>' : ''}</th>`;
        });
        tableHtml += '<th>Действия</th></tr></thead><tbody>';

        const activeTableLower = currentActiveTable.toLowerCase(); // Приводим к нижнему регистру для сравнения

        rowsData.forEach(row => {
            const pkValue = row[pkField];
            const isNewUnsavedRow = pkValue === null || pkValue === undefined;

            tableHtml += `<tr data-pk-value="${pkValue ? escapeHtml(pkValue) : ''}">`;

            columnsMeta.forEach(colMeta => {
                const columnName = colMeta.Field; // Оригинальное имя колонки из БД
                const columnNameLower = columnName.toLowerCase(); // Для сравнения
                let cellValue = row[columnName];
                let cellInteriorHtml = '';
                let tdAttributes = `data-td-for-column="${escapeHtml(columnName)}"`;
                const isPKAndAutoIncrement = (columnName === pkField) && colMeta.Extra && colMeta.Extra.toLowerCase().includes('auto_increment');
                let isContentEditable = !isPKAndAutoIncrement;

                if (activeTableLower === 'administradores') {
                    if (columnNameLower === 'role') { // Используем имя колонки в нижнем регистре для сравнения
                        console.log(`Rendering 'role' column for 'administradores'. Current value: ${cellValue}`);
                        let selectHtml = `<select data-column-name="${escapeHtml(columnName)}" class="admin-role-select">`;
                        PREDEFINED_ADMIN_ROLES.forEach(role => {
                            selectHtml += `<option value="${escapeHtml(role)}" ${cellValue === role ? 'selected' : ''}>${escapeHtml(role)}</option>`;
                        });
                        selectHtml += `</select>`;
                        cellInteriorHtml = selectHtml;
                        isContentEditable = false; 
                    } else if (columnNameLower === 'password') { // Используем имя колонки в нижнем регистре для сравнения
                        console.log("Rendering 'password' column for 'administradores'.");
                        cellInteriorHtml = isNewUnsavedRow ? '' : '********';
                        isContentEditable = true; 
                        tdAttributes += ` data-column-name="${escapeHtml(columnName)}"`;
                    }
                }
                
                if (!cellInteriorHtml) {
                    cellInteriorHtml = (cellValue !== null && cellValue !== undefined) ? escapeHtml(cellValue) : '';
                    if (isContentEditable) {
                        tdAttributes += ` data-column-name="${escapeHtml(columnName)}"`;
                    }
                }
                tableHtml += `<td ${tdAttributes} ${isContentEditable ? 'contenteditable="true"' : ''}>${cellInteriorHtml}</td>`;
            });

            tableHtml += `<td class="admin-row-action-buttons">
                            <button class="admin-save-button" title="Сохранить изменения">💾</button>
                            <button class="admin-delete-button" title="Удалить строку">🗑️</button>
                         </td>`;
            tableHtml += '</tr>';
        });

        tableHtml += '</tbody></table>';
        tableContainer.innerHTML = tableHtml;
    }
    
    function escapeHtml(unsafeText) {
        if (unsafeText === null || typeof unsafeText === 'undefined') return '';
        return String(unsafeText).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    addRowBtn.addEventListener('click', () => {
        if (!currentActiveTable) {
            displayAdminMessage('Пожалуйста, сначала выберите таблицу.', 'error');
            return;
        }
        fetch('php_scripts/add_row.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table: currentActiveTable })
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Ошибка сервера ${response.status}`) });
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayAdminMessage(data.message || 'Новая строка подготовлена. Заполните ее и сохраните.');
                fetchAndRenderTable(currentActiveTable);
            } else {
                displayAdminMessage(data.message || 'Не удалось добавить строку.', 'error');
            }
        })
        .catch(error => {
            console.error('Ошибка при добавлении строки:', error);
            displayAdminMessage(`Ошибка при добавлении строки: ${error.message}`, 'error');
        });
    });

    tableContainer.addEventListener('click', function(event) {
        const targetButton = event.target.closest('button');
        if (!targetButton) return;

        const tableRowElement = targetButton.closest('tr');
        if (!tableRowElement) return;

        const pkValueForRow = tableRowElement.dataset.pkValue;

        if (targetButton.classList.contains('admin-save-button')) {
            const rowDataObject = {};
            let isNewPasswordEntered = false;
            console.log("Сохранение строки. PK:", pkValueForRow, "Таблица:", currentActiveTable);

            const activeTableLowerForSave = currentActiveTable.toLowerCase(); // Для сравнения

            tableRowElement.querySelectorAll('td[contenteditable="true"][data-column-name], select[data-column-name]').forEach(inputElement => {
                const columnName = inputElement.dataset.columnName; // Оригинальное имя колонки из data-атрибута
                const columnNameLowerForSave = columnName.toLowerCase(); // Для сравнения
                let value;

                if (inputElement.tagName === 'SELECT') {
                    value = inputElement.value;
                } else { 
                    value = inputElement.textContent;
                }
                
                console.log(`Сбор данных: Колонка '${columnName}', Значение до обработки: '${value}'`);

                if (activeTableLowerForSave === 'administradores' && columnNameLowerForSave === 'password') {
                    console.log("Обработка колонки ПАРОЛЬ для таблицы administradores. Введенное значение:", value);
                    if (value.trim() !== '' && value.trim() !== '********') { // Добавил trim() и для '********'
                        rowDataObject[columnName] = value.trim(); // Отправляем очищенный пароль
                        isNewPasswordEntered = true;
                        console.log("Установлен флаг isNewPasswordEntered = true. Пароль для отправки:", value.trim());
                    } else {
                        console.log("Пароль не меняется (пусто или '********').");
                    }
                } else {
                    rowDataObject[columnName] = value;
                }
            });
            
            if (!rowDataObject[currentTablePrimaryKey] && pkValueForRow !== undefined && pkValueForRow !== '') {
                 rowDataObject[currentTablePrimaryKey] = pkValueForRow;
            }
            console.log("Данные для отправки на сервер:", JSON.stringify(rowDataObject)); // Показываем объект перед отправкой
            console.log("isNewPasswordProvided флаг:", isNewPasswordEntered);


            fetch('php_scripts/save_row.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table: currentActiveTable,
                    pkField: currentTablePrimaryKey,
                    data: rowDataObject,
                    isNewPasswordProvided: isNewPasswordEntered // Этот флаг критичен
                })
            })
            .then(response => {
                if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Ошибка сервера ${response.status}`) });
                return response.json();
            })
            .then(data => {
                console.log("Ответ от save_row.php:", data);
                if (data.success) {
                    displayAdminMessage(data.message || 'Строка успешно сохранена.');
                    targetButton.style.backgroundColor = 'lightgreen';
                    setTimeout(() => { targetButton.style.backgroundColor = ''; }, 2000);
                    
                    if (activeTableLowerForSave === 'administradores' && isNewPasswordEntered) {
                        const passwordCell = tableRowElement.querySelector('td[data-column-name="password"], td[data-td-for-column="password"]'); // Уточнил селектор
                        if (passwordCell) {
                            passwordCell.textContent = '********';
                        }
                    }
                } else {
                    displayAdminMessage(data.message || 'Не удалось сохранить строку.', 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка при сохранении строки:', error);
                displayAdminMessage(`Ошибка при сохранении: ${error.message}`, 'error');
            });
        }

        if (targetButton.classList.contains('admin-delete-button')) {
            if (confirm(`Вы уверены, что хотите удалить строку с PK '${pkValueForRow}' из таблицы '${currentActiveTable}'?`)) {
                fetch('php_scripts/delete_row.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        table: currentActiveTable,
                        pkField: currentTablePrimaryKey,
                        id: pkValueForRow
                    })
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Ошибка сервера ${response.status}`) });
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayAdminMessage(data.message || 'Строка успешно удалена.');
                        tableRowElement.remove();
                    } else {
                        displayAdminMessage(data.message || 'Не удалось удалить строку.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Ошибка при удалении строки:', error);
                    displayAdminMessage(`Ошибка при удалении: ${error.message}`, 'error');
                });
            }
        }
    });
});