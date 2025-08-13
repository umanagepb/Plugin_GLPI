<?php
/**
 * Arquivo para correção de problemas de migração/instalação
 * do plugin Hours Tracking
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Corrige entradas duplicadas na tabela de configurações
 * @return boolean
 */
function plugin_hourstracking_fix_duplicate_configs() {
    global $DB;

    $success = true;

    try {
        // Identifica e remove configurações duplicadas, mantendo apenas a mais recente
        $configs_to_check = [
            'enable_detailed_logging',
            'report_export_format',
            'default_hourly_rate'
        ];

        foreach ($configs_to_check as $config_name) {
            // Busca todas as entradas com este nome
            $duplicates = $DB->request([
                'FROM' => 'glpi_plugin_hourstracking_configs',
                'WHERE' => ['name' => $config_name],
                'ORDERBY' => 'id ASC'
            ]);

            $count = $duplicates->count();
            if ($count > 1) {
                // Mantém apenas o primeiro registro (mais antigo) e remove os demais
                $first = true;
                foreach ($duplicates as $duplicate) {
                    if (!$first) {
                        $success &= $DB->delete(
                            'glpi_plugin_hourstracking_configs',
                            ['id' => $duplicate['id']]
                        );
                    }
                    $first = false;
                }
            }
        }

        return $success;

    } catch (Exception $e) {
        error_log("Erro ao corrigir configurações duplicadas: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica a integridade da instalação
 * @return array
 */
function plugin_hourstracking_check_installation_integrity() {
    global $DB;

    $issues = [];

    // Verifica se as tabelas existem
    if (!$DB->tableExists('glpi_plugin_hourstracking_configs')) {
        $issues[] = "Tabela glpi_plugin_hourstracking_configs não existe";
    }

    if (!$DB->tableExists('glpi_plugin_hourstracking_clientrates')) {
        $issues[] = "Tabela glpi_plugin_hourstracking_clientrates não existe";
    }

    // Verifica configurações duplicadas
    if ($DB->tableExists('glpi_plugin_hourstracking_configs')) {
        $configs_to_check = [
            'enable_detailed_logging',
            'report_export_format', 
            'default_hourly_rate'
        ];

        foreach ($configs_to_check as $config_name) {
            $count = $DB->request([
                'FROM' => 'glpi_plugin_hourstracking_configs',
                'WHERE' => ['name' => $config_name]
            ])->count();

            if ($count > 1) {
                $issues[] = "Configuração '$config_name' tem entradas duplicadas ($count)";
            }
        }
    }

    return $issues;
}

/**
 * Executa correções automáticas
 * @return boolean
 */
function plugin_hourstracking_auto_fix() {
    $success = true;

    // Corrige configurações duplicadas
    $success &= plugin_hourstracking_fix_duplicate_configs();

    return $success;
}

/**
 * Reinstala o plugin de forma segura
 * @return boolean
 */
function plugin_hourstracking_safe_reinstall() {
    global $DB;

    $success = true;

    try {
        // Primeiro, corrige duplicatas
        $success &= plugin_hourstracking_fix_duplicate_configs();

        // Depois, verifica se todas as configurações necessárias estão presentes
        $required_configs = [
            ['name' => 'default_hourly_rate', 'value' => '100.00', 'is_active' => 1],
            ['name' => 'enable_detailed_logging', 'value' => '1', 'is_active' => 1],
            ['name' => 'report_export_format', 'value' => 'csv,pdf', 'is_active' => 1],
            ['name' => 'minimum_hours', 'value' => '1', 'is_active' => 1],
            ['name' => 'billing_workdays', 'value' => '22', 'is_active' => 1],
            ['name' => 'time_rounding', 'value' => '15', 'is_active' => 1]
        ];

        foreach ($required_configs as $config) {
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
                        'date_mod' => date('Y-m-d H:i:s'),
                        'is_active' => $config['is_active']
                    ]
                );
                $success &= $DB->query($query);
            }
        }

        return $success;

    } catch (Exception $e) {
        error_log("Erro na reinstalação segura: " . $e->getMessage());
        return false;
    }
}

?>
