<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Your Company
 */

function plugin_evolutionintegration_install() {
    global $DB;

    // Criar tabela de conversas
    $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_evolutionintegration_conversations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `ticket_id` INT(11) NOT NULL,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME DEFAULT NULL,
        `last_activity_time` DATETIME NOT NULL,
        `total_duration` INT(11) DEFAULT 0,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        KEY `ticket_id` (`ticket_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query);

    // Criar tabela de histórico de conversas
    $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_evolutionintegration_conversation_history` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `conversation_id` INT(11) NOT NULL,
        `message` TEXT,
        `timestamp` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `conversation_id` (`conversation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query);

    // Configurações padrão
    Config::setConfigurationValues('plugin:evolutionintegration', [
        'inactivity_timeout' => 900,
        'conversation_retention_days' => 30,
        'api_endpoint' => '',
        'api_token' => '',
        'auto_close_conversations' => 1,
        'webhook_url' => '',
        'default_instance' => 'default',
        'enable_notifications' => 1,
        'notification_template' => 'Novo ticket criado: {ticket_title}\nID: {ticket_id}\nStatus: {ticket_status}'
    ]);

    return true;
}
