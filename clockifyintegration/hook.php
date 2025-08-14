<?php
/**
 * Hook para o plugin Clockify Integration
 *
 * Este hook injeta as configurações definidas no plugin e inclui o script JavaScript.
 */
function plugin_clockifyintegration_init() {
   global $CFG_GLPI;

   // Verifica se estamos em uma sessão válida
   if (!Session::getLoginUserID()) {
      return;
   }

   // Debug para verificar se a função está sendo chamada
   error_log('Clockify Integration: Hook init chamado');

   // Obtém as configurações dinâmicas do plugin
   $api_key = Config::getConfigurationValue("plugin:Clockify Integration", "api_key") ?: '';
   $workspace_id = Config::getConfigurationValue("plugin:Clockify Integration", "workspace_id") ?: '';

   // Debug das configurações
   error_log('Clockify Integration: API Key = ' . (!empty($api_key) ? 'Configurado' : 'Vazio'));
   error_log('Clockify Integration: Workspace ID = ' . (!empty($workspace_id) ? 'Configurado' : 'Vazio'));

   // Usa json_encode para escape seguro
   $api_key_safe = json_encode($api_key);
   $workspace_id_safe = json_encode($workspace_id);

   // Insere um script com a configuração que será acessado pelo JavaScript (fallback)
   echo "<script type='text/javascript'>
       // Fallback para compatibilidade - apenas se ainda não foi definido
       if (typeof window.clockifyIntegrationConfig === 'undefined') {
           console.log('Clockify Integration: Usando configuração de fallback via hook');
           window.clockifyIntegrationConfig = {
               apiKey: {$api_key_safe},
               workspaceId: {$workspace_id_safe}
           };
           window.clockifyIntegrationConfigLoaded = true;
           console.log('Clockify Integration: Configuração de fallback definida', window.clockifyIntegrationConfig);
       }
   </script>";
}
