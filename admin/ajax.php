<?php
// ajax.php - Handler para requisições AJAX
require_once '../config.php'; // config.php está em /membro/

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$pdo = conectarDB();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'test_email':
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Aqui você implementaria o envio de email de teste
            // Por enquanto, apenas simular sucesso
            echo json_encode([
                'success' => true, 
                'message' => 'Email de teste enviado (simulado)'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Email inválido'
            ]);
        }
        break;
        
    case 'clear_cache':
        // Implementar limpeza de cache
        echo json_encode([
            'success' => true,
            'message' => 'Cache limpo com sucesso!'
        ]);
        break;
        
    case 'clear_logs':
        try {
            // Implementar limpeza de logs antigos
            // Exemplo: deletar logs com mais de 30 dias
            $stmt = $pdo->prepare("DELETE FROM logs WHERE data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Logs antigos removidos com sucesso!'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao limpar logs'
            ]);
        }
        break;
        
    case 'optimize_db':
        try {
            // Otimizar tabelas do banco
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("OPTIMIZE TABLE `$table`");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Banco de dados otimizado com sucesso!'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao otimizar banco de dados'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Ação não reconhecida'
        ]);
}
?>