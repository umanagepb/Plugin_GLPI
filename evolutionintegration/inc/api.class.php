<?php
/*
 * Evolution Integration plugin for GLPI
 * Copyright (C) 2023 by Umanage Tecnologia de Gestao Ltda
 */

class PluginEvolutionintegrationApi extends CommonDBTM {
    
    private static function getApiConfig() {
        return [
            'endpoint' => PluginEvolutionintegrationConfig::getConfigValue('api_endpoint'),
            'token' => PluginEvolutionintegrationConfig::getConfigValue('api_token')
        ];
    }

    /**
     * Send a message via Evolution API
     */
    public static function sendMessage($phone, $message, $instanceName = 'default') {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/message/sendText/' . $instanceName;
        
        $data = [
            'number' => $phone,
            'text' => $message
        ];

        $response = self::makeApiRequest($url, $data, $config['token']);
        return $response;
    }

    /**
     * Send a media message via Evolution API
     */
    public static function sendMedia($phone, $mediaUrl, $caption = '', $instanceName = 'default') {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/message/sendMedia/' . $instanceName;
        
        $data = [
            'number' => $phone,
            'mediatype' => 'image', // pode ser 'image', 'video', 'audio', 'document'
            'media' => $mediaUrl,
            'caption' => $caption
        ];

        $response = self::makeApiRequest($url, $data, $config['token']);
        return $response;
    }

    /**
     * Get instance information
     */
    public static function getInstanceInfo($instanceName = 'default') {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/instance/fetchInstances';
        
        $response = self::makeApiRequest($url, [], $config['token'], 'GET');
        return $response;
    }

    /**
     * Create a new instance
     */
    public static function createInstance($instanceName, $qrcode = true) {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/instance/create';
        
        $data = [
            'instanceName' => $instanceName,
            'qrcode' => $qrcode,
            'integration' => 'WHATSAPP-BAILEYS'
        ];

        $response = self::makeApiRequest($url, $data, $config['token']);
        return $response;
    }

    /**
     * Get QR Code for instance connection
     */
    public static function getQRCode($instanceName) {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/instance/connect/' . $instanceName;
        
        $response = self::makeApiRequest($url, [], $config['token'], 'GET');
        return $response;
    }

    /**
     * Set webhook for instance
     */
    public static function setWebhook($instanceName, $webhookUrl) {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/webhook/set/' . $instanceName;
        
        $data = [
            'url' => $webhookUrl,
            'enabled' => true,
            'events' => [
                'APPLICATION_STARTUP',
                'QRCODE_UPDATED',
                'MESSAGES_UPSERT',
                'MESSAGES_UPDATE',
                'SEND_MESSAGE',
                'CONNECTION_UPDATE'
            ]
        ];

        $response = self::makeApiRequest($url, $data, $config['token']);
        return $response;
    }

    /**
     * Process incoming webhook
     */
    public static function processWebhook($data) {
        if (!isset($data['event']) || !isset($data['data'])) {
            return ['success' => false, 'error' => 'Invalid webhook data'];
        }

        switch ($data['event']) {
            case 'MESSAGES_UPSERT':
                return self::processIncomingMessage($data['data']);
            
            case 'CONNECTION_UPDATE':
                return self::processConnectionUpdate($data['data']);
                
            default:
                return ['success' => true, 'message' => 'Event processed'];
        }
    }

    /**
     * Process incoming message
     */
    private static function processIncomingMessage($messageData) {
        // Implementar lógica para processar mensagem recebida
        // Pode criar um ticket, adicionar comentário, etc.
        
        if (isset($messageData['message']) && !$messageData['message']['fromMe']) {
            $phone = $messageData['message']['key']['remoteJid'];
            $message = $messageData['message']['message']['conversation'] ?? 
                      $messageData['message']['message']['extendedTextMessage']['text'] ?? 
                      'Mensagem de mídia recebida';
            
            // Aqui você pode implementar a lógica para:
            // 1. Encontrar ou criar um ticket baseado no número de telefone
            // 2. Adicionar a mensagem como comentário no ticket
            // 3. Notificar o técnico responsável
            
            return ['success' => true, 'message' => 'Message processed'];
        }

        return ['success' => true, 'message' => 'No action needed'];
    }

    /**
     * Process connection update
     */
    private static function processConnectionUpdate($connectionData) {
        // Implementar lógica para atualizar status da conexão
        return ['success' => true, 'message' => 'Connection status updated'];
    }

    /**
     * Make API request to Evolution API
     */
    private static function makeApiRequest($url, $data = [], $token = '', $method = 'POST') {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'apikey: ' . $token; // Evolution API também usa apikey
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => 'cURL Error: ' . $error];
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decodedResponse];
        } else {
            return [
                'success' => false, 
                'error' => 'HTTP ' . $httpCode . ': ' . ($decodedResponse['message'] ?? $response)
            ];
        }
    }

    /**
     * Test API connection
     */
    public static function testConnection() {
        $config = self::getApiConfig();
        
        if (empty($config['endpoint']) || empty($config['token'])) {
            return ['success' => false, 'error' => 'API configuration is incomplete'];
        }

        $url = rtrim($config['endpoint'], '/') . '/instance/fetchInstances';
        $response = self::makeApiRequest($url, [], $config['token'], 'GET');
        
        if ($response['success']) {
            return ['success' => true, 'message' => 'Connection successful'];
        } else {
            return $response;
        }
    }
}
?>
