<?php
<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginHourstrackingClientrate extends CommonDBTM {
    static $rightname = 'plugin_hourstracking_clientrate';

    // Constantes para validação
    const MIN_RATE = 0.01;
    const MAX_RATE = 99999.99;
    const TABLE = 'glpi_plugin_hourstracking_clientrates';

    public static function getTypeName($nb = 0) {
        return _n('Client Rate', 'Client Rates', $nb, 'hourstracking');
    }

    public static function getMenuName() {
        return __('Taxas por Cliente', 'hourstracking');
    }

    public static function getMenuContent() {
        $menu = [];
        if (Session::haveRight(static::$rightname, READ)) {
            $menu['title'] = self::getMenuName();
            $menu['page']  = '/plugins/hourstracking/front/client_rates.php';
            $menu['icon']  = 'fas fa-dollar-sign';
        }
        return $menu;
    }

    /**
     * Valida taxa horária
     * @param float $rate Taxa a ser validada
     * @return bool
     */
    private static function validateRate($rate): bool {
        $rate = filter_var($rate, FILTER_VALIDATE_FLOAT);
        return $rate !== false && $rate >= self::MIN_RATE && $rate <= self::MAX_RATE;
    }

    /**
     * Valida ID do cliente
     * @param int $clientId ID do cliente
     * @return bool
     */
    private static function validateClientId($clientId): bool {
        $clientId = filter_var($clientId, FILTER_VALIDATE_INT);
        if ($clientId === false || $clientId <= 0) {
            return false;
        }
        
        // Verifica se o cliente existe
        $entity = new Entity();
        return $entity->getFromDB($clientId);
    }

    /**
     * Salva taxa do cliente com validação
     * @param int $client_id ID do cliente
     * @param float $hourly_rate Taxa horária
     * @return bool
     * @throws Exception Se os dados forem inválidos
     */
    public function saveClientRate($client_id, $hourly_rate) {
        global $DB;

        try {
            // Valida dados de entrada
            if (!self::validateClientId($client_id)) {
                throw new Exception(__('Invalid client ID', 'hourstracking'));
            }
            if (!self::validateRate($hourly_rate)) {
                throw new Exception(__('Invalid hourly rate', 'hourstracking'));
            }

            // Verifica permissão de acesso à entidade
            if (!Session::haveAccessToEntity($client_id)) {
                throw new Exception(__('Access denied to this entity', 'hourstracking'));
            }

            // Prepara dados para inserção/atualização
            $data = [
                'client_id' => $client_id,
                'hourly_rate' => round($hourly_rate, 2),
                'date_mod' => $_SESSION['glpi_currenttime']
            ];

            // Usa prepared statement através do QueryBuilder do GLPI 10
            return $DB->updateOrInsert(
                self::TABLE,
                $data,
                ['client_id' => $client_id]
            );

        } catch (Exception $e) {
            // Log do erro
            Toolbox::logError('Error saving client rate: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém taxa do cliente com validação
     * @param int $client_id ID do cliente
     * @return float Taxa horária ou 0.0 se não encontrada
     */
    public function getClientRate($client_id) {
        global $DB;

        try {
            // Valida ID do cliente
            if (!self::validateClientId($client_id)) {
                throw new Exception(__('Invalid client ID', 'hourstracking'));
            }

            // Verifica permissão de acesso à entidade
            if (!Session::haveAccessToEntity($client_id)) {
                throw new Exception(__('Access denied to this entity', 'hourstracking'));
            }

            // Usa prepared statement através do QueryBuilder
            $result = $DB->request([
                'FROM' => self::TABLE,
                'WHERE' => ['client_id' => $client_id]
            ])->next();

            return $result ? (float)$result['hourly_rate'] : 0.0;

        } catch (Exception $e) {
            // Log do erro
            Toolbox::logError('Error getting client rate: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Exibe formulário para gerenciar taxas dos clientes
     */
    public function showForm() {
        global $DB;

        // Processa formulário se foi submetido
        if (isset($_POST['save_rate'])) {
            $client_id = $_POST['client_id'];
            $hourly_rate = $_POST['hourly_rate'];
            
            if ($this->saveClientRate($client_id, $hourly_rate)) {
                Session::addMessageAfterRedirect(
                    __('Taxa salva com sucesso!', 'hourstracking'),
                    false,
                    INFO
                );
            } else {
                Session::addMessageAfterRedirect(
                    __('Erro ao salvar taxa', 'hourstracking'),
                    false,
                    ERROR
                );
            }
        }

        echo "<div class='center'>";
        echo "<h2>" . __('Gerenciar Taxas por Cliente', 'hourstracking') . "</h2>";

        // Formulário para adicionar/editar taxa
        echo "<form method='post'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __('Configurar Taxa', 'hourstracking') . "</th></tr>";

        // Dropdown de clientes
        echo "<tr><td>" . __('Cliente', 'hourstracking') . "</td><td>";
        Entity::dropdown(['name' => 'client_id', 'value' => $_POST['client_id'] ?? 0]);
        echo "</td></tr>";

        // Campo de taxa horária
        echo "<tr><td>" . __('Taxa Horária (R$)', 'hourstracking') . "</td>";
        echo "<td><input type='number' step='0.01' min='0.01' max='99999.99' name='hourly_rate' value='" . 
             ($_POST['hourly_rate'] ?? '0.00') . "' required/></td></tr>";

        echo "<tr><td colspan='2' class='center'>";
        echo "<input type='submit' name='save_rate' value='" . __('Salvar', 'hourstracking') . "' class='submit'/>";
        echo "</td></tr>";
        echo "</table>";
        Html::closeForm();

        // Lista de taxas existentes
        echo "<br/>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='3'>" . __('Taxas Configuradas', 'hourstracking') . "</th></tr>";
        echo "<tr><th>" . __('Cliente', 'hourstracking') . "</th>";
        echo "<th>" . __('Taxa Horária', 'hourstracking') . "</th>";
        echo "<th>" . __('Última Modificação', 'hourstracking') . "</th></tr>";

        $criteria = [
            'FROM' => self::TABLE . ' AS cr',
            'LEFT JOIN' => [
                'glpi_entities AS e' => [
                    'ON' => ['cr' => 'client_id', 'e' => 'id']
                ]
            ],
            'SELECT' => [
                'cr.client_id',
                'e.name AS client_name',
                'cr.hourly_rate',
                'cr.date_mod'
            ],
            'WHERE' => getEntitiesRestrictCriteria('e'),
            'ORDER' => 'e.name'
        ];

        $iterator = $DB->request($criteria);
        
        if ($iterator->count() > 0) {
            foreach ($iterator as $row) {
                echo "<tr>";
                echo "<td>" . $row['client_name'] . "</td>";
                echo "<td>R$ " . number_format($row['hourly_rate'], 2, ',', '.') . "</td>";
                echo "<td>" . Html::convDateTime($row['date_mod']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='center'>" . __('Nenhuma taxa configurada', 'hourstracking') . "</td></tr>";
        }

        echo "</table>";
        echo "</div>";
    }

    /**
     * Define os direitos de acesso
     * @return array
     */
    public static function getRights() {
        $rights = parent::getRights();
        $rights[self::READNOTE] = ['short' => __('Read notes'),
                                  'long'  => __('Read client rate notes')];
        return $rights;
    }
}
