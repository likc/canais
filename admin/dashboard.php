<?php
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
            <div class="stat-value"><?php echo number_format($stats['usuarios_total'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--success-color);">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-content">
            <h3>Cadastros Hoje</h3>
            <div class="stat-value"><?php echo number_format($stats['usuarios_hoje'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--secondary-color);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3>Assinaturas Ativas</h3>
            <div class="stat-value"><?php echo number_format($stats['assinaturas_ativas'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning-color);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3>Pendentes</h3>
            <div class="stat-value"><?php echo number_format($stats['assinaturas_pendentes'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #10b981;">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <h3>Receita do Mês</h3>
            <div class="stat-value">R$ <?php echo number_format($stats['receita_mes'], 2, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #8b5cf6;">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-content">
            <h3>Emails Hoje</h3>
            <div class="stat-value"><?php echo number_format($stats['emails_hoje'], 0, ',', '.'); ?></div>
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
                    <td><?php echo htmlspecialchars($usuario['nome_usuario']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php 
                            $bandeiras = ['BRL' => '🇧🇷', 'USD' => '🇺🇸', 'EUR' => '🇪🇺', 'JPY' => '🇯🇵'];
                            echo $bandeiras[$usuario['regiao']] . ' ' . $usuario['regiao'];
                            ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
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
                    WHERE a.status = 'pendente' 
                    ORDER BY a.data_criacao DESC 
                    LIMIT 5
                ")->fetchAll();
                
                foreach ($pendentes as $pendente):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($pendente['nome_usuario']); ?></td>
                    <td><?php echo ucfirst($pendente['plano']); ?></td>
                    <td><?php echo formatarMoeda($pendente['valor'], $pendente['moeda']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($pendente['data_criacao'])); ?></td>
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

<!-- Gráficos (placeholder) -->
<div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="data-table">
        <div class="table-header">
            <h3>Receita por Mês</h3>
        </div>
        <div style="padding: 1.5rem; text-align: center; color: var(--text-light);">
            <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>Gráfico em desenvolvimento</p>
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
                $bandeiras = ['BRL' => '🇧🇷 Brasil', 'USD' => '🇺🇸 EUA', 'EUR' => '🇪🇺 Europa', 'JPY' => '🇯🇵 Japão'];
            ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span><?php echo $bandeiras[$reg['regiao']]; ?></span>
                    <strong><?php echo $reg['total']; ?></strong>
                </div>
                <div style="background: var(--light-color); height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: var(--primary-color); height: 100%; width: <?php echo ($reg['total'] / $stats['usuarios_total']) * 100; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>