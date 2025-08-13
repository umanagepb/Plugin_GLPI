<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginClockifyintegrationPlugin extends CommonDBTM {
   
   static function getTypeName($nb = 0) {
      return 'Clockify Integration';
   }

   static function canCreate() {
      return Session::haveRight(static::$rightname, CREATE);
   }

   static function canView() {
      return Session::haveRight(static::$rightname, READ);
   }

   static function canUpdate() {
      return Session::haveRight(static::$rightname, UPDATE);
   }
}
