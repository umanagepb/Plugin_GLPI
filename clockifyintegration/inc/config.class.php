<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginClockifyintegrationConfig extends CommonDBTM {
   
   static private $_instance = NULL;
   static public $rightname = 'config';

   /**
    * Singleton para a instância de configuração
    */
   static function getInstance() {
      if (!isset(self::$_instance)) {
         self::$_instance = new self();
      }
      return self::$_instance;
   }

   static public function getTypeName($nb = 0) {
      return __('Clockify Integration', 'clockifyintegration');
   }

   /**
    * Obtém o valor de uma opção de configuração
    */
   static public function getConfigValue($option) {
      $config = new self();
      return $config->getFromDB(1) ? $config->fields[$option] : '';
   }

   /**
    * Define o valor de uma opção de configuração
    */
   static public function setConfigValue($option, $value) {
      $config = new self();
      if ($config->getFromDB(1)) {
         $config->update([
            'id' => 1,
            $option => $value
         ]);
      } else {
         $config->add([
            'id' => 1,
            $option => $value
         ]);
      }
   }
}
