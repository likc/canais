<?php
// Gerenciamento de Pagamentos

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_metodo = $_GET['metodo'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? 'mes';
$busca = $_GET['busca'] ?? '';

// Calcular período
$data_inicio = match($filtro_periodo) {
    'hoje' => date('Y-m-d'),
    'semana' => date('Y-m-d', strtotime('-7 days')),
    'mes' => date('Y-m-d', strtotime('-30 days')),
    'trimestre' => date('Y-m-d', strtotime('-90 days')),
    'ano' => date('Y-m-d', strtotime('-365 days')),
    default => date('Y-m-d', strtotime('-30 days'))
};

// Query base
$sql = "SELECT p.*, u.nome_usuario, u.email, a.plano 
        FROM pagamentos p 
        JOIN usuarios u ON p.usuario_id = u.id 
        LEFT JOIN assinaturas a ON p.assinatura_id = a.id 
        WHERE p.data_pagamento >= ?";
$params = [$data_inicio];

// Aplicar filtros
if ($filtro_status) {
    $sql .= " AND p.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_metodo) {
    $sql .= " AND p.metodo = ?";
    $params[] = $filtro_metodo;
}

if ($busca) {
    $sql .= " AND (u.nome_usuario LIKE ? OR u.email LIKE ? OR p.transacao_id LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY p.data_pagamento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pagamentos = $stmt->fetchAll();

// Estatísticas de pagamentos
$stats_pagamentos = [];

// Total por status
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as qtd, SUM(valor) as total 
    FROM pagamentos 
    WHERE data_pagamento >= ? 
    GROUP BY status
");
$stmt->execute([$data_inicio]);
$stats_por_status = $stmt->fetchAll();

// Total por método
$stmt = $pdo->prepare("
    SELECT metodo, COUNT(*) as qtd, SUM(valor) as total 
    FROM pagamentos 
    WHERE data_pagamento >= ? AND status = 'aprovado'
    GROUP BY metodo
");
$stmt->execute([$data_inicio]);
$stats_por_metodo = $stmt->fetchAll();

// Receita por moeda
$stmt = $pdo->prepare("
    SELECT moeda, SUM(valor) as total 
    FROM pagamentos 
    WHERE data_pagamento >= ? AND status = 'aprovado'
    GROUP BY moeda
");
$stmt->execute([$data_inicio]);
$receita_por_moeda = $stmt->fetchAll();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aprovar_pagamento'])) {
        $pagamento_id = intval($_POST['pagamento_id']);
        $pdo->prepare("UPDATE pagamentos SET status = 'aprovado' WHERE id = ?")->execute([$pagamento_id]);
        echo '<div class="alert alert-success">Pagamento aprovado!</div>';
    }
    
    if (isset($_POST['recusar_pagamento'])) {
        $pagamento_id = intval($_POST['pagamento_id']);
        $pdo->prepare("UPDATE pagamentos SET status = 'recusado' WHERE id = ?")->execute([$pagamento_id]);
        echo '<div class="alert alert-success">Pagamento recusado!</div>';
    }
}
?>

<h1>Gerenciar Pagamentos</h1>

<!-- Cards de Estatísticas -->
<div class="stats-grid">
    <?php foreach ($stats_por_status as $stat): 
        $icon = match($stat['status']) {
            'aprovado' => 'check-circle',
            'pendente' => 'clock',
            'recusado' => 'times-circle',
            default => 'question-circle'
        };
        $color = match($stat['status']) {
            'aprovado' => 'var(--success-color)',
            'pendente' => 'var(--warning-color)',
            'recusado' => 'var(--danger-color)',
            default => 'var(--info-color)'
        };
    ?>
    <div class="stat-card">
        <div class="stat-icon" style="background: <?php echo $color; ?>;">
            <i class="fas fa-<?php echo $icon; ?>"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo ucfirst($stat['status']); ?></h3>
            <div class="stat-value"><?php echo number_format($stat['qtd'], 0, ',', '.'); ?></div>
            <small>Total: R$ <?php echo number_format(converterMoeda($stat['total'], 'BRL', 'BRL'), 2, ',', '.'); ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gráficos de Receita -->
<div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Receita por Método -->
    <div class="data-table">
        <div class="table-header">
            <h3>Receita por Método de Pagamento</h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php foreach ($stats_por_metodo as $stat): 
                $metodo_nome = match($stat['metodo']) {
                    'pix' => 'PIX',
                    'cartao' => 'Cartão de Crédito',
                    'boleto' => 'Boleto Bancário',
                    'transferencia' => 'Transferência',
                    default => ucfirst($stat['metodo'])
                };
            ?>
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span><?php echo $metodo_nome; ?></span>
                    <strong>R$ <?php echo number_format(converterMoeda($stat['total'], 'BRL', 'BRL'), 2, ',', '.'); ?></strong>
                </div>
                <div style="background: var(--light-color); height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: var(--primary-color); height: 100%; width: <?php echo ($stat['total'] / array_sum(array_column($stats_por_metodo, 'total'))) * 100; ?>%;"></div>
                </div>
                <small><?php echo $stat['qtd']; ?> transações</small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Receita por Moeda -->
    <div class="data-table">
        <div class="table-header">
            <h3>Receita por Região/Moeda</h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php 
            global $PRECOS;
            $total_geral_brl = 0;
            foreach ($receita_por_moeda as $receita): 
                $simbolo = $PRECOS[$receita['moeda']]['simbolo'];
                $bandeira = match($receita['moeda']) {
                    'BRL' => '🇧🇷 Brasil',
                    'USD' => '🇺🇸 EUA',
                    'EUR' => '🇪🇺 Europa',
                    'JPY' => '🇯🇵 Japão',
                    default => $receita['moeda']
                };
                $valor_brl = converterMoeda($receita['total'], $receita['moeda'], 'BRL');
                $total_geral_brl += $valor_brl;
            ?>
            <div style="margin-bottom: 1rem; padding: 1rem; background: var(--light-color); border-radius: 0.5rem;">
                <h4><?php echo $bandeira; ?></h4>
                <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo $simbolo . ' ' . number_format($receita['total'], $receita['moeda'] === 'JPY' ? 0 : 2, ',', '.'); ?>
                </p>
                <small>Equivalente: R$ <?php echo number_format($valor_brl, 2, ',', '.'); ?></small>
            </div>
            <?php endforeach; ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <strong>Total em BRL: R$ <?php echo number_format($total_geral_brl, 2, ',', '.'); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Pagamentos -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Histórico de Pagamentos</h2>
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="acao" value="pagamentos">
                
                <!-- Período -->
                <select name="periodo">
                    <option value="hoje" <?php echo $filtro_periodo === 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                    <option value="semana" <?php echo $filtro_periodo === 'semana' ? 'selected' : ''; ?>>Última semana</option>
                    <option value="mes" <?php echo $filtro_periodo === 'mes' ? 'selected' : ''; ?>>Último mês</option>
                    <option value="trimestre" <?php echo $filtro_periodo === 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                    <option value="ano" <?php echo $filtro_periodo === 'ano' ? 'selected' : ''; ?>>Último ano</option>
                </select>
                
                <!-- Status -->
                <select name="status">
                    <option value="">Todos os status</option>
                    <option value="aprovado" <?php echo $filtro_status === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                    <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="recusado" <?php echo $filtro_status === 'recusado' ? 'selected' : ''; ?>>Recusado</option>
                </select>
                
                <!-- Método -->
                <select name="metodo">
                    <option value="">Todos os métodos</option>
                    <option value="pix" <?php echo $filtro_metodo === 'pix' ? 'selected' : ''; ?>>PIX</option>
                    <option value="cartao" <?php echo $filtro_metodo === 'cartao' ? 'selected' : ''; ?>>Cartão</option>
                    <option value="boleto" <?php echo $filtro_metodo === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                    <option value="transferencia" <?php echo $filtro_metodo === 'transferencia' ? 'selected' : ''; ?>>Transferência</option>
                </select>
                
                <input type="text" name="busca" placeholder="Buscar..." value="<?php echo htmlspecialchars($busca); ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i>
                </button>
                
                <button type="button" onclick="exportarRelatorio()" class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </form>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Usuário</th>
                    <th>Plano</th>
                    <th>Valor</th>
                    <th>Valor em BRL</th>
                    <th>Método</th>
                    <th>Status</th>
                    <th>Transação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagamentos as $pagamento): 
                    $status_class = match($pagamento['status']) {
                        'aprovado' => 'success',
                        'pendente' => 'warning',
                        'recusado' => 'danger',
                        default => 'info'
                    };
                ?>
                <tr>
                    <td>#<?php echo $pagamento['id']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($pagamento['nome_usuario']); ?></strong><br>
                        <small><?php echo htmlspecialchars($pagamento['email']); ?></small>
                    </td>
                    <td><?php echo ucfirst($pagamento['plano'] ?? '-'); ?></td>
                    <td>
                        <strong><?php echo formatarMoeda($pagamento['valor'], $pagamento['moeda']); ?></strong>
                    </td>
                    <td>
                        R$ <?php echo number_format(converterMoeda($pagamento['valor'], $pagamento['moeda'], 'BRL'), 2, ',', '.'); ?>
                    </td>
                    <td>
                        <span class="badge badge-info">
                            <?php 
                            echo match($pagamento['metodo']) {
                                'pix' => 'PIX',
                                'cartao' => 'Cartão',
                                'boleto' => 'Boleto',
                                'transferencia' => 'Transferência',
                                default => ucfirst($pagamento['metodo'])
                            };
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $status_class; ?>">
                            <?php echo ucfirst($pagamento['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($pagamento['transacao_id']): ?>
                            <code style="font-size: 0.75rem;"><?php echo $pagamento['transacao_id']; ?></code>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($pagamento['status'] === 'pendente'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="pagamento_id" value="<?php echo $pagamento['id']; ?>">
                                <button type="submit" name="aprovar_pagamento" class="btn btn-sm btn-success" 
                                        onclick="return confirm('Aprovar este pagamento?')" title="Aprovar">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="pagamento_id" value="<?php echo $pagamento['id']; ?>">
                                <button type="submit" name="recusar_pagamento" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Recusar este pagamento?')" title="Recusar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <button type="button" onclick="verDetalhes(<?php echo $pagamento['id']; ?>)" 
                                    class="btn btn-sm btn-primary" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Resumo Financeiro -->
<div style="margin-top: 2rem; background: var(--primary-color); color: white; padding: 2rem; border-radius: 0.75rem;">
    <h3>Resumo do Período (em BRL)</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 1rem;">
        <?php 
        $total_aprovado_brl = 0;
        foreach ($stats_por_status as $stat) {
            if ($stat['status'] === 'aprovado') {
                // Aqui precisaríamos saber a moeda de cada pagamento para converter corretamente
                // Por simplicidade, assumindo que já está em BRL
                $total_aprovado_brl = converterMoeda($stat['total'], 'BRL', 'BRL');
            }
        }
        ?>
        <div>
            <h4>Total Aprovado</h4>
            <p style="font-size: 2rem; font-weight: bold;">R$ <?php echo number_format($total_geral_brl, 2, ',', '.'); ?></p>
        </div>
        <div>
            <h4>Transações</h4>
            <p style="font-size: 2rem; font-weight: bold;"><?php echo count($pagamentos); ?></p>
        </div>
        <div>
            <h4>Ticket Médio</h4>
            <p style="font-size: 2rem; font-weight: bold;">
                R$ <?php echo count($pagamentos) > 0 ? number_format($total_geral_brl / count($pagamentos), 2, ',', '.') : '0,00'; ?>
            </p>
        </div>
    </div>
</div>

<script>
function verDetalhes(id) {
    // Implementar modal de detalhes
    alert('Detalhes do pagamento #' + id);
}

function exportarRelatorio() {
    const params = new URLSearchParams(window.location.search);
    params.set('exportar', '1');
    window.location.href = '?' + params.toString();
}
</script>