<?php
/**
 * Hook para o plugin Clockify Integration
 *
 * Este hook injeta as configurações definidas no plugin e inclui o script JavaScript.
 */
function plugin_clockifyintegration_init() {
   global $CFG_GLPI;

   // Obtém as configurações dinâmicas do plugin
   $api_key      = addslashes(Config::getConfigurationValue("plugin:Clockify Integration", "api_key"));
   $workspace_id = addslashes(Config::getConfigurationValue("plugin:Clockify Integration", "workspace_id"));

   // Insere um script com a configuração que será acessado pelo JavaScript
   echo "<script>
       window.clockifyIntegrationConfig = {
           apiKey: '{$api_key}',
           workspaceId: '{$workspace_id}'
       };
   </script>";

   // Inclui o arquivo JavaScript do plugin
   Html::includeJS($CFG_GLPI['root_doc']."/plugins/clockifyintegration/js/clockify.js");
}
