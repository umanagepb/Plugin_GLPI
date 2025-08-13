<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

Html::header(__('Configuração do Controle de Horas', 'hourstracking'), $_SERVER['PHP_SELF'], "config", "pluginhourstracking");

$config = new PluginHourstrackingConfig();
$config->showConfigForm();

Html::footer();
