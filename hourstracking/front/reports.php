<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
   Html::displayNotFoundError();
}

Session::checkRight('plugin_hourstracking_report', READ);

Html::header(__('Relatórios de Controle de Horas', 'hourstracking'), $_SERVER['PHP_SELF'], "tools", "pluginhourstracking");

global $DB;

// ========================================================
// Obtém listas para os filtros adicionais (atendente e cliente)
// ========================================================

try {
    // Lista de atendentes (usuários ativos)
    $attendants = [];
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

    // Lista de clientes (utilizando a tabela de entidades com restrição de acesso)
    $clients = [];
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
    Session::addMessageAfterRedirect(
        __('Erro ao carregar dados: ', 'hourstracking') . $e->getMessage(),
        false,
        ERROR
    );
    $attendants = [];
    $clients = [];
}

// ========================================================
// Formulário de filtros e botões de exportação
// ========================================================
echo "<form method='post' name='report_form'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Filtrar Relatório', 'hourstracking') . "</th></tr>";

// Filtro de Data Início
echo "<tr><td>" . __('Data Início', 'hourstracking') . "</td>";
echo "<td><input type='date' name='start_date' value='" . Html::cleanInputText($_POST['start_date'] ?? '') . "' required></td></tr>";

// Filtro de Data Fim
echo "<tr><td>" . __('Data Fim', 'hourstracking') . "</td>";
echo "<td><input type='date' name='end_date' value='" . Html::cleanInputText($_POST['end_date'] ?? '') . "' required></td></tr>";

// Filtro de Atendente
echo "<tr><td>" . __('Atendente', 'hourstracking') . "</td><td>";
echo "<select name='attendant_id'>";
echo "<option value=''>" . __('Todos', 'hourstracking') . "</option>";
foreach ($attendants as $attendant) {
    $selected = (isset($_POST['attendant_id']) && $_POST['attendant_id'] == $attendant['id']) ? "selected" : "";
    echo "<option value='" . $attendant['id'] . "' $selected>" . Html::cleanInputText($attendant['name']) . "</option>";
}
echo "</select></td></tr>";

// Filtro de Cliente
echo "<tr><td>" . __('Cliente', 'hourstracking') . "</td><td>";
echo "<select name='client_id'>";
echo "<option value=''>" . __('Todos', 'hourstracking') . "</option>";
foreach ($clients as $client) {
    $selected = (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? "selected" : "";
    echo "<option value='" . $client['id'] . "' $selected>" . Html::cleanInputText($client['name']) . "</option>";
}
echo "</select></td></tr>";

echo "<tr><td colspan='2' style='text-align:center;'>";
echo "<input type='submit' name='generate_report' value='" . __('Gerar Relatório', 'hourstracking') . "' class='submit'> ";
echo "<input type='submit' name='export_csv' value='" . __('Exportar CSV', 'hourstracking') . "' class='submit'> ";
echo "<input type='submit' name='export_pdf' value='" . __('Exportar PDF', 'hourstracking') . "' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();

// ========================================================
// Processa a geração do relatório
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['generate_report']) || isset($_POST['export_csv']) || isset($_POST['export_pdf']))) {

    // Verifica CSRF token
    if (!Session::validateCSRF($_POST)) {
        Session::addMessageAfterRedirect(
            __('Ação não autorizada', 'hourstracking'),
            false,
            ERROR
        );
        Html::back();
    }

    // Validação rápida
    if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
        echo "<div class='center b'>" . __('Informe as datas de início e fim.', 'hourstracking') . "</div>";
    } else {
        $params = [
            'start_date'   => Html::cleanInputText($_POST['start_date']),
            'end_date'     => Html::cleanInputText($_POST['end_date']),
            'attendant_id' => !empty($_POST['attendant_id']) ? intval($_POST['attendant_id']) : '',
            'client_id'    => !empty($_POST['client_id']) ? intval($_POST['client_id']) : ''
        ];

        $reportObj = new PluginHourstrackingReport();
        $results = $reportObj->generateReport($params);

        // Verifica se houve erro no relatório
        if (isset($results['error'])) {
            echo "<div class='center b'>" . $results['error'] . "</div>";
        } else {
            // ========================================================
            // Exportação CSV
            // ========================================================
            if (isset($_POST['export_csv'])) {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="relatorio_horas_' . date('Y-m-d') . '.csv"');

                $output = fopen('php://output', 'w');
                // BOM para UTF-8
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                fputcsv($output, [
                    'Atendente', 'Cliente', 'Ticket', 'Assunto', 'Data', 
                    'Total Horas', 'Taxa Horária', 'Valor Total'
                ], ';');
                
                foreach ($results as $row) {
                    fputcsv($output, [
                        $row['attendant'] ?? '',
                        $row['cliente'] ?? '',
                        $row['nro_ticket'] ?? '',
                        $row['assunto'] ?? '',
                        $row['data_tarefa'] ?? '',
                        number_format($row['total_horas'] ?? 0, 2, ',', '.'),
                        'R$ ' . number_format($row['hourly_rate'] ?? 0, 2, ',', '.'),
                        'R$ ' . number_format($row['total_value'] ?? 0, 2, ',', '.')
                    ], ';');
                }
                fclose($output);
                exit();

            // ========================================================
            // Exportação PDF
            // ========================================================
            } elseif (isset($_POST['export_pdf'])) {
                // Usar biblioteca PDF do GLPI ou TCPDF se disponível
                require_once(GLPI_ROOT . '/vendor/tecnickcom/tcpdf/tcpdf.php');

                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                
                $pdf->SetCreator('GLPI Hours Tracking Plugin');
                $pdf->SetAuthor('GLPI');
                $pdf->SetTitle('Relatório de Controle de Horas');
                $pdf->SetSubject('Relatório de Horas');
                
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                
                $pdf->AddPage();
                
                $html = '<h1>Relatório de Controle de Horas</h1>';
                $html .= '<p>Período: ' . $params['start_date'] . ' a ' . $params['end_date'] . '</p>';
                $html .= '<table border="1" cellpadding="4">';
                $html .= '<tr><th>Atendente</th><th>Cliente</th><th>Ticket</th><th>Horas</th><th>Valor</th></tr>';
                
                foreach ($results as $row) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($row['attendant'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($row['cliente'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($row['nro_ticket'] ?? '') . '</td>';
                    $html .= '<td>' . number_format($row['total_horas'] ?? 0, 2) . '</td>';
                    $html .= '<td>R$ ' . number_format($row['total_value'] ?? 0, 2) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</table>';
                
                $pdf->writeHTML($html, true, false, true, false, '');
                $pdf->Output('relatorio_horas_' . date('Y-m-d') . '.pdf', 'D');
                exit();

            // ========================================================
            // Exibição na tela (HTML)
            // ========================================================
            } else {
                echo "<br/>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr>
                        <th>" . __('Atendente', 'hourstracking') . "</th>
                        <th>" . __('Cliente', 'hourstracking') . "</th>
                        <th>" . __('Ticket', 'hourstracking') . "</th>
                        <th>" . __('Assunto', 'hourstracking') . "</th>
                        <th>" . __('Data', 'hourstracking') . "</th>
                        <th>" . __('Total Horas', 'hourstracking') . "</th>
                        <th>" . __('Taxa Horária', 'hourstracking') . "</th>
                        <th>" . __('Valor Total', 'hourstracking') . "</th>
                      </tr>";

                $total_horas = 0;
                $total_valor = 0;

                foreach ($results as $row) {
                    echo "<tr>";
                    echo "<td>" . Html::cleanInputText($row['attendant'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['cliente'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['nro_ticket'] ?? '') . "</td>";
                    echo "<td>" . Html::cleanInputText($row['assunto'] ?? '') . "</td>";
                    echo "<td>" . Html::convDateTime($row['data_tarefa'] ?? '') . "</td>";
                    echo "<td>" . number_format($row['total_horas'] ?? 0, 2) . "</td>";
                    echo "<td>R$ " . number_format($row['hourly_rate'] ?? 0, 2) . "</td>";
                    echo "<td>R$ " . number_format($row['total_value'] ?? 0, 2) . "</td>";
                    echo "</tr>";

                    $total_horas += $row['total_horas'] ?? 0;
                    $total_valor += $row['total_value'] ?? 0;
                }

                // Linha de totais
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='5'><strong>" . __('TOTAL', 'hourstracking') . "</strong></td>";
                echo "<td><strong>" . number_format($total_horas, 2) . "</strong></td>";
                echo "<td></td>";
                echo "<td><strong>R$ " . number_format($total_valor, 2) . "</strong></td>";
                echo "</tr>";

                echo "</table>";
            }
        }
    }
}

Html::footer();
