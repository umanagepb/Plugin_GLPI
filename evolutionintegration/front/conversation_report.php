<?php
include('../../../inc/includes.php');

Session::checkLoginFIELD("config", READ);

Html::header(__('Conversation Report', 'evolutionintegration'), $_SERVER['PHP_SELF'], "config", "pluginevolutionintegrationreport");

$report = PluginEvolutionintegrationReport::generateConversationReport($_GET);
PluginEvolutionintegrationReport::displayReport($report);

Html::footer();
