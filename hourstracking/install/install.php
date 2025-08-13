<?php
/**
 * Arquivo de instalação do plugin Hours Tracking
 * Executa as operações necessárias durante a instalação
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Função principal de instalação
 * @return boolean
 */
function plugin_hourstracking_install() {
    global $DB;

    $install_status = true;

    // Verifica se as tabelas já existem
    if (!$DB->tableExists('glpi_plugin_hourstracking_configs')) {
        $install_status &= plugin_hourstracking_create_configs_table();
    }

    if (!$DB->tableExists('glpi_plugin_hourstracking_clientrates')) {
        $install_status &= plugin_hourstracking_create_clientrates_table();
    }

    // Insere configurações padrão
    $install_status &= plugin_hourstracking_insert_default_configs();

    // Configura perfis de usuário
    $install_status &= plugin_hourstracking_install_profiles();

    return $install_status;
}

/**
 * Cria tabela de configurações
 * @return boolean
 */
function plugin_hourstracking_create_configs_table() {
    global $DB;

    $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hourstracking_configs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `value` TEXT,
        `date_mod` DATETIME DEFAULT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_name` (`name`),
        KEY `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    return $DB->queryOrDie($query, "Error creating configs table");
}

/**
 * Cria tabela de taxas por cliente
 * @return boolean
 */
function plugin_hourstracking_create_clientrates_table() {
    global $DB;

    $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hourstracking_clientrates` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `client_id` INT(11) NOT NULL,
        `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
        `date_mod` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_client` (`client_id`),
        KEY `idx_rate` (`hourly_rate`),
        FOREIGN KEY (`client_id`) REFERENCES `glpi_entities` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    return $DB->queryOrDie($query, "Error creating clientrates table");
}

/**
 * Insere configurações padrão
 * @return boolean
 */
function plugin_hourstracking_insert_default_configs() {
    global $DB;

    $default_configs = [
        ['name' => 'default_hourly_rate', 'value' => '100.00', 'is_active' => 1],
        ['name' => 'enable_detailed_logging', 'value' => '1', 'is_active' => 1],
        ['name' => 'report_export_format', 'value' => 'csv,pdf', 'is_active' => 1],
        ['name' => 'minimum_hours', 'value' => '1', 'is_active' => 1],
        ['name' => 'billing_workdays', 'value' => '22', 'is_active' => 1],
        ['name' => 'time_rounding', 'value' => '15', 'is_active' => 1]
    ];

    $success = true;
    foreach ($default_configs as $config) {
        // Verifica se já existe antes de inserir
        $existing = $DB->request([
            'FROM' => 'glpi_plugin_hourstracking_configs',
            'WHERE' => ['name' => $config['name']]
        ])->count();

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
            $success &= $DB->queryOrDie($query, "Error inserting config: " . $config['name']);
        }
    }

    return $success;
}

/**
 * Configura perfis de usuário
 * @return boolean
 */
function plugin_hourstracking_install_profiles() {
    include_once(Plugin::getPhpDir('hourstracking') . "/inc/profile.class.php");
    $profile = new PluginHourstrackingProfile();
    return $profile->initProfile();
}
