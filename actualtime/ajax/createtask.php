<?php

/**
 * -------------------------------------------------------------------------
 * ActualTime plugin for GLPI
 * Copyright (C) 2018-2025 by the TICGAL Team.
 * https://www.tic.gal/
 * -------------------------------------------------------------------------
 * LICENSE
 * This file is part of the ActualTime plugin.
 * ActualTime plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ActualTime plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along withOneTimeSecret. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @package   ActualTime
 * @author    the TICGAL team
 * @copyright Copyright (c) 2018-2025 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal/
 * @since     2018
 * -------------------------------------------------------------------------
 */

include("../../../inc/includes.php");

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["action"])) {
    switch ($_POST["action"]) {
        case 'create_task_and_start_timer':
            $ticket_id = (int)$_POST["ticket_id"];
            $description = $_POST["description"] ?? "Trabalho no ticket";
            
            // Create a new task
            $task = new TicketTask();
            $task_data = [
                'tickets_id' => $ticket_id,
                'content' => $description,
                'state' => 1, // Planning state
                'users_id' => Session::getLoginUserID()
            ];
            
            $task_id = $task->add($task_data);
            
            if ($task_id) {
                // Start timer for the new task
                $result = PluginActualtimeTask::startTimer($task_id, 'TicketTask', PluginActualtimeTask::WEB);
                $result['task_id'] = $task_id;
                echo json_encode($result);
            } else {
                echo json_encode([
                    'type' => 'error',
                    'message' => 'Erro ao criar tarefa'
                ]);
            }
            break;
            
        case 'finish_task_with_description':
            $task_id = (int)$_POST["task_id"];
            $final_description = $_POST["final_description"] ?? "";
            $duration_seconds = (int)$_POST["duration_seconds"];
            
            // Stop the timer
            $result = PluginActualtimeTask::stopTimer($task_id, 'TicketTask', PluginActualtimeTask::WEB);
            
            // Update task description if provided
            if (!empty($final_description)) {
                $task = new TicketTask();
                $task->update([
                    'id' => $task_id,
                    'content' => $final_description,
                    'state' => 2, // Done
                    'actiontime' => $duration_seconds
                ]);
            }
            
            echo json_encode($result);
            break;
            
        case 'get_current_ticket':
            // Try to detect current ticket from referrer or session
            $ticket_id = null;
            $ticket_title = "";
            
            // Check if there's a ticket ID in the request
            if (isset($_POST['url'])) {
                $url = $_POST['url'];
                if (preg_match('/ticket\.form\.php.*[?&]id=(\d+)/', $url, $matches)) {
                    $ticket_id = (int)$matches[1];
                    
                    // Get ticket title
                    $ticket = new Ticket();
                    if ($ticket->getFromDB($ticket_id)) {
                        $ticket_title = $ticket->fields['name'];
                    }
                }
            }
            
            echo json_encode([
                'ticket_id' => $ticket_id,
                'ticket_title' => $ticket_title
            ]);
            break;
            
        default:
            echo json_encode([
                'type' => 'error', 
                'message' => 'Ação não reconhecida'
            ]);
            break;
    }
} else {
    echo json_encode([
        'type' => 'error',
        'message' => 'Nenhuma ação especificada'
    ]);
}
