<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Umanage Tecnologia de Gestao Ltda
 */

include('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

Html::header(__('Evolution Integration', 'evolutionintegration'), $_SERVER['PHP_SELF'], "config", "plugins");

if (isset($_POST['update'])) {
    PluginEvolutionintegrationConfig::updateConfig($_POST);
    Session::addMessageAfterRedirect(__('Configuration updated successfully', 'evolutionintegration'));
    Html::redirect($_SERVER['PHP_SELF']);
}

echo "<div class='center'>";
echo "<h2>" . __('Evolution Integration Configuration', 'evolutionintegration') . "</h2>";

echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_1'>";

// API Endpoint
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('API Endpoint', 'evolutionintegration') . "</td>";
echo "<td><input type='text' name='api_endpoint' size='50' value='" . 
     htmlspecialchars(PluginEvolutionintegrationConfig::getConfigValue('api_endpoint')) . "'></td>";
echo "</tr>";

// API Token
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('API Token', 'evolutionintegration') . "</td>";
echo "<td><input type='password' name='api_token' size='50' value='" . 
     htmlspecialchars(PluginEvolutionintegrationConfig::getConfigValue('api_token')) . "'></td>";
echo "</tr>";

// Inactivity Timeout
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Inactivity Timeout (seconds)', 'evolutionintegration') . "</td>";
echo "<td><input type='number' name='inactivity_timeout' min='60' max='3600' value='" . 
     PluginEvolutionintegrationConfig::getConfigValue('inactivity_timeout') . "'></td>";
echo "</tr>";

// Conversation Retention Days
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Conversation Retention Days', 'evolutionintegration') . "</td>";
echo "<td><input type='number' name='conversation_retention_days' min='1' max='365' value='" . 
     PluginEvolutionintegrationConfig::getConfigValue('conversation_retention_days') . "'></td>";
echo "</tr>";

// Auto Close Conversations
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Auto Close Conversations', 'evolutionintegration') . "</td>";
echo "<td>";
Dropdown::showYesNo('auto_close_conversations', PluginEvolutionintegrationConfig::getConfigValue('auto_close_conversations'));
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_2'>";
echo "<td colspan='2' class='center'>";
echo "<input type='submit' name='update' value='" . _sx('button', 'Update') . "' class='submit'>";
echo "</td>";
echo "</tr>";

echo "</table>";
Html::closeForm();

echo "</div>";

Html::footer();
?>
