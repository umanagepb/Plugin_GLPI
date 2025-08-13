# Plugin Hours Tracking para GLPI

## Descrição

O plugin Hours Tracking é uma solução completa para controle e rastreamento de horas de atendimento no GLPI. Permite o gerenciamento de taxas horárias por cliente, geração de relatórios detalhados e exportação de dados para CSV e PDF.

## Funcionalidades

### 📊 Relatórios
- **Relatórios Gerais**: Visão geral de todas as atividades com filtros por período, atendente e cliente
- **Relatórios por Atendente**: Análise detalhada do trabalho de cada técnico (resumo e detalhado)
- **Relatórios por Cliente**: Relatórios específicos para envio aos clientes com detalhamento completo

### 💰 Gestão de Taxas
- Configuração de taxas horárias específicas por cliente/entidade
- Taxa padrão configurável para clientes sem taxa específica
- Cálculo automático de valores baseado no tempo trabalhado

### 📋 Configurações
- Taxa horária padrão
- Horas mínimas de faturamento
- Dias úteis de faturamento
- Arredondamento de tempo (1, 5, 10, 15, 30 ou 60 minutos)
- Logs detalhados habilitáveis

### 📄 Exportação
- **CSV**: Relatórios em formato CSV com codificação UTF-8
- **PDF**: Relatórios formatados em PDF usando TCPDF
- Dados prontos para faturamento e análise

### 🔒 Segurança
- Controle de acesso baseado em perfis do GLPI
- Proteção CSRF em todos os formulários
- Validação rigorosa de dados de entrada
- Logs de erro para auditoria

## Requisitos

- GLPI 10.0 ou superior
- PHP 7.4 ou superior
- Extensão PHP: mysqli, json
- Biblioteca TCPDF (incluída no GLPI 10+)

## Instalação

1. Faça o download ou clone o repositório na pasta `plugins/hourstracking` do GLPI
2. Acesse **Configurar > Plugins** no GLPI
3. Localize o plugin "Controle de Horas" e clique em **Instalar**
4. Após a instalação, clique em **Ativar**
5. Configure as permissões nos perfis de usuário em **Administração > Perfis**

## Configuração

### Permissões
Configure as seguintes permissões nos perfis:
- `plugin_hourstracking_report`: Visualizar e gerar relatórios
- `plugin_hourstracking_clientrate`: Gerenciar taxas dos clientes
- `plugin_hourstracking_config`: Configurar o plugin

### Configurações Iniciais
1. Acesse **Ferramentas > Controle de Horas > Configurações**
2. Configure:
   - Taxa horária padrão (R$)
   - Horas mínimas de faturamento
   - Dias úteis de faturamento (para cálculos mensais)
   - Arredondamento de tempo

### Taxas por Cliente
1. Acesse **Ferramentas > Controle de Horas > Taxas por Cliente**
2. Selecione o cliente/entidade
3. Defina a taxa horária específica
4. Salve as configurações

## Uso

### Gerando Relatórios

#### Relatório Geral
1. Acesse **Ferramentas > Controle de Horas > Relatórios**
2. Defina o período (data início e fim)
3. Selecione filtros opcionais (atendente, cliente)
4. Clique em **Gerar Relatório**, **Exportar CSV** ou **Exportar PDF**

#### Relatório por Atendente
1. Acesse **Ferramentas > Controle de Horas > Por Atendente**
2. Defina o período
3. Escolha entre **Resumo** (totais por atendente) ou **Detalhado** (todos os atendimentos)
4. Gere o relatório

#### Relatório por Cliente
1. Acesse **Ferramentas > Controle de Horas > Por Cliente**
2. Defina o período
3. Selecione o cliente específico
4. Gere o relatório ou exporte em CSV

### Dados Utilizados

O plugin utiliza as seguintes tabelas do GLPI:
- `glpi_tickettasks`: Tarefas dos tickets (fonte dos tempos)
- `glpi_tickets`: Informações dos tickets
- `glpi_entities`: Clientes/entidades
- `glpi_users`: Usuários (técnicos e solicitantes)

E cria as seguintes tabelas:
- `glpi_plugin_hourstracking_configs`: Configurações do plugin
- `glpi_plugin_hourstracking_clientrates`: Taxas por cliente

## Estrutura do Plugin

```
hourstracking/
├── ajax/                    # Scripts AJAX
│   └── hourstracking.php
├── front/                   # Interfaces web
│   ├── config.form.php     # Configurações
│   ├── reports.php         # Relatórios gerais
│   ├── attendant_report.php # Relatórios por atendente
│   ├── client_report.php   # Relatórios por cliente
│   └── client_rates.php    # Gestão de taxas
├── inc/                     # Classes PHP
│   ├── config.class.php    # Configurações
│   ├── report.class.php    # Relatórios
│   ├── clientrate.class.php # Taxas dos clientes
│   ├── profile.class.php   # Perfis e permissões
│   └── plugin.class.php    # Classe principal
├── install/                 # Scripts de instalação
│   ├── install.php
│   └── uninstall.php
├── locales/                 # Traduções
│   └── pt_BR.po
├── hook.php                 # Hooks do GLPI
├── setup.php               # Configuração do plugin
├── manifest.xml            # Metadados
└── README.md               # Esta documentação
```

## Cálculos Realizados

### Tempo de Trabalho
- Baseado no campo `actiontime` da tabela `glpi_tickettasks`
- Convertido de segundos para horas (divisão por 3600)
- Arredondamento configurável

### Valores Monetários
- Valor = (Tempo em horas) × (Taxa horária do cliente)
- Se cliente não tem taxa específica, usa taxa padrão
- Valores exibidos em formato brasileiro (R$ 1.234,56)

### Filtros de Segurança
- Apenas entidades que o usuário tem acesso são mostradas
- Apenas tickets com tempo > 0 são considerados
- Validação de datas e IDs numéricos

## Solução de Problemas

### Plugin não aparece no menu
- Verifique se está ativado em **Configurar > Plugins**
- Verifique as permissões do perfil do usuário
- Verifique os logs de erro do GLPI

### Relatórios em branco
- Verifique se há dados no período selecionado
- Verifique se o usuário tem acesso às entidades
- Verifique se existem tarefas com tempo registrado

### Erro de permissão
- Configure as permissões nos perfis
- Verifique se o usuário está no perfil correto
- Recarregue a página após alterar permissões

### Problemas de exportação
- Verifique se a biblioteca TCPDF está disponível (PDF)
- Verifique permissões de escrita temporária (CSV/PDF)
- Verifique se não há saída antes dos headers (erro PHP)

## Segurança

### Medidas Implementadas
- Validação de tokens CSRF
- Sanitização de todas as entradas
- Uso de prepared statements (QueryBuilder do GLPI)
- Controle de acesso por entidades
- Logs de erros para auditoria

### Recomendações
- Mantenha o GLPI sempre atualizado
- Configure perfis com permissões mínimas necessárias
- Monitore os logs de erro regularmente
- Faça backups regulares do banco de dados

## Changelog

### Versão 1.0.0
- Lançamento inicial
- Relatórios gerais, por atendente e por cliente
- Gestão de taxas horárias
- Exportação CSV e PDF
- Sistema completo de permissões
- Tradução para português brasileiro

## Suporte

Para suporte técnico ou relatório de bugs:
1. Verifique os logs de erro do GLPI
2. Consulte esta documentação
3. Verifique as configurações de permissões

## Licença

Este plugin é distribuído sob a licença GPL v2+, a mesma do GLPI.

## Contribuições

Contribuições são bem-vindas! Por favor:
1. Faça um fork do projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Faça um pull request

---

**Desenvolvido para GLPI 10.0+**  
**Compatível com PHP 7.4+**  
**Versão: 1.0.0**
