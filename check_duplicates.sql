SELECT name, COUNT(*) as count 
FROM glpi_plugin_hourstracking_configs 
GROUP BY name 
HAVING count > 1;
