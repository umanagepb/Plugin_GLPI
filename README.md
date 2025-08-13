# GLPI com Plugins - Configuração Docker

Este repositório contém a configuração completa do GLPI 10.0 com plugins personalizados usando Docker.

## Plugins Incluídos

- **Evolution Integration**: Integração com sistema Evolution
- **Hours Tracking**: Controle de horas trabalhadas
- **Clockify Integration**: Integração com Clockify

## Pré-requisitos

- Docker
- Docker Compose
- Git (para clonar o repositório)

### Windows
- PowerShell (para script .ps1)
- Git Bash ou WSL (para scripts .sh)

## Instalação Rápida

### No Windows (PowerShell)
```powershell
# Execute como administrador ou ajuste a política de execução
powershell -ExecutionPolicy Bypass -File setup-glpi.ps1
```

### No Linux/Mac ou Git Bash
```bash
# Torna o script executável e executa
chmod +x setup-glpi.sh
./setup-glpi.sh
```

## Scripts Disponíveis

### 1. Configuração Inicial
- `setup-glpi.ps1` - Script para Windows (PowerShell)
- `setup-glpi.sh` - Script para Linux/Mac/Git Bash

### 2. Verificação de Status
```bash
./check-status.sh
```
Verifica se todos os serviços estão funcionando corretamente.

### 3. Manutenção
```bash
./maintenance.sh [opção]
```

**Opções disponíveis:**
- `start` - Inicia os containers
- `stop` - Para os containers
- `restart` - Reinicia os containers
- `logs` - Mostra logs dos containers
- `backup` - Faz backup do banco de dados
- `restore` - Restaura backup do banco
- `clean` - Remove containers e volumes (CUIDADO!)
- `plugins` - Lista status dos plugins
- `permissions` - Corrige permissões dos plugins
- `cache` - Limpa cache do GLPI
- `status` - Executa verificação de status
- `help` - Mostra ajuda

## Acesso ao Sistema

Após a instalação:

- **URL**: http://localhost:8080
- **Usuário**: glpi
- **Senha**: glpi123

## Estrutura dos Containers

### Container do Banco (glpi_db)
- **Imagem**: mariadb:10.11
- **Porta**: 3306 (interna)
- **Dados**: Persistidos no volume `db_data`

### Container do GLPI (glpi_app)
- **Imagem**: ghcr.io/glpi-project/glpi:10.0
- **Porta**: 8080 (mapeada para 80 interna)
- **Volumes**: 
  - Arquivos GLPI persistidos
  - Plugins montados como volumes bind

## Volumes Docker

- `db_data` - Dados do MariaDB
- `files` - Arquivos do GLPI
- `config` - Configurações do GLPI
- `marketplace` - Marketplace do GLPI
- `plugins` - Plugins instalados

## Health Checks

Os containers possuem verificações de saúde:
- **MariaDB**: Verifica conectividade com mysqladmin
- **GLPI**: Verifica resposta HTTP

## Desenvolvimento de Plugins

Os plugins estão montados como volumes bind, permitindo edição em tempo real:

```bash
# Estrutura dos plugins
├── evolutionintegration/
├── hourstracking/
└── clockifyintegration/
```

### Reiniciar após mudanças nos plugins:
```bash
./maintenance.sh restart
./maintenance.sh cache
```

## Troubleshooting

### Problemas Comuns

#### 1. Containers não iniciam
```bash
./maintenance.sh logs
```

#### 2. Permissões incorretas
```bash
./maintenance.sh permissions
```

#### 3. Cache corrompido
```bash
./maintenance.sh cache
```

#### 4. Backup do banco
```bash
./maintenance.sh backup
```

#### 5. Verificação completa
```bash
./check-status.sh
```

### Logs Detalhados
```bash
# Logs em tempo real
docker-compose logs -f

# Logs específicos
docker logs glpi_app
docker logs glpi_db
```

### Reinicialização Completa
```bash
# Para containers
./maintenance.sh stop

# Remove volumes (CUIDADO: perde dados!)
./maintenance.sh clean

# Reconfigura tudo
./setup-glpi.sh
```

## Configurações Avançadas

### Variáveis de Ambiente

Edite o `docker-compose.yml` para ajustar:

```yaml
environment:
  PHP_MEMORY_LIMIT: 512M
  PHP_MAX_EXECUTION_TIME: 600
  TZ: America/Sao_Paulo
  GLPI_ADMIN_PASSWORD: glpi123
```

### Portas Customizadas

Para mudar a porta do GLPI:
```yaml
ports:
  - 8081:80  # Muda para porta 8081
```

### Backup Automático

Crie um cron job para backup automático:
```bash
# Adicione ao crontab
0 2 * * * cd /path/to/project && ./maintenance.sh backup
```

## Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## Suporte

Para suporte técnico:
- Email: suporte@umanage.com.br
- Site: https://umanage.com.br

## Licença

Este projeto está licenciado sob GPL v2+ - veja os arquivos de licença individuais dos plugins.
