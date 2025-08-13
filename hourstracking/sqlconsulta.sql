-- Consulta SQL otimizada para o plugin Hours Tracking
-- Esta consulta retorna todos os dados necessários para os relatórios de controle de horas
-- Corrigida para GLPI 10.0+ com cálculos de horas corretos

SELECT 
    tt.tickets_id AS nro_ticket,
    t.name AS assunto,
    t.date AS data_ticket,
    e.name AS cliente,
    ROUND(tt.actiontime / 3600, 2) AS total_horas,  -- Convertido corretamente para horas
    tec.name AS atendente,
    REPLACE(REPLACE(REPLACE(t.content, '<p>', ''), '<br />', ''), '</p>', '') AS desc_solicitacao,
    tt.date AS data_tarefa,
    sol.name AS solicitante,
    REPLACE(REPLACE(REPLACE(tt.content, '<p>', ''), '<br />', ''), '</p>', '') AS desc_tarefa,
    COALESCE(cr.hourly_rate, 0) AS hourly_rate,
    ROUND((tt.actiontime / 3600) * COALESCE(cr.hourly_rate, 0), 2) AS valor_total,
    e.id AS client_id
FROM glpi_tickettasks tt
LEFT JOIN glpi_tickets t ON t.id = tt.tickets_id
LEFT JOIN glpi_entities e ON e.id = t.entities_id
LEFT JOIN glpi_users tec ON tt.users_id = tec.id
LEFT JOIN glpi_users sol ON t.users_id_recipient = sol.id
LEFT JOIN glpi_plugin_hourstracking_clientrates cr ON cr.client_id = e.id
WHERE tt.actiontime > 0  -- Apenas tarefas com tempo registrado
ORDER BY tt.date DESC;