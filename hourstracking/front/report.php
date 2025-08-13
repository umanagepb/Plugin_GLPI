<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight("plugin_hourstracking_report", READ);

Html::header(PluginHourstrackingReport::getMenuName(), $_SERVER['PHP_SELF'], "tools", "pluginhourstracking");

$report = new PluginHourstrackingReport();
$report->display($_GET);

Html::footer();
