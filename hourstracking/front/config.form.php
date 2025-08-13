<?php
include ("../../../inc/includes.php");

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight("config", UPDATE);

$config = new PluginHourstrackingConfig();

Html::header(__("Configuração do Controle de Horas", 'hourstracking'), $_SERVER['PHP_SELF'], "admin", "pluginhourstrackingconfig");

$config->showConfigForm();

Html::footer();
