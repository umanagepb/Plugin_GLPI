#!/bin/bash

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}Iniciando configuração do GLPI com Docker...${NC}"

# Verifica se o Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker não encontrado. Por favor, instale o Docker primeiro.${NC}"
    exit 1
fi

# Verifica se o Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose não encontrado. Por favor, instale o Docker Compose primeiro.${NC}"
    exit 1
fi

# Inicia os containers
echo -e "${GREEN}Iniciando containers...${NC}"
docker-compose up -d

# Aguarda o MariaDB iniciar completamente
echo -e "${GREEN}Aguardando o banco de dados inicializar...${NC}"
sleep 30

# Aguarda o MariaDB estar pronto com healthcheck
echo -e "${GREEN}Verificando status do MariaDB...${NC}"
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    attempt=$((attempt + 1))
    echo -e "${GREEN}Tentativa $attempt de $max_attempts...${NC}"
    if docker exec glpi_db mysqladmin ping -h localhost -u glpi -pglpi &> /dev/null; then
        echo -e "${GREEN}MariaDB está pronto!${NC}"
        break
    fi
    if [ $attempt -eq $max_attempts ]; then
        echo -e "${RED}Erro: Banco de dados não iniciou corretamente${NC}"
        exit 1
    fi
    sleep 5
done

# Instala o GLPI
echo -e "${GREEN}Instalando GLPI...${NC}"
docker exec glpi_app php bin/console db:install \
    --default-language=pt_BR \
    --force

# Verifica se a instalação foi bem sucedida
if [ $? -ne 0 ]; then
    echo -e "${RED}Erro na instalação do GLPI. Verificando logs...${NC}"
    docker-compose logs glpi_app
    exit 1
fi

# Configura permissões dos diretórios GLPI
echo -e "${GREEN}Configurando permissões...${NC}"

# Diretórios principais
glpi_dirs=(
    "/var/glpi/config"
    "/var/glpi/files"
    "/var/glpi/files/_cache"
    "/var/glpi/files/_cron"
    "/var/glpi/files/_dumps"
    "/var/glpi/files/_graphs"
    "/var/glpi/files/_locales"
    "/var/glpi/files/_lock"
    "/var/glpi/files/_pictures"
    "/var/glpi/files/_plugins"
    "/var/glpi/files/_rss"
    "/var/glpi/files/_sessions"
    "/var/glpi/files/_tmp"
    "/var/glpi/files/_uploads"
    "/var/glpi/files/_inventories"
    "/var/www/glpi/marketplace"
    "/var/www/glpi/plugins"
    "/var/glpi/logs"
)

for dir in "${glpi_dirs[@]}"; do
    echo -e "${GREEN}Configurando permissões para $dir...${NC}"
    docker exec glpi_app mkdir -p "$dir"
    docker exec glpi_app chown -R www-data:www-data "$dir"
    docker exec glpi_app chmod -R u+rwx "$dir"
    # Para diretórios
    docker exec glpi_app find "$dir" -type d -exec chmod u+rwx {} \;
    # Para arquivos
    docker exec glpi_app find "$dir" -type f -exec chmod u+rw {} \;
done

# Aguarda o GLPI estar completamente inicializado
echo -e "${GREEN}Aguardando GLPI inicializar completamente...${NC}"
sleep 10

# Ativa os plugins
echo -e "${GREEN}Instalando e ativando plugins...${NC}"

# Evolution Integration
echo -e "${GREEN}Instalando Evolution Integration...${NC}"
if docker exec glpi_app php bin/console plugin:install evolutionintegration --force; then
    docker exec glpi_app php bin/console plugin:activate evolutionintegration
else
    echo -e "${RED}Erro ao instalar Evolution Integration${NC}"
fi

# Hours Tracking
echo -e "${GREEN}Instalando Hours Tracking...${NC}"
if docker exec glpi_app php bin/console plugin:install hourstracking --force; then
    docker exec glpi_app php bin/console plugin:activate hourstracking
else
    echo -e "${RED}Erro ao instalar Hours Tracking${NC}"
fi

# Clockify Integration
echo -e "${GREEN}Instalando Clockify Integration...${NC}"
if docker exec glpi_app php bin/console plugin:install clockifyintegration --force; then
    docker exec glpi_app php bin/console plugin:activate clockifyintegration
else
    echo -e "${RED}Erro ao instalar Clockify Integration${NC}"
fi

# Limpa o cache
echo -e "${GREEN}Limpando cache...${NC}"
docker exec glpi_app php bin/console cache:clear

# Configura permissões finais
echo -e "${GREEN}Configurando permissões finais...${NC}"
docker exec glpi_app chown -R www-data:www-data /var/www/glpi
docker exec glpi_app chmod -R 755 /var/www/glpi

echo -e "\n${GREEN}Configuração concluída!${NC}"
echo -e "\n${GREEN}Informações de acesso:${NC}"
echo -e "${GREEN}URL: http://localhost:8080${NC}"
echo -e "${GREEN}Usuário: glpi${NC}"
echo -e "${GREEN}Senha: glpi123${NC}"

echo -e "\n${GREEN}Notas importantes:${NC}"
echo -e "${GREEN}1. Aguarde alguns minutos para o GLPI inicializar completamente${NC}"
echo -e "${GREEN}2. Se encontrar erros, verifique os logs com: docker-compose logs -f${NC}"
echo -e "${GREEN}3. Para reiniciar os serviços use: docker-compose restart${NC}"
echo -e "${GREEN}4. Os plugins devem aparecer em Configurar > Plugins${NC}"
