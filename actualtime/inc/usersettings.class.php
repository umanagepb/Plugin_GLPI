<?php

/**
 * -------------------------------------------------------------------------
 * ActualTime plugin for GLPI
 * Copyright (C) 2018-2025 by the TICGAL Team.
 * https://www.tic.gal/
 * -------------------------------------------------------------------------
 * LICENSE
 * This file is part of the ActualTime plugin.
 * ActualTime plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ActualTime plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along withOneTimeSecret. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @package   ActualTime
 * @author    the TICGAL team
 * @copyright Copyright (c) 2018-2025 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal/
 * @since     2018
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginActualtimeUsersettings
 * Manages user-specific settings for ActualTime plugin
 */
class PluginActualtimeUsersettings extends CommonDBTM
{
    public static $rightname = 'preference';

    /**
     * {@inheritDoc}
     */
    public static function getTypeName($nb = 0): string
    {
        return __("ActualTime User Settings", "actualtime");
    }

    /**
     * getTabNameForItem
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item->getType() == 'User' && $item->canUpdate()) {
            return __("ActualTime", "actualtime");
        }
        return '';
    }

    /**
     * displayTabContentForItem
     *
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item->getType() == 'User') {
            $settings = new self();
            $settings->showUserSettings($item->getID());
            return true;
        }
        return false;
    }

    /**
     * showUserSettings
     * Display user settings form
     *
     * @param int $users_id
     * @return void
     */
    public function showUserSettings(int $users_id): void
    {
        $current_user_id = Session::getLoginUserID();
        
        // Only allow users to edit their own settings or if they have admin rights
        if ($users_id != $current_user_id && !Session::haveRight('user', UPDATE)) {
            return;
        }

        if (isset($_POST['update_actualtime_settings'])) {
            $this->updateUserSettings($users_id, $_POST);
            Session::addMessageAfterRedirect(__('Settings updated successfully'));
            Html::redirect($_SERVER['PHP_SELF'] . '?id=' . $users_id);
        }

        $clockify_api_key = $this->getUserSetting($users_id, 'clockify_api_key');

        echo "<div class='center'>";
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "?id=" . $users_id . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __('Clockify Integration Settings', 'actualtime') . "</th></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Clockify API Key', 'actualtime') . "</td>";
        echo "<td>";
        echo "<input type='password' name='clockify_api_key' value='" . Html::entities_deep($clockify_api_key) . "' size='60' />";
        echo "<br><small>" . __('Your personal Clockify API Key. Keep it private!', 'actualtime') . "</small>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'>";
        echo "<p><strong>" . __('How to get your Clockify API Key:', 'actualtime') . "</strong></p>";
        echo "<ol>";
        echo "<li>" . __('Go to Clockify web app', 'actualtime') . "</li>";
        echo "<li>" . __('Click on your profile picture in the top right corner', 'actualtime') . "</li>";
        echo "<li>" . __('Select "Profile settings"', 'actualtime') . "</li>";
        echo "<li>" . __('Go to "API" tab', 'actualtime') . "</li>";
        echo "<li>" . __('Generate a new API key if you don\'t have one', 'actualtime') . "</li>";
        echo "<li>" . __('Copy and paste the API key above', 'actualtime') . "</li>";
        echo "</ol>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='submit' name='update_actualtime_settings' value='" . __('Save') . "' class='btn btn-primary' />";
        echo "</td>";
        echo "</tr>";
        
        echo "</table>";
        echo "</form>";
        echo "</div>";
    }

    /**
     * getUserSetting
     * Get a specific user setting
     *
     * @param int $users_id
     * @param string $key
     * @return string
     */
    public function getUserSetting(int $users_id, string $key): string
    {
        return Config::getConfigurationValue('plugin:ActualTime:User:' . $users_id, $key) ?: '';
    }

    /**
     * updateUserSettings
     * Update user settings
     *
     * @param int $users_id
     * @param array $data
     * @return bool
     */
    public function updateUserSettings(int $users_id, array $data): bool
    {
        $config_data = [];
        
        if (isset($data['clockify_api_key'])) {
            $config_data['clockify_api_key'] = trim($data['clockify_api_key']);
        }
        
        if (!empty($config_data)) {
            Config::setConfigurationValues('plugin:ActualTime:User:' . $users_id, $config_data);
            return true;
        }
        
        return false;
    }

    /**
     * getClockifyApiKey
     * Get Clockify API Key for current user
     *
     * @param int|null $users_id
     * @return string
     */
    public static function getClockifyApiKey(?int $users_id = null): string
    {
        if ($users_id === null) {
            $users_id = Session::getLoginUserID();
        }
        
        $settings = new self();
        return $settings->getUserSetting($users_id, 'clockify_api_key');
    }

    /**
     * install
     * Installation method for user settings
     *
     * @param Migration $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        // No database table needed - using Config table
        // Just ensure that the tab is properly registered
    }

    /**
     * uninstall
     * Cleanup user settings on uninstall
     *
     * @param Migration $migration
     * @return void
     */
    public static function uninstall(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Clean up all user-specific ActualTime configurations
        $DB->delete(
            'glpi_configs',
            [
                'context' => ['LIKE', 'plugin:ActualTime:User:%']
            ]
        );
    }
    
    /**
     * Install process for plugin
     *
     * @param Migration $migration Migration instance
     * @return boolean
     */
    static function install(Migration $migration) {
        // User settings are stored in glpi_configs table
        // No need to create additional tables
        return true;
    }

    /**
     * Uninstall process for plugin
     *
     * @return boolean
     */
    static function uninstall() {
        global $DB;
        
        // Clean up all user-specific ActualTime configurations
        $DB->delete(
            'glpi_configs',
            [
                'context' => ['LIKE', 'plugin:ActualTime:User:%']
            ]
        );
        
        return true;
    }
}
