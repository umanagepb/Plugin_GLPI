<?php
/**
 * Arquivo de configuração JavaScript dinâmico para o plugin Clockify Integration
 * Este arquivo é servido como JavaScript mas processado como PHP para injetar configurações
 */

// Define o cabeçalho como JavaScript
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Inclui os arquivos do GLPI apenas se necessário
if (!defined('GLPI_ROOT')) {
    $glpi_root = realpath(__DIR__ . '/../../../');
    if (file_exists($glpi_root . '/inc/includes.php')) {
        define('GLPI_ROOT', $glpi_root);
        include_once(GLPI_ROOT . '/inc/includes.php');
    }
}

// Verifica se o GLPI foi carregado corretamente
if (!defined('GLPI_ROOT') || !class_exists('Session') || !class_exists('Config')) {
    // Se não conseguiu carregar o GLPI, retorna configuração vazia
    echo "// GLPI não carregado - configuração vazia\n";
    echo "window.clockifyIntegrationConfig = { apiKey: '', workspaceId: '' };\n";
    echo "console.warn('Clockify Integration: GLPI não carregado corretamente');\n";
    exit;
}

// Verifica se há uma sessão válida
if (!Session::getLoginUserID()) {
    echo "// Usuário não logado - configuração vazia\n";
    echo "window.clockifyIntegrationConfig = { apiKey: '', workspaceId: '' };\n";
    echo "console.warn('Clockify Integration: Usuário não está logado');\n";
    exit;
}

// Obtém as configurações do plugin
$api_key = Config::getConfigurationValue("plugin:Clockify Integration", "api_key") ?: '';
$workspace_id = Config::getConfigurationValue("plugin:Clockify Integration", "workspace_id") ?: '';

// Escapa os valores para uso seguro em JavaScript
$api_key_safe = json_encode($api_key);
$workspace_id_safe = json_encode($workspace_id);

// Gera o JavaScript com as configurações
?>
// Configurações do plugin Clockify Integration
// Gerado automaticamente em <?php echo date('Y-m-d H:i:s'); ?>

console.log('Clockify Integration: Arquivo de configuração carregado');

window.clockifyIntegrationConfig = {
    apiKey: <?php echo $api_key_safe; ?>,
    workspaceId: <?php echo $workspace_id_safe; ?>
};

console.log('Clockify Integration: Configurações definidas', {
    apiKey: window.clockifyIntegrationConfig.apiKey ? 'Configurado (' + window.clockifyIntegrationConfig.apiKey.length + ' chars)' : 'Vazio',
    workspaceId: window.clockifyIntegrationConfig.workspaceId ? 'Configurado (' + window.clockifyIntegrationConfig.workspaceId.length + ' chars)' : 'Vazio'
});

// Marca que as configurações foram carregadas
window.clockifyIntegrationConfigLoaded = true;
