# Correção de Erros de Instalação - Plugin Hours Tracking

## Problema Identificado

O erro no console indicava tentativas de inserção de registros duplicados na tabela `glpi_plugin_hourstracking_configs`:

```
Error: Duplicate entry 'enable_detailed_logging' for key 'uniq_name'
Error: Duplicate entry 'report_export_format' for key 'uniq_name'
```

## Causa Raiz

- Havia duas funções `plugin_hourstracking_install()` diferentes:
  - Uma em `hook.php` (linha 11)
  - Outra em `install/install.php` (linha 15)
- A função em `hook.php` não verificava se as configurações já existiam antes de tentar inseri-las
- Isso causava violação da constraint UNIQUE KEY `uniq_name` quando o plugin era reinstalado

## Correções Aplicadas

### 1. Reorganização das Funções de Instalação

- **`hook.php`**: Modificado para chamar a instalação completa do `install.php`
- **`install/install.php`**: Renomeado para `plugin_hourstracking_install_complete()` para evitar conflito
- Adicionada verificação de existência antes de inserir configurações

### 2. Novo Sistema de Migração

Criado `install/migration.php` com funções para:
- `plugin_hourstracking_fix_duplicate_configs()`: Remove duplicatas mantendo a entrada mais antiga
- `plugin_hourstracking_check_installation_integrity()`: Verifica problemas na instalação
- `plugin_hourstracking_auto_fix()`: Executa correções automáticas
- `plugin_hourstracking_safe_reinstall()`: Reinstalação segura

### 3. Script de Correção Imediata

Criado `fix_duplicates.php` para correção imediata dos problemas existentes.

## Como Usar

### Correção Imediata (Execute no container)

```bash
# Entre no container GLPI
docker exec -it <container_name> bash

# Execute o script de correção
php /var/www/glpi/plugins/hourstracking/fix_duplicates.php
```

### Reinstalação do Plugin

1. Acesse GLPI como administrador
2. Vá em Setup > Plugins
3. Desinstale o plugin Hours Tracking
4. Reinstale o plugin

As verificações agora evitarão duplicatas.

## Verificações Implementadas

### No Código
- Verificação de existência antes de inserir configurações
- Tratamento de exceções durante a instalação
- Logs de erro detalhados

### No Banco
- Manutenção da constraint UNIQUE KEY para evitar duplicatas futuras
- Limpeza automática de registros duplicados existentes

## Configurações Gerenciadas

O plugin gerencia as seguintes configurações:
- `default_hourly_rate`: Taxa padrão por hora (100.00)
- `enable_detailed_logging`: Log detalhado ativado (1)
- `report_export_format`: Formatos de exportação (csv,pdf)
- `minimum_hours`: Horas mínimas (1)
- `billing_workdays`: Dias úteis para faturamento (22)
- `time_rounding`: Arredondamento de tempo em minutos (15)

## Estrutura de Arquivos

```
hourstracking/
├── hook.php                    # Hooks principais do plugin
├── setup.php                   # Configuração e inicialização
├── install/
│   ├── install.php             # Instalação completa
│   ├── migration.php           # Funções de migração e correção
│   └── uninstall.php          # Desinstalação
├── fix_duplicates.php          # Script de correção imediata
└── INSTALL_FIX.md             # Esta documentação
```

## Monitoramento

Para verificar se há problemas futuros, você pode:

1. Verificar logs do GLPI em `/var/www/glpi/files/_log/`
2. Executar verificação de integridade:
   ```php
   include 'plugins/hourstracking/install/migration.php';
   $issues = plugin_hourstracking_check_installation_integrity();
   var_dump($issues);
   ```

## Prevenção

- O código agora sempre verifica existência antes de inserir
- Função de auto-correção é executada automaticamente durante instalação
- Logs detalhados ajudam a identificar problemas rapidamente
