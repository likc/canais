<?php
// Gerenciamento de Assinaturas

// Filtros
$filtro_status = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';
$categoria = $_GET['categoria'] ?? '';

// Query base
$sql = "SELECT a.*, u.nome_usuario, u.email, u.telefone, u.regiao,
        DATEDIFF(a.data_fim, CURDATE()) as dias_restantes
        FROM assinaturas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE 1=1";
$params = [];

// Aplicar filtros
if ($filtro_status) {
    $sql .= " AND a.status = ?";
    $params[] = $filtro_status;
}

if ($busca) {
    $sql .= " AND (u.nome_usuario LIKE ? OR u.email LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

// Categorias especiais
switch ($categoria) {
    case 'ativas':
        $sql .= " AND a.status = 'ativa' AND a.data_fim >= CURDATE()";
        break;
    case 'expiradas':
        $sql .= " AND a.status = 'ativa' AND a.data_fim < CURDATE()";
        break;
    case 'expirando':
        $sql .= " AND a.status = 'ativa' AND DATEDIFF(a.data_fim, CURDATE()) BETWEEN 0 AND 7";
        break;
    case 'pendentes':
        $sql .= " AND a.status = 'pendente'";
        break;
}

$sql .= " ORDER BY a.data_criacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assinaturas = $stmt->fetchAll();

// Estatísticas
$stats_assinaturas = [
    'total' => $pdo->query("SELECT COUNT(*) FROM assinaturas")->fetchColumn(),
    'ativas' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'ativa' AND data_fim >= CURDATE()")->fetchColumn(),
    'expiradas' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'ativa' AND data_fim < CURDATE()")->fetchColumn(),
    'expirando' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'ativa' AND DATEDIFF(data_fim, CURDATE()) BETWEEN 0 AND 7")->fetchColumn(),
    'pendentes' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'pendente'")->fetchColumn(),
    'canceladas' => $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status = 'cancelada'")->fetchColumn()
];

// Ações em massa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_massa'])) {
    $ids = $_POST['assinatura_ids'] ?? [];
    $acao = $_POST['acao_massa'];
    
    if (!empty($ids)) {
        switch ($acao) {
            case 'enviar_email':
                $_SESSION['assinaturas_selecionadas'] = $ids;
                header('Location: ?acao=email-massa');
                exit;
                break;
            case 'exportar':
                exportarAssinaturas($ids);
                break;
        }
    }
}
?>

<h1>Gerenciar Assinaturas</h1>

<!-- Cards de Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--primary-color);">
            <i class="fas fa-credit-card"></i>
        </div>
        <div class="stat-content">
            <h3>Total</h3>
            <div class="stat-value"><?php echo number_format($stats_assinaturas['total'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--success-color);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3>Ativas</h3>
            <div class="stat-value"><?php echo number_format($stats_assinaturas['ativas'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--danger-color);">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-content">
            <h3>Expiradas</h3>
            <div class="stat-value"><?php echo number_format($stats_assinaturas['expiradas'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning-color);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3>Expirando (7 dias)</h3>
            <div class="stat-value"><?php echo number_format($stats_assinaturas['expirando'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f59e0b;">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-content">
            <h3>Pendentes</h3>
            <div class="stat-value"><?php echo number_format($stats_assinaturas['pendentes'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #64748b;">
            <i class="fas fa-ban"></i>
        </div>
        <div class="stat-content">
            <h3>Canceladas</h3>
            <div class="stat-value"><?php echo number_format($stats_assinaturas['canceladas'], 0, ',', '.'); ?></div>
        </div>
    </div>
</div>

<!-- Tabela de Assinaturas -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Lista de Assinaturas</h2>
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="acao" value="assinaturas">
                
                <!-- Filtro por categoria -->
                <select name="categoria">
                    <option value="">Todas as categorias</option>
                    <option value="ativas" <?php echo $categoria === 'ativas' ? 'selected' : ''; ?>>Ativas</option>
                    <option value="expiradas" <?php echo $categoria === 'expiradas' ? 'selected' : ''; ?>>Expiradas</option>
                    <option value="expirando" <?php echo $categoria === 'expirando' ? 'selected' : ''; ?>>Expirando (7 dias)</option>
                    <option value="pendentes" <?php echo $categoria === 'pendentes' ? 'selected' : ''; ?>>Pendentes</option>
                </select>
                
                <!-- Filtro por status -->
                <select name="status">
                    <option value="">Todos os status</option>
                    <option value="ativa" <?php echo $filtro_status === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                    <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="cancelada" <?php echo $filtro_status === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    <option value="expirada" <?php echo $filtro_status === 'expirada' ? 'selected' : ''; ?>>Expirada</option>
                </select>
                
                <input type="text" name="busca" placeholder="Buscar..." value="<?php echo htmlspecialchars($busca); ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    <form method="POST" id="form-assinaturas">
        <div style="padding: 1rem; background: var(--light-color); border-bottom: 1px solid var(--border-color);">
            <label style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="selecionar-todos">
                <span>Selecionar todos</span>
            </label>
            <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                <select name="acao_massa" class="btn btn-sm btn-outline">
                    <option value="">Ação em massa...</option>
                    <option value="enviar_email">Enviar email</option>
                    <option value="exportar">Exportar dados</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-play"></i> Executar
                </button>
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Período</th>
                        <th>Dias Restantes</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assinaturas as $assinatura): 
                        $status_class = match($assinatura['status']) {
                            'ativa' => 'success',
                            'pendente' => 'warning',
                            'cancelada' => 'danger',
                            'expirada' => 'danger',
                            default => 'info'
                        };
                        
                        // Verificar se está expirada
                        if ($assinatura['status'] === 'ativa' && strtotime($assinatura['data_fim']) < time()) {
                            $status_real = 'expirada';
                            $status_class = 'danger';
                        } else {
                            $status_real = $assinatura['status'];
                        }
                    ?>
                    <tr <?php echo $assinatura['dias_restantes'] <= 7 && $assinatura['dias_restantes'] >= 0 ? 'style="background: #fff3cd;"' : ''; ?>>
                        <td>
                            <input type="checkbox" name="assinatura_ids[]" value="<?php echo $assinatura['id']; ?>" class="assinatura-checkbox">
                        </td>
                        <td>#<?php echo $assinatura['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($assinatura['nome_usuario']); ?></strong><br>
                            <small><?php echo htmlspecialchars($assinatura['email']); ?></small>
                        </td>
                        <td><?php echo ucfirst($assinatura['plano']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo ucfirst($status_real); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($assinatura['data_inicio'])); ?><br>
                            até <?php echo date('d/m/Y', strtotime($assinatura['data_fim'])); ?>
                        </td>
                        <td>
                            <?php 
                            if ($assinatura['dias_restantes'] < 0) {
                                echo '<span class="badge badge-danger">Expirado há ' . abs($assinatura['dias_restantes']) . ' dias</span>';
                            } elseif ($assinatura['dias_restantes'] == 0) {
                                echo '<span class="badge badge-warning">Expira hoje!</span>';
                            } elseif ($assinatura['dias_restantes'] <= 7) {
                                echo '<span class="badge badge-warning">' . $assinatura['dias_restantes'] . ' dias</span>';
                            } else {
                                echo $assinatura['dias_restantes'] . ' dias';
                            }
                            ?>
                        </td>
                        <td><?php echo formatarMoeda($assinatura['valor'], $assinatura['moeda']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" onclick="verDetalhes(<?php echo $assinatura['id']; ?>)" 
                                        class="btn btn-sm btn-primary" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($assinatura['status'] === 'pendente'): ?>
                                <a href="?acao=ativar" class="btn btn-sm btn-success" title="Ativar">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                
                                <button type="button" onclick="enviarEmailIndividual(<?php echo $assinatura['usuario_id']; ?>)" 
                                        class="btn btn-sm btn-info" title="Enviar email">
                                    <i class="fas fa-envelope"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
// Selecionar todos
document.getElementById('selecionar-todos').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.assinatura-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Ver detalhes
function verDetalhes(id) {
    window.location.href = '?acao=usuarios&detalhes=' + id;
}

// Enviar email individual
function enviarEmailIndividual(usuarioId) {
    window.location.href = '?acao=email-massa&usuario=' + usuarioId;
}
</script>