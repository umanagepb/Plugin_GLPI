<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginHourstrackingProfile extends Profile {

    static $rightname = "profile";

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return __('Hours Tracking', 'hourstracking');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            $profile = new self();
            $profile->showForm($item->getID());
        }
        return true;
    }

    /**
     * Inicializa o perfil durante a instalação
     */
    public function initProfile() {
        global $DB;

        $rights = [
            'plugin_hourstracking_report' => ALLSTANDARDRIGHT,
            'plugin_hourstracking_clientrate' => ALLSTANDARDRIGHT,
            'plugin_hourstracking_config' => READ | UPDATE
        ];

        // Adiciona os direitos ao perfil Super-Admin
        foreach ($rights as $right => $value) {
            $query = "UPDATE `glpi_profilerights` 
                      SET `rights` = '$value' 
                      WHERE `profiles_id` = '4' 
                      AND `name` = '$right'";
            $DB->queryOrDie($query, "Error adding $right");

            // Se não existe, insere
            if ($DB->affectedRows() == 0) {
                $query = "INSERT INTO `glpi_profilerights` 
                          (`profiles_id`, `name`, `rights`) 
                          VALUES ('4', '$right', '$value')";
                $DB->queryOrDie($query, "Error inserting $right");
            }
        }
    }

    /**
     * Atualiza o perfil durante upgrade
     */
    public function updateProfile() {
        $this->initProfile();
    }

    /**
     * Exibe formulário de configuração de direitos
     */
    public function showForm($profiles_id) {
        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        if (!Session::haveRight("profile", READ)) {
            return false;
        }

        $canedit = Session::haveRight("profile", UPDATE);

        echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='2'>" . __('Rights authorization', 'hourstracking') . "</th>";
        echo "</tr>";

        $rights = [
            'plugin_hourstracking_report' => __('View Reports', 'hourstracking'),
            'plugin_hourstracking_clientrate' => __('Manage Client Rates', 'hourstracking'),
            'plugin_hourstracking_config' => __('Plugin Configuration', 'hourstracking')
        ];

        foreach ($rights as $right => $label) {
            $this->displayRightsChoiceMatrix(
                $right,
                $label,
                $profile->fields[$right] ?? 0,
                $canedit
            );
        }

        if ($canedit) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2' class='center'>";
            echo "<input type='hidden' name='profiles_id' value='$profiles_id'>";
            echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='submit'>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
        Html::closeForm();
    }

    /**
     * Exibe matriz de escolha de direitos
     */
    private function displayRightsChoiceMatrix($name, $label, $value, $canedit) {
        echo "<tr class='tab_bg_2'>";
        echo "<td>$label</td>";
        echo "<td>";

        if ($canedit) {
            echo "<select name='$name'>";
            echo "<option value='0'" . ($value == 0 ? ' selected' : '') . ">" . __('No') . "</option>";
            echo "<option value='" . READ . "'" . ($value == READ ? ' selected' : '') . ">" . __('Read') . "</option>";
            echo "<option value='" . (READ | UPDATE) . "'" . ($value == (READ | UPDATE) ? ' selected' : '') . ">" . __('Read/Update') . "</option>";
            echo "<option value='" . ALLSTANDARDRIGHT . "'" . ($value == ALLSTANDARDRIGHT ? ' selected' : '') . ">" . __('All') . "</option>";
            echo "</select>";
        } else {
            $rights_names = [
                0 => __('No'),
                READ => __('Read'),
                READ | UPDATE => __('Read/Update'),
                ALLSTANDARDRIGHT => __('All')
            ];
            echo $rights_names[$value] ?? __('Unknown');
        }

        echo "</td>";
        echo "</tr>";
    }
}
