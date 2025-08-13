<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight('plugin_hourstracking_clientrate', READ);

Html::header(__('Taxas Horárias por Cliente', 'hourstracking'), $_SERVER['PHP_SELF'], "tools", "pluginhourstracking");

$rates = new PluginHourstrackingClientrate();
$rates->showForm();

Html::footer();
