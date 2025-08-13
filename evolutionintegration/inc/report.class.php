<?php
class PluginEvolutionintegrationReport extends CommonDBTM {
    
    public static function generateConversationReport($params = []) {
        global $DB;

        $default_params = [
            'start_date' => date('Y-m-d', strtotime('-30 days')),
            'end_date' => date('Y-m-d'),
            'ticket_id' => null
        ];
        $params = array_merge($default_params, $params);

        $query = [
            'SELECT' => [
                'c.ticket_id',
                'c.start_time',
                'c.end_time',
                'c.total_duration',
                't.name AS ticket_name'
            ],
            'FROM' => 'glpi_plugin_evolutionintegration_conversations c',
            'INNER JOIN' => [
                'glpi_tickets t' => [
                    'FKEY' => [
                        'c' => 'ticket_id',
                        't' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'c.status' => PluginEvolutionintegrationConversation::STATUS_CLOSED,
                'c.end_time' => ['>=', strtotime($params['start_date'])],
                'c.start_time' => ['<=', strtotime($params['end_date'])]
            ]
        ];

        if ($params['ticket_id']) {
            $query['WHERE']['c.ticket_id'] = $params['ticket_id'];
        }

        $results = $DB->request($query);
        
        $report = [];
        foreach ($results as $row) {
            $report[] = [
                'ticket_id' => $row['ticket_id'],
                'ticket_name' => $row['ticket_name'],
                'start_time' => date('Y-m-d H:i:s', $row['start_time']),
                'end_time' => date('Y-m-d H:i:s', $row['end_time']),
                'duration_minutes' => round($row['total_duration'] / 60, 2)
            ];
        }

        return $report;
    }

    public static function displayReport($report) {
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th>".__('Ticket')."</th><th>".__('Start Time')."</th><th>".__('End Time')."</th><th>".__('Duration (minutes)')."</th></tr>";
        
        foreach ($report as $entry) {
            echo "<tr>";
            echo "<td>{$entry['ticket_id']} - {$entry['ticket_name']}</td>";
            echo "<td>{$entry['start_time']}</td>";
            echo "<td>{$entry['end_time']}</td>";
            echo "<td>{$entry['duration_minutes']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
