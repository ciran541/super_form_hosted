<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Update with your domain in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Validate required fields
        $requiredFields = ['name', 'email', 'contactNumber', 'loanType', 'propertyType'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Get Google Sheet URL from .env
        $scriptUrl = $_ENV['GOOGLE_SHEET_URL'];
        
        if (empty($scriptUrl)) {
            throw new Exception("Google Sheet URL not configured");
        }
        
        // Add timestamp if not present
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
        }

        // Forward to Google Sheets
        $ch = curl_init($scriptUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        curl_close($ch);

        echo json_encode([
            'status' => 'success',
            'message' => 'Form submitted successfully!'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}