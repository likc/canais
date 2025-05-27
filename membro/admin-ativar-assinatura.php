<?php
// ARQUIVO ADMINISTRATIVO - PROTEJA COM SENHA OU REMOVA APÓS USO!
require_once 'config.php';
require_once 'email-templates.php';
require_once 'funcoes-email.php';

// PROTEÇÃO BÁSICA - MUDE A SENHA!
$senha_admin = 'SuaSenhaSegura123!';

$mensagem = '';
$erro = '';

// Verificar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['senha_admin'] !== $senha_admin) {
        $erro = 'Senha administrativa incorreta!';
    } else {
        // Processar ativação
        if (isset($_POST['ativar'])) {
            $assinatura_id = intval($_POST['assinatura_id']);
            $usuario_iptv = $_POST['usuario_iptv'] ?? '';
            $senha_iptv = $_POST['senha_iptv'] ?? '';
            
            if ($assinatura_id && $usuario_iptv && $senha_iptv) {
                $resultado = ativarAssinatura($assinatura_id, $usuario_iptv, $senha_iptv);
                if ($resultado['sucesso']) {
                    $mensagem = $resultado['mensagem'];
                } else {
                    $erro = $resultado['mensagem'];
                }
            } else {
                $erro = 'Preencha todos os campos!';
            }
        }
    }
}

// Função para ativar assinatura
function ativarAssinatura($assinatura_id, $usuario_iptv, $senha_iptv) {
    $pdo = conectarDB();
    
    try {
        // Buscar dados da assinatura
        $stmt = $pdo->prepare("
            SELECT a.*, u.email, u.nome_usuario 
            FROM assinaturas a 
            JOIN usuarios u ON a.usuario_id = u.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$assinatura_id]);
        $assinatura = $stmt->fetch();
        
        if (!$assinatura) {
            return ['sucesso' => false, 'mensagem' => 'Assinatura não encontrada!'];
        }
        
        // Atualizar status da assinatura
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = 'ativa' 
            WHERE id = ?
        ");
        $stmt->execute([$assinatura_id]);
        
        // Atualizar pagamento relacionado
        $stmt = $pdo->prepare("
            UPDATE pagamentos 
            SET status = 'aprovado' 
            WHERE assinatura_id = ?
        ");
        $stmt->execute([$assinatura_id]);
        
        // Enviar email com credenciais
        $enviado = enviarEmailComLog($assinatura['email'], 'assinatura_ativada', [
            'nome_usuario' => $assinatura['nome_usuario'],
            'usuario_iptv' => $usuario_iptv,
            'senha_iptv' => $senha_iptv,
            'url_servidor' => 'http://dns.appcanais.net:80',
            'plano' => $assinatura['plano'],
            'data_fim' => date('d/m/Y', strtotime($assinatura['data_fim']))
        ], $assinatura['usuario_id']);
        
        if ($enviado) {
            return [
                'sucesso' => true, 
                'mensagem' => "Assinatura #{$assinatura_id} ativada com sucesso! Email enviado para {$assinatura['email']}"
            ];
        } else {
            return [
                'sucesso' => true, 
                'mensagem' => "Assinatura #{$assinatura_id} ativada! ATENÇÃO: Falha ao enviar email. Envie as credenciais manualmente."
            ];
        }
        
    } catch (Exception $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao ativar: ' . $e->getMessage()];
    }
}

// Buscar assinaturas pendentes
$pdo = conectarDB();
$stmt = $pdo->query("
    SELECT a.*, u.nome_usuario, u.email, p.transacao_id 
    FROM assinaturas a 
    JOIN usuarios u ON a.usuario_id = u.id 
    LEFT JOIN pagamentos p ON p.assinatura_id = a.id
    WHERE a.status = 'pendente' 
    ORDER BY a.data_criacao DESC
");
$pendentes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Ativar Assinaturas</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        .admin-header {
            background: var(--dark-color);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem 0.5rem 0 0;
            text-align: center;
        }
        .admin-content {
            background: white;
            padding: 2rem;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: var(--shadow);
        }
        .pendentes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .pendentes-table th {
            background: var(--light-color);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
        }
        .pendentes-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .pendentes-table tr:hover {
            background: var(--light-color);
        }
        .ativar-form {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        .ativar-form input {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
        }
        .ativar-form button {
            padding: 0.5rem 1rem;
        }
        .senha-form {
            background: var(--light-color);
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .aviso {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-user-shield"></i> Painel Administrativo - Ativar Assinaturas</h1>
        </div>
        
        <div class="admin-content">
            <div class="aviso">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>ATENÇÃO:</strong> Este é um arquivo administrativo. 
                Proteja-o com senha forte ou remova após o uso!
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert alert-error">
                    <i class="fas fa-times-circle"></i> <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_POST['senha_admin']) || $_POST['senha_admin'] !== $senha_admin): ?>
                <form method="POST" class="senha-form">
                    <h3>Digite a senha administrativa</h3>
                    <p>
                        <input type="password" name="senha_admin" placeholder="Senha admin" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock-open"></i> Acessar
                        </button>
                    </p>
                </form>
            <?php else: ?>
                <h2>Assinaturas Pendentes de Ativação</h2>
                
                <?php if (empty($pendentes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Nenhuma assinatura pendente!</p>
                    </div>
                <?php else: ?>
                    <table class="pendentes-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Plano</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Data</th>
                                <th>Transação</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendentes as $pendente): ?>
                            <tr>
                                <td>#<?php echo $pendente['id']; ?></td>
                                <td><?php echo htmlspecialchars($pendente['nome_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($pendente['email']); ?></td>
                                <td><?php echo ucfirst($pendente['plano']); ?></td>
                                <td><?php echo formatarMoeda($pendente['valor'], $pendente['moeda']); ?></td>
                                <td><?php echo ucfirst($pendente['metodo_pagamento']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pendente['data_criacao'])); ?></td>
                                <td><?php echo $pendente['transacao_id'] ?: '-'; ?></td>
                                <td>
                                    <form method="POST" class="ativar-form">
                                        <input type="hidden" name="senha_admin" value="<?php echo $senha_admin; ?>">
                                        <input type="hidden" name="assinatura_id" value="<?php echo $pendente['id']; ?>">
                                        <input type="text" name="usuario_iptv" placeholder="Usuário IPTV" required size="10">
                                        <input type="text" name="senha_iptv" placeholder="Senha IPTV" required size="10">
                                        <button type="submit" name="ativar" class="btn btn-success">
                                            <i class="fas fa-check"></i> Ativar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div style="margin-top: 2rem; padding: 1rem; background: var(--light-color); border-radius: 0.5rem;">
                    <h3>Instruções:</h3>
                    <ol>
                        <li>Verifique o pagamento do cliente</li>
                        <li>Crie as credenciais no sistema IPTV</li>
                        <li>Digite o usuário e senha IPTV nos campos</li>
                        <li>Clique em "Ativar" para ativar a assinatura e enviar email</li>
                        <li>O cliente receberá um email com as instruções de acesso</li>
                    </ol>
                    
                    <h4 style="margin-top: 1rem;">Dados do Servidor IPTV:</h4>
                    <ul>
                        <li>URL: <code>http://dns.appcanais.net:80</code></li>
                        <li>Tipo: Xtream Codes API</li>
                        <li>App: IPTV Smarters Pro</li>
                    </ul>
                </div>

                <div style="margin-top: 2rem; text-align: center;">
                    <form method="GET" style="display: inline;">
                        <button type="submit" class="btn btn-outline">
                            <i class="fas fa-sync"></i> Atualizar Lista
                        </button>
                    </form>
                    <a href="dashboard.php" class="btn btn-outline" style="margin-left: 1rem;">
                        <i class="fas fa-home"></i> Voltar ao Site
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>