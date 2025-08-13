<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight('plugin_hourstracking_report', READ);

Html::header(__('Relatórios por Atendente', 'hourstracking'), $_SERVER['PHP_SELF'], "tools", "pluginhourstracking");

global $DB;

// Lista de atendentes (usuários ativos)
$attendants = [];
try {
    $criteria = [
        'FROM' => 'glpi_users',
        'SELECT' => ['id', 'name'],
        'WHERE' => ['is_active' => 1],
        'ORDER' => 'name'
    ];
    
    $iterator = $DB->request($criteria);
    foreach ($iterator as $row) {
        $attendants[] = $row;
    }
} catch (Exception $e) {
    $attendants = [];
}

// Formulário de filtros
echo "<form method='post' name='attendant_report_form'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Relatório por Atendente', 'hourstracking') . "</th></tr>";

// Filtro de Data Início
echo "<tr><td>" . __('Data Início', 'hourstracking') . "</td>";
echo "<td><input type='date' name='start_date' value='" . Html::cleanInputText($_POST['start_date'] ?? '') . "' required></td></tr>";

// Filtro de Data Fim
echo "<tr><td>" . __('Data Fim', 'hourstracking') . "</td>";
echo "<td><input type='date' name='end_date' value='" . Html::cleanInputText($_POST['end_date'] ?? '') . "' required></td></tr>";

// Tipo de relatório
echo "<tr><td>" . __('Tipo de Relatório', 'hourstracking') . "</td><td>";
echo "<input type='radio' name='report_type' value='summary' " . (($_POST['report_type'] ?? 'summary') == 'summary' ? 'checked' : '') . "> " . __('Resumo', 'hourstracking') . "<br>";
echo "<input type='radio' name='report_type' value='detailed' " . (($_POST['report_type'] ?? '') == 'detailed' ? 'checked' : '') . "> " . __('Detalhado', 'hourstracking');
echo "</td></tr>";

echo "<tr><td colspan='2' style='text-align:center;'>";
echo "<input type='submit' name='generate_report' value='" . __('Gerar Relatório', 'hourstracking') . "' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();

// Processamento do relatório
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    // Verifica CSRF token
    if (!Session::validateCSRF($_POST)) {
        Session::addMessageAfterRedirect(
            __('Ação não autorizada', 'hourstracking'),
            false,
            ERROR
        );
        Html::back();
    }

    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $params = [
            'start_date' => Html::cleanInputText($_POST['start_date']),
            'end_date' => Html::cleanInputText($_POST['end_date'])
        ];

        $detailed = ($_POST['report_type'] ?? 'summary') == 'detailed';

        $reportObj = new PluginHourstrackingReport();
        $results = $reportObj->generateByAttendant($params, $detailed);

        if (isset($results['error'])) {
            echo "<div class='center b'>" . $results['error'] . "</div>";
        } else {
            echo "<br/>";
            echo "<table class='tab_cadre_fixe'>";
            
            if ($detailed) {
                echo "<tr>
                        <th>" . __('Atendente', 'hourstracking') . "</th>
                        <th>" . __('Cliente', 'hourstracking') . "</th>
                        <th>" . __('Data', 'hourstracking') . "</th>
                        <th>" . __('Ticket', 'hourstracking') . "</th>
                        <th>" . __('Assunto', 'hourstracking') . "</th>
                        <th>" . __('Horas', 'hourstracking') . "</th>
                        <th>" . __('Valor', 'hourstracking') . "</th>
                      </tr>";
            } else {
                echo "<tr>
                        <th>" . __('Atendente', 'hourstracking') . "</th>
                        <th>" . __('Total Horas', 'hourstracking') . "</th>
                        <th>" . __('Valor Total', 'hourstracking') . "</th>
                      </tr>";
            }

            $total_horas = 0;
            $total_valor = 0;

            foreach ($results as $row) {
                echo "<tr>";
                echo "<td>" . Html::cleanInputText($row['atendente'] ?? '') . "</td>";
                
                if ($detailed) {
                    echo "<td>" . Html::cleanInputText($row['cliente'] ?? '') . "</td>";
                    echo "<td>" . Html::convDateTime($row['data_tarefa'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['nro_ticket'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['assunto'] ?? '') . "</td>";
                }
                
                echo "<td>" . number_format($row['total_horas'] ?? 0, 2) . "</td>";
                echo "<td>R$ " . number_format($row['valor_total'] ?? 0, 2) . "</td>";
                echo "</tr>";

                $total_horas += $row['total_horas'] ?? 0;
                $total_valor += $row['valor_total'] ?? 0;
            }

            // Linha de totais
            $colspan = $detailed ? 5 : 1;
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='$colspan'><strong>" . __('TOTAL', 'hourstracking') . "</strong></td>";
            echo "<td><strong>" . number_format($total_horas, 2) . "</strong></td>";
            echo "<td><strong>R$ " . number_format($total_valor, 2) . "</strong></td>";
            echo "</tr>";

            echo "</table>";
        }
    } else {
        echo "<div class='center b'>" . __('Informe as datas de início e fim.', 'hourstracking') . "</div>";
    }
}

Html::footer();
