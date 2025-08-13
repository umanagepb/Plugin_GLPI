# Evolution Integration Plugin for GLPI

Plugin de integração entre GLPI e Evolution API para WhatsApp Business.

## Funcionalidades

- ✅ Integração completa com Evolution API
- ✅ Gerenciamento de conversas automático
- ✅ Notificações via WhatsApp
- ✅ Relatórios de tempo de conversa
- ✅ Webhook para recebimento de mensagens
- ✅ Configuração através da interface do GLPI
- ✅ Limpeza automática de dados antigos (cron)
- ✅ Suporte a múltiplas instâncias
- ✅ Teste de conectividade com a API

## Instalação

1. Copie o diretório `evolutionintegration` para `plugins/` do GLPI
2. Acesse **Configuração > Plugins** no GLPI
3. Instale e ative o plugin "Evolution Integration"
4. Configure o plugin em **Configuração > Evolution Integration**

## Configuração

### 1. Configuração da API
- **API Endpoint**: URL base da sua instância Evolution API
- **API Token**: Token de acesso da API
- **Instância Padrão**: Nome da instância WhatsApp padrão

### 2. Configuração de Conversas
- **Tempo Limite de Inatividade**: Tempo em segundos para considerar conversa inativa
- **Dias de Retenção**: Quantos dias manter histórico de conversas
- **Fechar Conversas Automaticamente**: Se deve fechar conversas quando ticket é fechado

### 3. Configuração de Notificações
- **Habilitar Notificações**: Ativar envio de notificações via WhatsApp
- **Modelo de Notificação**: Template das mensagens (suporta variáveis)
- **URL do Webhook**: URL para receber webhooks da Evolution API

### Variáveis Disponíveis no Template:
- `{ticket_title}`: Título do ticket
- `{ticket_id}`: ID do ticket
- `{ticket_status}`: Status do ticket
- `{action}`: Ação realizada (created, closed, etc.)

## Uso

### Notificações Automáticas
O plugin enviará automaticamente notificações via WhatsApp quando:
- Um novo ticket for criado
- Um ticket for fechado
- Outras ações configuradas

### Webhook
Configure o webhook na Evolution API para:
```
https://seu-glpi.com/plugins/evolutionintegration/ajax/webhook.php
```

### Relatórios
Acesse **Ferramentas > Relatórios > Evolution Integration** para visualizar:
- Tempo total de conversas por ticket
- Histórico de mensagens
- Estatísticas de uso

## Estrutura do Banco de Dados

### Tabela: glpi_plugin_evolutionintegration_conversations
- `id`: ID único da conversa
- `ticket_id`: ID do ticket relacionado
- `start_time`: Início da conversa
- `end_time`: Fim da conversa
- `last_activity_time`: Última atividade
- `total_duration`: Duração total em segundos
- `status`: Status da conversa (1=aberta, 2=fechada)

### Tabela: glpi_plugin_evolutionintegration_conversation_history
- `id`: ID único do registro
- `conversation_id`: ID da conversa
- `message`: Conteúdo da mensagem
- `timestamp`: Data/hora da mensagem

## API Integration

### Métodos Disponíveis

#### Enviar Mensagem
```php
PluginEvolutionintegrationApi::sendMessage($phone, $message, $instance);
```

#### Enviar Mídia
```php
PluginEvolutionintegrationApi::sendMedia($phone, $mediaUrl, $caption, $instance);
```

#### Testar Conexão
```php
PluginEvolutionintegrationApi::testConnection();
```

#### Criar Instância
```php
PluginEvolutionintegrationApi::createInstance($instanceName, $qrcode);
```

## Manutenção

### Limpeza Automática
O plugin executa automaticamente (via cron) a limpeza de:
- Conversas antigas baseadas na configuração de retenção
- Histórico de mensagens relacionado

### Logs
Webhooks são registrados em: `files/_log/evolution_webhook.log`

## Requisitos

- GLPI 10.0 ou superior
- PHP 7.4 ou superior
- Evolution API configurada e funcionando
- Extensões PHP: curl, json

## Suporte

Para suporte técnico, entre em contato com:
- **Empresa**: Umanage Tecnologia de Gestão Ltda
- **Website**: https://umanage.com.br

## Licença

GPL v2+ - Veja o arquivo LICENSE para detalhes.

## Changelog

### Versão 1.0.0
- Versão inicial com integração completa Evolution API
- Suporte a webhooks e notificações
- Relatórios de conversas
- Configuração via interface web
- Limpeza automática de dados
