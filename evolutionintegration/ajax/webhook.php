<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Umanage Tecnologia de Gestao Ltda
 */

include('../../../inc/includes.php');

header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Ler dados do webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

try {
    // Log do webhook recebido (opcional, para debug)
    if (defined('GLPI_LOG_DIR')) {
        file_put_contents(
            GLPI_LOG_DIR . '/evolution_webhook.log', 
            date('Y-m-d H:i:s') . ' - ' . $input . "\n", 
            FILE_APPEND | LOCK_EX
        );
    }

    // Processar webhook
    $result = PluginEvolutionintegrationApi::processWebhook($data);
    echo json_encode($result);
} catch (Exception $e) {
    error_log('Evolution Integration Webhook Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
