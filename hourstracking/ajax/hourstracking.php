<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated("hourstracking")) {
    http_response_code(404);
    exit;
}

// Verificação de segurança
Session::checkLoginUser();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'get_client_rate':
            if (!Session::haveRight('plugin_hourstracking_clientrate', READ)) {
                throw new Exception(__('Access denied', 'hourstracking'));
            }
            
            $client_id = filter_var($_GET['client_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$client_id) {
                throw new Exception(__('Invalid client ID', 'hourstracking'));
            }
            
            $rate = new PluginHourstrackingClientrate();
            $hourly_rate = $rate->getClientRate($client_id);
            
            echo json_encode([
                'success' => true,
                'rate' => $hourly_rate,
                'formatted_rate' => 'R$ ' . number_format($hourly_rate, 2, ',', '.')
            ]);
            break;
            
        case 'save_client_rate':
            if (!Session::haveRight('plugin_hourstracking_clientrate', UPDATE)) {
                throw new Exception(__('Access denied', 'hourstracking'));
            }
            
            // Verifica CSRF token
            if (!Session::validateCSRF($_POST)) {
                throw new Exception(__('CSRF token validation failed', 'hourstracking'));
            }
            
            $client_id = filter_var($_POST['client_id'] ?? 0, FILTER_VALIDATE_INT);
            $hourly_rate = filter_var($_POST['hourly_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            
            if (!$client_id || $hourly_rate === false) {
                throw new Exception(__('Invalid parameters', 'hourstracking'));
            }
            
            $rate = new PluginHourstrackingClientrate();
            $success = $rate->saveClientRate($client_id, $hourly_rate);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 
                    __('Rate saved successfully', 'hourstracking') : 
                    __('Error saving rate', 'hourstracking')
            ]);
            break;
            
        default:
            throw new Exception(__('Invalid action', 'hourstracking'));
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
