<?php
// backup.php - Handler para backup do sistema
require_once '../config.php'; // config.php está em /membro/

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: ../index.php');
    exit;
}

$tipo = $_GET['tipo'] ?? 'database';
$pdo = conectarDB();

// Nome do arquivo de backup
$backup_filename = 'backup_' . date('Y-m-d_His') . '.sql';

// Configurações do banco
$host = DB_HOST;
$database = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

if ($tipo === 'database') {
    // Backup apenas do banco de dados
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $backup_filename . '"');
    
    // Comando mysqldump (simplificado - em produção use exec() ou similar)
    echo "-- Backup do banco de dados\n";
    echo "-- Gerado em: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Listar todas as tabelas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "\n-- Estrutura da tabela `$table`\n";
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        echo $create['Create Table'] . ";\n\n";
        
        // Dados da tabela
        echo "-- Dados da tabela `$table`\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, array_values($row));
                
                echo "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
        }
        echo "\n";
    }
    
} elseif ($tipo === 'completo') {
    // Backup completo (banco + arquivos)
    // Em produção, você criaria um arquivo ZIP com banco + arquivos
    
    header('Content-Type: text/plain');
    echo "Backup completo em desenvolvimento.\n";
    echo "Em produção, isso criaria um arquivo ZIP com:\n";
    echo "- Backup do banco de dados\n";
    echo "- Arquivos do sistema\n";
    echo "- Configurações\n";
}
?>