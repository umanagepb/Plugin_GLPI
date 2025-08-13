<?php
/**
 * Arquivo hook.php para o plugin Hours Tracking
 * Define funções de instalação, desinstalação e atualização
 */

/**
 * Função chamada durante a instalação do plugin
 * @return boolean
 */
function plugin_hourstracking_install() {
    // Inclui arquivos de instalação
    include_once(Plugin::getPhpDir('hourstracking') . "/install/install.php");
    include_once(Plugin::getPhpDir('hourstracking') . "/install/migration.php");
    
    // Primeiro, verifica se há problemas existentes e os corrige
    if (function_exists('plugin_hourstracking_auto_fix')) {
        plugin_hourstracking_auto_fix();
    }
    
    // Depois executa a instalação completa
    if (function_exists('plugin_hourstracking_install_complete')) {
        return plugin_hourstracking_install_complete();
    }
    
    // Fallback para instalação básica
    return plugin_hourstracking_install_basic();
}

/**
 * Função de instalação básica (fallback)
 * @return boolean
 */
function plugin_hourstracking_install_basic() {
    global $DB;

    // Array para armazenar o status da instalação
    $install_status = true;

    // Criação da tabela de configurações do plugin
    if (!$DB->tableExists('glpi_plugin_hourstracking_configs')) {
        $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hourstracking_configs` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `value` TEXT,
            `date_mod` DATETIME DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $install_status &= $DB->query($query);
    }

    // Criação da tabela de taxas por cliente
    if (!$DB->tableExists('glpi_plugin_hourstracking_clientrates')) {
        $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hourstracking_clientrates` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id` INT(11) UNSIGNED NOT NULL,
            `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `date_mod` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_client` (`client_id`),
            KEY `idx_rate` (`hourly_rate`),
            KEY `idx_client_id` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $install_status &= $DB->query($query);
    }

    // Inserir configurações padrão (apenas se não existirem)
    $default_configs = [
        ['name' => 'default_hourly_rate', 'value' => '100.00', 'is_active' => 1],
        ['name' => 'enable_detailed_logging', 'value' => '1', 'is_active' => 1],
        ['name' => 'report_export_format', 'value' => 'csv,pdf', 'is_active' => 1]
    ];

    foreach ($default_configs as $config) {
        // Verifica se a configuração já existe
        $existing = $DB->request([
            'FROM' => 'glpi_plugin_hourstracking_configs',
            'WHERE' => ['name' => $config['name']]
        ])->count();

        // Só insere se não existir
        if ($existing == 0) {
            $query = $DB->buildInsert(
                'glpi_plugin_hourstracking_configs', 
                [
                    'name' => $config['name'], 
                    'value' => $config['value'], 
                    'date_mod' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
                    'is_active' => $config['is_active']
                ]
            );
            $install_status &= $DB->query($query);
        }
    }

    return $install_status;
}

/**
 * Função chamada durante a desinstalação do plugin
 * @return boolean
 */
function plugin_hourstracking_uninstall() {
    global $DB;

    // Array para armazenar o status da desinstalação
    $uninstall_status = true;

    // Lista de tabelas a serem removidas
    $tables = [
        'glpi_plugin_hourstracking_configs',
        'glpi_plugin_hourstracking_clientrates'
    ];

    // Remove todas as tabelas
    foreach ($tables as $table) {
        $query = "DROP TABLE IF EXISTS `$table`";
        $uninstall_status &= $DB->query($query);
    }

    // Remove configurações relacionadas ao plugin
    $query = "DELETE FROM `glpi_configs` WHERE `context` = 'plugin:hourstracking'";
    $uninstall_status &= $DB->query($query);

    return $uninstall_status;
}

/**
 * Função para verificar a compatibilidade durante a atualização
 * @param string $current_version Versão atual do plugin
 * @param string $migrate_version Versão para a qual será migrado
 * @return boolean
 */
function plugin_hourstracking_upgrade($current_version, $migrate_version) {
    global $DB;

    $upgrade_status = true;

    // Exemplos de migrações entre versões
    switch ($current_version) {
        case '1.0.0':
            // Migração da versão 1.0.0 para 1.1.0
            if (version_compare($migrate_version, '1.1.0', '>=')) {
                // Adicionar nova coluna ou fazer alterações no banco
                $query = "ALTER TABLE `glpi_plugin_hourstracking_timelogs` 
                          ADD COLUMN `project_id` INT(11) NULL AFTER `users_id`";
                $upgrade_status &= $DB->query($query);
            }
            break;
        
        case '1.1.0':
            // Migração da versão 1.1.0 para 1.2.0
            if (version_compare($migrate_version, '1.2.0', '>=')) {
                // Possíveis alterações de estrutura
                $query = "ALTER TABLE `glpi_plugin_hourstracking_clientrates` 
                          MODIFY COLUMN `hourly_rate` DECIMAL(12,4) NOT NULL DEFAULT '0.0000'";
                $upgrade_status &= $DB->query($query);
            }
            break;
    }

    return $upgrade_status;
}

/**
 * Função para definir as permissões do plugin
 * @param string $type Tipo de permissão
 * @return array
 */
function plugin_hourstracking_permission($type) {
    switch ($type) {
        case 'config':
            return [
                'hourstracking:config' => __('Configurações do Plugin', 'hourstracking'),
                'hourstracking:admin'  => __('Administração Avançada', 'hourstracking')
            ];
        
        case 'profile':
            return [
                'hourstracking:read'   => __('Visualizar Relatórios', 'hourstracking'),
                'hourstracking:write'  => __('Editar Relatórios', 'hourstracking'),
                'hourstracking:delete' => __('Excluir Relatórios', 'hourstracking')
            ];
    }

    return [];
}

/**
 * Adiciona itens de menu personalizados
 * @return array
 */
function plugin_hourstracking_menu_entries() {
    $menu = [
        'title' => __('Controle de Horas', 'hourstracking'),
        'page'  => '/plugins/hourstracking/front/reports.php',
        'icon'  => 'fas fa-clock'
    ];

    return $menu;
}
