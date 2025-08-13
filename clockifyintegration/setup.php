<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

define('PLUGIN_CLOCKIFYINTEGRATION_VERSION', '1.0.0');
define('PLUGIN_CLOCKIFYINTEGRATION_MIN_GLPI', '10.0.0');
define('PLUGIN_CLOCKIFYINTEGRATION_MAX_GLPI', '10.1.99');
define('PLUGIN_CLOCKIFYINTEGRATION_DIRECTORY', basename(dirname(__FILE__)));

use Glpi\Plugin\Hooks;

function plugin_init_clockifyintegration() {
   global $PLUGIN_HOOKS;

   // Registra classes para autoload
   Plugin::registerClass('PluginClockifyintegrationConfig');
   Plugin::registerClass('PluginClockifyintegrationPlugin');

   $PLUGIN_HOOKS['csrf_compliant']['clockifyintegration'] = true;
   $PLUGIN_HOOKS['config_page']['clockifyintegration'] = 'front/config.form.php';
   
   // Hook para incluir JavaScript e configurações
   if (Session::getLoginUserID()) {
      $PLUGIN_HOOKS['add_javascript']['clockifyintegration'][] = 'js/clockify.js';
      $PLUGIN_HOOKS['init']['clockifyintegration'] = 'plugin_clockifyintegration_init';
   }
}

function plugin_version_clockifyintegration() {
   return [
      'name'           => 'Clockify Integration',
      'version'        => '1.0.0',
      'author'         => 'Assistente de IA',
      'license'        => 'GLPv2+',
      'homepage'       => '',
      'requirements'   => [
         'glpi'   => [
            'min' => '10.0',
            'max' => '11.0',
         ],
         'php'    => [
            'min' => '7.4',
         ]
      ]
   ];
}

function plugin_clockifyintegration_check_prerequisites() {
   if (!extension_loaded('curl')) {
      echo "A extensão PHP 'curl' é necessária para este plugin.";
      return false;
   }
   return true;
}

function plugin_clockifyintegration_check_config() {
   return true;
}

/**
 * Função de instalação do plugin
 */
function plugin_clockifyintegration_install() {
   include_once(Plugin::getPhpDir('clockifyintegration') . '/install/install.php');
   return plugin_clockifyintegration_install_process();
}

/**
 * Função de desinstalação do plugin
 */
function plugin_clockifyintegration_uninstall() {
   include_once(Plugin::getPhpDir('clockifyintegration') . '/install/uninstall.php');
   return plugin_clockifyintegration_uninstall_process();
}
