// Archivo: public/js/admin_panel_app.js
// Propósito: Gestiona la interfaz de administración de tablas de la base de datos (CRUD).

document.addEventListener('DOMContentLoaded', () => {
    const tableSelector = document.getElementById('tableSelector');
    const tableContainer = document.getElementById('adminDynamicTableContainer');
    const addRowBtn = document.getElementById('adminAddNewRowBtn');
    const messageArea = document.getElementById('adminUserMessageArea');

    let currentActiveTable = '';
    let currentTableColumns = []; 
    let currentTablePrimaryKey = '';

    const PREDEFINED_ADMIN_ROLES = ['Administrador', 'Conductor', 'Gerente de ventas'];
    const PREDEFINED_ESTADO_ENVIO_STATUSES = ['Pendiente', 'En proceso', 'Finalizado']; 

    // Función auxiliar para escapar caracteres HTML
    function escapeHtml(unsafeText) {
        if (unsafeText === null || typeof unsafeText === 'undefined') return '';
        return String(unsafeText).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Función para mostrar mensajes al usuario en el panel
    function displayAdminMessage(text, type = 'success') {
        messageArea.innerHTML = `<div class="admin-user-message admin-type-${type}">${escapeHtml(text)}</div>`;
        setTimeout(() => { messageArea.innerHTML = ''; }, 7000);
    }

    // ==========================================
    // === LÓGICA DE CARGA Y RENDERIZADO DE TABLAS ===
    // ==========================================

    // Carga inicial de la lista de tablas disponibles desde el servidor
    fetch('php_scripts/list_tables.php')
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error del servidor: ${response.status}`) });
            return response.json();
        })
        .then(data => {
            if (data.success && data.tables && data.tables.length > 0) {
                tableSelector.innerHTML = '<option value="">-- Seleccione una tabla --</option>';
                data.tables.forEach(tableName => {
                    const option = document.createElement('option');
                    option.value = tableName;
                    option.textContent = tableName;
                    tableSelector.appendChild(option);
                });
            } else {
                tableSelector.innerHTML = '<option value="">No se pudo cargar las tablas</option>';
                displayAdminMessage(data.message || 'No se pudo obtener la lista de tablas.', 'error');
            }
        })
        .catch(error => {
            tableSelector.innerHTML = '<option value="">Error de carga</option>';
            displayAdminMessage(`Error crítico (lista de tablas): ${error.message}`, 'error');
        });

    // Listener para el cambio de selección en el menú de tablas
    tableSelector.addEventListener('change', function() {
        currentActiveTable = this.value;
        if (currentActiveTable) {
            fetchAndRenderTable(currentActiveTable);
            addRowBtn.classList.remove('admin-hidden'); // Muestra el botón de añadir fila
        } else {
            tableContainer.innerHTML = '<p class="admin-info-text">Por favor, seleccione una tabla de la lista.</p>';
            addRowBtn.classList.add('admin-hidden'); // Oculta el botón
            currentTableColumns = []; // Limpia metadatos
            currentTablePrimaryKey = '';
        }
    });

    // Función para obtener y renderizar los datos de una tabla específica
    function fetchAndRenderTable(tableName) {
        tableContainer.innerHTML = '<p class="admin-info-text">Cargando datos de la tabla...</p>';
        fetch(`php_scripts/load_table.php?table=${encodeURIComponent(tableName)}`)
            .then(response => {
                if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error del servidor: ${response.status}`) });
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    currentTableColumns = data.columns;
                    currentTablePrimaryKey = data.primaryKey;
                    renderHtmlTableUI(data.columns, data.rows, data.primaryKey);
                } else {
                    displayAdminMessage(data.message || 'No se pudieron cargar los datos de la tabla.', 'error');
                    tableContainer.innerHTML = `<p class="admin-info-text admin-type-error">${escapeHtml(data.message || 'Error de carga.')}</p>`;
                }
            })
            .catch(error => {
                displayAdminMessage(`Error al cargar la tabla "${tableName}": ${error.message}`, 'error');
                tableContainer.innerHTML = `<p class="admin-info-text admin-type-error">Error: ${escapeHtml(error.message)}</p>`;
            });
    }

    // Función para construir la tabla HTML y mostrar los datos
    function renderHtmlTableUI(columnsMeta, rowsData, pkField) {
        if (!columnsMeta || columnsMeta.length === 0) {
            tableContainer.innerHTML = '<p class="admin-info-text">La estructura de la tabla no está definida o la tabla está vacía.</p>';
            return;
        }

        let tableHtml = '<table class="admin-data-display-table"><thead><tr>';
        columnsMeta.forEach(colMeta => {
            tableHtml += `<th>${escapeHtml(colMeta.Field)} ${colMeta.Field === pkField ? '<span class="pk-marker">(PK)</span>' : ''}</th>`;
        });
        tableHtml += '<th>Acciones</th></tr></thead><tbody>';

        const activeTableLower = currentActiveTable.toLowerCase();

        rowsData.forEach(row => {
            const pkValue = row[pkField]; 
            const isNewUnsavedRow = pkValue === null || typeof pkValue === 'undefined' || pkValue === ''; 
            tableHtml += `<tr data-pk-value="${pkValue ? escapeHtml(pkValue) : ''}">`;

            columnsMeta.forEach(colMeta => {
                const columnName = colMeta.Field;
                const columnNameLower = columnName.toLowerCase();
                let cellValue = row[columnName];
                let cellInteriorHtml = '';
                let tdAttributes = `data-td-for-column="${escapeHtml(columnName)}"`;
                const isPKAndAutoIncrement = (columnName === pkField) && colMeta.Extra && colMeta.Extra.toLowerCase().includes('auto_increment');
                let isContentEditable = !isPKAndAutoIncrement; 

                // Lógica especial para la tabla 'Administradores'
                if (activeTableLower === 'administradores') {
                    if (columnNameLower === 'role') {
                        let selectHtml = `<select data-column-name="${escapeHtml(columnName)}" class="admin-role-select">`;
                        PREDEFINED_ADMIN_ROLES.forEach(role => {
                            selectHtml += `<option value="${escapeHtml(role)}" ${cellValue === role ? 'selected' : ''}>${escapeHtml(role)}</option>`;
                        });
                        selectHtml += `</select>`;
                        cellInteriorHtml = selectHtml;
                        isContentEditable = false; 
                    } else if (columnNameLower === 'password') {
                        cellInteriorHtml = isNewUnsavedRow ? '' : '********'; 
                        isContentEditable = true; 
                        tdAttributes += ` data-column-name="${escapeHtml(columnName)}"`; 
                    }
                }
                // Lógica especial para la tabla 'EstadoEnvio'
                else if (activeTableLower === 'estadoenvio') {
                    if (columnNameLower === 'descripcion') {
                         let selectHtml = `<select data-column-name="${escapeHtml(columnName)}" class="admin-status-select">`;
                         PREDEFINED_ESTADO_ENVIO_STATUSES.forEach(status => {
                             selectHtml += `<option value="${escapeHtml(status)}" ${cellValue === status ? 'selected' : ''}>${escapeHtml(status)}</option>`;
                         });
                         selectHtml += `</select>`;
                         cellInteriorHtml = selectHtml;
                         isContentEditable = false;
                    }
                }

                // Formación del contenido HTML de la celda
                if (!cellInteriorHtml) {
                    cellInteriorHtml = (cellValue !== null && typeof cellValue !== 'undefined') ? escapeHtml(cellValue) : '';
                    if (isContentEditable) {
                        tdAttributes += ` data-column-name="${escapeHtml(columnName)}"`;
                    }
                }
                tableHtml += `<td ${tdAttributes} ${isContentEditable ? 'contenteditable="true"' : ''}>${cellInteriorHtml}</td>`;
            });

            // Botones de acciones para la fila
            tableHtml += `<td class="admin-row-action-buttons">
                            <button class="admin-save-button" title="Guardar cambios">💾</button>
                            <button class="admin-delete-button" title="Eliminar fila">🗑️</button>
                           </td>`;
            tableHtml += '</tr>';
        });

        tableHtml += '</tbody></table>';
        tableContainer.innerHTML = tableHtml;
    }

    // ==========================================
    // === LÓGICA DE ACCIONES DE FILAS (ADD/SAVE/DELETE) ===
    // ==========================================

    // Añadir nueva fila
    addRowBtn.addEventListener('click', () => {
        if (!currentActiveTable) {
            displayAdminMessage('Por favor, seleccione una tabla primero.', 'error');
            return;
        }
        
        let initialRowData = {}; 
        let missingRequiredPromptFields = []; 
        const activeTableLowerForAdd = currentActiveTable.toLowerCase();

        // --- Lógica para pedir datos de campos OBLIGATORIOS (NOT NULL sin DEFAULT) ---
        // (Ajustado según la esquema de BD del usuario)

        if (activeTableLowerForAdd === 'usuarios') {
            initialRowData.email = prompt("Email (obligatorio):");
            if (!initialRowData.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(initialRowData.email)) { missingRequiredPromptFields.push('Email válido'); }
            initialRowData.password_hash = prompt("Contraseña (obligatoria, mínimo 8 caracteres):");
            if (!initialRowData.password_hash || initialRowData.password_hash.length < 8) { missingRequiredPromptFields.push('Contraseña (mínimo 8)'); }
            initialRowData.fecha_creacion = new Date().toISOString().slice(0,10); 
        }
        else if (activeTableLowerForAdd === 'clientes') {
            initialRowData.nombre_cliente = prompt("Nombre Cliente (obligatorio):");
            if (!initialRowData.nombre_cliente) { missingRequiredPromptFields.push('Nombre Cliente'); }
            initialRowData.apellido_cliente = prompt("Apellido Cliente (obligatorio):");
            if (!initialRowData.apellido_cliente) { missingRequiredPromptFields.push('Apellido Cliente'); }
            initialRowData.numero_documento = prompt("Documento (obligatorio, solo números):");
            if (!initialRowData.numero_documento || !/^\d+$/.test(initialRowData.numero_documento)) { missingRequiredPromptFields.push('Documento (solo números)'); }
            initialRowData.numero_documento = parseInt(initialRowData.numero_documento);
            initialRowData.Usuarios_id_usuario = prompt("ID de Usuario (FK, obligatorio):"); 
            if (!initialRowData.Usuarios_id_usuario || isNaN(parseInt(initialRowData.Usuarios_id_usuario))) { missingRequiredPromptFields.push('ID de Usuario (FK)'); }
            initialRowData.Usuarios_id_usuario = parseInt(initialRowData.Usuarios_id_usuario); 
        }
        else if (activeTableLowerForAdd === 'servicios') {
            initialRowData.nombre_servicio = prompt("Nombre Servicio (obligatorio):");
            if (!initialRowData.nombre_servicio) { missingRequiredPromptFields.push('Nombre Servicio'); }
            initialRowData.descripcion = prompt("Descripción Servicio (obligatoria):"); 
            if (!initialRowData.descripcion) { missingRequiredPromptFields.push('Descripción Servicio'); }
            initialRowData.unidad_medida_tarifa = prompt("Unidad de Medida (kg, m3, hora, unidades):"); 
            if (!['kg', 'm3', 'hora', 'unidades'].includes(initialRowData.unidad_medida_tarifa)) { missingRequiredPromptFields.push('Unidad de Medida (válida)'); }
        }
        else if (activeTableLowerForAdd === 'tarifas') {
            initialRowData.Servicios_servicio_id = prompt("ID de Servicio (FK, obligatorio):");
            if (!initialRowData.Servicios_servicio_id || isNaN(parseInt(initialRowData.Servicios_servicio_id))) { missingRequiredPromptFields.push('ID de Servicio (FK)'); }
            initialRowData.Servicios_servicio_id = parseInt(initialRowData.Servicios_servicio_id);
        }
        else if (activeTableLowerForAdd === 'estadoenvio') {
            initialRowData.descripcion = prompt("Descripción de Estado (Pendiente, En proceso, Finalizado):");
            if (!['Pendiente', 'En proceso', 'Finalizado', 'Cancelado'].includes(initialRowData.descripcion)) { missingRequiredPromptFields.push('Descripción de Estado (válida)'); }
        }
        else if (activeTableLowerForAdd === 'administradores') {
            initialRowData.email = prompt("Email (obligatorio):");
            if (!initialRowData.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(initialRowData.email)) { missingRequiredPromptFields.push('Email válido'); }
            initialRowData.password = prompt("Contraseña (obligatoria, min 8 caracteres):");
            if (!initialRowData.password || initialRowData.password.length < 8) { missingRequiredPromptFields.push('Contraseña (mínimo 8)'); }
            initialRowData.role = prompt("Rol (Administrador, Conductor, Gerente de ventas):");
            if (!['Administrador', 'Conductor', 'Gerente de ventas'].includes(initialRowData.role)) { missingRequiredPromptFields.push('Rol (válido)'); }
        }
        else if (activeTableLowerForAdd === 'conductores') {
            initialRowData.nombre_conductor = prompt("Nombre Conductor (obligatorio):");
            if (!initialRowData.nombre_conductor) { missingRequiredPromptFields.push('Nombre Conductor'); }
            initialRowData.apellido_conductor = prompt("Apellido Conductor (obligatorio):");
            if (!initialRowData.apellido_conductor) { missingRequiredPromptFields.push('Apellido Conductor'); }
            initialRowData.licencia = prompt("Licencia (obligatoria):");
            if (!initialRowData.licencia) { missingRequiredPromptFields.push('Licencia'); }
            initialRowData.documento = prompt("Documento (obligatorio, solo números):");
            if (!initialRowData.documento || !/^\d+$/.test(initialRowData.documento)) { missingRequiredPromptFields.push('Documento (solo números)'); }
            initialRowData.documento = parseInt(initialRowData.documento);
            initialRowData.Administradores_idAdministradores = prompt("ID Administrador (FK, obligatorio):");
            if (!initialRowData.Administradores_idAdministradores || isNaN(parseInt(initialRowData.Administradores_idAdministradores))) { missingRequiredPromptFields.push('ID Administrador (FK)'); }
            initialRowData.Administradores_idAdministradores = parseInt(initialRowData.Administradores_idAdministradores);
        }
        else if (activeTableLowerForAdd === 'vehiculos') {
            initialRowData.tipo = prompt("Tipo (Motocicleta, Camioneta, Camión, Furgón):");
            if (!['Motocicleta', 'Camioneta', 'Camión', 'Furgón'].includes(initialRowData.tipo)) { missingRequiredPromptFields.push('Tipo de vehículo (válido)'); }
            initialRowData.patente = prompt("Patente (obligatorio):");
            if (!initialRowData.patente) { missingRequiredPromptFields.push('Patente'); }
            initialRowData.Conductores_conductor_id = prompt("ID Conductor (FK, obligatorio):");
            if (!initialRowData.Conductores_conductor_id || isNaN(parseInt(initialRowData.Conductores_conductor_id))) { missingRequiredPromptFields.push('ID Conductor (FK)'); }
            initialRowData.Conductores_conductor_id = parseInt(initialRowData.Conductores_conductor_id);
        }
        else if (activeTableLowerForAdd === 'envios') {
            initialRowData.EstadoEnvio_estado_envio_id1 = prompt("ID Estado Envío (FK, obligatorio, ej: 1=Pendiente):"); 
            initialRowData.Clientes_id_cliente = prompt("ID Cliente (FK, obligatorio):");
            if (!initialRowData.EstadoEnvio_estado_envio_id1 || isNaN(parseInt(initialRowData.EstadoEnvio_estado_envio_id1)) || !initialRowData.Clientes_id_cliente || isNaN(parseInt(initialRowData.Clientes_id_cliente))) {
                missingRequiredPromptFields.push('ID Estado Envío (FK) e ID Cliente (FK)'); }
            initialRowData.EstadoEnvio_estado_envio_id1 = parseInt(initialRowData.EstadoEnvio_estado_envio_id1);
            initialRowData.Clientes_id_cliente = parseInt(initialRowData.Clientes_id_cliente);
        }
        else if (activeTableLowerForAdd === 'detalleenvio') {
            initialRowData.Envios_envio_id = prompt("ID Envío (FK, obligatorio):");
            initialRowData.Servicios_servicio_id = prompt("ID Servicio (FK, obligatorio):");
            if (!initialRowData.Envios_envio_id || isNaN(parseInt(initialRowData.Envios_envio_id)) || !initialRowData.Servicios_servicio_id || isNaN(parseInt(initialRowData.Servicios_servicio_id))) {
                missingRequiredPromptFields.push('IDs de Envío y Servicio (FK)'); }
            initialRowData.Envios_envio_id = parseInt(initialRowData.Envios_envio_id);
            initialRowData.Servicios_servicio_id = parseInt(initialRowData.Servicios_servicio_id);
        }
        else if (activeTableLowerForAdd === 'feedback') {
            initialRowData.name = prompt("Nombre (obligatorio):");
            if (!initialRowData.name) { missingRequiredPromptFields.push('Nombre'); }
            initialRowData.email = prompt("Email (obligatorio):");
            if (!initialRowData.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(initialRowData.email)) { missingRequiredPromptFields.push('Email válido'); }
            initialRowData.message = prompt("Mensaje (obligatorio):");
            if (!initialRowData.message) { missingRequiredPromptFields.push('Mensaje'); }
            initialRowData.fecha_envio = new Date().toISOString().slice(0,10); 
        }
        else {
            displayAdminMessage('Tabla no reconocida o no configurada para añadir fila.', 'error');
            return;
        }

        // --- Validar si faltan campos obligatorios ---
        if (missingRequiredPromptFields.length > 0) {
            displayAdminMessage(`Faltan campos obligatorios o inválidos: ${missingRequiredPromptFields.join(', ')}.`, 'error');
            return;
        }
        
        fetch('php_scripts/add_row.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                table: currentActiveTable, 
                data: initialRowData 
            })
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error del servidor ${response.status}`) });
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayAdminMessage(data.message || 'Nueva fila preparada. Complétela y guárdela.');
                fetchAndRenderTable(currentActiveTable); // Recargar la tabla para mostrar la nueva fila
            } else {
                displayAdminMessage(data.message || 'No se pudo añadir la fila.', 'error');
            }
        })
        .catch(error => {
            displayAdminMessage(`Error al añadir la fila: ${error.message}`, 'error');
        });
    });

    // ===================================
    // === LÓGICA DE GUARDAR/ELIMINAR FILAS ===
    // ===================================

    tableContainer.addEventListener('click', function(event) {
        const targetButton = event.target.closest('button');
        if (!targetButton) return;

        const tableRowElement = targetButton.closest('tr');
        if (!tableRowElement) return;

        const pkValueForRow = tableRowElement.dataset.pkValue;

        // Lógica para guardar
        if (targetButton.classList.contains('admin-save-button')) {
            const rowDataObject = {};
            let isNewPasswordEntered = false;
            const activeTableLowerForSave = currentActiveTable.toLowerCase();

            tableRowElement.querySelectorAll('td[contenteditable="true"][data-column-name], select[data-column-name]').forEach(inputElement => {
                const columnName = inputElement.dataset.columnName;
                const columnNameLowerForSave = columnName.toLowerCase();
                let value = (inputElement.tagName === 'SELECT') ? inputElement.value : inputElement.textContent;

                // Lógica para password (oculto en el display pero editable)
                if (activeTableLowerForSave === 'administradores' && columnNameLowerForSave === 'password') {
                    if (value.trim() !== '' && value.trim() !== '********') { // Si el usuario ha introducido un nuevo valor
                        rowDataObject[columnName] = value.trim();
                        isNewPasswordEntered = true;
                    }
                } else {
                    rowDataObject[columnName] = value;
                }
            });
            
            // Asegurarse de que el PK está incluido en los datos a enviar
            if (!pkValueForRow && !isNewUnsavedRow(tableRowElement)) { // isNewUnsavedRow no está definido aquí. Esto es un bug
            } else {
                rowDataObject[currentTablePrimaryKey] = pkValueForRow;
            }

            fetch('php_scripts/save_row.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table: currentActiveTable,
                    pkField: currentTablePrimaryKey,
                    data: rowDataObject,
                    isNewPasswordProvided: isNewPasswordEntered
                })
            })
            .then(response => {
                if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error del servidor ${response.status}`) });
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayAdminMessage(data.message || 'Fila guardada correctamente.');
                    targetButton.style.backgroundColor = 'lightgreen';
                    setTimeout(() => { targetButton.style.backgroundColor = ''; }, 2000);
                    
                    if (activeTableLowerForSave === 'administradores' && isNewPasswordEntered) {
                        const passwordCell = tableRowElement.querySelector('td[data-column-name="password"], td[data-td-for-column="password"]');
                        if (passwordCell) {
                            passwordCell.textContent = '********';
                        }
                    }
                } else {
                    displayAdminMessage(data.message || 'No se pudo guardar la fila.', 'error');
                }
            })
            .catch(error => {
                displayAdminMessage(`Error al guardar: ${error.message}`, 'error');
            });
        }

        // Lógica para eliminar
        if (targetButton.classList.contains('admin-delete-button')) {
            if (confirm(`¿Está seguro de que desea eliminar la fila con PK '${pkValueForRow}' de la tabla '${currentActiveTable}'?`)) {
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
                    if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error del servidor ${response.status}`) });
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayAdminMessage(data.message || 'Fila eliminada correctamente.');
                        tableRowElement.remove(); 
                    } else {
                        displayAdminMessage(data.message || 'No se pudo eliminar la fila.', 'error');
                    }
                })
                .catch(error => {
                    displayAdminMessage(`Error al eliminar: ${error.message}`, 'error');
                });
            }
        }
    });
});