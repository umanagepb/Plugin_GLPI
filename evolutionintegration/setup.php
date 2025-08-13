<?php
/*
 * -------------------------------------------------------------------------
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Your Company
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This file is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

define('PLUGIN_EVOLUTIONINTEGRATION_VERSION', '1.0.0');
define('PLUGIN_EVOLUTIONINTEGRATION_MIN_GLPI', '10.0');
define('PLUGIN_EVOLUTIONINTEGRATION_MAX_GLPI', '10.1');

if (!defined('GLPI_ROOT')) {
   die("Acesso não autorizado");
}

// Função de inicialização do plugin
function plugin_init_evolutionintegration() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['evolutionintegration'] = true;

    // Hooks para ticket
    $PLUGIN_HOOKS['pre_item_add']['evolutionintegration'] = [
        'Ticket' => 'plugin_evolutionintegration_ticket_add'
    ];
    $PLUGIN_HOOKS['pre_item_update']['evolutionintegration'] = [
        'Ticket' => 'plugin_evolutionintegration_ticket_update'
    ];

    // Hook para cron
    $PLUGIN_HOOKS['cron']['evolutionintegration'] = [
        'PluginEvolutionintegrationConversation::cleanOldConversations' => [
            'frequency' => 86400, // Uma vez por dia
            'description' => __('Clean old conversations', 'evolutionintegration')
        ]
    ];

    // Menu de configuração
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['evolutionintegration'] = 'front/config.form.php';
    }

    // Menu de relatórios
    if (Session::haveRight('config', READ)) {
        $PLUGIN_HOOKS['menu_toadd']['evolutionintegration'] = [
            'reports' => 'PluginEvolutionintegrationReport'
        ];
    }

    // Registro de classes
    Plugin::registerClass('PluginEvolutionintegrationConfig');
    Plugin::registerClass('PluginEvolutionintegrationConversation');
    Plugin::registerClass('PluginEvolutionintegrationReport');
    Plugin::registerClass('PluginEvolutionintegrationApi');
}

// Informações básicas do plugin
function plugin_version_evolutionintegration() {
    return [
        'name'           => __('Evolution Integration', 'evolutionintegration'),
        'version'        => PLUGIN_EVOLUTIONINTEGRATION_VERSION,
        'author'         => 'Umanage Tecnologia de Gestao Ltda',
        'license'        => 'GPL v2+',
        'homepage'       => 'https://umanage.com.br',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_EVOLUTIONINTEGRATION_MIN_GLPI,
                'max' => PLUGIN_EVOLUTIONINTEGRATION_MAX_GLPI
            ]
        ]
    ];
}

// Verificação de pré-requisitos
function plugin_evolutionintegration_check_prerequisites() {
    return true;
}

// Verificação de configuração
function plugin_evolutionintegration_check_config($verbose = false) {
    return true;
}
