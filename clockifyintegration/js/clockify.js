(function() {
    // Debug: verificar se o script está carregando
    console.log('Clockify Integration: JavaScript principal carregado');
    
    // Função para aguardar as configurações serem carregadas
    const waitForConfig = (callback, maxAttempts = 50) => {
        let attempts = 0;
        
        const checkConfig = () => {
            attempts++;
            
            if (window.clockifyIntegrationConfigLoaded && window.clockifyIntegrationConfig) {
                console.log('Clockify Integration: Configurações encontradas!');
                callback();
                return;
            }
            
            if (attempts >= maxAttempts) {
                console.warn('Clockify Integration: Timeout aguardando configurações. Usando configuração vazia.');
                // Define configuração vazia se não conseguir carregar
                window.clockifyIntegrationConfig = { apiKey: '', workspaceId: '' };
                callback();
                return;
            }
            
            // Tenta novamente em 100ms
            setTimeout(checkConfig, 100);
        };
        
        checkConfig();
    };
    
    // Inicia a aplicação quando as configurações estiverem prontas
    waitForConfig(() => {
        initClockifyIntegration();
    });
    
    // Função principal que inicializa a integração
    function initClockifyIntegration() {
        // Log das configurações finais
        const config = window.clockifyIntegrationConfig || {};
        console.log('Clockify Integration - Estado final das configurações:', {
            configExists: !!window.clockifyIntegrationConfig,
            apiKey: config.apiKey ? 'Configurado (' + config.apiKey.length + ' chars)' : 'Não configurado',
            workspaceId: config.workspaceId ? 'Configurado (' + config.workspaceId.length + ' chars)' : 'Não configurado',
            fullConfig: config
        });

        // Se não há configurações, exibe aviso detalhado
        if (!config.apiKey || !config.workspaceId) {
            console.warn('Clockify Integration: Configurações incompletas!', {
                'API Key': config.apiKey ? 'OK' : 'FALTANDO',
                'Workspace ID': config.workspaceId ? 'OK' : 'FALTANDO',
                'Verifique': 'Configurações > Plugins > Clockify Integration'
            });
        }

    /**
     * Função para obter configurações
     */
    const getConfig = () => {
        return window.clockifyIntegrationConfig || {};
    };

    /**
     * Função para obter informações do ticket da URL e página
     */
    const getTicketInfo = () => {
        let ticketId = null;
        let ticketTitle = '';

        // Obtém o ID do ticket da URL
        const urlParams = new URLSearchParams(window.location.search);
        ticketId = urlParams.get('id');
        
        // Se não encontrou na URL, tenta extrair do título da página
        if (!ticketId && document.title) {
            const titleMatch = document.title.match(/Chamado \(#(\d+)\)/);
            if (titleMatch) {
                ticketId = titleMatch[1];
            }
        }

        // Extrai o título do ticket do título da página (método mais confiável)
        if (document.title) {
            // Extrai de "Chamado (#1) - testee - GLPI"
            const titleMatch = document.title.match(/Chamado \(#\d+\) - (.+?) - GLPI/);
            if (titleMatch) {
                ticketTitle = titleMatch[1].trim();
            } else {
                // Fallback: tenta outros padrões
                const genericMatch = document.title.match(/(.+?) - GLPI/);
                if (genericMatch) {
                    ticketTitle = genericMatch[1].replace(/Chamado \(#\d+\) - /, '').trim();
                }
            }
        }

        // Se ainda não encontrou título, tenta nos elementos da página
        if (!ticketTitle) {
            const inputName = document.querySelector('input[name="name"]');
            if (inputName && inputName.value) {
                ticketTitle = inputName.value.trim();
            }
        }

        // Fallback final
        if (!ticketTitle) {
            ticketTitle = 'Ticket sem título';
        }

        console.log('Clockify: Informações do ticket obtidas:', { 
            ticketId, 
            ticketTitle, 
            url: window.location.href,
            pageTitle: document.title 
        });
        
        return { ticketId, ticketTitle };
    };

    /**
     * Função que inicia o timer no Clockify utilizando a API.
     */
    const startClockifyTimer = (description) => {
        const config = getConfig();
        const apiKey = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
        }

        const data = {
            "start": new Date().toISOString(),
            "description": description
        };

        return fetch(`https://api.clockify.me/api/v1/workspaces/${workspace}/time-entries`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Api-Key": apiKey,
            },
            body: JSON.stringify(data),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    };

    /**
     * Função para obter informações do usuário (incluindo ID)
     */
    const getCurrentUser = (apiKey) => {
        return fetch("https://api.clockify.me/api/v1/user", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "X-Api-Key": apiKey,
            },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    };

    /**
     * Função para obter projetos do workspace
     */
    const getWorkspaceProjects = () => {
        const config = getConfig();
        const apiKey = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
        }

        return fetch(`https://api.clockify.me/api/v1/workspaces/${workspace}/projects`, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "X-Api-Key": apiKey,
            },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    };

    /**
     * Função para atualizar um time-entry com projeto
     */
    const updateTimeEntryProject = (timeEntryId, projectId, description, startTime) => {
        const config = getConfig();
        const apiKey = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
        }

        return fetch(`https://api.clockify.me/api/v1/workspaces/${workspace}/time-entries/${timeEntryId}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-Api-Key": apiKey,
            },
            body: JSON.stringify({
                "start": startTime,
                "description": description,
                "projectId": projectId
            }),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    };

    /**
     * Função que para o timer no Clockify utilizando a API.
     * Agora com seleção de projeto opcional.
     */
    const stopClockifyTimer = (projectId = null, currentTimeEntry = null) => {
        const config = getConfig();
        const apiKey = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
        }

        let stopPromise;

        // Se um projeto foi especificado e temos informações do time-entry atual
        if (projectId && currentTimeEntry) {
            console.log('Clockify: Atualizando time-entry com projeto antes de parar...');
            
            // Primeiro atualiza com o projeto, depois para
            stopPromise = updateTimeEntryProject(
                currentTimeEntry.id, 
                projectId, 
                currentTimeEntry.description, 
                currentTimeEntry.timeInterval.start
            )
            .then(updatedEntry => {
                console.log('Clockify: Time-entry atualizado com projeto:', updatedEntry);
                // Agora para o timer
                return getCurrentUser(apiKey);
            });
        } else {
            // Para diretamente sem atualizar projeto
            stopPromise = getCurrentUser(apiKey);
        }

        return stopPromise
            .then(user => {
                console.log('Clockify: Usuário obtido:', user.name, 'ID:', user.id);
                
                // Para o timer usando o endpoint correto com userId
                return fetch(`https://api.clockify.me/api/v1/workspaces/${workspace}/user/${user.id}/time-entries`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Api-Key": apiKey,
                    },
                    body: JSON.stringify({
                        "end": new Date().toISOString()
                    }),
                });
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    };

    /**
     * Cria o modal de seleção de projetos
     */
    const createProjectModal = () => {
        // Remove modal existente se houver
        const existingModal = document.getElementById('clockify-project-modal');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'clockify-project-modal';
        modal.innerHTML = `
            <div class="clockify-modal-overlay">
                <div class="clockify-modal-content">
                    <div class="clockify-modal-header">
                        <h3>📋 Selecionar Projeto</h3>
                        <button class="clockify-modal-close">×</button>
                    </div>
                    <div class="clockify-modal-body">
                        <p>Escolha um projeto para associar ao seu registro de tempo:</p>
                        <input type="text" id="clockify-project-search" placeholder="🔍 Buscar projeto..." />
                        <div id="clockify-project-list" class="clockify-project-list">
                            <div class="clockify-loading">Carregando projetos...</div>
                        </div>
                    </div>
                    <div class="clockify-modal-footer">
                        <button class="clockify-btn-cancel">Cancelar</button>
                    </div>
                </div>
            </div>
        `;

        // Estilos CSS inline para o modal
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 20000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        const overlay = modal.querySelector('.clockify-modal-overlay');
        overlay.style.cssText = `
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        `;

        const content = modal.querySelector('.clockify-modal-content');
        content.style.cssText = `
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 70vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        `;

        const header = modal.querySelector('.clockify-modal-header');
        header.style.cssText = `
            padding: 20px 20px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;

        const headerTitle = header.querySelector('h3');
        headerTitle.style.cssText = `
            margin: 0;
            color: #333;
            font-size: 18px;
        `;

        const closeBtn = header.querySelector('.clockify-modal-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        `;

        const body = modal.querySelector('.clockify-modal-body');
        body.style.cssText = `
            padding: 20px;
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        `;

        const searchInput = modal.querySelector('#clockify-project-search');
        searchInput.style.cssText = `
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            box-sizing: border-box;
        `;

        const projectList = modal.querySelector('#clockify-project-list');
        projectList.style.cssText = `
            flex: 1;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 4px;
            min-height: 200px;
        `;

        const loading = modal.querySelector('.clockify-loading');
        loading.style.cssText = `
            padding: 40px;
            text-align: center;
            color: #666;
        `;

        const footer = modal.querySelector('.clockify-modal-footer');
        footer.style.cssText = `
            padding: 15px 20px 20px;
            border-top: 1px solid #eee;
            text-align: right;
        `;

        const cancelBtn = modal.querySelector('.clockify-btn-cancel');
        cancelBtn.style.cssText = `
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        `;

        document.body.appendChild(modal);
        return modal;
    };

    /**
     * Renderiza a lista de projetos no modal
     */
    const renderProjectList = (projects, searchTerm = '') => {
        const projectList = document.getElementById('clockify-project-list');
        projectList.innerHTML = '';

        // Filtra projetos baseado no termo de busca
        const filteredProjects = projects.filter(project => 
            project.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (project.clientName && project.clientName.toLowerCase().includes(searchTerm.toLowerCase()))
        );

        // Opção "Sem projeto"
        const noProjectItem = document.createElement('div');
        noProjectItem.className = 'clockify-project-item';
        noProjectItem.innerHTML = `
            <div class="clockify-project-name">
                <span class="clockify-project-color" style="background-color: #ccc;"></span>
                Sem projeto
            </div>
            <div class="clockify-project-client">Nenhum projeto será associado</div>
        `;
        
        noProjectItem.style.cssText = `
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        `;

        noProjectItem.addEventListener('mouseenter', () => {
            noProjectItem.style.backgroundColor = '#f8f9fa';
        });
        
        noProjectItem.addEventListener('mouseleave', () => {
            noProjectItem.style.backgroundColor = 'transparent';
        });

        noProjectItem.addEventListener('click', () => {
            selectProject(null);
        });

        projectList.appendChild(noProjectItem);

        // Renderiza projetos filtrados
        filteredProjects.forEach(project => {
            const projectItem = document.createElement('div');
            projectItem.className = 'clockify-project-item';
            
            const projectColor = project.color || '#007bff';
            const clientInfo = project.clientName ? 
                `<div class="clockify-project-client">Cliente: ${project.clientName}</div>` : '';
            
            projectItem.innerHTML = `
                <div class="clockify-project-name">
                    <span class="clockify-project-color" style="background-color: ${projectColor};"></span>
                    ${project.name}
                </div>
                ${clientInfo}
            `;
            
            projectItem.style.cssText = `
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                transition: background-color 0.2s;
            `;

            const projectName = projectItem.querySelector('.clockify-project-name');
            projectName.style.cssText = `
                font-weight: 600;
                color: #333;
                margin-bottom: 4px;
                display: flex;
                align-items: center;
            `;

            const colorSpan = projectItem.querySelector('.clockify-project-color');
            colorSpan.style.cssText = `
                width: 16px;
                height: 16px;
                border-radius: 50%;
                display: inline-block;
                margin-right: 8px;
            `;

            const clientEl = projectItem.querySelector('.clockify-project-client');
            if (clientEl) {
                clientEl.style.cssText = `
                    font-size: 12px;
                    color: #666;
                `;
            }

            projectItem.addEventListener('mouseenter', () => {
                projectItem.style.backgroundColor = '#f8f9fa';
            });
            
            projectItem.addEventListener('mouseleave', () => {
                projectItem.style.backgroundColor = 'transparent';
            });

            projectItem.addEventListener('click', () => {
                selectProject(project);
            });

            projectList.appendChild(projectItem);
        });

        if (filteredProjects.length === 0 && searchTerm) {
            const noResults = document.createElement('div');
            noResults.style.cssText = `
                padding: 40px;
                text-align: center;
                color: #666;
            `;
            noResults.textContent = 'Nenhum projeto encontrado';
            projectList.appendChild(noResults);
        }
    };

    /**
     * Mostra o modal de seleção de projetos
     */
    const showProjectSelectionModal = (currentTimeEntry) => {
        const modal = createProjectModal();
        
        // Armazenar referência do time-entry atual
        window.currentClockifyTimeEntry = currentTimeEntry;
        
        // Configurar eventos
        const closeBtn = modal.querySelector('.clockify-modal-close');
        const cancelBtn = modal.querySelector('.clockify-btn-cancel');
        const searchInput = modal.querySelector('#clockify-project-search');
        const overlay = modal.querySelector('.clockify-modal-overlay');

        // Fechar modal
        const closeModal = () => {
            modal.remove();
            window.currentClockifyTimeEntry = null;
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Fechar ao clicar fora
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal();
            }
        });

        // Fechar com ESC
        const handleEsc = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEsc);
            }
        };
        document.addEventListener('keydown', handleEsc);

        // Hover no botão fechar
        closeBtn.addEventListener('mouseenter', () => {
            closeBtn.style.backgroundColor = '#f0f0f0';
        });
        closeBtn.addEventListener('mouseleave', () => {
            closeBtn.style.backgroundColor = 'transparent';
        });

        // Hover no botão cancelar
        cancelBtn.addEventListener('mouseenter', () => {
            cancelBtn.style.backgroundColor = '#545b62';
        });
        cancelBtn.addEventListener('mouseleave', () => {
            cancelBtn.style.backgroundColor = '#6c757d';
        });

        // Carregar e renderizar projetos
        getWorkspaceProjects()
            .then(projects => {
                window.clockifyProjects = projects;
                renderProjectList(projects);
                
                // Configurar busca
                searchInput.addEventListener('input', (e) => {
                    renderProjectList(projects, e.target.value);
                });
                
                searchInput.focus();
            })
            .catch(error => {
                console.error('Clockify: Erro ao carregar projetos:', error);
                const projectList = document.getElementById('clockify-project-list');
                projectList.innerHTML = `
                    <div style="padding: 40px; text-align: center; color: #dc3545;">
                        Erro ao carregar projetos: ${error.message}
                    </div>
                `;
            });
    };

    /**
     * Seleciona um projeto e prossegue com a parada do timer
     */
    const selectProject = (project) => {
        const currentTimeEntry = window.currentClockifyTimeEntry;
        
        if (project) {
            console.log('Clockify: Projeto selecionado:', project.name);
        } else {
            console.log('Clockify: Parar timer sem projeto');
        }
        
        // Fechar modal
        const modal = document.getElementById('clockify-project-modal');
        if (modal) {
            modal.remove();
        }
        
        // Executar parada do timer com projeto (se selecionado)
        executeStopTimer(project ? project.id : null, currentTimeEntry);
    };

    /**
     * Executa a parada do timer, opcionalmente com projeto
     */
    const executeStopTimer = (projectId, currentTimeEntry) => {
        console.log('Clockify: Executando parada do timer...');
        
        stopClockifyTimer(projectId, currentTimeEntry)
            .then((result) => {
                console.log('Clockify: Timer parado com sucesso:', result);
                
                // Atualizar interface se necessário
                const statusEl = document.querySelector('.clockify-status');
                if (statusEl) {
                    statusEl.textContent = 'Cronômetro parado';
                    statusEl.style.color = '#dc3545';
                }
                
                // Resetar botões
                const startBtn = document.querySelector('.clockify-start-btn');
                const stopBtn = document.querySelector('.clockify-stop-btn');
                if (startBtn && stopBtn) {
                    startBtn.style.display = 'inline-flex';
                    stopBtn.style.display = 'none';
                }
                
                // Resetar timer display
                const timerDisplay = document.querySelector('.timer-text');
                if (timerDisplay) {
                    timerDisplay.textContent = '00:00:00';
                }
                
                // Limpar interval se existir
                if (window.clockifyTimerInterval) {
                    clearInterval(window.clockifyTimerInterval);
                    window.clockifyTimerInterval = null;
                }
                
                window.clockifyStartTime = null;
                window.currentClockifyTimeEntry = null;
                
                console.log('Clockify: Interface atualizada após parar timer');
            })
            .catch((error) => {
                console.error('Clockify: Erro ao parar timer:', error);
                
                const statusEl = document.querySelector('.clockify-status');
                if (statusEl) {
                    statusEl.textContent = 'Erro ao parar';
                    statusEl.style.color = '#dc3545';
                }
            });
    };
    const createClockifyPopup = (ticketInfo) => {
        // Remove popup existente se houver
        const existingPopup = document.getElementById('clockify-popup');
        if (existingPopup) {
            existingPopup.remove();
        }

        const popup = document.createElement('div');
        popup.id = 'clockify-popup';
        popup.className = 'clockify-popup';
        
        // Título do popup com código e assunto do ticket
        const title = ticketInfo.ticketId 
            ? `#${ticketInfo.ticketId} - ${ticketInfo.ticketTitle}`
            : ticketInfo.ticketTitle;

        popup.innerHTML = `
            <div class="clockify-header">
                <span class="clockify-title">${title}</span>
                <div class="clockify-controls">
                    <button class="clockify-minimize" title="Minimizar">−</button>
                    <button class="clockify-close" title="Fechar">×</button>
                </div>
            </div>
            <div class="clockify-content">
                <div class="clockify-timer-display">
                    <span class="timer-text">00:00:00</span>
                </div>
                <div class="clockify-buttons">
                    <button class="clockify-start-btn" title="Iniciar cronômetro">
                        <span class="btn-icon">▶</span>
                        <span class="btn-text">Iniciar</span>
                    </button>
                    <button class="clockify-stop-btn" title="Parar cronômetro" style="display: none;">
                        <span class="btn-icon">⏸</span>
                        <span class="btn-text">Parar</span>
                    </button>
                </div>
                <div class="clockify-status">Pronto para iniciar</div>
            </div>
        `;

        // Aplicar estilos CSS inline
        popup.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 280px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
        `;

        document.body.appendChild(popup);
        
        // Aplicar estilos para os elementos internos
        const header = popup.querySelector('.clockify-header');
        header.style.cssText = `
            background: #03A9F4;
            color: white;
            padding: 12px 16px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
            user-select: none;
        `;

        const title_el = popup.querySelector('.clockify-title');
        title_el.style.cssText = `
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        `;

        const controls = popup.querySelector('.clockify-controls');
        controls.style.cssText = `
            display: flex;
            gap: 8px;
        `;

        const controlButtons = popup.querySelectorAll('.clockify-minimize, .clockify-close');
        controlButtons.forEach(btn => {
            btn.style.cssText = `
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                width: 20px;
                height: 20px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
                font-weight: bold;
                line-height: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s;
            `;
        });

        const content = popup.querySelector('.clockify-content');
        content.style.cssText = `
            padding: 16px;
        `;

        const timerDisplay = popup.querySelector('.clockify-timer-display');
        timerDisplay.style.cssText = `
            text-align: center;
            margin-bottom: 16px;
        `;

        const timerText = popup.querySelector('.timer-text');
        timerText.style.cssText = `
            font-size: 24px;
            font-weight: bold;
            color: #333;
            font-family: 'Courier New', monospace;
        `;

        const buttonsContainer = popup.querySelector('.clockify-buttons');
        buttonsContainer.style.cssText = `
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        `;

        const actionButtons = popup.querySelectorAll('.clockify-start-btn, .clockify-stop-btn');
        actionButtons.forEach(btn => {
            btn.style.cssText = `
                flex: 1;
                padding: 8px 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                transition: all 0.2s;
            `;
        });

        const startBtn = popup.querySelector('.clockify-start-btn');
        startBtn.style.background = '#28a745';
        startBtn.style.color = 'white';

        const stopBtn = popup.querySelector('.clockify-stop-btn');
        stopBtn.style.background = '#dc3545';
        stopBtn.style.color = 'white';

        const status = popup.querySelector('.clockify-status');
        status.style.cssText = `
            text-align: center;
            font-size: 11px;
            color: #666;
            font-weight: 500;
        `;

        console.log('Clockify: Popup criado com sucesso');
        
        return popup;
    };

    /**
     * Configura os eventos do popup
     */
    const setupPopupEvents = (popup, ticketInfo) => {
        const header = popup.querySelector('.clockify-header');
        const minimizeBtn = popup.querySelector('.clockify-minimize');
        const closeBtn = popup.querySelector('.clockify-close');
        const content = popup.querySelector('.clockify-content');
        const startBtn = popup.querySelector('.clockify-start-btn');
        const stopBtn = popup.querySelector('.clockify-stop-btn');
        const statusEl = popup.querySelector('.clockify-status');
        const timerDisplay = popup.querySelector('.timer-text');

        let isDragging = false;
        let startX, startY, initialX, initialY;
        let isMinimized = false;
        let timerInterval = null;
        let startTime = null;

        // Hover effects nos botões de controle
        [minimizeBtn, closeBtn].forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.background = 'rgba(255,255,255,0.3)';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.background = 'rgba(255,255,255,0.2)';
            });
        });

        // Hover effects nos botões de ação
        startBtn.addEventListener('mouseenter', () => {
            startBtn.style.background = '#218838';
        });
        startBtn.addEventListener('mouseleave', () => {
            startBtn.style.background = '#28a745';
        });

        stopBtn.addEventListener('mouseenter', () => {
            stopBtn.style.background = '#c82333';
        });
        stopBtn.addEventListener('mouseleave', () => {
            stopBtn.style.background = '#dc3545';
        });

        // Drag and drop do popup
        header.addEventListener('mousedown', (e) => {
            if (e.target === minimizeBtn || e.target === closeBtn) return;
            
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialX = popup.offsetLeft;
            initialY = popup.offsetTop;
            
            document.addEventListener('mousemove', handleDrag);
            document.addEventListener('mouseup', stopDrag);
            e.preventDefault();
        });

        function handleDrag(e) {
            if (!isDragging) return;
            
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            
            popup.style.left = (initialX + dx) + 'px';
            popup.style.top = (initialY + dy) + 'px';
        }

        function stopDrag() {
            isDragging = false;
            document.removeEventListener('mousemove', handleDrag);
            document.removeEventListener('mouseup', stopDrag);
        }

        // Minimizar/maximizar
        minimizeBtn.addEventListener('click', () => {
            isMinimized = !isMinimized;
            if (isMinimized) {
                content.style.display = 'none';
                minimizeBtn.textContent = '+';
                minimizeBtn.title = 'Maximizar';
                popup.style.height = 'auto';
            } else {
                content.style.display = 'block';
                minimizeBtn.textContent = '−';
                minimizeBtn.title = 'Minimizar';
            }
        });

        // Fechar popup
        closeBtn.addEventListener('click', () => {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            popup.remove();
        });

        // Atualizar timer
        function updateTimer() {
            if (!startTime) return;
            
            const elapsed = Date.now() - startTime;
            const hours = Math.floor(elapsed / 3600000);
            const minutes = Math.floor((elapsed % 3600000) / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            
            timerDisplay.textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        // Iniciar cronômetro
        startBtn.addEventListener('click', () => {
            const description = ticketInfo.ticketId 
                ? `GLPI Ticket #${ticketInfo.ticketId} - ${ticketInfo.ticketTitle}`
                : `GLPI - ${ticketInfo.ticketTitle}`;

            console.log('Clockify: Iniciando cronômetro para:', description);
            
            // Desabilita o botão durante a requisição
            startBtn.disabled = true;
            startBtn.style.opacity = '0.6';
            statusEl.textContent = 'Iniciando...';
            statusEl.style.color = '#ffc107';
            
            // Chama a API do Clockify
            startClockifyTimer(description)
                .then((timeEntry) => {
                    // Armazenar informações do time-entry para usar quando parar
                    window.currentClockifyTimeEntry = timeEntry;
                    window.clockifyStartTime = Date.now();
                    
                    // Sucesso - inicia o timer local
                    startTime = Date.now();
                    timerInterval = setInterval(updateTimer, 1000);
                    window.clockifyTimerInterval = timerInterval;
                    
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-flex';
                    statusEl.textContent = 'Cronômetro ativo';
                    statusEl.style.color = '#28a745';
                    
                    console.log('Clockify: Timer iniciado com sucesso - Time Entry ID:', timeEntry.id);
                })
                .catch((error) => {
                    // Erro - reabilita o botão
                    console.error('Clockify: Erro ao iniciar timer:', error);
                    startBtn.disabled = false;
                    startBtn.style.opacity = '1';
                    statusEl.textContent = 'Erro ao iniciar';
                    statusEl.style.color = '#dc3545';
                    
                    // Volta para o estado inicial após 3 segundos
                    setTimeout(() => {
                        statusEl.textContent = 'Pronto para iniciar';
                        statusEl.style.color = '#666';
                    }, 3000);
                });
        });

        // Parar cronômetro
        stopBtn.addEventListener('click', () => {
            console.log('Clockify: Solicitando parada do cronômetro...');
            
            // Desabilita o botão durante o processo
            stopBtn.disabled = true;
            stopBtn.style.opacity = '0.6';
            statusEl.textContent = 'Preparando parada...';
            statusEl.style.color = '#ffc107';
            
            // Verificar se temos informações do time-entry atual
            const currentTimeEntry = window.currentClockifyTimeEntry;
            
            if (!currentTimeEntry) {
                console.warn('Clockify: Informações do time-entry não encontradas, parando diretamente...');
                executeStopTimer(null, null);
                return;
            }
            
            // Mostrar modal de seleção de projeto
            showProjectSelectionModal(currentTimeEntry);
            
            // Reabilitar o botão (caso o usuário cancele a seleção)
            setTimeout(() => {
                if (stopBtn.disabled) {
                    stopBtn.disabled = false;
                    stopBtn.style.opacity = '1';
                    statusEl.textContent = 'Cronômetro ativo';
                    statusEl.style.color = '#28a745';
                }
            }, 1000);
        });
    };

    /**
     * Função principal que cria e configura o popup do Clockify
     */
    const insertClockifyButton = () => {
        // Verifica se já existe um popup para evitar duplicação
        if (document.querySelector('#clockify-popup')) {
            console.log('Clockify: Popup já existe, pulando criação');
            return;
        }

        // Obtém informações do ticket
        const ticketInfo = getTicketInfo();
        
        if (!ticketInfo.ticketId) {
            console.log('Clockify: ID do ticket não encontrado, cancelando criação do popup');
            return;
        }

        console.log('Clockify: Criando popup para ticket:', ticketInfo);

        // Cria o popup
        const popup = createClockifyPopup(ticketInfo);
        
        // Configura os eventos
        setupPopupEvents(popup, ticketInfo);
        
        console.log('Clockify: Popup criado e configurado com sucesso!');
    };

    // Função que verifica se estamos em uma página de ticket
    const isTicketPage = () => {
        const url = window.location.href;
        const pathname = window.location.pathname;
        const title = document.title;
        
        // Verifica múltiplos critérios
        const urlCheck = (
            url.includes('ticket') || 
            url.includes('front/ticket.form.php') ||
            pathname.includes('/ticket.form.php')
        );
        
        const titleCheck = title && (
            title.includes('Chamado (#') ||
            title.includes('Ticket (#') ||
            title.includes('ticket')
        );
        
        const domCheck = (
            document.querySelector('input[name="id"]') !== null ||
            document.querySelector('form[name="form"]') !== null ||
            document.querySelector('.itil-object') !== null
        );
        
        const result = urlCheck || titleCheck || domCheck;
        
        console.log('Clockify: Verificação de página de ticket:', {
            url: url,
            pathname: pathname,
            title: title,
            urlCheck: urlCheck,
            titleCheck: titleCheck,
            domCheck: domCheck,
            result: result
        });
        
        return result;
    };

    // Inicialização
    const init = () => {
        console.log('Clockify: Iniciando integração...');
        
        if (isTicketPage()) {
            console.log('Clockify: Página de ticket detectada');
            
            // Tenta inserir o popup imediatamente
            insertClockifyButton();
            
            // Também tenta após pequenos delays para aguardar carregamento dinâmico
            setTimeout(insertClockifyButton, 500);
            setTimeout(insertClockifyButton, 1000);
            setTimeout(insertClockifyButton, 2000);
            
            // Observa mudanças no DOM para páginas com carregamento dinâmico
            const observer = new MutationObserver((mutations) => {
                let shouldRetry = false;
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // Verifica se foram adicionados elementos significativos
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                if (node.classList && (
                                    node.classList.contains('card') ||
                                    node.classList.contains('timeline-item') ||
                                    node.classList.contains('page-header') ||
                                    node.classList.contains('main-content')
                                )) {
                                    shouldRetry = true;
                                }
                            }
                        });
                    }
                });
                
                if (shouldRetry && !document.querySelector('#clockify-popup')) {
                    console.log('Clockify: DOM alterado, tentando criar popup novamente...');
                    setTimeout(insertClockifyButton, 500);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            // Para garantir, tenta novamente quando a página estiver completamente carregada
            if (document.readyState !== 'complete') {
                window.addEventListener('load', () => {
                    setTimeout(insertClockifyButton, 1000);
                });
            }
        } else {
            console.log('Clockify: Não é uma página de ticket');
        }
    };

    // Executa quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    } // Fecha initClockifyIntegration
    
})();
