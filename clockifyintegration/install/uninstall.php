<?php

/**
 * Função de desinstalação do plugin
 *
 * @return boolean
 */
function plugin_clockifyintegration_uninstall_process() {
   global $DB;

   // Remove as configurações do plugin
   Config::deleteConfigurationValues("plugin:Clockify Integration", [
      'api_key',
      'workspace_id'
   ]);

   return true;
}
