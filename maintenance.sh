#!/bin/bash

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

show_help() {
    echo -e "${GREEN}=== Script de Manutenção do GLPI ===${NC}"
    echo -e "${BLUE}Uso: $0 [opção]${NC}\n"
    echo -e "${YELLOW}Opções disponíveis:${NC}"
    echo -e "  start       - Inicia os containers"
    echo -e "  stop        - Para os containers"
    echo -e "  restart     - Reinicia os containers"
    echo -e "  logs        - Mostra logs dos containers"
    echo -e "  backup      - Faz backup do banco de dados"
    echo -e "  restore     - Restaura backup do banco (requer arquivo)"
    echo -e "  clean       - Remove containers e volumes (CUIDADO!)"
    echo -e "  plugins     - Lista status dos plugins"
    echo -e "  permissions - Corrige permissões dos plugins"
    echo -e "  cache       - Limpa cache do GLPI"
    echo -e "  status      - Executa verificação de status"
    echo -e "  help        - Mostra esta ajuda"
    echo
}

start_containers() {
    echo -e "${GREEN}Iniciando containers...${NC}"
    docker-compose up -d
}

stop_containers() {
    echo -e "${YELLOW}Parando containers...${NC}"
    docker-compose down
}

restart_containers() {
    echo -e "${YELLOW}Reiniciando containers...${NC}"
    docker-compose restart
}

show_logs() {
    echo -e "${GREEN}Logs dos containers (Ctrl+C para sair):${NC}"
    docker-compose logs -f
}

backup_database() {
    timestamp=$(date +%Y%m%d_%H%M%S)
    backup_file="backup_glpi_${timestamp}.sql"
    echo -e "${GREEN}Criando backup do banco de dados...${NC}"
    docker exec glpi_db mysqldump -u glpi -pglpi glpi > "$backup_file"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Backup criado: $backup_file${NC}"
    else
        echo -e "${RED}✗ Erro ao criar backup${NC}"
    fi
}

restore_database() {
    read -p "Digite o caminho do arquivo de backup: " backup_file
    if [ -f "$backup_file" ]; then
        echo -e "${YELLOW}Restaurando banco de dados...${NC}"
        docker exec -i glpi_db mysql -u glpi -pglpi glpi < "$backup_file"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Backup restaurado com sucesso${NC}"
        else
            echo -e "${RED}✗ Erro ao restaurar backup${NC}"
        fi
    else
        echo -e "${RED}✗ Arquivo de backup não encontrado${NC}"
    fi
}

clean_environment() {
    echo -e "${RED}ATENÇÃO: Esta operação irá remover todos os containers e volumes!${NC}"
    read -p "Tem certeza? (digite 'yes' para confirmar): " confirm
    if [ "$confirm" = "yes" ]; then
        echo -e "${YELLOW}Removendo containers e volumes...${NC}"
        docker-compose down -v
        docker volume prune -f
        echo -e "${GREEN}✓ Ambiente limpo${NC}"
    else
        echo -e "${YELLOW}Operação cancelada${NC}"
    fi
}

check_plugins() {
    echo -e "${GREEN}Status dos plugins:${NC}"
    docker exec glpi_app php bin/console plugin:list
}

fix_permissions() {
    echo -e "${GREEN}Corrigindo permissões...${NC}"
    
    # Diretórios principais
    glpi_dirs=(
        "/var/glpi/config"
        "/var/glpi/files"
        "/var/www/glpi/marketplace"
        "/var/www/glpi/plugins"
    )
    
    for dir in "${glpi_dirs[@]}"; do
        echo -e "${GREEN}Configurando permissões para $dir...${NC}"
        docker exec glpi_app chown -R www-data:www-data "$dir"
        docker exec glpi_app chmod -R 755 "$dir"
    done
    
    echo -e "${GREEN}✓ Permissões corrigidas${NC}"
}

clear_cache() {
    echo -e "${GREEN}Limpando cache do GLPI...${NC}"
    docker exec glpi_app php bin/console cache:clear
    echo -e "${GREEN}✓ Cache limpo${NC}"
}

check_status() {
    if [ -f "./check-status.sh" ]; then
        bash ./check-status.sh
    else
        echo -e "${RED}Script de status não encontrado${NC}"
    fi
}

# Menu principal
case "$1" in
    start)
        start_containers
        ;;
    stop)
        stop_containers
        ;;
    restart)
        restart_containers
        ;;
    logs)
        show_logs
        ;;
    backup)
        backup_database
        ;;
    restore)
        restore_database
        ;;
    clean)
        clean_environment
        ;;
    plugins)
        check_plugins
        ;;
    permissions)
        fix_permissions
        ;;
    cache)
        clear_cache
        ;;
    status)
        check_status
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo -e "${RED}Opção inválida: $1${NC}\n"
        show_help
        exit 1
        ;;
esac
