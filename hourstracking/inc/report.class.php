<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginHourstrackingReport extends CommonDBTM {
    static $rightname = 'plugin_hourstracking_report';

    // Constantes para tipos de relatório
    const REPORT_TYPE_DETAILED = 'detailed';
    const REPORT_TYPE_SUMMARY = 'summary';
    
    // Constantes para campos de data
    const DATE_FORMAT = 'Y-m-d H:i:s';
    const MIN_DATE = '2000-01-01';
    const MAX_DATE = '2099-12-31';

    public static function getMenuName() {
        return __('Controle de Horas', 'hourstracking');
    }

    public static function getMenuContent() {
        $menu = [];
        if (Session::haveRight(static::$rightname, READ)) {
            $menu['title'] = self::getMenuName();
            $menu['page']  = '/plugins/hourstracking/front/reports.php';
            $menu['icon']  = 'fas fa-clock';
            
            $menu['options']['report']['title'] = __('Relatórios', 'hourstracking');
            $menu['options']['report']['page']  = '/plugins/hourstracking/front/reports.php';
            $menu['options']['report']['icon']  = 'fas fa-chart-bar';
            
            $menu['options']['attendant']['title'] = __('Por Atendente', 'hourstracking');
            $menu['options']['attendant']['page']  = '/plugins/hourstracking/front/attendant_report.php';
            $menu['options']['attendant']['icon']  = 'fas fa-user';
            
            $menu['options']['client']['title'] = __('Por Cliente', 'hourstracking');
            $menu['options']['client']['page']  = '/plugins/hourstracking/front/client_report.php';
            $menu['options']['client']['icon']  = 'fas fa-building';

            $menu['options']['rates']['title'] = __('Taxas por Cliente', 'hourstracking');
            $menu['options']['rates']['page']  = '/plugins/hourstracking/front/client_rates.php';
            $menu['options']['rates']['icon']  = 'fas fa-dollar-sign';
        }
        return $menu;
    }

    public static function getTypeName($nb = 0) {
        return _n('Report', 'Reports', $nb, 'hourstracking');
    }

    /**
     * Valida parâmetros de entrada do relatório
     * @param array $params Parâmetros a serem validados
     * @return array Parâmetros limpos e validados
     * @throws Exception Se os parâmetros forem inválidos
     */
    private function validateParams(array $params): array {
        $cleanParams = [];
        
        // Validação de datas
        if (!empty($params['start_date'])) {
            $startDate = DateTime::createFromFormat('Y-m-d', $params['start_date']);
            if (!$startDate || $startDate->format('Y-m-d') < self::MIN_DATE) {
                throw new Exception(__('Invalid start date', 'hourstracking'));
            }
            $cleanParams['start_date'] = $startDate->format('Y-m-d');
        }

        if (!empty($params['end_date'])) {
            $endDate = DateTime::createFromFormat('Y-m-d', $params['end_date']);
            if (!$endDate || $endDate->format('Y-m-d') > self::MAX_DATE) {
                throw new Exception(__('Invalid end date', 'hourstracking'));
            }
            $cleanParams['end_date'] = $endDate->format('Y-m-d');
        }

        // Validação de IDs numéricos
        if (!empty($params['attendant_id'])) {
            $cleanParams['attendant_id'] = filter_var($params['attendant_id'], FILTER_VALIDATE_INT);
            if ($cleanParams['attendant_id'] === false) {
                throw new Exception(__('Invalid attendant ID', 'hourstracking'));
            }
        }

        if (!empty($params['client_id'])) {
            $cleanParams['client_id'] = filter_var($params['client_id'], FILTER_VALIDATE_INT);
            if ($cleanParams['client_id'] === false) {
                throw new Exception(__('Invalid client ID', 'hourstracking'));
            }
        }

        return $cleanParams;
    }

    /**
     * Gera o relatório detalhado conforme consulta SQL fornecida.
     * @param array $params Parâmetros do relatório
     * @return array Dados do relatório
     */
    public function generateReport($params) {
        global $DB;

        try {
            $params = $this->validateParams($params);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }

        $criteria = [
            'SELECT' => [
                'tt.tickets_id AS nro_ticket',
                't.name AS assunto',
                't.date AS data_ticket',
                'e.name AS cliente',
                new QueryExpression('ROUND(tt.actiontime/3600, 2) AS total_horas'),
                'tec.name AS attendant',
                new QueryExpression('REPLACE(REPLACE(REPLACE(t.content,"<p>",""),"<br />",""),"</p>","") AS desc_solicitacao'),
                'tt.date AS data_tarefa',
                'sol.name AS solicitante',
                new QueryExpression('REPLACE(REPLACE(REPLACE(tt.content,"<p>",""),"<br />",""),"</p>","") AS desc_tarefa'),
                new QueryExpression('COALESCE(cr.hourly_rate, 0) AS hourly_rate'),
                new QueryExpression('ROUND((tt.actiontime/3600) * COALESCE(cr.hourly_rate, 0), 2) AS total_value'),
                'e.id AS client_id'
            ],
            'FROM' => 'glpi_tickettasks AS tt',
            'LEFT JOIN' => [
                'glpi_tickets AS t' => [
                    'ON' => ['tt' => 'tickets_id', 't' => 'id']
                ],
                'glpi_entities AS e' => [
                    'ON' => ['t' => 'entities_id', 'e' => 'id']
                ],
                'glpi_users AS tec' => [
                    'ON' => ['tt' => 'users_id', 'tec' => 'id']
                ],
                'glpi_users AS sol' => [
                    'ON' => ['t' => 'users_id_recipient', 'sol' => 'id']
                ],
                'glpi_plugin_hourstracking_clientrates AS cr' => [
                    'ON' => ['e' => 'id', 'cr' => 'client_id']
                ]
            ],
            'WHERE' => [
                'tt.actiontime' => ['>', 0]
            ],
            'ORDER' => ['tt.date DESC']
        ];

        // Adiciona filtros baseados nos parâmetros validados
        if (!empty($params['start_date'])) {
            $criteria['WHERE'][] = ['tt.date' => ['>=', $params['start_date'] . ' 00:00:00']];
        }
        if (!empty($params['end_date'])) {
            $criteria['WHERE'][] = ['tt.date' => ['<=', $params['end_date'] . ' 23:59:59']];
        }
        if (!empty($params['attendant_id'])) {
            $criteria['WHERE'][] = ['tt.users_id' => intval($params['attendant_id'])];
        }
        if (!empty($params['client_id'])) {
            $criteria['WHERE'][] = ['e.id' => intval($params['client_id'])];
        }

        // Adiciona verificação de entidades que o usuário pode acessar
        $criteria['WHERE'] = array_merge($criteria['WHERE'], getEntitiesRestrictCriteria('e'));

        try {
            $iterator = $DB->request($criteria);
            $report = [];
            foreach ($iterator as $row) {
                $report[] = $row;
            }
            return $report;
        } catch (Exception $e) {
            Toolbox::logError('Error generating report: ' . $e->getMessage());
            return ['error' => __('Error generating report', 'hourstracking')];
        }
    }

    /**
     * Gera relatório agrupado por atendente
     * @param array $params Parâmetros do relatório
     * @param bool $detailed Se deve incluir detalhes
     * @return array Dados do relatório
     */
    public function generateByAttendant($params, $detailed = false) {
        global $DB;

        try {
            $params = $this->validateParams($params);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }

        if ($detailed) {
            $criteria = [
                'SELECT' => [
                    'tec.name AS atendente',
                    'e.name AS cliente',
                    'tt.date AS data_tarefa',
                    'tt.tickets_id AS nro_ticket',
                    't.name AS assunto',
                    new QueryExpression('ROUND(tt.actiontime/3600, 2) AS total_horas'),
                    new QueryExpression('ROUND((tt.actiontime/3600) * COALESCE(cr.hourly_rate, 0), 2) AS valor_total')
                ],
                'ORDER' => ['tec.name', 'tt.date DESC']
            ];
        } else {
            $criteria = [
                'SELECT' => [
                    'tec.name AS atendente',
                    new QueryExpression('SUM(ROUND(tt.actiontime/3600, 2)) AS total_horas'),
                    new QueryExpression('SUM(ROUND((tt.actiontime/3600) * COALESCE(cr.hourly_rate, 0), 2)) AS valor_total')
                ],
                'GROUPBY' => ['tec.id'],
                'ORDER' => ['tec.name']
            ];
        }

        $criteria['FROM'] = 'glpi_tickettasks AS tt';
        $criteria['LEFT JOIN'] = [
            'glpi_users AS tec' => [
                'ON' => ['tt' => 'users_id', 'tec' => 'id']
            ],
            'glpi_tickets AS t' => [
                'ON' => ['t' => 'id', 'tt' => 'tickets_id']
            ],
            'glpi_entities AS e' => [
                'ON' => ['e' => 'id', 't' => 'entities_id']
            ],
            'glpi_plugin_hourstracking_clientrates AS cr' => [
                'ON' => ['cr' => 'client_id', 'e' => 'id']
            ]
        ];

        $criteria['WHERE'] = ['tt.actiontime' => ['>', 0]];

        // Adiciona filtros baseados nos parâmetros validados
        if (!empty($params['start_date'])) {
            $criteria['WHERE'][] = ['tt.date' => ['>=', $params['start_date'] . ' 00:00:00']];
        }
        if (!empty($params['end_date'])) {
            $criteria['WHERE'][] = ['tt.date' => ['<=', $params['end_date'] . ' 23:59:59']];
        }

        // Adiciona verificação de entidades que o usuário pode acessar
        $criteria['WHERE'] = array_merge($criteria['WHERE'], getEntitiesRestrictCriteria('e'));

        try {
            $iterator = $DB->request($criteria);
            $report = [];
            foreach ($iterator as $row) {
                $report[] = $row;
            }
            return $report;
        } catch (Exception $e) {
            Toolbox::logError('Error generating attendant report: ' . $e->getMessage());
            return ['error' => __('Error generating report', 'hourstracking')];
        }
    }

    /**
     * Gera relatório detalhado para envio ao cliente
     * @param array $params Parâmetros do relatório
     * @return array Dados do relatório
     */
    public function generateDetailedClientReport($params) {
        global $DB;

        try {
            $params = $this->validateParams($params);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }

        $criteria = [
            'SELECT' => [
                new QueryExpression('SEC_TO_TIME(tt.actiontime) AS tempo_hhmm'),
                new QueryExpression('ROUND((tt.actiontime/3600) * COALESCE(cr.hourly_rate, 0), 2) AS valor_atendimento'),
                'tt.date AS data_tarefa',
                'sol.name AS solicitante',
                'tec.name AS agente',
                't.name AS assunto',
                new QueryExpression('REPLACE(REPLACE(REPLACE(tt.content,"<p>",""),"<br />",""),"</p>","") AS desc_tarefa')
            ],
            'FROM' => 'glpi_tickettasks AS tt',
            'LEFT JOIN' => [
                'glpi_tickets AS t' => [
                    'ON' => ['t' => 'id', 'tt' => 'tickets_id']
                ],
                'glpi_entities AS e' => [
                    'ON' => ['e' => 'id', 't' => 'entities_id']
                ],
                'glpi_users AS tec' => [
                    'ON' => ['tt' => 'users_id', 'tec' => 'id']
                ],
                'glpi_users AS sol' => [
                    'ON' => ['t' => 'users_id_recipient', 'sol' => 'id']
                ],
                'glpi_plugin_hourstracking_clientrates AS cr' => [
                    'ON' => ['cr' => 'client_id', 'e' => 'id']
                ]
            ],
            'WHERE' => ['tt.actiontime' => ['>', 0]],
            'ORDER' => ['tt.date DESC']
        ];

        // Adiciona filtros baseados nos parâmetros validados
        if (!empty($params['start_date'])) {
            $criteria['WHERE'][] = ['tt.date' => ['>=', $params['start_date'] . ' 00:00:00']];
        }
        if (!empty($params['end_date'])) {
            $criteria['WHERE'][] = ['tt.date' => ['<=', $params['end_date'] . ' 23:59:59']];
        }
        if (!empty($params['client_id'])) {
            $criteria['WHERE'][] = ['e.id' => intval($params['client_id'])];
        }

        // Adiciona verificação de entidades que o usuário pode acessar
        $criteria['WHERE'] = array_merge($criteria['WHERE'], getEntitiesRestrictCriteria('e'));

        try {
            $iterator = $DB->request($criteria);
            $report = [];
            foreach ($iterator as $row) {
                $report[] = $row;
            }
            return $report;
        } catch (Exception $e) {
            Toolbox::logError('Error generating client report: ' . $e->getMessage());
            return ['error' => __('Error generating report', 'hourstracking')];
        }
    }
}
