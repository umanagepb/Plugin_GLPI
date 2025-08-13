<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Umanage Tecnologia de Gestao Ltda
 */

function plugin_evolutionintegration_ticket_add($ticket) {
    // Criar conversa quando ticket é criado
    $conversation = new PluginEvolutionintegrationConversation();
    $conversation->createConversation($ticket->fields['id']);
    
    // Enviar notificação se habilitado
    if (PluginEvolutionintegrationConfig::getConfigValue('enable_notifications')) {
        plugin_evolutionintegration_send_notification($ticket->fields['id'], 'created');
    }
}

function plugin_evolutionintegration_ticket_update($ticket) {
    // Se ticket foi fechado, fechar conversa ativa
    if ($ticket->fields['status'] == Ticket::CLOSED) {
        $conversation = new PluginEvolutionintegrationConversation();
        $conversation->closeActiveConversation($ticket->fields['id']);
        
        // Enviar notificação de fechamento se habilitado
        if (PluginEvolutionintegrationConfig::getConfigValue('enable_notifications')) {
            plugin_evolutionintegration_send_notification($ticket->fields['id'], 'closed');
        }
    }
}

function plugin_evolutionintegration_cron($task) {
    PluginEvolutionintegrationConversation::cleanOldConversations();
    return true;
}

function plugin_evolutionintegration_send_notification($ticket_id, $action = 'created') {
    $ticket = new Ticket();
    if (!$ticket->getFromDB($ticket_id)) {
        return false;
    }
    
    // Verificar se há número de telefone associado ao usuário requerente
    $user = new User();
    if ($user->getFromDB($ticket->fields['users_id_recipient'])) {
        $phone = $user->fields['phone'] ?? $user->fields['mobile'] ?? '';
        
        if (!empty($phone)) {
            $template = PluginEvolutionintegrationConfig::getConfigValue('notification_template');
            $instance = PluginEvolutionintegrationConfig::getConfigValue('default_instance');
            
            // Substituir variáveis no template
            $message = str_replace([
                '{ticket_title}',
                '{ticket_id}',
                '{ticket_status}',
                '{action}'
            ], [
                $ticket->fields['name'],
                $ticket->fields['id'],
                Ticket::getStatus($ticket->fields['status']),
                $action
            ], $template);
            
            // Enviar mensagem via Evolution API
            PluginEvolutionintegrationApi::sendMessage($phone, $message, $instance);
        }
    }
    
    return true;
}
?>
