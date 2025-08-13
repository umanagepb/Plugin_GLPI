<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

define('PLUGIN_HOURSTRACKING_VERSION', '1.0.0');
define('PLUGIN_HOURSTRACKING_MIN_GLPI', '10.0.0');
define('PLUGIN_HOURSTRACKING_MAX_GLPI', '10.1.99');
define('PLUGIN_HOURSTRACKING_DIRECTORY', basename(dirname(__FILE__)));

// Estados de tarefas
define('PLUGIN_HOURSTRACKING_TASK_DONE', \Planning::DONE);
define('PLUGIN_HOURSTRACKING_TASK_TODO', \Planning::TODO);

function plugin_init_hourstracking() {
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $plugin = new Plugin();
    if (!$plugin->isInstalled('hourstracking') || !$plugin->isActivated('hourstracking')) {
        return false;
    }

    // Registra classes principais para autoload
    Plugin::registerClass('PluginHourstrackingConfig');
    Plugin::registerClass('PluginHourstrackingReport');
    Plugin::registerClass('PluginHourstrackingClientrate');
    Plugin::registerClass('PluginHourstrackingPlugin');

    $PLUGIN_HOOKS['csrf_compliant']['hourstracking'] = true;
    
    // Menu principal
    if (Session::haveRight('plugin_hourstracking_report', READ)) {
        $PLUGIN_HOOKS['menu_toadd']['hourstracking'] = 'tools';
        $PLUGIN_HOOKS['menu_entry']['hourstracking'] = true;
    }

    // Configurações de segurança
    $PLUGIN_HOOKS['secured_configs']['hourstracking'] = true;

    // Adiciona configuração
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['hourstracking'] = 'front/config.form.php';
    }

    // Hook para instalação
    $PLUGIN_HOOKS['install']['hourstracking'] = 'plugin_hourstracking_install';
    $PLUGIN_HOOKS['uninstall']['hourstracking'] = 'plugin_hourstracking_uninstall';
}

function plugin_version_hourstracking() {
    return [
        'name'         => __("Controle de Horas", 'hourstracking'),
        'version'      => PLUGIN_HOURSTRACKING_VERSION,
        'author'       => 'Assistente de IA',
        'license'      => 'GPL v2+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => '10.0',
                'max' => '10.1'
            ]
        ]
    ];
}

function plugin_hourstracking_check_prerequisites() {
    if (!method_exists('Plugin', 'registerClass')) {
        echo "Este plugin requer GLPI >= 10.0";
        return false;
    }
    return true;
}

function plugin_hourstracking_check_config($verbose = false) {
    if (true) { // Adicione aqui suas verificações de configuração
        return true;
    }
    return false;
}

/**
 * Define os direitos do plugin
 */
function plugin_hourstracking_install_profiles() {
    include_once(Plugin::getPhpDir('hourstracking') . "/inc/profile.class.php");
    $profile = new PluginHourstrackingProfile();
    $profile->initProfile();
    return true;
}

/**
 * Atualiza direitos de perfil
 */
function plugin_hourstracking_update_profiles() {
    include_once(Plugin::getPhpDir('hourstracking') . "/inc/profile.class.php");
    $profile = new PluginHourstrackingProfile();
    $profile->updateProfile();
    return true;
}
