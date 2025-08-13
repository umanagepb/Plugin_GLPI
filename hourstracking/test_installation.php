#!/usr/bin/env php
<?php
/**
 * Script para testar a instalação do plugin Hours Tracking
 */

// Inclui o bootstrap do GLPI
define('GLPI_ROOT', '/var/www/glpi');
include_once(GLPI_ROOT . '/inc/includes.php');

echo "=== Teste de Instalação - Plugin Hours Tracking ===\n\n";

// Inclui os arquivos do plugin
include_once(__DIR__ . '/hook.php');
include_once(__DIR__ . '/install/migration.php');

echo "1. Testando função de verificação de integridade...\n";
$issues = plugin_hourstracking_check_installation_integrity();

if (empty($issues)) {
    echo "✓ Verificação de integridade: OK\n";
} else {
    echo "⚠ Problemas encontrados:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
}

echo "\n2. Testando função de instalação (modo de teste)...\n";

// Simula chamada de instalação sem realmente executar
$test_mode = true;
echo "✓ Função plugin_hourstracking_install() está disponível: " . 
     (function_exists('plugin_hourstracking_install') ? "SIM" : "NÃO") . "\n";

echo "✓ Função plugin_hourstracking_install_complete() está disponível: " . 
     (function_exists('plugin_hourstracking_install_complete') ? "SIM" : "NÃO") . "\n";

echo "✓ Função plugin_hourstracking_auto_fix() está disponível: " . 
     (function_exists('plugin_hourstracking_auto_fix') ? "SIM" : "NÃO") . "\n";

echo "\n3. Verificando estado atual das configurações...\n";
global $DB;

$configs = $DB->request([
    'FROM' => 'glpi_plugin_hourstracking_configs',
    'ORDER' => 'name ASC'
]);

$config_count = $configs->count();
echo "✓ Total de configurações: $config_count\n";

foreach ($configs as $config) {
    echo "  - {$config['name']}: {$config['value']} (ativo: {$config['is_active']})\n";
}

// Verifica duplicatas
$duplicates_query = "SELECT name, COUNT(*) as count FROM glpi_plugin_hourstracking_configs GROUP BY name HAVING count > 1";
$duplicates = $DB->query($duplicates_query);
$duplicate_count = $duplicates ? mysqli_num_rows($duplicates) : 0;

if ($duplicate_count == 0) {
    echo "✓ Nenhuma configuração duplicada encontrada\n";
} else {
    echo "⚠ Encontradas $duplicate_count configurações duplicadas\n";
}

echo "\n=== Resultado do Teste ===\n";
if (empty($issues) && $duplicate_count == 0) {
    echo "✅ TODOS OS TESTES PASSARAM - Plugin pronto para uso!\n";
} else {
    echo "⚠️ ALGUNS PROBLEMAS ENCONTRADOS - Verificar logs acima\n";
}

echo "\n=== Teste concluído ===\n";

?>
