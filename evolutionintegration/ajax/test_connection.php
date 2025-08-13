<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Umanage Tecnologia de Gestao Ltda
 */

include('../../../inc/includes.php');

header('Content-Type: application/json');

// Verificar permissões
if (!Session::haveRight('config', READ)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    $result = PluginEvolutionintegrationApi::testConnection();
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
