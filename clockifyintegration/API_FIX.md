# Correção da API do Clockify - Endpoints Corretos

## Problema Identificado

O erro `{"message":"No static resource v1/time-entries.","code":3000}` indicava que o endpoint da API do Clockify estava incorreto.

## Endpoint Incorreto (Anterior)
```javascript
fetch("https://api.clockify.me/api/v1/time-entries", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "X-Api-Key": apiKey,
    },
    body: JSON.stringify({
        "start": new Date().toISOString(),
        "description": description,
        "workspaceId": workspace,  // ❌ workspaceId no body
    }),
})
```

## Endpoint Correto (Corrigido)
```javascript
fetch(`https://api.clockify.me/api/v1/workspaces/${workspace}/time-entries`, {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "X-Api-Key": apiKey,
    },
    body: JSON.stringify({
        "start": new Date().toISOString(),
        "description": description
        // ✅ workspaceId agora está na URL
    }),
})
```

## Diferenças Corrigidas

### 1. URL do Endpoint
- **Antes**: `https://api.clockify.me/api/v1/time-entries`
- **Depois**: `https://api.clockify.me/api/v1/workspaces/${workspace}/time-entries`

### 2. Estrutura do Body
- **Antes**: Incluía `workspaceId` no JSON do body
- **Depois**: O workspace ID agora é parte da URL

### 3. Conformidade com a API
O endpoint corrigido segue o padrão da API REST do Clockify:
- Recursos específicos de workspace devem incluir o workspace ID na URL
- O workspace ID não deve ser enviado no body da requisição

## Documentação da API Clockify

### Endpoints Corretos:
- **Criar Time Entry:** `POST /workspaces/{workspaceId}/time-entries`
- **Obter Usuário:** `GET /user`
- **Parar Time Entry Ativo:** `PATCH /workspaces/{workspaceId}/user/{userId}/time-entries`

### Fluxo para Parar Timer:
1. **GET** `/user` → Obtém `userId`
2. **PATCH** `/workspaces/{workspaceId}/user/{userId}/time-entries` → Para o timer

## Teste da Correção

Após a correção, as chamadas da API devem retornar:
- **Criar Timer - Sucesso:** Status 201 com dados do time entry criado
- **Obter Usuário - Sucesso:** Status 200 com dados do usuário (incluindo ID)
- **Parar Timer - Sucesso:** Status 200 com dados do time entry finalizado
- **Erro de configuração:** Verificar API Key e Workspace ID
- **Erro de permissão:** Verificar se a API Key tem permissões no workspace

## Validação

Para verificar se a correção funcionou:

1. Abra o console do navegador (F12)
2. Vá para uma página de ticket do GLPI
3. Clique no botão "Iniciar" do Clockify
4. Observe os logs do console:
   - ✅ `Clockify: Timer iniciado com sucesso`
   - ❌ `Clockify: Erro ao iniciar timer:` (se ainda houver problemas)

## Arquivos Modificados

- `js/clockify.js` - Corrigido endpoint para criar time entries

## Próximos Passos

Se ainda houver erros:
1. Verifique se a API Key está correta
2. Confirme se o Workspace ID está correto
3. Teste as credenciais diretamente na API do Clockify
4. Verifique se há restrições de CORS ou firewall
