<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Your Company
 */

function plugin_evolutionintegration_uninstall() {
    global $DB;

    // Remover tabelas
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_evolutionintegration_conversations`");
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_evolutionintegration_conversation_history`");

    // Remover configurações
    Config::deleteConfigurationValues('plugin:evolutionintegration');

    return true;
}
