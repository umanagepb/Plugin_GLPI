<?php

/**
 * Função de instalação do plugin
 *
 * @return boolean
 */
function plugin_clockifyintegration_install_process() {
   global $DB;

   // Define valores padrão de configuração
   $default_config = [
      'api_key' => '',
      'workspace_id' => ''
   ];

   // Salva configuração padrão no banco
   Config::setConfigurationValues("plugin:Clockify Integration", $default_config);

   return true;
}
