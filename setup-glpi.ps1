# Script de configuração do GLPI com Docker para Windows

# Verifica a política de execução atual
$currentPolicy = Get-ExecutionPolicy
if ($currentPolicy -eq "Restricted") {
    Write-Host "A política de execução atual está configurada como Restricted." -ForegroundColor Yellow
    Write-Host "Para executar este script, você tem duas opções:" -ForegroundColor Cyan
    Write-Host "1. Execute o PowerShell como administrador e digite: Set-ExecutionPolicy RemoteSigned" -ForegroundColor White
    Write-Host "2. Ou execute este script diretamente com: powershell -ExecutionPolicy Bypass -File setup-glpi.ps1" -ForegroundColor White
    exit 1
}

Write-Host "Iniciando configuração do GLPI com Docker..." -ForegroundColor Green

# Verifica se o Docker está instalado
if (!(Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "Docker não encontrado. Por favor, instale o Docker primeiro." -ForegroundColor Red
    exit 1
}

# Verifica se o Docker Compose está instalado
if (!(Get-Command docker-compose -ErrorAction SilentlyContinue)) {
    Write-Host "Docker Compose não encontrado. Por favor, instale o Docker Compose primeiro." -ForegroundColor Red
    exit 1
}

# Inicia os containers
Write-Host "Iniciando containers..." -ForegroundColor Green
docker-compose up -d

# Aguarda o MariaDB iniciar completamente
Write-Host "Aguardando o banco de dados inicializar..." -ForegroundColor Green
Start-Sleep -Seconds 30

# Aguarda o MariaDB estar pronto
Write-Host "Verificando status do MariaDB..." -ForegroundColor Green
$maxAttempts = 30
$attempt = 0
do {
    $attempt++
    Write-Host "Tentativa $attempt de $maxAttempts..." -ForegroundColor Yellow
    $dbReady = docker exec glpi_db mysqladmin ping -h localhost -u glpi -pglpi 2>$null
    if (-not $dbReady) {
        Start-Sleep -Seconds 5
    }
} while (-not $dbReady -and $attempt -lt $maxAttempts)

if (-not $dbReady) {
    Write-Host "Erro: Banco de dados não iniciou corretamente" -ForegroundColor Red
    exit 1
}

# Instala o GLPI
Write-Host "Instalando GLPI..." -ForegroundColor Green
docker exec glpi_app php bin/console db:install `
    --default-language=pt_BR `
    --force

# Verifica se a instalação foi bem sucedida
if ($LASTEXITCODE -ne 0) {
    Write-Host "Erro na instalação do GLPI. Verificando logs..." -ForegroundColor Red
    docker-compose logs glpi_app
    exit 1
}

# Configura permissões dos diretórios GLPI
Write-Host "Configurando permissões..." -ForegroundColor Green

# Diretórios principais
$glpiDirs = @(
    "/var/glpi/config",
    "/var/glpi/files",
    "/var/glpi/files/_cache",
    "/var/glpi/files/_cron",
    "/var/glpi/files/_dumps",
    "/var/glpi/files/_graphs",
    "/var/glpi/files/_locales",
    "/var/glpi/files/_lock",
    "/var/glpi/files/_pictures",
    "/var/glpi/files/_plugins",
    "/var/glpi/files/_rss",
    "/var/glpi/files/_sessions",
    "/var/glpi/files/_tmp",
    "/var/glpi/files/_uploads",
    "/var/glpi/files/_inventories",
    "/var/www/html/glpi/marketplace",
    "/var/glpi/logs"
)

foreach ($dir in $glpiDirs) {
    Write-Host "Configurando permissões para $dir..." -ForegroundColor Green
    docker exec glpi_app mkdir -p $dir
    docker exec glpi_app chown -R www-data:www-data $dir
    docker exec glpi_app chmod -R u+rwx $dir
    # Para diretórios
    docker exec glpi_app find $dir -type d -exec chmod u+rwx {} \;
    # Para arquivos
    docker exec glpi_app find $dir -type f -exec chmod u+rw {} \;
}

# Cria diretório de plugins se não existir
Write-Host "Criando diretório de plugins..." -ForegroundColor Green

# Aguarda o GLPI estar completamente inicializado
Write-Host "Aguardando GLPI inicializar completamente..." -ForegroundColor Green
Start-Sleep -Seconds 10

# Ativa os plugins
Write-Host "Instalando e ativando plugins..." -ForegroundColor Green
try {
    # Evolution Integration
    Write-Host "Instalando Evolution Integration..." -ForegroundColor Green
    docker exec glpi_app php bin/console plugin:install evolutionintegration --force
    if ($LASTEXITCODE -eq 0) {
        docker exec glpi_app php bin/console plugin:activate evolutionintegration
    } else {
        Write-Host "Erro ao instalar Evolution Integration" -ForegroundColor Yellow
    }

    # Hours Tracking
    Write-Host "Instalando Hours Tracking..." -ForegroundColor Green
    docker exec glpi_app php bin/console plugin:install hourstracking --force
    if ($LASTEXITCODE -eq 0) {
        docker exec glpi_app php bin/console plugin:activate hourstracking
    } else {
        Write-Host "Erro ao instalar Hours Tracking" -ForegroundColor Yellow
    }

    # Clockify Integration
    Write-Host "Instalando Clockify Integration..." -ForegroundColor Green
    docker exec glpi_app php bin/console plugin:install clockifyintegration --force
    if ($LASTEXITCODE -eq 0) {
        docker exec glpi_app php bin/console plugin:activate clockifyintegration
    } else {
        Write-Host "Erro ao instalar Clockify Integration" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Erro ao instalar plugins: $_" -ForegroundColor Red
}

# Limpa o cache
Write-Host "Limpando cache..." -ForegroundColor Green
docker exec glpi_app php bin/console cache:clear

# Configura permissões finais
Write-Host "Configurando permissões finais..." -ForegroundColor Green
docker exec glpi_app chown -R www-data:www-data /var/www/glpi
docker exec glpi_app chmod -R 755 /var/www/glpi

Write-Host "`nConfiguração concluída!" -ForegroundColor Green
Write-Host "`nInformações de acesso:" -ForegroundColor Cyan
Write-Host "URL: http://localhost:8080" -ForegroundColor Yellow
Write-Host "Usuário: glpi" -ForegroundColor Yellow
Write-Host "Senha: glpi123" -ForegroundColor Yellow

Write-Host "`nNotas importantes:" -ForegroundColor Cyan
Write-Host "1. Aguarde alguns minutos para o GLPI inicializar completamente" -ForegroundColor White
Write-Host "2. Se encontrar erros, verifique os logs com: docker-compose logs -f" -ForegroundColor White
Write-Host "3. Para reiniciar os serviços use: docker-compose restart" -ForegroundColor White
Write-Host "4. Os plugins devem aparecer em Configurar > Plugins" -ForegroundColor White
