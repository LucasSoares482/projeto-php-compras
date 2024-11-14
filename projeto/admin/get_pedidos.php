<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit('Não autorizado');
}

if (!isset($_GET['cliente_id'])) {
    http_response_code(400);
    exit('ID do cliente não fornecido');
}

$db = Database::getInstance();

try {
    $pedidos = $db->query("
        SELECT 
            c.*,
            COUNT(ci.id) as total_itens
        FROM compras c
        LEFT JOIN compras_itens ci ON c.id = ci.compra_id
        WHERE c.cliente_id = ?
        GROUP BY c.id
        ORDER BY c.data_compra DESC
    ", [$_GET['cliente_id']])->fetchAll();
    
    // Formatar dados para JSON
    foreach ($pedidos as &$pedido) {
        $pedido['data_compra'] = date('d/m/Y H:i', strtotime($pedido['data_compra']));
        $pedido['valor_total'] = number_format($pedido['valor_total'], 2, ',', '.');
    }
    
    header('Content-Type: application/json');
    echo json_encode($pedidos);
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Erro ao buscar pedidos: ' . $e->getMessage());
}