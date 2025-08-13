<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2023 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../../../inc/includes.php');

Session::checkRight("config", UPDATE);

if (!isset($_GET["id"])) $_GET["id"] = "";

$plugin = new Plugin();
if (isset($_POST["update"])) {
   $config = [];
   
   if (isset($_POST['api_key'])) {
      $config['api_key'] = trim($_POST['api_key']);
   }
   if (isset($_POST['workspace_id'])) {
      $config['workspace_id'] = trim($_POST['workspace_id']);
   }
   
   Config::setConfigurationValues("plugin:Clockify Integration", $config);
   
   Session::addMessageAfterRedirect(__('Update successful'));
   Html::back();
}

Html::header(__('Clockify Integration', 'clockifyintegration'), $_SERVER['PHP_SELF'], "config", "plugins");

echo "<div class='center'>";
echo "<form method='post' action='".$_SERVER['PHP_SELF']."'>";

$api_key = Config::getConfigurationValue("plugin:Clockify Integration", "api_key");
$workspace_id = Config::getConfigurationValue("plugin:Clockify Integration", "workspace_id");

echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Clockify Integration Configuration', 'clockifyintegration') . "</th></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Clockify API Key', 'clockifyintegration') . "</td>";
echo "<td><input type='text' name='api_key' value='" . Html::entities_deep($api_key) . "' size='50' /></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Clockify Workspace ID', 'clockifyintegration') . "</td>";
echo "<td><input type='text' name='workspace_id' value='" . Html::entities_deep($workspace_id) . "' size='50' /></td>";
echo "</tr>";

echo "<tr class='tab_bg_2'>";
echo "<td class='center' colspan='2'>";
echo "<input type='submit' name='update' value='" . _sx('button', 'Update') . "' class='submit'>";
echo "</td></tr>";

echo "</table>";
Html::closeForm();
echo "</div>";

Html::footer();
?>
