<?php
class PluginHourstrackingConfig extends CommonDBTM {
    static $rightname = 'config';
    private static $config_cache = null;

    public static function getConfig($key, $default = null) {
        if (self::$config_cache === null) {
            $conf = Config::getConfigurationValues('plugin:hourstracking');
            if (!is_array($conf)) {
                $conf = [];
            }
            self::$config_cache = $conf;
        }
        $value = isset(self::$config_cache[$key]) ? self::$config_cache[$key] : null;
        return $value !== null ? $value : $default;
    }

    public static function setConfig($values) {
        if (!is_array($values)) {
            throw new \Exception(__('Configuration values must be an array', 'hourstracking'));
        }
        // Corrige possíveis valores booleanos salvos anteriormente
        $current = Config::getConfigurationValues('plugin:hourstracking');
        if (!is_array($current)) {
            if (method_exists('Config', 'deleteConfigurationValues')) {
                Config::deleteConfigurationValues('plugin:hourstracking');
            }
        }
        Config::setConfigurationValues('plugin:hourstracking', (array)$values);
        self::$config_cache = null;
    }

    public static function getTypeName($nb = 0) {
        return __("Configuração do Controle de Horas", 'hourstracking');
    }

    public static function processConfigForm() {
        if (isset($_POST['update'])) {
            try {
                $config = [];
                
                // Validar e converter valores para o formato correto
                $config['default_hour_rate'] = isset($_POST['default_hour_rate']) ? 
                    max(0, floatval($_POST['default_hour_rate'])) : 0.00;
                
                $config['minimum_hours'] = isset($_POST['minimum_hours']) ? 
                    max(0, floatval($_POST['minimum_hours'])) : 1;
                
                $config['billing_workdays'] = isset($_POST['billing_workdays']) ? 
                    max(1, min(31, intval($_POST['billing_workdays']))) : 22;
                
                $config['time_rounding'] = isset($_POST['time_rounding']) ? 
                    max(1, min(60, intval($_POST['time_rounding']))) : 15;
                
                // Tenta salvar cada configuração
                self::setConfig($config);
                
                Session::addMessageAfterRedirect(
                    __('Configurações salvas com sucesso!', 'hourstracking'),
                    false,
                    INFO
                );
            } catch (\Exception $e) {
                Session::addMessageAfterRedirect(
                    __('Erro ao salvar configurações: ', 'hourstracking') . $e->getMessage(),
                    false,
                    ERROR
                );
            }
        }
    }

    public function showConfigForm() {
        self::processConfigForm();
        // Exibe formulário de configurações gerais
        echo "<form method='post' action='" . $this->getFormURL() . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __('Configurações Gerais', 'hourstracking') . "</th></tr>";
        
        // Campo para valor da hora padrão
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Valor da hora padrão (R$)', 'hourstracking') . "</td>";
        echo "<td><input type='number' step='0.01' name='default_hour_rate' value='" . 
             self::getConfig('default_hour_rate', '0.00') . "'/></td>";
        echo "</tr>";

        // Campo para horas mínimas de faturamento
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Horas mínimas de faturamento', 'hourstracking') . "</td>";
        echo "<td><input type='number' step='0.5' name='minimum_hours' value='" . 
             self::getConfig('minimum_hours', '1') . "'/></td>";
        echo "</tr>";

        // Campo para dias úteis de faturamento
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Dias úteis de faturamento', 'hourstracking') . "</td>";
        echo "<td><input type='number' name='billing_workdays' value='" . 
             self::getConfig('billing_workdays', '22') . "'/></td>";
        echo "</tr>";

        // Campo para arredondamento de horas
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Arredondamento de horas (em minutos)', 'hourstracking') . "</td>";
        echo "<td><select name='time_rounding'>";
        $rounding_options = array('1' => '1', '5' => '5', '10' => '10', '15' => '15', '30' => '30', '60' => '60');
        foreach ($rounding_options as $value => $label) {
            $selected = (self::getConfig('time_rounding', '15') == $value) ? ' selected' : '';
            echo "<option value='{$value}'{$selected}>{$label}</option>";
        }
        echo "</select></td>";
        echo "</tr>";
        
        echo "</table>";
        
        // Botões de ação
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='submit' name='update' value='" . __('Salvar', 'hourstracking') . "' class='submit'>";
        echo "</td></tr>";
        echo "</table>";

        Html::closeForm();
    }
}
