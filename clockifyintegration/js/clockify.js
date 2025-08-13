(function() {
    // Recupera as configurações dinâmicas injetadas via PHP
    let config = window.clockifyIntegrationConfig || {};
    
    // Log para debug das configurações
    console.log('Clockify Integration - Configurações carregadas:', {
        apiKey: config.apiKey ? 'Configurado' : 'Não configurado',
        workspaceId: config.workspaceId ? 'Configurado' : 'Não configurado'
    });

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
        const apiKey = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
        }

        const data = {
            "start": new Date().toISOString(),
            "description": description,
            "workspaceId": workspace,
        };

        return fetch("https://api.clockify.me/api/v1/time-entries", {
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
     * Função que para o timer no Clockify utilizando a API.
     */
    const stopClockifyTimer = () => {
        const apiKey = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
        }

        return fetch(`https://api.clockify.me/api/v1/workspaces/${workspace}/user/time-entries`, {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json",
                "X-Api-Key": apiKey,
            },
            body: JSON.stringify({
                "end": new Date().toISOString()
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
     * Cria o popup do Clockify
     */
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
                .then(() => {
                    // Sucesso - inicia o timer local
                    startTime = Date.now();
                    timerInterval = setInterval(updateTimer, 1000);
                    
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-flex';
                    statusEl.textContent = 'Cronômetro ativo';
                    statusEl.style.color = '#28a745';
                    
                    console.log('Clockify: Timer iniciado com sucesso');
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
            console.log('Clockify: Parando cronômetro');
            
            // Desabilita o botão durante a requisição
            stopBtn.disabled = true;
            stopBtn.style.opacity = '0.6';
            statusEl.textContent = 'Parando...';
            statusEl.style.color = '#ffc107';
            
            // Chama a API do Clockify
            stopClockifyTimer()
                .then(() => {
                    // Sucesso - para o timer local
                    if (timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                    
                    startBtn.style.display = 'inline-flex';
                    stopBtn.style.display = 'none';
                    statusEl.textContent = 'Cronômetro parado';
                    statusEl.style.color = '#dc3545';
                    timerDisplay.textContent = '00:00:00';
                    startTime = null;
                    
                    console.log('Clockify: Timer parado com sucesso');
                    
                    // Volta para o estado inicial após 2 segundos
                    setTimeout(() => {
                        statusEl.textContent = 'Pronto para iniciar';
                        statusEl.style.color = '#666';
                    }, 2000);
                })
                .catch((error) => {
                    // Erro - reabilita o botão
                    console.error('Clockify: Erro ao parar timer:', error);
                    stopBtn.disabled = false;
                    stopBtn.style.opacity = '1';
                    statusEl.textContent = 'Erro ao parar';
                    statusEl.style.color = '#dc3545';
                    
                    // Volta para o estado anterior após 3 segundos
                    setTimeout(() => {
                        statusEl.textContent = 'Cronômetro ativo';
                        statusEl.style.color = '#28a745';
                    }, 3000);
                });
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
            
            // Tenta inserir o botão imediatamente
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
                
                if (shouldRetry && !document.querySelector('.clockify-integration-container')) {
                    console.log('Clockify: DOM alterado, tentando inserir botão novamente...');
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
})();
