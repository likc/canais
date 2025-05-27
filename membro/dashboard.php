<?php
require_once 'config.php';

// Verificar se está logado
verificarLogin();

// Obter dados do usuário
$usuario = obterUsuario($_SESSION['usuario_id']);
$assinatura = verificarAssinaturaAtiva($_SESSION['usuario_id']);

// ===== ADICIONAR ESTE BLOCO PARA VERIFICAR SE É ADMIN =====
// Verificar se é admin
$pdo = conectarDB(); // ADICIONAR ESTA LINHA ANTES DE USAR $pdo
$stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$is_admin = $stmt->fetchColumn();

// Se for admin, salvar na sessão
if ($is_admin) {
    $_SESSION['is_admin'] = 1;
}
// ===== FIM DO BLOCO ADMIN =====

// Se não tiver assinatura ativa, redireciona para pagamento
if (!$assinatura) {
    header('Location: pagamento.php');
    exit;
}

// Calcular dias restantes
$dias_restantes = calcularDiasRestantes($assinatura['data_fim']);

// Obter histórico de pagamentos
// $pdo = conectarDB(); // Esta linha já não é mais necessária pois já conectamos acima
$stmt = $pdo->prepare("
    SELECT * FROM pagamentos 
    WHERE usuario_id = ? 
    ORDER BY data_pagamento DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['usuario_id']]);
$pagamentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" class="sidebar-logo">
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i> Início
                </a>
                <a href="#instrucoes" class="nav-item" onclick="mostrarSecao('instrucoes')">
                    <i class="fas fa-book"></i> Instruções
                </a>
                <a href="#assinatura" class="nav-item" onclick="mostrarSecao('assinatura')">
                    <i class="fas fa-credit-card"></i> Assinatura
                </a>
                <a href="#pagamentos" class="nav-item" onclick="mostrarSecao('pagamentos')">
                    <i class="fas fa-history"></i> Pagamentos
                </a>
                <a href="#perfil" class="nav-item" onclick="mostrarSecao('perfil')">
                    <i class="fas fa-user"></i> Perfil
                </a>
                
                <?php // ===== ADICIONAR ESTE BLOCO PARA MOSTRAR LINK DO ADMIN ===== ?>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 1rem 0;">
                <a href="<?php echo SITE_URL; ?>/membro/admin/" class="nav-item" style="background: rgba(255, 193, 7, 0.2); color: #ffc107;">
                    <i class="fas fa-user-shield"></i> Painel Admin
                </a>
                <?php endif; ?>
                <?php // ===== FIM DO BLOCO LINK ADMIN ===== ?>
                
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="https://wa.me/819042662408" target="_blank" class="support-link">
                    <i class="fab fa-whatsapp"></i> Suporte 24/7
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="content-header">
                <h1>Olá, <?php echo htmlspecialchars($usuario['nome_usuario']); ?>!</h1>
                <div class="header-actions">
                    <span class="region-badge">
                        <?php 
                        $bandeiras = ['BRL' => '🇧🇷', 'USD' => '🇺🇸', 'EUR' => '🇪🇺', 'JPY' => '🇯🇵'];
                        echo $bandeiras[$usuario['regiao']] . ' ' . $usuario['regiao'];
                        ?>
                    </span>
                    
                    <?php // ===== ADICIONAR BADGE DE ADMIN NO HEADER ===== ?>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                    <span class="badge" style="background: var(--warning-color); color: white; margin-left: 0.5rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                        <i class="fas fa-crown"></i> Admin
                    </span>
                    <?php endif; ?>
                    <?php // ===== FIM DO BADGE ADMIN ===== ?>
                </div>
            </header>

            <!-- Mensagens -->
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    switch($_GET['sucesso']) {
                        case 'perfil': echo 'Perfil atualizado com sucesso!'; break;
                        default: echo 'Operação realizada com sucesso!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Overview -->
            <section id="inicio" class="content-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #4caf50;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Status da Assinatura</h3>
                            <p class="stat-value" style="color: #4caf50;">Ativa</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2196f3;">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Plano Atual</h3>
                            <p class="stat-value"><?php echo ucfirst($assinatura['plano']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ff9800;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Dias Restantes</h3>
                            <p class="stat-value"><?php echo $dias_restantes; ?> dias</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #9c27b0;">
                            <i class="fas fa-tv"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Dispositivos</h3>
                            <p class="stat-value">1 tela</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2>Ações Rápidas</h2>
                    <div class="action-grid">
                        <button class="action-card" onclick="mostrarSecao('instrucoes')">
                            <i class="fas fa-download"></i>
                            <span>Baixar App</span>
                        </button>
                        <button class="action-card" onclick="mostrarSecao('assinatura')">
                            <i class="fas fa-sync"></i>
                            <span>Renovar Plano</span>
                        </button>
                        <button class="action-card" onclick="window.open('https://wa.me/819042662408', '_blank')">
                            <i class="fab fa-whatsapp"></i>
                            <span>Suporte</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Instruções -->
            <section id="instrucoes" class="content-section" style="display: none;">
                <h2><i class="fas fa-book"></i> Como usar nosso sistema IPTV</h2>
                
                <div class="instructions-container">
                    <div class="instruction-card">
                        <div class="instruction-header">
                            <h3>1. Baixe o Aplicativo</h3>
                        </div>
                        <div class="instruction-content">
                            <p>Baixe o aplicativo <strong>IPTV Smarters Pro</strong> no seu dispositivo:</p>
                            <div class="download-links">
                                <a href="#" class="download-btn">
                                    <i class="fab fa-google-play"></i> Google Play
                                </a>
                                <a href="#" class="download-btn">
                                    <i class="fab fa-app-store"></i> App Store
                                </a>
                                <a href="#" class="download-btn">
                                    <i class="fas fa-tv"></i> Smart TV
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="instruction-card">
                        <div class="instruction-header">
                            <h3>2. Configure o Aplicativo</h3>
                        </div>
                        <div class="instruction-content">
                            <p>Ao abrir o app, selecione <strong>"Login com Xtream Codes API"</strong></p>
                            <div class="credentials-box">
                                <div class="credential-item">
                                    <label>Nome:</label>
                                    <span>Qualquer nome (ex: Canais TV)</span>
                                </div>
                                <div class="credential-item">
                                    <label>Usuário:</label>
                                    <span>Será enviado por email</span>
                                </div>
                                <div class="credential-item">
                                    <label>Senha:</label>
                                    <span>Será enviada por email</span>
                                </div>
                                <div class="credential-item">
                                    <label>URL:</label>
                                    <span>http://dns.appcanais.net:80</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="instruction-card">
                        <div class="instruction-header">
                            <h3>3. Comece a Assistir!</h3>
                        </div>
                        <div class="instruction-content">
                            <p>Após fazer login, você terá acesso a:</p>
                            <ul>
                                <li><i class="fas fa-check"></i> Mais de 5.000 canais ao vivo</li>
                                <li><i class="fas fa-check"></i> Filmes e séries on demand</li>
                                <li><i class="fas fa-check"></i> Canais em HD e 4K</li>
                                <li><i class="fas fa-check"></i> Guia de programação (EPG)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="video-tutorial">
                    <h3><i class="fas fa-video"></i> Tutorial em Vídeo</h3>
                    <div class="video-placeholder">
                        <i class="fas fa-play-circle"></i>
                        <p>Vídeo tutorial disponível em breve</p>
                    </div>
                </div>
            </section>

            <!-- Assinatura -->
            <section id="assinatura" class="content-section" style="display: none;">
                <h2><i class="fas fa-credit-card"></i> Detalhes da Assinatura</h2>
                
                <div class="subscription-info">
                    <div class="info-card">
                        <h3>Informações do Plano</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Plano:</label>
                                <span><?php echo ucfirst($assinatura['plano']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Valor:</label>
                                <span><?php echo formatarMoeda($assinatura['valor'], $assinatura['moeda']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Início:</label>
                                <span><?php echo date('d/m/Y', strtotime($assinatura['data_inicio'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Vencimento:</label>
                                <span><?php echo date('d/m/Y', strtotime($assinatura['data_fim'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Status:</label>
                                <span class="status-badge status-active">Ativa</span>
                            </div>
                            <div class="info-item">
                                <label>Dias restantes:</label>
                                <span><?php echo $dias_restantes; ?> dias</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($dias_restantes <= 7): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Sua assinatura vence em <?php echo $dias_restantes; ?> dias. Renove agora para não perder acesso!
                    </div>
                    <?php endif; ?>

                    <div class="renewal-section">
                        <h3>Renovar Assinatura</h3>
                        <p>Escolha um plano para renovar sua assinatura:</p>
                        
                        <div class="plan-grid">
                            <?php 
                            global $PRECOS;
                            $moeda = $usuario['regiao'];
                            $planos = ['mensal', 'semestral', 'anual'];
                            $descontos = ['mensal' => '', 'semestral' => '1 mês grátis', 'anual' => '2 meses grátis'];
                            
                            foreach ($planos as $plano): 
                            ?>
                            <div class="plan-card <?php echo $plano === $assinatura['plano'] ? 'current' : ''; ?>">
                                <h4><?php echo ucfirst($plano); ?></h4>
                                <div class="plan-price">
                                    <?php echo formatarMoeda($PRECOS[$moeda][$plano], $moeda); ?>
                                </div>
                                <?php if ($descontos[$plano]): ?>
                                    <div class="plan-discount"><?php echo $descontos[$plano]; ?></div>
                                <?php endif; ?>
                                <a href="renovar.php?plano=<?php echo $plano; ?>" class="btn btn-primary btn-block">
                                    Renovar
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Histórico de Pagamentos -->
            <section id="pagamentos" class="content-section" style="display: none;">
                <h2><i class="fas fa-history"></i> Histórico de Pagamentos</h2>
                
                <?php if (empty($pagamentos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>Nenhum pagamento encontrado</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>ID Transação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagamentos as $pagamento): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])); ?></td>
                                    <td><?php echo formatarMoeda($pagamento['valor'], $pagamento['moeda']); ?></td>
                                    <td><?php echo ucfirst($pagamento['metodo']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $pagamento['status']; ?>">
                                            <?php echo ucfirst($pagamento['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $pagamento['transacao_id'] ?: '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Perfil -->
            <section id="perfil" class="content-section" style="display: none;">
                <h2><i class="fas fa-user"></i> Meu Perfil</h2>
                
                <div class="profile-container">
                    <form action="processar.php" method="POST" class="profile-form">
                        <input type="hidden" name="acao" value="atualizar_perfil">
                        
                        <div class="form-group">
                            <label>Nome de Usuário</label>
                            <input type="text" value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" disabled>
                            <small class="form-hint">O nome de usuário não pode ser alterado</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                            <small class="form-hint">O email não pode ser alterado</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="perfil-telefone">
                                <i class="fab fa-whatsapp"></i> Telefone/WhatsApp
                            </label>
                            <input type="tel" id="perfil-telefone" name="telefone" 
                                   value="<?php echo htmlspecialchars($usuario['telefone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="perfil-regiao">
                                <i class="fas fa-globe"></i> Região
                            </label>
                            <select id="perfil-regiao" name="regiao" required>
                                <option value="BRL" <?php echo $usuario['regiao'] === 'BRL' ? 'selected' : ''; ?>>
                                    🇧🇷 Brasil (R$)
                                </option>
                                <option value="USD" <?php echo $usuario['regiao'] === 'USD' ? 'selected' : ''; ?>>
                                    🇺🇸 Estados Unidos ($)
                                </option>
                                <option value="EUR" <?php echo $usuario['regiao'] === 'EUR' ? 'selected' : ''; ?>>
                                    🇪🇺 Europa (€)
                                </option>
                                <option value="JPY" <?php echo $usuario['regiao'] === 'JPY' ? 'selected' : ''; ?>>
                                    🇯🇵 Japão (¥)
                                </option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </form>

                    <div class="danger-zone">
                        <h3>Zona de Perigo</h3>
                        <p>Ações irreversíveis para sua conta</p>
                        <button class="btn btn-danger" disabled>
                            <i class="fas fa-trash"></i> Excluir Conta
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Função para mostrar seção
        function mostrarSecao(secao) {
            // Esconder todas as seções
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Remover active de todos os nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Mostrar seção selecionada
            document.getElementById(secao).style.display = 'block';
            
            // Adicionar active ao nav item correspondente
            document.querySelector(`a[href="#${secao}"]`).classList.add('active');
            
            // Salvar no histórico
            history.pushState(null, null, `#${secao}`);
        }

        // Verificar hash na URL ao carregar
        window.addEventListener('load', function() {
            const hash = window.location.hash.slice(1);
            if (hash && document.getElementById(hash)) {
                mostrarSecao(hash);
            }
        });

        // Lidar com botão voltar do navegador
        window.addEventListener('popstate', function() {
            const hash = window.location.hash.slice(1);
            if (hash && document.getElementById(hash)) {
                mostrarSecao(hash);
            } else {
                mostrarSecao('inicio');
            }
        });

        // Máscara para telefone
        document.getElementById('perfil-telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 6) {
                value = `(${value.slice(0,2)}) ${value.slice(2,7)}-${value.slice(7)}`;
            } else if (value.length > 2) {
                value = `(${value.slice(0,2)}) ${value.slice(2)}`;
            } else if (value.length > 0) {
                value = `(${value}`;
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>