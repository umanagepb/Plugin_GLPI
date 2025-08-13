#!/usr/bin/env php
<?php
/**
 * Script para corrigir problemas de configurações duplicadas
 * do plugin Hours Tracking
 * 
 * Execute este script no container GLPI:
 * php /var/www/glpi/plugins/hourstracking/fix_duplicates.php
 */

// Inclui o bootstrap do GLPI
define('GLPI_ROOT', '/var/www/glpi');
include_once(GLPI_ROOT . '/inc/includes.php');

// Inclui funções de migração
include_once(__DIR__ . '/install/migration.php');

echo "=== Correção de Configurações Duplicadas - Plugin Hours Tracking ===\n\n";

// Verifica a integridade atual
echo "1. Verificando problemas existentes...\n";
$issues = plugin_hourstracking_check_installation_integrity();

if (empty($issues)) {
    echo "✓ Nenhum problema encontrado.\n";
} else {
    echo "⚠ Problemas encontrados:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\n";
}

// Executa correções
echo "2. Executando correções automáticas...\n";
$success = plugin_hourstracking_auto_fix();

if ($success) {
    echo "✓ Correções aplicadas com sucesso.\n";
} else {
    echo "✗ Erro ao aplicar correções.\n";
}

// Verifica novamente
echo "\n3. Verificação pós-correção...\n";
$issues_after = plugin_hourstracking_check_installation_integrity();

if (empty($issues_after)) {
    echo "✓ Todos os problemas foram resolvidos.\n";
} else {
    echo "⚠ Problemas remanescentes:\n";
    foreach ($issues_after as $issue) {
        echo "  - $issue\n";
    }
}

echo "\n=== Processo concluído ===\n";

?>
