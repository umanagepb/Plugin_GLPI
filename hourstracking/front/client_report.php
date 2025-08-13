<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight('plugin_hourstracking_report', READ);

Html::header(__('Relatórios por Cliente', 'hourstracking'), $_SERVER['PHP_SELF'], "tools", "pluginhourstracking");

global $DB;

// Lista de clientes (entidades com restrição de acesso)
$clients = [];
try {
    $criteria = [
        'FROM' => 'glpi_entities',
        'SELECT' => ['id', 'name'],
        'WHERE' => getEntitiesRestrictCriteria('glpi_entities'),
        'ORDER' => 'name'
    ];
    
    $iterator = $DB->request($criteria);
    foreach ($iterator as $row) {
        $clients[] = $row;
    }
} catch (Exception $e) {
    $clients = [];
}

// Formulário de filtros
echo "<form method='post' name='client_report_form'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Relatório Detalhado para Cliente', 'hourstracking') . "</th></tr>";

// Filtro de Data Início
echo "<tr><td>" . __('Data Início', 'hourstracking') . "</td>";
echo "<td><input type='date' name='start_date' value='" . Html::cleanInputText($_POST['start_date'] ?? '') . "' required></td></tr>";

// Filtro de Data Fim
echo "<tr><td>" . __('Data Fim', 'hourstracking') . "</td>";
echo "<td><input type='date' name='end_date' value='" . Html::cleanInputText($_POST['end_date'] ?? '') . "' required></td></tr>";

// Filtro de Cliente
echo "<tr><td>" . __('Cliente', 'hourstracking') . "</td><td>";
echo "<select name='client_id' required>";
echo "<option value=''>" . __('Selecione um cliente', 'hourstracking') . "</option>";
foreach ($clients as $client) {
    $selected = (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? "selected" : "";
    echo "<option value='" . $client['id'] . "' $selected>" . Html::cleanInputText($client['name']) . "</option>";
}
echo "</select></td></tr>";

echo "<tr><td colspan='2' style='text-align:center;'>";
echo "<input type='submit' name='generate_report' value='" . __('Gerar Relatório', 'hourstracking') . "' class='submit'> ";
echo "<input type='submit' name='export_csv' value='" . __('Exportar CSV', 'hourstracking') . "' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();

// Processamento do relatório
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['generate_report']) || isset($_POST['export_csv']))) {
    // Verifica CSRF token
    if (!Session::validateCSRF($_POST)) {
        Session::addMessageAfterRedirect(
            __('Ação não autorizada', 'hourstracking'),
            false,
            ERROR
        );
        Html::back();
    }

    if (!empty($_POST['start_date']) && !empty($_POST['end_date']) && !empty($_POST['client_id'])) {
        $params = [
            'start_date' => Html::cleanInputText($_POST['start_date']),
            'end_date' => Html::cleanInputText($_POST['end_date']),
            'client_id' => intval($_POST['client_id'])
        ];

        $reportObj = new PluginHourstrackingReport();
        $results = $reportObj->generateDetailedClientReport($params);

        if (isset($results['error'])) {
            echo "<div class='center b'>" . $results['error'] . "</div>";
        } else {
            // Exportação CSV
            if (isset($_POST['export_csv'])) {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="relatorio_cliente_' . date('Y-m-d') . '.csv"');

                $output = fopen('php://output', 'w');
                // BOM para UTF-8
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                fputcsv($output, [
                    'Tempo (HH:MM)', 'Valor do Atendimento', 'Data da Tarefa', 
                    'Solicitante', 'Agente', 'Assunto', 'Descrição da Tarefa'
                ], ';');
                
                foreach ($results as $row) {
                    fputcsv($output, [
                        $row['tempo_hhmm'] ?? '',
                        'R$ ' . number_format($row['valor_atendimento'] ?? 0, 2, ',', '.'),
                        $row['data_tarefa'] ?? '',
                        $row['solicitante'] ?? '',
                        $row['agente'] ?? '',
                        $row['assunto'] ?? '',
                        strip_tags($row['desc_tarefa'] ?? '')
                    ], ';');
                }
                fclose($output);
                exit();
            } else {
                // Exibição na tela
                echo "<br/>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr>
                        <th>" . __('Tempo', 'hourstracking') . "</th>
                        <th>" . __('Valor', 'hourstracking') . "</th>
                        <th>" . __('Data', 'hourstracking') . "</th>
                        <th>" . __('Solicitante', 'hourstracking') . "</th>
                        <th>" . __('Agente', 'hourstracking') . "</th>
                        <th>" . __('Assunto', 'hourstracking') . "</th>
                        <th>" . __('Descrição', 'hourstracking') . "</th>
                      </tr>";

                $total_valor = 0;

                foreach ($results as $row) {
                    echo "<tr>";
                    echo "<td>" . Html::cleanInputText($row['tempo_hhmm'] ?? '') . "</td>";
                    echo "<td>R$ " . number_format($row['valor_atendimento'] ?? 0, 2) . "</td>";
                    echo "<td>" . Html::convDateTime($row['data_tarefa'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['solicitante'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['agente'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['assunto'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText(substr(strip_tags($row['desc_tarefa'] ?? ''), 0, 100)) . "...</td>";
                    echo "</tr>";

                    $total_valor += $row['valor_atendimento'] ?? 0;
                }

                // Linha de totais
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='6'><strong>" . __('TOTAL', 'hourstracking') . "</strong></td>";
                echo "<td><strong>R$ " . number_format($total_valor, 2) . "</strong></td>";
                echo "</tr>";

                echo "</table>";
            }
        }
    } else {
        echo "<div class='center b'>" . __('Informe as datas e selecione um cliente.', 'hourstracking') . "</div>";
    }
}

Html::footer();
