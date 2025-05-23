<?php
// Painel Administrativo - Canais.net
require_once '../config.php';

// Verificar senha admin
session_start();

// ALTERE ESTA SENHA!
$senha_admin = 'SuaSenhaSegura123!';

// Verificar se está logado como admin
if (!isset($_SESSION['admin_logado'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
        if ($_POST['senha'] === $senha_admin) {
            $_SESSION['admin_logado'] = true;
        } else {
            $erro = 'Senha incorreta!';
        }
    }
    
    if (!isset($_SESSION['admin_logado'])) {
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

// Dashboard stats
$stats = [
    'usuarios_total' => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'usuarios_hoje' => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE DATE(data_cadastro) = CURDATE()")->fetchColumn(),
    'assinaturas_ativas' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'ativa' AND data_fim >= CURDATE()")->fetchColumn(),
    'assinaturas_pendentes' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'pendente'")->fetchColumn(),
    'receita_mes' => $pdo->query("SELECT SUM(valor) FROM pagamentos WHERE status = 'aprovado' AND MONTH(data_pagamento) = MONTH(CURDATE())")->fetchColumn() ?: 0,
    'emails_hoje' => $pdo->query("SELECT COUNT(*) FROM logs_email WHERE DATE(data_envio) = CURDATE()")->fetchColumn()
];

// Ação selecionada
$acao = $_GET['acao'] ?? 'dashboard';
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
                <a href="?acao=dashboard" class="<?php echo $acao === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="?acao=usuarios" class="<?php echo $acao === 'usuarios' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Usuários
                </a>
                <a href="?acao=assinaturas" class="<?php echo $acao === 'assinaturas' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> Assinaturas
                </a>
                <a href="?acao=pagamentos" class="<?php echo $acao === 'pagamentos' ? 'active' : ''; ?>">
                    <i class="fas fa-dollar-sign"></i> Pagamentos
                </a>
                <a href="?acao=emails" class="<?php echo $acao === 'emails' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Logs de Email
                </a>
                <a href="?acao=ativar" class="<?php echo $acao === 'ativar' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Ativar Assinaturas
                </a>
                <a href="?acao=configuracoes" class="<?php echo $acao === 'configuracoes' ? 'active' : ''; ?>">
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
                case 'dashboard':
                    include 'dashboard.php';
                    break;
                case 'usuarios':
                    include 'usuarios.php';
                    break;
                case 'assinaturas':
                    include 'assinaturas.php';
                    break;
                case 'pagamentos':
                    include 'pagamentos.php';
                    break;
                case 'emails':
                    include 'emails.php';
                    break;
                case 'ativar':
                    include 'ativar.php';
                    break;
                case 'configuracoes':
                    include 'configuracoes.php';
                    break;
                default:
                    include 'dashboard.php';
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
            document.getElementById(id).style.display = 'flex';
        }

        function fecharModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>