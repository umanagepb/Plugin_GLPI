<?php
/**
 * Arquivo de desinstalação do plugin Hours Tracking
 * Remove todas as tabelas e dados do plugin
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Função principal de desinstalação
 * @return boolean
 */
function plugin_hourstracking_uninstall() {
    global $DB;

    $uninstall_status = true;

    // Remove tabelas do plugin
    $uninstall_status &= plugin_hourstracking_drop_tables();

    // Remove configurações do plugin
    $uninstall_status &= plugin_hourstracking_remove_configs();

    // Remove direitos de perfil
    $uninstall_status &= plugin_hourstracking_remove_profile_rights();

    return $uninstall_status;
}

/**
 * Remove tabelas do plugin
 * @return boolean
 */
function plugin_hourstracking_drop_tables() {
    global $DB;

    $tables = [
        'glpi_plugin_hourstracking_clientrates',
        'glpi_plugin_hourstracking_configs'
    ];

    $success = true;
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $query = "DROP TABLE `$table`";
            $success &= $DB->queryOrDie($query, "Error dropping table $table");
        }
    }

    return $success;
}

/**
 * Remove configurações do plugin
 * @return boolean
 */
function plugin_hourstracking_remove_configs() {
    global $DB;

    // Remove configurações do core do GLPI
    $query = "DELETE FROM `glpi_configs` WHERE `context` = 'plugin:hourstracking'";
    $DB->queryOrDie($query, "Error removing plugin configs");

    return true;
}

/**
 * Remove direitos de perfil
 * @return boolean
 */
function plugin_hourstracking_remove_profile_rights() {
    global $DB;

    $rights = [
        'plugin_hourstracking_report',
        'plugin_hourstracking_clientrate',
        'plugin_hourstracking_config'
    ];

    $success = true;
    foreach ($rights as $right) {
        $query = "DELETE FROM `glpi_profilerights` WHERE `name` = '$right'";
        $success &= $DB->queryOrDie($query, "Error removing profile right $right");
    }

    return $success;
}
