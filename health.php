<?php
// Health check endpoint for Railway
header('Content-Type: application/json');

// Check database connection
try {
    require 'includes/db.php';
    echo json_encode([
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'unhealthy',
        'database' => 'disconnected',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>