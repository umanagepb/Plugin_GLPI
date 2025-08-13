<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Inicializa o plugin e suas classes
 */
class PluginHoursTracking extends Plugin {
   public function init() {
      // Registra autoloader para as classes do plugin
      spl_autoload_register([__CLASS__, 'autoload']);
   }

   /**
    * Autoloader para as classes do plugin
    */
   public static function autoload($classname) {
      if (strpos($classname, 'PluginHourstracking') === 0) {
         $filename = GLPI_ROOT . '/plugins/hourstracking/inc/' .
                    strtolower(str_replace('PluginHourstracking', '', $classname)) .
                    '.class.php';
         if (file_exists($filename)) {
            include_once($filename);
         }
      }
   }

   public static function uninstall() {
      $config = new Config();
      $config->deleteConfigurationValues('plugin:hourstracking');

      return true;
   }
}
