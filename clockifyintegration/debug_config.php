<?php
/**
 * Script para debugar configurações do plugin Clockify Integration
 * Execute este arquivo via web browser para verificar se as configurações estão sendo lidas corretamente
 */

// Inclui os arquivos do GLPI
include_once('../../../inc/includes.php');

// Verifica se o usuário está logado
Session::checkRight("config", READ);

echo "<h1>Debug - Configurações Clockify Integration</h1>";

echo "<h2>Informações do Session</h2>";
echo "User ID: " . Session::getLoginUserID() . "<br>";
echo "Login User: " . Session::isLoggedIn() . "<br>";

echo "<h2>Configurações armazenadas</h2>";
$api_key = Config::getConfigurationValue("plugin:Clockify Integration", "api_key");
$workspace_id = Config::getConfigurationValue("plugin:Clockify Integration", "workspace_id");

echo "API Key: " . (!empty($api_key) ? "Configurado (" . strlen($api_key) . " chars): " . substr($api_key, 0, 10) . "..." : "Vazio") . "<br>";
echo "Workspace ID: " . (!empty($workspace_id) ? "Configurado (" . strlen($workspace_id) . " chars): " . $workspace_id : "Vazio") . "<br>";

echo "<h2>Debug das configurações brutas</h2>";
echo "<pre>";
var_dump([
    'api_key' => $api_key,
    'workspace_id' => $workspace_id
]);
echo "</pre>";

echo "<h2>Todas as configurações do plugin</h2>";
$all_configs = Config::getConfigurationValues("plugin:Clockify Integration");
echo "<pre>";
var_dump($all_configs);
echo "</pre>";

echo "<h2>Teste do Hook</h2>";
echo "O hook deveria injetar o seguinte JavaScript:<br>";
echo "<pre>";
echo htmlspecialchars("
<script type='text/javascript'>
    console.log('Clockify Integration: Configurações injetadas via PHP');
    window.clockifyIntegrationConfig = {
        apiKey: '" . addslashes($api_key) . "',
        workspaceId: '" . addslashes($workspace_id) . "'
    };
    console.log('Clockify Integration: window.clockifyIntegrationConfig =', window.clockifyIntegrationConfig);
</script>
");
echo "</pre>";

echo "<h2>Verificar se o plugin está ativo</h2>";
$plugin = new Plugin();
if ($plugin->isActivated('clockifyintegration')) {
    echo "✓ Plugin está ATIVO<br>";
} else {
    echo "✗ Plugin está INATIVO<br>";
}

echo "<h2>Verificar hooks registrados</h2>";
global $PLUGIN_HOOKS;
if (isset($PLUGIN_HOOKS['init']['clockifyintegration'])) {
    echo "✓ Hook 'init' está registrado: " . $PLUGIN_HOOKS['init']['clockifyintegration'] . "<br>";
} else {
    echo "✗ Hook 'init' NÃO está registrado<br>";
}

if (function_exists('plugin_clockifyintegration_init')) {
    echo "✓ Função plugin_clockifyintegration_init existe<br>";
} else {
    echo "✗ Função plugin_clockifyintegration_init NÃO existe<br>";
}

?>
