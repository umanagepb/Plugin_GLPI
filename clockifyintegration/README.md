# Clockify Integration Plugin para GLPI

Este plugin permite integrar o GLPI com o Clockify, adicionando um botão para iniciar cronômetros diretamente na interface dos tickets.

## Funcionalidades

- Botão "Iniciar Clockify" integrado na área do cabeçalho dos tickets
- Inicialização automática de cronômetros no Clockify com o título e ID do ticket
- Configuração simples via interface do GLPI
- Compatível com GLPI 10.x e 9.x
- Interface responsiva e integrada ao design do GLPI

## Instalação

1. Extraia o plugin para o diretório `plugins/clockifyintegration/` do seu GLPI
2. Acesse o GLPI como administrador
3. Vá em **Configurar > Plugins**
4. Localize "Clockify Integration" e clique em **Instalar**
5. Após a instalação, clique em **Ativar**

## Configuração

1. Vá em **Configurar > Plugins**
2. Clique em "Clockify Integration"
3. Configure os seguintes campos:
   - **Clockify API Key**: Sua chave de API do Clockify (obtenha em: https://clockify.me/user/settings)
   - **Clockify Workspace ID**: ID do workspace do Clockify

### Como obter as credenciais do Clockify:

1. **API Key**: 
   - Acesse https://clockify.me/user/settings
   - Na seção "API Key", gere uma nova chave

2. **Workspace ID**:
   - Acesse https://clockify.me/workspaces
   - Copie o ID do workspace desejado (está na URL)

## Uso

1. Abra qualquer ticket no GLPI
2. O botão "⏱️ Iniciar Clockify" aparecerá no cabeçalho do ticket, próximo ao título
3. Clique no botão para iniciar um cronômetro no Clockify
4. A descrição do cronômetro incluirá automaticamente o ID e título do ticket

## Funcionalidades Técnicas

### Detecção Inteligente de Local
O plugin utiliza múltiplos seletores CSS para encontrar o melhor local para inserir o botão:
- GLPI 10.x: Integração com Bootstrap e cards
- GLPI 9.x: Compatibilidade com tabelas tradicionais
- Fallbacks para diferentes estruturas de layout

### Obtenção de Informações do Ticket
- ID do ticket: Extraído de campos hidden ou URL
- Título do ticket: Capturado de múltiplas fontes possíveis
- Descrição automática: Formato "#ID - Título"

### Tratamento de Erros
- Validação de configurações antes de enviar
- Mensagens de erro amigáveis
- Logs detalhados no console para debug

## Estrutura do Plugin

```
clockifyintegration/
├── css/
│   └── clockify.css          # Estilos visuais do botão
├── front/
│   └── config.form.php       # Interface de configuração
├── inc/
│   ├── config.class.php      # Classe de configuração
│   └── plugin.class.php      # Classe principal do plugin
├── install/
│   ├── install.php           # Script de instalação
│   └── uninstall.php         # Script de desinstalação
├── js/
│   └── clockify.js           # Lógica JavaScript principal
├── locales/
│   └── pt_BR.po              # Traduções PT-BR
├── hook.php                  # Hooks do GLPI
├── manifest.xml              # Metadados do plugin
├── setup.php                 # Configuração e inicialização
└── README.md                 # Esta documentação
```

## Compatibilidade

- **GLPI**: 10.0+ (testado até 10.1.99)
- **PHP**: 7.4+
- **Navegadores**: Chrome, Firefox, Safari, Edge
- **Extensões PHP**: cURL (obrigatória)

## Troubleshooting

### O botão não aparece
1. Verifique se o plugin está ativado
2. Confirme se você está em uma página de ticket
3. Abra o console do navegador (F12) e procure por logs do "Clockify"
4. Verifique se não há erros JavaScript

### Erro ao iniciar cronômetro

#### Erro: "No static resource v1/time-entries" (CORRIGIDO)
Este erro foi corrigido na versão atual. Se ainda estiver ocorrendo:
1. Verifique se você tem a versão mais recente do plugin
2. O endpoint correto é: `https://api.clockify.me/api/v1/workspaces/{workspaceId}/time-entries`

#### Outros erros de API
1. Verifique se a API Key está correta
2. Confirme se o Workspace ID está correto
3. Verifique sua conexão com a internet
4. Teste as credenciais diretamente na API do Clockify usando o arquivo `test_api_fix.html`

### Problemas de layout
1. O CSS do plugin usa `!important` para evitar conflitos
2. Em caso de problemas, verifique se há CSS personalizado interferindo
3. Teste em diferentes temas do GLPI

## Changelog

### Versão 1.0.0 (Atual)
- ✅ Botão integrado no cabeçalho dos tickets
- ✅ Detecção automática do melhor local de inserção
- ✅ Compatibilidade com GLPI 10.x e 9.x
- ✅ Estilos CSS responsivos
- ✅ Tratamento robusto de erros
- ✅ Múltiplas estratégias de captura de dados do ticket
- ✅ Observador de mudanças no DOM para carregamento dinâmico

## Suporte

Para reportar bugs ou solicitar funcionalidades, abra uma issue no repositório do projeto.
