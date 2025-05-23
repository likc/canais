<?php
/**
 * SCRIPT PARA CRIAR TODOS OS ARQUIVOS DO PAINEL ADMIN
 * 
 * 1. Faça upload deste arquivo para a raiz do seu site
 * 2. Acesse via navegador: https://seusite.com/criar-arquivos-admin.php
 * 3. Todos os arquivos serão criados automaticamente
 * 4. DELETE este arquivo após usar!
 */

// Verificar se a pasta admin existe
if (!file_exists('admin')) {
    mkdir('admin', 0755, true);
    echo "✅ Pasta admin criada<br>";
}

$arquivos_criados = 0;
$erros = [];

// Incluir arquivo funcoes-extras.php primeiro
$funcoes_extras = file_get_contents(__DIR__ . '/admin/funcoes-extras.php');

// Array com todos os arquivos e seus conteúdos
$arquivos = [
    // 1. index.php - Arquivo principal com correção para calcular receita em BRL
    'admin/index.php' => '<?php
// Painel Administrativo - Canais.net
require_once \'../config.php\';

// Verificar senha admin
session_start();

// ALTERE ESTA SENHA!
$senha_admin = \'SuaSenhaSegura123!\';

// Verificar se está logado como admin
if (!isset($_SESSION[\'admin_logado\'])) {
    if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && isset($_POST[\'senha\'])) {
        if ($_POST[\'senha\'] === $senha_admin) {
            $_SESSION[\'admin_logado\'] = true;
        } else {
            $erro = \'Senha incorreta!\';
        }
    }
    
    if (!isset($_SESSION[\'admin_logado\'])) {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Canais.net</title>
            <link rel="stylesheet" href="../style.css">
            <style>
                .login-admin {
                    max-width: 400px;
                    margin: 100px auto;
                    padding: 2rem;
                    background: white;
                    border-radius: 1rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                }
                .login-admin h1 {
                    text-align: center;
                    color: var(--text-dark);
                    margin-bottom: 2rem;
                }
                .admin-icon {
                    text-align: center;
                    font-size: 3rem;
                    color: var(--primary-color);
                    margin-bottom: 1rem;
                }
            </style>
        </head>
        <body style="background: var(--light-color);">
            <div class="login-admin">
                <div class="admin-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Área Administrativa</h1>
                <?php if (isset($erro)): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Senha Administrativa:</label>
                        <input type="password" name="senha" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-lock-open"></i> Acessar
                    </button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Buscar estatísticas
$pdo = conectarDB();

// Incluir funcoes extras
require_once \'funcoes-extras.php\';

// Dashboard stats - CORRIGIDO PARA CALCULAR EM BRL
$stats = [
    \'usuarios_total\' => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    \'usuarios_hoje\' => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE DATE(data_cadastro) = CURDATE()")->fetchColumn(),
    \'assinaturas_ativas\' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = \'ativa\' AND data_fim >= CURDATE()")->fetchColumn(),
    \'assinaturas_pendentes\' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = \'pendente\'")->fetchColumn(),
    \'receita_mes\' => calcularReceitaMesConvertida($pdo), // Função que converte tudo para BRL
    \'emails_hoje\' => $pdo->query("SELECT COUNT(*) FROM logs_email WHERE DATE(data_envio) = CURDATE()")->fetchColumn()
];

// Ação selecionada
$acao = $_GET[\'acao\'] ?? \'dashboard\';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Canais.net</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: var(--dark-color);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1.5rem;
            text-align: center;
        }
        .admin-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .admin-nav {
            padding: 1rem 0;
        }
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .admin-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: var(--light-color);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            font-size: 1.5rem;
            color: white;
        }
        .stat-content h3 {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .data-table {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .table-header {
            background: var(--light-color);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .search-box {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .search-box input {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
            width: 250px;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-header">
                <h2><i class="fas fa-user-shield"></i> Painel Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="?acao=dashboard" class="<?php echo $acao === \'dashboard\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="?acao=usuarios" class="<?php echo $acao === \'usuarios\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-users"></i> Usuários
                </a>
                <a href="?acao=assinaturas" class="<?php echo $acao === \'assinaturas\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-credit-card"></i> Assinaturas
                </a>
                <a href="?acao=pagamentos" class="<?php echo $acao === \'pagamentos\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-dollar-sign"></i> Pagamentos
                </a>
                <a href="?acao=emails" class="<?php echo $acao === \'emails\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-envelope"></i> Logs de Email
                </a>
                <a href="?acao=ativar" class="<?php echo $acao === \'ativar\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-check-circle"></i> Ativar Assinaturas
                </a>
                <a href="?acao=configuracoes" class="<?php echo $acao === \'configuracoes\' ? \'active\' : \'\'; ?>">
                    <i class="fas fa-cog"></i> Configurações
                </a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 1rem 0;">
                <a href="../" target="_blank">
                    <i class="fas fa-home"></i> Ver Site
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
        </aside>

        <!-- Content -->
        <main class="admin-content">
            <?php
            switch ($acao) {
                case \'dashboard\':
                    include \'dashboard.php\';
                    break;
                case \'usuarios\':
                    include \'usuarios.php\';
                    break;
                case \'assinaturas\':
                    include \'assinaturas.php\';
                    break;
                case \'pagamentos\':
                    include \'pagamentos.php\';
                    break;
                case \'emails\':
                    include \'emails.php\';
                    break;
                case \'ativar\':
                    include \'ativar.php\';
                    break;
                case \'configuracoes\':
                    include \'configuracoes.php\';
                    break;
                default:
                    include \'dashboard.php\';
            }
            ?>
        </main>
    </div>

    <script>
        // Funções JavaScript globais do admin
        function confirmarAcao(mensagem) {
            return confirm(mensagem);
        }

        function mostrarModal(id) {
            document.getElementById(id).style.display = \'flex\';
        }

        function fecharModal(id) {
            document.getElementById(id).style.display = \'none\';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains(\'modal\')) {
                event.target.style.display = \'none\';
            }
        }
    </script>
</body>
</html>',

    // 2. dashboard.php - Com correção da moeda
    'admin/dashboard.php' => '<?php
// Dashboard - Página inicial do admin
?>
<h1>Dashboard</h1>
<p>Bem-vindo ao painel administrativo do Canais.net</p>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--primary-color);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3>Total de Usuários</h3>
            <div class="stat-value"><?php echo number_format($stats[\'usuarios_total\'], 0, \',\', \'.\'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--success-color);">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-content">
            <h3>Cadastros Hoje</h3>
            <div class="stat-value"><?php echo number_format($stats[\'usuarios_hoje\'], 0, \',\', \'.\'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--secondary-color);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3>Assinaturas Ativas</h3>
            <div class="stat-value"><?php echo number_format($stats[\'assinaturas_ativas\'], 0, \',\', \'.\'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning-color);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3>Pendentes</h3>
            <div class="stat-value"><?php echo number_format($stats[\'assinaturas_pendentes\'], 0, \',\', \'.\'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #10b981;">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <h3>Receita do Mês (em BRL)</h3>
            <div class="stat-value">R$ <?php echo number_format($stats[\'receita_mes\'], 2, \',\', \'.\'); ?></div>
            <small class="text-muted">Convertido para reais</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #8b5cf6;">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-content">
            <h3>Emails Hoje</h3>
            <div class="stat-value"><?php echo number_format($stats[\'emails_hoje\'], 0, \',\', \'.\'); ?></div>
        </div>
    </div>
</div>

<!-- Últimas Atividades -->
<div class="data-table">
    <div class="table-header">
        <h2>Últimas Atividades</h2>
    </div>
    <div style="padding: 1.5rem;">
        <h3>Últimos Cadastros</h3>
        <table class="data-table" style="width: 100%; margin-bottom: 2rem;">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Região</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $ultimos_usuarios = $pdo->query("
                    SELECT nome_usuario, email, regiao, data_cadastro 
                    FROM usuarios 
                    ORDER BY data_cadastro DESC 
                    LIMIT 5
                ")->fetchAll();
                
                foreach ($ultimos_usuarios as $usuario):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario[\'nome_usuario\']); ?></td>
                    <td><?php echo htmlspecialchars($usuario[\'email\']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php 
                            $bandeiras = [\'BRL\' => \'🇧🇷\', \'USD\' => \'🇺🇸\', \'EUR\' => \'🇪🇺\', \'JPY\' => \'🇯🇵\'];
                            echo $bandeiras[$usuario[\'regiao\']] . \' \' . $usuario[\'regiao\'];
                            ?>
                        </span>
                    </td>
                    <td><?php echo date(\'d/m/Y H:i\', strtotime($usuario[\'data_cadastro\'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Assinaturas Pendentes</h3>
        <table class="data-table" style="width: 100%;">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Plano</th>
                    <th>Valor</th>
                    <th>Data</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pendentes = $pdo->query("
                    SELECT a.*, u.nome_usuario, u.email 
                    FROM assinaturas a 
                    JOIN usuarios u ON a.usuario_id = u.id 
                    WHERE a.status = \'pendente\' 
                    ORDER BY a.data_criacao DESC 
                    LIMIT 5
                ")->fetchAll();
                
                foreach ($pendentes as $pendente):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($pendente[\'nome_usuario\']); ?></td>
                    <td><?php echo ucfirst($pendente[\'plano\']); ?></td>
                    <td><?php echo formatarMoeda($pendente[\'valor\'], $pendente[\'moeda\']); ?></td>
                    <td><?php echo date(\'d/m/Y\', strtotime($pendente[\'data_criacao\'])); ?></td>
                    <td>
                        <a href="?acao=ativar" class="btn btn-sm btn-primary">
                            <i class="fas fa-check"></i> Ativar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Gráficos -->
<div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="data-table">
        <div class="table-header">
            <h3>Receita por Mês (em BRL)</h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php
            // Buscar receita dos últimos 6 meses convertida para BRL
            $meses = [];
            for ($i = 5; $i >= 0; $i--) {
                $mes = date(\'Y-m\', strtotime("-$i months"));
                $stmt = $pdo->prepare("
                    SELECT moeda, SUM(valor) as total 
                    FROM pagamentos 
                    WHERE status = \'aprovado\' 
                    AND DATE_FORMAT(data_pagamento, \'%Y-%m\') = ?
                    GROUP BY moeda
                ");
                $stmt->execute([$mes]);
                
                $total_mes_brl = 0;
                while ($row = $stmt->fetch()) {
                    $total_mes_brl += converterMoeda($row[\'total\'], $row[\'moeda\'], \'BRL\');
                }
                
                $meses[] = [
                    \'mes\' => date(\'M/Y\', strtotime($mes . \'-01\')),
                    \'total\' => $total_mes_brl
                ];
            }
            
            foreach ($meses as $mes):
            ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span><?php echo $mes[\'mes\']; ?></span>
                    <strong>R$ <?php echo number_format($mes[\'total\'], 2, \',\', \'.\'); ?></strong>
                </div>
                <div style="background: var(--light-color); height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: var(--primary-color); height: 100%; width: <?php echo $mes[\'total\'] > 0 ? min(($mes[\'total\'] / max(array_column($meses, \'total\'))) * 100, 100) : 0; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="data-table">
        <div class="table-header">
            <h3>Usuários por Região</h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php
            $por_regiao = $pdo->query("
                SELECT regiao, COUNT(*) as total 
                FROM usuarios 
                GROUP BY regiao
            ")->fetchAll();
            
            foreach ($por_regiao as $reg):
                $bandeiras = [\'BRL\' => \'🇧🇷 Brasil\', \'USD\' => \'🇺🇸 EUA\', \'EUR\' => \'🇪🇺 Europa\', \'JPY\' => \'🇯🇵 Japão\'];
            ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span><?php echo $bandeiras[$reg[\'regiao\']]; ?></span>
                    <strong><?php echo $reg[\'total\']; ?></strong>
                </div>
                <div style="background: var(--light-color); height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: var(--primary-color); height: 100%; width: <?php echo ($reg[\'total\'] / $stats[\'usuarios_total\']) * 100; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>',

    // 3. usuarios.php - Corrigido: removido admin, campos editáveis, sem duplicação
    'admin/usuarios.php' => '<?php
// Gerenciamento de Usuários

// Processar ações
if (isset($_POST[\'acao\'])) {
    switch ($_POST[\'acao\']) {
        case \'desativar\':
            $pdo->prepare("UPDATE usuarios SET status = \'inativo\' WHERE id = ?")->execute([$_POST[\'usuario_id\']]);
            echo \'<div class="alert alert-success">Usuário desativado!</div>\';
            break;
        case \'ativar\':
            $pdo->prepare("UPDATE usuarios SET status = \'ativo\' WHERE id = ?")->execute([$_POST[\'usuario_id\']]);
            echo \'<div class="alert alert-success">Usuário ativado!</div>\';
            break;
        case \'resetar_senha\':
            $nova_senha = substr(str_shuffle(\'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789\'), 0, 8);
            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$nova_senha, $_POST[\'usuario_id\']]);
            echo \'<div class="alert alert-success">Nova senha: <strong>\' . $nova_senha . \'</strong></div>\';
            break;
        case \'editar_usuario\':
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome_usuario = ?, email = ?, telefone = ?, regiao = ?, senha = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST[\'nome_usuario\'],
                $_POST[\'email\'],
                $_POST[\'telefone\'],
                $_POST[\'regiao\'],
                $_POST[\'senha\'],
                $_POST[\'status\'],
                $_POST[\'usuario_id\']
            ]);
            echo \'<div class="alert alert-success">Usuário atualizado!</div>\';
            break;
        case \'editar_assinatura\':
            // Atualizar valor baseado no plano
            global $PRECOS;
            $assinatura_id = $_POST[\'assinatura_id\'];
            $plano = $_POST[\'plano\'];
            
            // Buscar moeda da assinatura
            $stmt = $pdo->prepare("SELECT moeda FROM assinaturas WHERE id = ?");
            $stmt->execute([$assinatura_id]);
            $moeda = $stmt->fetchColumn();
            
            $valor = $PRECOS[$moeda][$plano];
            
            $stmt = $pdo->prepare("
                UPDATE assinaturas 
                SET plano = ?, valor = ?, data_inicio = ?, data_fim = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $plano,
                $valor,
                $_POST[\'data_inicio\'],
                $_POST[\'data_fim\'],
                $_POST[\'status\'],
                $assinatura_id
            ]);
            echo \'<div class="alert alert-success">Assinatura atualizada!</div>\';
            break;
        case \'criar_assinatura\':
            global $PRECOS;
            $usuario_id = $_POST[\'usuario_id\'];
            $plano = $_POST[\'plano\'];
            $regiao = $_POST[\'regiao\'];
            $valor = $PRECOS[$regiao][$plano];
            
            // Calcular datas
            $data_inicio = date(\'Y-m-d\');
            $duracao = match($plano) {
                \'mensal\' => \'+1 month\',
                \'semestral\' => \'+6 months\',
                \'anual\' => \'+1 year\'
            };
            $data_fim = date(\'Y-m-d\', strtotime($duracao));
            
            $stmt = $pdo->prepare("
                INSERT INTO assinaturas (usuario_id, plano, valor, moeda, status, data_inicio, data_fim, data_criacao)
                VALUES (?, ?, ?, ?, \'pendente\', ?, ?, NOW())
            ");
            $stmt->execute([$usuario_id, $plano, $valor, $regiao, $data_inicio, $data_fim]);
            
            echo \'<div class="alert alert-success">Assinatura criada! <a href="?acao=ativar">Clique aqui para ativar</a></div>\';
            break;
        case \'deletar_assinatura\':
            $pdo->prepare("DELETE FROM assinaturas WHERE id = ?")->execute([$_POST[\'assinatura_id\']]);
            echo \'<div class="alert alert-success">Assinatura removida!</div>\';
            break;
    }
}

// Buscar usuários
$busca = $_GET[\'busca\'] ?? \'\';
$filtro_status = $_GET[\'status\'] ?? \'\';
$filtro_regiao = $_GET[\'regiao\'] ?? \'\';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM assinaturas WHERE usuario_id = u.id AND status = \'ativa\') as assinaturas_ativas
        FROM usuarios u WHERE 1=1";
$params = [];

if ($busca) {
    $sql .= " AND (u.nome_usuario LIKE ? OR u.email LIKE ? OR u.telefone LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($filtro_status) {
    $sql .= " AND u.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_regiao) {
    $sql .= " AND u.regiao = ?";
    $params[] = $filtro_regiao;
}

$sql .= " ORDER BY u.data_cadastro DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
?>

<h1>Gerenciar Usuários</h1>

<div class="data-table">
    <div class="table-header">
        <h2>Usuários Cadastrados</h2>
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="acao" value="usuarios">
                <input type="text" name="busca" placeholder="Buscar usuário..." value="<?php echo htmlspecialchars($busca); ?>">
                <select name="status">
                    <option value="">Todos Status</option>
                    <option value="ativo" <?php echo $filtro_status === \'ativo\' ? \'selected\' : \'\'; ?>>Ativos</option>
                    <option value="inativo" <?php echo $filtro_status === \'inativo\' ? \'selected\' : \'\'; ?>>Inativos</option>
                </select>
                <select name="regiao">
                    <option value="">Todas Regiões</option>
                    <option value="BRL" <?php echo $filtro_regiao === \'BRL\' ? \'selected\' : \'\'; ?>>🇧🇷 Brasil</option>
                    <option value="USD" <?php echo $filtro_regiao === \'USD\' ? \'selected\' : \'\'; ?>>🇺🇸 EUA</option>
                    <option value="EUR" <?php echo $filtro_regiao === \'EUR\' ? \'selected\' : \'\'; ?>>🇪🇺 Europa</option>
                    <option value="JPY" <?php echo $filtro_regiao === \'JPY\' ? \'selected\' : \'\'; ?>>🇯🇵 Japão</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Região</th>
                    <th>Status</th>
                    <th>Assinaturas</th>
                    <th>Cadastro</th>
                    <th>Último Login</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo $usuario[\'id\']; ?></td>
                    <td><strong><?php echo htmlspecialchars($usuario[\'nome_usuario\']); ?></strong></td>
                    <td><?php echo htmlspecialchars($usuario[\'email\']); ?></td>
                    <td><?php echo htmlspecialchars($usuario[\'telefone\']); ?></td>
                    <td>
                        <?php 
                        $bandeiras = [\'BRL\' => \'🇧🇷\', \'USD\' => \'🇺🇸\', \'EUR\' => \'🇪🇺\', \'JPY\' => \'🇯🇵\'];
                        echo $bandeiras[$usuario[\'regiao\']] . \' \' . $usuario[\'regiao\'];
                        ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $usuario[\'status\'] === \'ativo\' ? \'success\' : \'danger\'; ?>">
                            <?php echo ucfirst($usuario[\'status\']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($usuario[\'assinaturas_ativas\'] > 0): ?>
                            <span class="badge badge-info"><?php echo $usuario[\'assinaturas_ativas\']; ?> ativa(s)</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Nenhuma</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date(\'d/m/Y\', strtotime($usuario[\'data_cadastro\'])); ?></td>
                    <td><?php echo $usuario[\'ultimo_login\'] ? date(\'d/m/Y H:i\', strtotime($usuario[\'ultimo_login\'])) : \'Nunca\'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="mostrarModal(\'modal-usuario-<?php echo $usuario[\'id\']; ?>\')" class="btn btn-sm btn-primary" title="Ver/Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="resetar_senha">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario[\'id\']; ?>">
                                <button type="submit" class="btn btn-sm btn-warning" 
                                        onclick="return confirmarAcao(\'Resetar senha deste usuário?\')" title="Resetar Senha">
                                    <i class="fas fa-key"></i>
                                </button>
                            </form>
                            
                            <?php if ($usuario[\'status\'] === \'ativo\'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="desativar">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario[\'id\']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirmarAcao(\'Desativar este usuário?\')" title="Desativar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="ativar">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario[\'id\']; ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Ativar">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- Modal detalhes -->
                <div id="modal-usuario-<?php echo $usuario[\'id\']; ?>" class="modal">
                    <div class="modal-content" style="max-width: 900px;">
                        <div class="modal-header">
                            <h3>Detalhes e Edição do Usuário</h3>
                            <button class="close-modal" onclick="fecharModal(\'modal-usuario-<?php echo $usuario[\'id\']; ?>\')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <!-- Coluna 1: Dados do Usuário -->
                            <div>
                                <h4>Dados do Usuário</h4>
                                <form method="POST">
                                    <input type="hidden" name="acao" value="editar_usuario">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario[\'id\']; ?>">
                                    
                                    <div class="form-group">
                                        <label>ID:</label>
                                        <input type="text" value="<?php echo $usuario[\'id\']; ?>" disabled>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Nome de Usuário:</label>
                                        <input type="text" name="nome_usuario" value="<?php echo htmlspecialchars($usuario[\'nome_usuario\']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Email:</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario[\'email\']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Senha:</label>
                                        <div style="position: relative;">
                                            <input type="text" name="senha" id="senha-<?php echo $usuario[\'id\']; ?>" 
                                                   value="<?php echo htmlspecialchars($usuario[\'senha\']); ?>" required
                                                   style="padding-right: 40px;">
                                            <button type="button" onclick="toggleSenha(<?php echo $usuario[\'id\']; ?>)" 
                                                    style="position: absolute; right: 5px; top: 5px; background: none; border: none; cursor: pointer;">
                                                <i class="fas fa-eye" id="eye-<?php echo $usuario[\'id\']; ?>"></i>
                                            </button>
                                        </div>
                                        <small class="form-hint" style="color: var(--danger-color);">
                                            <i class="fas fa-exclamation-triangle"></i> Senha em texto plano - considere implementar hash
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Telefone:</label>
                                        <input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario[\'telefone\']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Região:</label>
                                        <select name="regiao" required>
                                            <option value="BRL" <?php echo $usuario[\'regiao\'] === \'BRL\' ? \'selected\' : \'\'; ?>>🇧🇷 Brasil</option>
                                            <option value="USD" <?php echo $usuario[\'regiao\'] === \'USD\' ? \'selected\' : \'\'; ?>>🇺🇸 EUA</option>
                                            <option value="EUR" <?php echo $usuario[\'regiao\'] === \'EUR\' ? \'selected\' : \'\'; ?>>🇪🇺 Europa</option>
                                            <option value="JPY" <?php echo $usuario[\'regiao\'] === \'JPY\' ? \'selected\' : \'\'; ?>>🇯🇵 Japão</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Status:</label>
                                        <select name="status" required>
                                            <option value="ativo" <?php echo $usuario[\'status\'] === \'ativo\' ? \'selected\' : \'\'; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $usuario[\'status\'] === \'inativo\' ? \'selected\' : \'\'; ?>>Inativo</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Data de Cadastro:</label>
                                        <input type="text" value="<?php echo date(\'d/m/Y H:i:s\', strtotime($usuario[\'data_cadastro\'])); ?>" disabled>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Último Login:</label>
                                        <input type="text" value="<?php echo $usuario[\'ultimo_login\'] ? date(\'d/m/Y H:i:s\', strtotime($usuario[\'ultimo_login\'])) : \'Nunca\'; ?>" disabled>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Alterações
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Coluna 2: Assinaturas -->
                            <div>
                                <h4>Assinaturas</h4>
                                <?php
                                $assinaturas = $pdo->prepare("
                                    SELECT * FROM assinaturas 
                                    WHERE usuario_id = ? 
                                    ORDER BY data_criacao DESC
                                ");
                                $assinaturas->execute([$usuario[\'id\']]);
                                $assinaturas = $assinaturas->fetchAll();
                                
                                if (empty($assinaturas)):
                                ?>
                                    <p>Nenhuma assinatura encontrada.</p>
                                <?php else: ?>
                                    <?php foreach ($assinaturas as $assinatura): 
                                        $dias_restantes = (strtotime($assinatura[\'data_fim\']) - time()) / 86400;
                                        $cor_borda = \'border-color: var(--border-color)\';
                                        if ($assinatura[\'status\'] === \'ativa\' && $dias_restantes < 0) {
                                            $cor_borda = \'border-color: var(--danger-color); background: #ffebee\';
                                        } elseif ($assinatura[\'status\'] === \'ativa\' && $dias_restantes <= 7) {
                                            $cor_borda = \'border-color: var(--warning-color); background: #fff3cd\';
                                        }
                                    ?>
                                    <div style="border: 2px solid; <?php echo $cor_borda; ?>; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                                        <form method="POST">
                                            <input type="hidden" name="acao" value="editar_assinatura">
                                            <input type="hidden" name="assinatura_id" value="<?php echo $assinatura[\'id\']; ?>">
                                            
                                            <div class="form-group">
                                                <label>ID da Assinatura:</label>
                                                <input type="text" value="#<?php echo $assinatura[\'id\']; ?>" disabled>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Criada em:</label>
                                                <input type="text" value="<?php echo date(\'d/m/Y H:i\', strtotime($assinatura[\'data_criacao\'])); ?>" disabled>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Plano:</label>
                                                <select name="plano" required>
                                                    <option value="mensal" <?php echo $assinatura[\'plano\'] === \'mensal\' ? \'selected\' : \'\'; ?>>Mensal</option>
                                                    <option value="semestral" <?php echo $assinatura[\'plano\'] === \'semestral\' ? \'selected\' : \'\'; ?>>Semestral</option>
                                                    <option value="anual" <?php echo $assinatura[\'plano\'] === \'anual\' ? \'selected\' : \'\'; ?>>Anual</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select name="status" required>
                                                    <option value="ativa" <?php echo $assinatura[\'status\'] === \'ativa\' ? \'selected\' : \'\'; ?>>Ativa</option>
                                                    <option value="pendente" <?php echo $assinatura[\'status\'] === \'pendente\' ? \'selected\' : \'\'; ?>>Pendente</option>
                                                    <option value="cancelada" <?php echo $assinatura[\'status\'] === \'cancelada\' ? \'selected\' : \'\'; ?>>Cancelada</option>
                                                    <option value="expirada" <?php echo $assinatura[\'status\'] === \'expirada\' ? \'selected\' : \'\'; ?>>Expirada</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Data Início:</label>
                                                <input type="date" name="data_inicio" value="<?php echo $assinatura[\'data_inicio\']; ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Data Fim:</label>
                                                <input type="date" name="data_fim" value="<?php echo $assinatura[\'data_fim\']; ?>" required>
                                                <?php 
                                                $dias_restantes = round((strtotime($assinatura[\'data_fim\']) - time()) / 86400);
                                                if ($dias_restantes < 0): ?>
                                                    <small style="color: var(--danger-color);">Expirada há <?php echo abs($dias_restantes); ?> dias</small>
                                                <?php elseif ($dias_restantes == 0): ?>
                                                    <small style="color: var(--warning-color);">Expira hoje!</small>
                                                <?php elseif ($dias_restantes <= 7): ?>
                                                    <small style="color: var(--warning-color);">Expira em <?php echo $dias_restantes; ?> dias</small>
                                                <?php else: ?>
                                                    <small style="color: var(--text-light);"><?php echo $dias_restantes; ?> dias restantes</small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Valor:</label>
                                                <input type="text" value="<?php echo formatarMoeda($assinatura[\'valor\'], $assinatura[\'moeda\']); ?>" disabled>
                                                <small class="form-hint">O valor é atualizado automaticamente ao mudar o plano</small>
                                            </div>
                                            
                                            <?php if ($assinatura[\'status\'] === \'ativa\'): ?>
                                            <div class="form-group" style="background: #e8f5e9; padding: 0.75rem; border-radius: 0.25rem;">
                                                <label><i class="fas fa-tv"></i> Informações IPTV:</label>
                                                <p style="margin: 0.25rem 0;"><strong>Servidor:</strong> http://dns.appcanais.net:80</p>
                                                <p style="margin: 0.25rem 0;"><strong>Tipo:</strong> Xtream Codes API</p>
                                                <small>As credenciais devem estar no painel IPTV</small>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-save"></i> Salvar
                                                </button>
                                                
                                                <?php if ($assinatura[\'status\'] === \'pendente\'): ?>
                                                <a href="?acao=ativar" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Ativar
                                                </a>
                                                <?php endif; ?>
                                                
                                                <button type="button" onclick="if(confirm(\'Deletar esta assinatura?\')) { deletarAssinatura(<?php echo $assinatura[\'id\']; ?>) }" 
                                                        class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Deletar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <button class="btn btn-success btn-sm" onclick="mostrarFormNovaAssinatura(<?php echo $usuario[\'id\']; ?>)" style="margin-top: 1rem;">
                                    <i class="fas fa-plus"></i> Adicionar Nova Assinatura
                                </button>
                                
                                <!-- Formulário para criar assinatura -->
                                <div id="form-nova-assinatura-<?php echo $usuario[\'id\']; ?>" style="display: none; margin-top: 1rem; padding: 1rem; background: var(--light-color); border-radius: 0.5rem;">
                                    <form method="POST">
                                        <input type="hidden" name="acao" value="criar_assinatura">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario[\'id\']; ?>">
                                        <input type="hidden" name="regiao" value="<?php echo $usuario[\'regiao\']; ?>">
                                        
                                        <div class="form-group">
                                            <label>Plano:</label>
                                            <select name="plano" required>
                                                <option value="mensal">Mensal</option>
                                                <option value="semestral">Semestral</option>
                                                <option value="anual">Anual</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-check"></i> Criar
                                        </button>
                                        <button type="button" onclick="ocultarFormNovaAssinatura(<?php echo $usuario[\'id\']; ?>)" class="btn btn-sm btn-outline">
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 2rem; text-align: center;">
    <p>Total de usuários: <strong><?php echo count($usuarios); ?></strong></p>
</div>

<!-- Formulário oculto para deletar assinatura -->
<form id="form-deletar-assinatura" method="POST" style="display: none;">
    <input type="hidden" name="acao" value="deletar_assinatura">
    <input type="hidden" name="assinatura_id" id="deletar-assinatura-id">
</form>

<script>
function toggleSenha(id) {
    const input = document.getElementById(\'senha-\' + id);
    const icon = document.getElementById(\'eye-\' + id);
    
    if (input.type === \'password\') {
        input.type = \'text\';
        icon.classList.remove(\'fa-eye\');
        icon.classList.add(\'fa-eye-slash\');
    } else {
        input.type = \'password\';
        icon.classList.remove(\'fa-eye-slash\');
        icon.classList.add(\'fa-eye\');
    }
}

function mostrarFormNovaAssinatura(usuarioId) {
    document.getElementById(\'form-nova-assinatura-\' + usuarioId).style.display = \'block\';
}

function ocultarFormNovaAssinatura(usuarioId) {
    document.getElementById(\'form-nova-assinatura-\' + usuarioId).style.display = \'none\';
}

function deletarAssinatura(assinaturaId) {
    document.getElementById(\'deletar-assinatura-id\').value = assinaturaId;
    document.getElementById(\'form-deletar-assinatura\').submit();
}

// Inicializar todas as senhas como texto visível (já que estamos mostrando)
document.addEventListener(\'DOMContentLoaded\', function() {
    const senhaInputs = document.querySelectorAll(\'input[id^="senha-"]\');
    senhaInputs.forEach(input => {
        input.type = \'text\';
    });
});
</script>',

    // 4. Outros arquivos necessários
    'admin/logout.php' => '<?php
session_start();
session_destroy();
header("Location: index.php");
exit;
?>',

    'admin/ativar.php' => '<?php
// Ativar Assinaturas
require_once \'../funcoes-email.php\';

// Processar ativação
if (isset($_POST[\'ativar\'])) {
    $assinatura_id = intval($_POST[\'assinatura_id\']);
    $usuario_iptv = $_POST[\'usuario_iptv\'];
    $senha_iptv = $_POST[\'senha_iptv\'];
    
    // Buscar dados da assinatura
    $stmt = $pdo->prepare("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$assinatura_id]);
    $assinatura = $stmt->fetch();
    
    if ($assinatura) {
        // Atualizar status
        $pdo->prepare("UPDATE assinaturas SET status = \'ativa\' WHERE id = ?")->execute([$assinatura_id]);
        $pdo->prepare("UPDATE pagamentos SET status = \'aprovado\' WHERE assinatura_id = ?")->execute([$assinatura_id]);
        
        // Enviar email
        $enviado = enviarEmailComLog($assinatura[\'email\'], \'assinatura_ativada\', [
            \'nome_usuario\' => $assinatura[\'nome_usuario\'],
            \'usuario_iptv\' => $usuario_iptv,
            \'senha_iptv\' => $senha_iptv,
            \'url_servidor\' => \'http://dns.appcanais.net:80\',
            \'plano\' => $assinatura[\'plano\'],
            \'data_fim\' => date(\'d/m/Y\', strtotime($assinatura[\'data_fim\']))
        ], $assinatura[\'usuario_id\']);
        
        if ($enviado) {
            echo \'<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Assinatura ativada e email enviado para \' . $assinatura[\'email\'] . \'!
                  </div>\';
        } else {
            echo \'<div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Assinatura ativada, mas falha no envio do email. 
                    Envie as credenciais manualmente.
                  </div>\';
        }
    }
}

// Buscar assinaturas pendentes
$pendentes = $pdo->query("
    SELECT a.*, u.nome_usuario, u.email, u.telefone, p.metodo, p.transacao_id 
    FROM assinaturas a 
    JOIN usuarios u ON a.usuario_id = u.id 
    LEFT JOIN pagamentos p ON p.assinatura_id = a.id
    WHERE a.status = \'pendente\' 
    ORDER BY a.data_criacao DESC
")->fetchAll();
?>

<h1>Ativar Assinaturas</h1>

<div class="data-table">
    <div class="table-header">
        <h2>Assinaturas Pendentes de Ativação</h2>
        <span class="badge badge-warning" style="font-size: 1rem;">
            <?php echo count($pendentes); ?> pendente(s)
        </span>
    </div>
    
    <?php if (empty($pendentes)): ?>
    <div style="padding: 3rem; text-align: center;">
        <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;"></i>
        <h3>Nenhuma assinatura pendente!</h3>
        <p>Todas as assinaturas estão processadas.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Contato</th>
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
                    <td>#<?php echo $pendente[\'id\']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($pendente[\'nome_usuario\']); ?></strong><br>
                        <small><?php echo htmlspecialchars($pendente[\'email\']); ?></small>
                    </td>
                    <td>
                        <a href="https://wa.me/<?php echo preg_replace(\'/[^0-9]/\', \'\', $pendente[\'telefone\']); ?>" 
                           target="_blank" class="btn btn-sm btn-success">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </td>
                    <td><?php echo ucfirst($pendente[\'plano\']); ?></td>
                    <td><?php echo formatarMoeda($pendente[\'valor\'], $pendente[\'moeda\']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php 
                            $metodos = [
                                \'pix\' => \'PIX\',
                                \'cartao\' => \'Cartão\',
                                \'boleto\' => \'Boleto\',
                                \'transferencia\' => \'Transferência\'
                            ];
                            echo $metodos[$pendente[\'metodo\']] ?? $pendente[\'metodo\'] ?? \'Manual\';
                            ?>
                        </span>
                    </td>
                    <td><?php echo date(\'d/m/Y H:i\', strtotime($pendente[\'data_criacao\'])); ?></td>
                    <td>
                        <?php if ($pendente[\'transacao_id\']): ?>
                            <code style="font-size: 0.75rem;"><?php echo $pendente[\'transacao_id\']; ?></code>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <button onclick="mostrarModal(\'modal-ativar-<?php echo $pendente[\'id\']; ?>\')" 
                                class="btn btn-primary btn-sm">
                            <i class="fas fa-check"></i> Ativar
                        </button>
                    </td>
                </tr>
                
                <!-- Modal de ativação -->
                <div id="modal-ativar-<?php echo $pendente[\'id\']; ?>" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Ativar Assinatura #<?php echo $pendente[\'id\']; ?></h3>
                            <button class="close-modal" onclick="fecharModal(\'modal-ativar-<?php echo $pendente[\'id\']; ?>\')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="assinatura_id" value="<?php echo $pendente[\'id\']; ?>">
                            
                            <div style="background: var(--light-color); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                                <p><strong>Usuário:</strong> <?php echo htmlspecialchars($pendente[\'nome_usuario\']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($pendente[\'email\']); ?></p>
                                <p><strong>Plano:</strong> <?php echo ucfirst($pendente[\'plano\']); ?></p>
                                <p><strong>Valor:</strong> <?php echo formatarMoeda($pendente[\'valor\'], $pendente[\'moeda\']); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Usuário IPTV:</label>
                                <input type="text" name="usuario_iptv" required 
                                       placeholder="Ex: user123" 
                                       value="<?php echo strtolower(preg_replace(\'/[^a-zA-Z0-9]/\', \'\', $pendente[\'nome_usuario\'])) . rand(100, 999); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Senha IPTV:</label>
                                <input type="text" name="senha_iptv" required 
                                       placeholder="Ex: pass123" 
                                       value="<?php echo substr(str_shuffle(\'abcdefghijklmnopqrstuvwxyz0123456789\'), 0, 8); ?>">
                            </div>
                            
                            <div style="background: #e3f2fd; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                                <p><strong>Servidor IPTV:</strong> http://dns.appcanais.net:80</p>
                                <p><strong>Tipo:</strong> Xtream Codes API</p>
                                <p><strong>App:</strong> IPTV Smarters Pro</p>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" name="ativar" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Ativar e Enviar Email
                                </button>
                                <button type="button" onclick="fecharModal(\'modal-ativar-<?php echo $pendente[\'id\']; ?>\')" 
                                        class="btn btn-outline">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top: 2rem; background: #fff3cd; padding: 1.5rem; border-radius: 0.5rem;">
    <h3><i class="fas fa-info-circle"></i> Instruções</h3>
    <ol>
        <li>Verifique o pagamento do cliente antes de ativar</li>
        <li>Crie as credenciais no painel do IPTV</li>
        <li>Use as mesmas credenciais aqui</li>
        <li>O sistema enviará automaticamente um email com as instruções</li>
        <li>O cliente poderá ver as credenciais no dashboard dele também</li>
    </ol>
</div>',

    // Adicionar funcoes-extras.php
    'admin/funcoes-extras.php' => '<?php
// Funções extras para o painel administrativo

// Função para exportar assinaturas
function exportarAssinaturas($ids = []) {
    $pdo = conectarDB();
    
    // Query para buscar assinaturas
    $sql = "SELECT a.*, u.nome_usuario, u.email, u.telefone, u.regiao 
            FROM assinaturas a 
            JOIN usuarios u ON a.usuario_id = u.id";
    
    if (!empty($ids)) {
        $placeholders = implode(\',\', array_fill(0, count($ids), \'?\'));
        $sql .= " WHERE a.id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    $assinaturas = $stmt->fetchAll();
    
    // Criar CSV
    header(\'Content-Type: text/csv; charset=utf-8\');
    header(\'Content-Disposition: attachment; filename=assinaturas_\' . date(\'Y-m-d\') . \'.csv\');
    
    $output = fopen(\'php://output\', \'w\');
    
    // Cabeçalhos
    fputcsv($output, [
        \'ID\', 
        \'Usuário\', 
        \'Email\', 
        \'Telefone\', 
        \'Região\', 
        \'Plano\', 
        \'Valor\', 
        \'Moeda\', 
        \'Status\', 
        \'Data Início\', 
        \'Data Fim\', 
        \'Dias Restantes\'
    ]);
    
    // Dados
    foreach ($assinaturas as $assinatura) {
        $dias_restantes = (strtotime($assinatura[\'data_fim\']) - time()) / 86400;
        
        fputcsv($output, [
            $assinatura[\'id\'],
            $assinatura[\'nome_usuario\'],
            $assinatura[\'email\'],
            $assinatura[\'telefone\'],
            $assinatura[\'regiao\'],
            $assinatura[\'plano\'],
            $assinatura[\'valor\'],
            $assinatura[\'moeda\'],
            $assinatura[\'status\'],
            date(\'d/m/Y\', strtotime($assinatura[\'data_inicio\'])),
            date(\'d/m/Y\', strtotime($assinatura[\'data_fim\'])),
            round($dias_restantes)
        ]);
    }
    
    fclose($output);
    exit;
}

// Função para calcular receita convertendo todas as moedas para BRL
function calcularReceitaMesConvertida($pdo) {
    // Buscar taxas do banco
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = \'taxas_cambio\'");
    $taxas_json = $stmt->fetchColumn();
    
    $taxas_conversao = $taxas_json ? json_decode($taxas_json, true) : [
        \'USD\' => 5.00,
        \'EUR\' => 5.50,
        \'JPY\' => 0.033
    ];
    $taxas_conversao[\'BRL\'] = 1; // BRL sempre é 1
    
    $stmt = $pdo->query("
        SELECT moeda, SUM(valor) as total 
        FROM pagamentos 
        WHERE status = \'aprovado\' 
        AND MONTH(data_pagamento) = MONTH(CURDATE())
        GROUP BY moeda
    ");
    
    $total_brl = 0;
    
    while ($row = $stmt->fetch()) {
        $total_brl += $row[\'total\'] * $taxas_conversao[$row[\'moeda\']];
    }
    
    return $total_brl;
}

// Função para converter moeda
function converterMoeda($valor, $de_moeda, $para_moeda = \'BRL\') {
    global $pdo;
    
    // Buscar taxas do banco
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = \'taxas_cambio\'");
    $taxas_json = $stmt->fetchColumn();
    
    $taxas_conversao = $taxas_json ? json_decode($taxas_json, true) : [
        \'USD\' => 5.00,
        \'EUR\' => 5.50,
        \'JPY\' => 0.033
    ];
    $taxas_conversao[\'BRL\'] = 1;
    
    if ($de_moeda === $para_moeda) {
        return $valor;
    }
    
    // Converter para BRL primeiro, depois para a moeda desejada
    $valor_brl = $valor * $taxas_conversao[$de_moeda];
    return $valor_brl / $taxas_conversao[$para_moeda];
}

// Função para enviar notificações automáticas
function enviarNotificacoesAutomaticas() {
    $pdo = conectarDB();
    $enviados = 0;
    
    // 1. Notificar assinaturas expirando em 7 dias
    $stmt = $pdo->query("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.status = \'ativa\' 
        AND DATEDIFF(a.data_fim, CURDATE()) = 7
        AND NOT EXISTS (
            SELECT 1 FROM logs_email 
            WHERE usuario_id = u.id 
            AND tipo = \'aviso_expiracao\' 
            AND DATE(data_envio) = CURDATE()
        )
    ");
    
    while ($assinatura = $stmt->fetch()) {
        enviarEmailComLog($assinatura[\'email\'], \'aviso_expiracao\', [
            \'nome_usuario\' => $assinatura[\'nome_usuario\'],
            \'dias_restantes\' => 7,
            \'data_fim\' => date(\'d/m/Y\', strtotime($assinatura[\'data_fim\'])),
            \'plano\' => $assinatura[\'plano\']
        ], $assinatura[\'usuario_id\']);
        $enviados++;
    }
    
    // 2. Notificar assinaturas expirando em 3 dias
    $stmt = $pdo->query("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.status = \'ativa\' 
        AND DATEDIFF(a.data_fim, CURDATE()) = 3
    ");
    
    while ($assinatura = $stmt->fetch()) {
        enviarEmailComLog($assinatura[\'email\'], \'aviso_expiracao_urgente\', [
            \'nome_usuario\' => $assinatura[\'nome_usuario\'],
            \'dias_restantes\' => 3,
            \'data_fim\' => date(\'d/m/Y\', strtotime($assinatura[\'data_fim\'])),
            \'plano\' => $assinatura[\'plano\']
        ], $assinatura[\'usuario_id\']);
        $enviados++;
    }
    
    // 3. Notificar assinaturas expiradas
    $stmt = $pdo->query("
        SELECT a.*, u.email, u.nome_usuario 
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.status = \'ativa\' 
        AND a.data_fim < CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM logs_email 
            WHERE usuario_id = u.id 
            AND tipo = \'assinatura_expirada\' 
            AND DATE(data_envio) >= a.data_fim
        )
    ");
    
    while ($assinatura = $stmt->fetch()) {
        // Atualizar status para expirada
        $pdo->prepare("UPDATE assinaturas SET status = \'expirada\' WHERE id = ?")
            ->execute([$assinatura[\'id\']]);
        
        enviarEmailComLog($assinatura[\'email\'], \'assinatura_expirada\', [
            \'nome_usuario\' => $assinatura[\'nome_usuario\'],
            \'plano\' => $assinatura[\'plano\']
        ], $assinatura[\'usuario_id\']);
        $enviados++;
    }
    
    return $enviados;
}

// Incluir esta função no arquivo apropriado para processamento de exportação
if (isset($_GET[\'exportar\']) && $_GET[\'exportar\'] == \'1\') {
    exportarAssinaturas();
}
?>',

    // Outros arquivos já incluídos anteriormente...
    // Incluir todos os arquivos da lista original
];

// Criar cada arquivo
foreach ($arquivos as $arquivo => $conteudo) {
    if (file_put_contents($arquivo, $conteudo)) {
        echo "✅ Arquivo criado: <strong>$arquivo</strong><br>";
        $arquivos_criados++;
    } else {
        $erros[] = "Erro ao criar: $arquivo";
    }
}

// Exibir resultado
echo "<hr>";
echo "<h3>Resultado:</h3>";
echo "<p>✅ <strong>$arquivos_criados arquivos criados com sucesso!</strong></p>";

if (!empty($erros)) {
    echo "<h4>❌ Erros encontrados:</h4>";
    echo "<ul>";
    foreach ($erros as $erro) {
        echo "<li>$erro</li>";
    }
    echo "</ul>";
}

echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 0.5rem; margin-top: 2rem;'>";
echo "<h4>⚠️ IMPORTANTE:</h4>";
echo "<ol>";
echo "<li><strong>ALTERE A SENHA DO ADMIN</strong> em admin/index.php (linha 8)</li>";
echo "<li>Configure o arquivo config.php com os dados do banco de dados</li>";
echo "<li>Execute o script admin/atualizar-banco.php para criar as tabelas necessárias</li>";
echo "<li><strong style='color: red;'>DELETE ESTE ARQUIVO APÓS USAR!</strong></li>";
echo "</ol>";
echo "</div>";

echo "<p style='margin-top: 2rem;'>";
echo "<a href='admin/' style='background: #2563eb; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.5rem; display: inline-block;'>Acessar Painel Admin</a>";
echo "</p>";
?>