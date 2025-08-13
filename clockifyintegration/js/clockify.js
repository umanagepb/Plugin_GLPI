(function() {
    // Recupera as configurações dinâmicas injetadas via PHP
    let config = window.clockifyIntegrationConfig || {};
    
    // Log para debug das configurações
    console.log('Clockify Integration - Configurações carregadas:', {
        apiKey: config.apiKey ? 'Configurado' : 'Não configurado',
        workspaceId: config.workspaceId ? 'Configurado' : 'Não configurado'
    });

    /**
     * Função que inicia o timer no Clockify utilizando a API.
     * @param {string} description - A descrição da tarefa, contendo o número e o título do ticket.
     */
    const startTimer = (description) => {
        const apiKey    = config.apiKey;
        const workspace = config.workspaceId;

        if (!apiKey || !workspace) {
            alert('Configurações do Clockify não encontradas. Por favor, configure a API Key e o Workspace ID nas configurações do plugin.');
            return;
        }

        const data = {
            "start": new Date().toISOString(),
            "description": description,
            "workspaceId": workspace,
        };

        fetch("https://api.clockify.me/api/v1/time-entries", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Api-Key": apiKey,
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(data => {
            alert("Timer iniciado: " + JSON.stringify(data));
        })
        .catch((error) => {
            console.error("Erro ao iniciar o timer:", error);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Verifica se a URL indica que a página é de ticket
        if (window.location.href.indexOf("ticket") !== -1) {
            let description = "";

            // Tenta capturar o elemento que contém o título do ticket.
            // Ajuste o seletor conforme a estrutura do seu GLPI.
            let ticketTitleElement = document.querySelector("title");
            if (!ticketTitleElement) {
                // Se não encontrar por classe, tenta pegar o primeiro <h1> da página.
                ticketTitleElement = document.querySelector("h1");
            }
            if (ticketTitleElement) {
                description = ticketTitleElement.textContent.trim();
            }

            // Tenta obter o ID do ticket a partir de um campo hidden ou da query string.
            let ticketId = null;
            const inputId = document.querySelector('input[name="id"]');
            if (inputId && inputId.value) {
                ticketId = inputId.value;
            } else {
                const urlParams = new URLSearchParams(window.location.search);
                ticketId = urlParams.get('id');
            }
            if (ticketId) {
                description = "#" + ticketId + " " + description;
            }

            // Cria o botão que iniciará o timer
            let container = document.createElement('div');
            container.style.marginTop = "10px";
            let button = document.createElement('button');
            button.innerText = 'Iniciar Clockify';
            button.onclick = () => startTimer(description);
            container.appendChild(button);

            // Insere o botão logo após o elemento do título do ticket.
            if (ticketTitleElement && ticketTitleElement.parentNode) {
                ticketTitleElement.parentNode.insertBefore(container, ticketTitleElement.nextSibling);
            } else {
                document.body.appendChild(container);
            }
        }
    });
})();
