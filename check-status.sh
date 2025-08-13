#!/bin/bash

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Verificação de Status do GLPI ===${NC}\n"

# Verifica se os containers estão rodando
echo -e "${GREEN}1. Status dos Containers:${NC}"
if docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(glpi_app|glpi_db)"; then
    echo -e "${GREEN}✓ Containers estão rodando${NC}\n"
else
    echo -e "${RED}✗ Alguns containers não estão rodando${NC}\n"
    docker-compose ps
    echo
fi

# Verifica conectividade com banco
echo -e "${GREEN}2. Conectividade com Banco de Dados:${NC}"
if docker exec glpi_db mysqladmin ping -h localhost -u glpi -pglpi &> /dev/null; then
    echo -e "${GREEN}✓ Banco de dados está acessível${NC}\n"
else
    echo -e "${RED}✗ Não foi possível conectar ao banco de dados${NC}\n"
fi

# Verifica se o GLPI responde
echo -e "${GREEN}3. Status do GLPI Web:${NC}"
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 | grep -q "200\|302"; then
    echo -e "${GREEN}✓ GLPI está respondendo na porta 8080${NC}\n"
else
    echo -e "${RED}✗ GLPI não está respondendo na porta 8080${NC}\n"
fi

# Verifica plugins instalados
echo -e "${GREEN}4. Status dos Plugins:${NC}"
echo -e "${YELLOW}Verificando plugins instalados...${NC}"

plugins=("evolutionintegration" "hourstracking" "clockifyintegration")
for plugin in "${plugins[@]}"; do
    if docker exec glpi_app php bin/console plugin:list | grep -q "$plugin"; then
        status=$(docker exec glpi_app php bin/console plugin:list | grep "$plugin" | awk '{print $3}')
        if [ "$status" = "Enabled" ] || [ "$status" = "Ativado" ]; then
            echo -e "${GREEN}✓ $plugin: Ativado${NC}"
        else
            echo -e "${YELLOW}⚠ $plugin: Instalado mas não ativado${NC}"
        fi
    else
        echo -e "${RED}✗ $plugin: Não encontrado${NC}"
    fi
done

echo

# Verifica logs de erro recentes
echo -e "${GREEN}5. Logs Recentes:${NC}"
echo -e "${YELLOW}Últimas 5 linhas de log do GLPI:${NC}"
docker logs glpi_app --tail 5

echo -e "\n${YELLOW}Últimas 5 linhas de log do MariaDB:${NC}"
docker logs glpi_db --tail 5

echo -e "\n${GREEN}=== Verificação Concluída ===${NC}"
echo -e "${GREEN}Para acessar o GLPI: http://localhost:8080${NC}"
echo -e "${GREEN}Usuário: glpi | Senha: glpi123${NC}"
