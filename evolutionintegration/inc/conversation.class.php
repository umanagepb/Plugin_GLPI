<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Your Company
 */

class PluginEvolutionintegrationConversation extends CommonDBTM {
    const STATUS_OPEN = 1;
    const STATUS_CLOSED = 2;

    public function createConversation($ticket_id) {
        global $DB;

        $conversation = [
            'ticket_id' => $ticket_id,
            'start_time' => date('Y-m-d H:i:s'),
            'last_activity_time' => date('Y-m-d H:i:s'),
            'status' => self::STATUS_OPEN,
            'total_duration' => 0
        ];

        return $DB->insert('glpi_plugin_evolutionintegration_conversations', $conversation);
    }

    public function recordActivity($ticket_id, $message) {
        global $DB;

        $conversation = $this->getActiveConversation($ticket_id);
        $now = date('Y-m-d H:i:s');

        if (!$conversation) {
            $this->createConversation($ticket_id);
            $conversation = $this->getActiveConversation($ticket_id);
        }

        $inactivity_timeout = PluginEvolutionintegrationConfig::getConfigValue('inactivity_timeout');
        
        // Calcular duração adicional baseada na última atividade
        $last_activity = strtotime($conversation['last_activity_time']);
        $current_time = strtotime($now);
        $time_diff = $current_time - $last_activity;
        
        // Se passou do tempo limite de inatividade, não adicionar tempo
        $additional_duration = ($time_diff > $inactivity_timeout) ? 0 : $time_diff;
        
        $DB->update(
            'glpi_plugin_evolutionintegration_conversations',
            [
                'last_activity_time' => $now,
                'total_duration' => $conversation['total_duration'] + $additional_duration
            ],
            ['id' => $conversation['id']]
        );

        // Registrar histórico de mensagem
        $DB->insert('glpi_plugin_evolutionintegration_conversation_history', [
            'conversation_id' => $conversation['id'],
            'message' => $message,
            'timestamp' => $now
        ]);
    }

    public function closeActiveConversation($ticket_id) {
        global $DB;

        $conversation = $this->getActiveConversation($ticket_id);
        
        if ($conversation) {
            $now = date('Y-m-d H:i:s');
            $duration = $conversation['total_duration'] + 
                (strtotime($now) - strtotime($conversation['last_activity_time']));
            
            // Atualiza a conversa
            $DB->update(
                'glpi_plugin_evolutionintegration_conversations',
                [
                    'end_time' => $now,
                    'status' => self::STATUS_CLOSED,
                    'total_duration' => $duration
                ],
                ['id' => $conversation['id']]
            );

            // Cria uma nova tarefa no ticket com o tempo da conversa
            $ticketTask = new TicketTask();
            $taskData = [
                'tickets_id' => $ticket_id,
                'content'    => __('Tempo de conversa registrado automaticamente', 'evolutionintegration'),
                'state'      => Planning::DONE,
                'actiontime' => $duration, // Tempo em segundos
                'users_id'   => Session::getLoginUserID(),
                'date'       => $now
            ];
            $ticketTask->add($taskData);
        }
    }

    public function getActiveConversation($ticket_id) {
        global $DB;

        $result = $DB->request([
            'FROM' => 'glpi_plugin_evolutionintegration_conversations',
            'WHERE' => [
                'ticket_id' => $ticket_id,
                'status' => self::STATUS_OPEN
            ]
        ])->next();

        return $result ?: false;
    }

    public static function cleanOldConversations() {
        global $DB;

        $retention_days = PluginEvolutionintegrationConfig::getConfigValue('conversation_retention_days');
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        // Primeiro, obter IDs das conversas antigas para limpar o histórico
        $old_conversations = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_plugin_evolutionintegration_conversations',
            'WHERE' => [
                'end_time' => ['<', $cutoff_date],
                'status' => self::STATUS_CLOSED
            ]
        ]);

        $conversation_ids = [];
        foreach ($old_conversations as $conv) {
            $conversation_ids[] = $conv['id'];
        }

        // Remover histórico de conversas antigas
        if (!empty($conversation_ids)) {
            $DB->delete(
                'glpi_plugin_evolutionintegration_conversation_history', 
                ['conversation_id' => $conversation_ids]
            );
        }

        // Remover conversas antigas
        $DB->delete(
            'glpi_plugin_evolutionintegration_conversations', 
            [
                'end_time' => ['<', $cutoff_date],
                'status' => self::STATUS_CLOSED
            ]
        );

        return count($conversation_ids);
    }
}
