# Atualização do Plugin Clockify - Interface Popup

## Resumo das Mudanças

O plugin Clockify foi completamente reformulado para usar uma interface de popup minimizável ao invés do botão simples anterior. As principais melhorias incluem:

### ✅ Principais Funcionalidades Implementadas

1. **Popup Minimizável**
   - Interface moderna e intuitiva
   - Posicionado no canto superior direito da tela
   - Pode ser minimizado/maximizado
   - Pode ser movido arrastando pela barra de título
   - Botão para fechar o popup

2. **Obtenção Simplificada de Dados**
   - Código e assunto do ticket obtidos diretamente da URL
   - Parsing inteligente do título da página do GLPI
   - Processo mais confiável e simples

3. **Interface do Cronômetro**
   - Display do tempo em formato HH:MM:SS
   - Botões para iniciar e parar o cronômetro
   - Status visual em tempo real
   - Feedback visual durante operações da API

4. **Integração com API do Clockify**
   - Chamadas reais para a API do Clockify
   - Tratamento de erros robusto
   - Estados de loading durante requisições
   - Feedback ao usuário sobre sucesso/erro

## Estrutura do Popup

```
┌─────────────────────────────────────┐
│ #1 - Título do Ticket        [−][×] │ ← Barra de título (draggable)
├─────────────────────────────────────┤
│           00:00:00                  │ ← Display do cronômetro
│                                     │
│    [▶ Iniciar]  [⏸ Parar]          │ ← Botões de controle
│                                     │
│         Pronto para iniciar         │ ← Status
└─────────────────────────────────────┘
```

## Funcionalidades do Popup

### Controles da Interface
- **Arrastar**: Clique e arraste na barra de título para mover o popup
- **Minimizar**: Clique no botão `−` para ocultar o conteúdo
- **Maximizar**: Clique no botão `+` para mostrar o conteúdo novamente
- **Fechar**: Clique no botão `×` para remover o popup

### Controles do Cronômetro
- **Iniciar**: Inicia o cronômetro no Clockify com a descrição do ticket
- **Parar**: Para o cronômetro ativo no Clockify
- **Display**: Mostra o tempo decorrido em formato HH:MM:SS

## Obtenção de Dados do Ticket

O sistema agora obtém as informações do ticket de forma mais simples:

1. **ID do Ticket**: Extraído do parâmetro `id` da URL
2. **Título do Ticket**: Extraído do título da página (`document.title`)
3. **Formato esperado**: `Chamado (#1) - testee - GLPI`

### Exemplo de Processamento
```javascript
URL: /front/ticket.form.php?id=1
Título da página: "Chamado (#1) - testee - GLPI"

Resultado:
- ticketId: "1"
- ticketTitle: "testee"
- Descrição final: "GLPI Ticket #1 - testee"
```

## Estados Visuais

### Estados do Status
- **Pronto para iniciar** (cinza): Estado inicial
- **Iniciando...** (amarelo): Fazendo requisição para iniciar
- **Cronômetro ativo** (verde): Timer rodando
- **Parando...** (amarelo): Fazendo requisição para parar
- **Cronômetro parado** (vermelho): Timer parado
- **Erro ao iniciar/parar** (vermelho): Erro na API

### Estados dos Botões
- **Iniciar**: Visível quando o cronômetro não está ativo
- **Parar**: Visível quando o cronômetro está ativo
- **Desabilitado**: Durante requisições à API (opacity: 0.6)

## Integração com API do Clockify

### Função `startClockifyTimer(description)`
- Inicia um novo timer no Clockify
- Usa as configurações do plugin (apiKey, workspaceId)
- Retorna uma Promise

### Função `stopClockifyTimer()`
- Para o timer ativo no usuário atual
- Adiciona o timestamp de fim
- Retorna uma Promise

### Tratamento de Erros
- Configurações ausentes (apiKey/workspaceId)
- Erros de rede
- Erros da API do Clockify
- Feedback visual ao usuário

## Inicialização Robusta

O popup é criado automaticamente quando:
1. Uma página de ticket é detectada
2. As informações do ticket são obtidas com sucesso
3. Múltiplas tentativas em intervalos diferentes
4. Observação de mudanças no DOM para carregamento dinâmico

## Compatibilidade

O sistema foi projetado para funcionar com:
- GLPI 10.x (estrutura moderna)
- Diferentes tipos de tickets
- Carregamento dinâmico de conteúdo
- Múltiplas estruturas de URL

## Testes Recomendados

1. **Teste Básico**
   - Navegar para um ticket (ex: ticket.form.php?id=1)
   - Verificar se o popup aparece no canto superior direito
   - Testar arrastar o popup
   - Testar minimizar/maximizar

2. **Teste do Cronômetro**
   - Clicar em "Iniciar" e verificar a integração com Clockify
   - Observar o timer contando
   - Clicar em "Parar" e verificar se para

3. **Teste de Erros**
   - Testar sem configurações (apiKey/workspaceId)
   - Testar com configurações inválidas
   - Verificar mensagens de erro

4. **Teste de Interface**
   - Verificar responsividade
   - Testar em diferentes resoluções
   - Verificar se não interfere com a interface do GLPI

## Arquivos Modificados

- `clockifyintegration/js/clockify.js`: Lógica principal reescrita
- Este documento: `POPUP_UPDATE.md`

## Próximos Passos

1. Testar em ambiente real do GLPI
2. Validar com diferentes tipos de tickets
3. Ajustar estilos se necessário
4. Adicionar configurações personalizáveis para posicionamento
5. Implementar persistência da posição do popup
