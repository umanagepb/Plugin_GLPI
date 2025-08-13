<?php
class PluginEvolutionintegrationConfig extends CommonDBTM {
    
    public static function getConfigOptions() {
        return [
            'inactivity_timeout' => 900, // 15 minutos
            'conversation_retention_days' => 30,
            'api_endpoint' => '',
            'api_token' => '',
            'auto_close_conversations' => 1,
            'webhook_url' => '',
            'default_instance' => 'default',
            'enable_notifications' => 1,
            'notification_template' => 'Novo ticket criado: {ticket_title}\nID: {ticket_id}\nStatus: {ticket_status}'
        ];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Config') {
            return __('Evolution Integration', 'evolutionintegration');
        }
        return '';
    }

    public static function displayConfigForm(Config $config) {
        $options = self::getConfigOptions();
        
        echo "<form method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        echo "<table class='tab_cadre_fixe'>";
        
        // Campos de configuração
        $fields = [
            'api_endpoint' => __('API Endpoint', 'evolutionintegration'),
            'api_token' => __('API Token', 'evolutionintegration'),
            'inactivity_timeout' => __('Inactivity Timeout (seconds)', 'evolutionintegration'),
            'conversation_retention_days' => __('Conversation Retention Days', 'evolutionintegration'),
            'auto_close_conversations' => __('Auto Close Conversations', 'evolutionintegration'),
            'webhook_url' => __('Webhook URL', 'evolutionintegration'),
            'default_instance' => __('Default Instance', 'evolutionintegration'),
            'enable_notifications' => __('Enable Notifications', 'evolutionintegration'),
            'notification_template' => __('Notification Template', 'evolutionintegration')
        ];

        foreach ($fields as $field => $label) {
            echo "<tr>";
            echo "<td>$label</td>";
            
            if ($field === 'auto_close_conversations' || $field === 'enable_notifications') {
                echo "<td>";
                Dropdown::showYesNo($field, Plugin::getOption('evolutionintegration', $field, $options[$field]));
                echo "</td>";
            } elseif ($field === 'notification_template') {
                echo "<td><textarea name='$field' rows='3' cols='50'>" . 
                     htmlspecialchars(Plugin::getOption('evolutionintegration', $field, $options[$field])) . "</textarea></td>";
            } elseif ($field === 'api_token') {
                echo "<td><input type='password' name='$field' size='50' value='" . 
                     htmlspecialchars(Plugin::getOption('evolutionintegration', $field, $options[$field])) . "'></td>";
            } else {
                echo "<td><input type='text' name='$field' size='50' value='" . 
                     htmlspecialchars(Plugin::getOption('evolutionintegration', $field, $options[$field])) . "'></td>";
            }
            echo "</tr>";
        }

        echo "<tr><td colspan='2'><input type='submit' name='update' value='" . __('Update') . "'></td></tr>";
        echo "</table></form>";
        
        // Test connection button
        echo "<br><table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __('API Connection Test', 'evolutionintegration') . "</th></tr>";
        echo "<tr><td colspan='2' class='center'>";
        echo "<button type='button' onclick='testEvolutionConnection()' class='submit'>" . 
             __('Test Connection', 'evolutionintegration') . "</button>";
        echo "<div id='connection-result'></div>";
        echo "</td></tr>";
        echo "</table>";
        
        // JavaScript para teste de conexão
        echo "<script>
        function testEvolutionConnection() {
            document.getElementById('connection-result').innerHTML = '<div class=\"loading\">" . 
            __('Testing connection...', 'evolutionintegration') . "</div>';
            
            fetch('" . Plugin::getWebDir('evolutionintegration') . "/ajax/test_connection.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('connection-result').innerHTML = 
                        '<div class=\"success\">" . __('Connection successful!', 'evolutionintegration') . "</div>';
                } else {
                    document.getElementById('connection-result').innerHTML = 
                        '<div class=\"error\">" . __('Connection failed:', 'evolutionintegration') . " ' + data.error + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('connection-result').innerHTML = 
                    '<div class=\"error\">" . __('Connection test failed:', 'evolutionintegration') . " ' + error + '</div>';
            });
        }
        </script>";
    }

    public static function updateConfig($input) {
        $config_fields = array_keys(self::getConfigOptions());
        
        foreach ($config_fields as $field) {
            if (isset($input[$field])) {
                Plugin::setOption('evolutionintegration', $field, $input[$field]);
            }
        }
        
        return true;
    }

    /**
     * Get a specific configuration value
     */
    public static function getConfigValue($key) {
        $options = self::getConfigOptions();
        return Plugin::getOption('evolutionintegration', $key, $options[$key] ?? '');
    }

    /**
     * Set a specific configuration value
     */
    public static function setConfigValue($key, $value) {
        return Plugin::setOption('evolutionintegration', $key, $value);
    }

    /**
     * Get all configuration values
     */
    public static function getAllConfigValues() {
        $options = self::getConfigOptions();
        $config = [];
        
        foreach ($options as $key => $default) {
            $config[$key] = Plugin::getOption('evolutionintegration', $key, $default);
        }
        
        return $config;
    }
}
