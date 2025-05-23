<?php
// Gerenciamento de Emails e Logs

// Verificar se é envio em massa
$tab = $_GET['tab'] ?? 'logs';

// Processar envio de email em massa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_massa'])) {
    $assunto = $_POST['assunto'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';
    $destinatarios = $_POST['destinatarios'] ?? 'todos';
    $categoria = $_POST['categoria'] ?? '';
    
    if ($assunto && $conteudo) {
        $resultado = enviarEmailMassa($assunto, $conteudo, $destinatarios, $categoria);
        echo '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> ' . $resultado['mensagem'] . '
              </div>';
    }
}

// Função para enviar email em massa
function enviarEmailMassa($assunto, $conteudo, $destinatarios, $categoria) {
    $pdo = conectarDB();
    
    // Determinar lista de emails
    $sql = "SELECT DISTINCT u.id, u.email, u.nome_usuario FROM usuarios u ";
    $params = [];
    
    switch ($destinatarios) {
        case 'ativos':
            $sql .= "JOIN assinaturas a ON u.id = a.usuario_id 
                     WHERE a.status = 'ativa' AND a.data_fim >= CURDATE()";
            break;
        case 'expirados':
            $sql .= "JOIN assinaturas a ON u.id = a.usuario_id 
                     WHERE a.status = 'ativa' AND a.data_fim < CURDATE()";
            break;
        case 'expirando':
            $sql .= "JOIN assinaturas a ON u.id = a.usuario_id 
                     WHERE a.status = 'ativa' AND DATEDIFF(a.data_fim, CURDATE()) BETWEEN 0 AND 7";
            break;
        case 'pendentes':
            $sql .= "JOIN assinaturas a ON u.id = a.usuario_id 
                     WHERE a.status = 'pendente'";
            break;
        case 'sem_assinatura':
            $sql .= "LEFT JOIN assinaturas a ON u.id = a.usuario_id 
                     WHERE a.id IS NULL";
            break;
        case 'selecionados':
            if (isset($_SESSION['assinaturas_selecionadas'])) {
                $ids = $_SESSION['assinaturas_selecionadas'];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= "JOIN assinaturas a ON u.id = a.usuario_id 
                         WHERE a.id IN ($placeholders)";
                $params = $ids;
                unset($_SESSION['assinaturas_selecionadas']);
            }
            break;
    }
    
    $sql .= " AND u.status = 'ativo'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll();
    
    $enviados = 0;
    $falhas = 0;
    
    // Template do email
    $template = templateBase($conteudo);
    
    foreach ($usuarios as $usuario) {
        // Substituir variáveis
        $corpo_final = str_replace(
            ['{nome}', '{email}'],
            [$usuario['nome_usuario'], $usuario['email']],
            $template
        );
        
        $enviado = enviarEmailComLog($usuario['email'], 'boletim', [
            'assunto_custom' => $assunto,
            'corpo_custom' => $corpo_final
        ], $usuario['id']);
        
        if ($enviado) {
            $enviados++;
        } else {
            $falhas++;
        }
    }
    
    return [
        'mensagem' => "Emails enviados: $enviados | Falhas: $falhas",
        'enviados' => $enviados,
        'falhas' => $falhas
    ];
}

// Buscar logs de email
if ($tab === 'logs') {
    $filtro_tipo = $_GET['tipo'] ?? '';
    $filtro_status = $_GET['status'] ?? '';
    $busca = $_GET['busca'] ?? '';
    
    $sql = "SELECT l.*, u.nome_usuario 
            FROM logs_email l 
            LEFT JOIN usuarios u ON l.usuario_id = u.id 
            WHERE 1=1";
    $params = [];
    
    if ($filtro_tipo) {
        $sql .= " AND l.tipo = ?";
        $params[] = $filtro_tipo;
    }
    
    if ($filtro_status) {
        $sql .= " AND l.enviado = ?";
        $params[] = $filtro_status === 'enviado' ? 1 : 0;
    }
    
    if ($busca) {
        $sql .= " AND (l.destinatario LIKE ? OR u.nome_usuario LIKE ?)";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }
    
    $sql .= " ORDER BY l.data_envio DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}

// Estatísticas de email
$stats_email = [
    'hoje' => $pdo->query("SELECT COUNT(*) FROM logs_email WHERE DATE(data_envio) = CURDATE()")->fetchColumn(),
    'enviados' => $pdo->query("SELECT COUNT(*) FROM logs_email WHERE enviado = 1 AND DATE(data_envio) = CURDATE()")->fetchColumn(),
    'falhas' => $pdo->query("SELECT COUNT(*) FROM logs_email WHERE enviado = 0 AND DATE(data_envio) = CURDATE()")->fetchColumn(),
    'total_mes' => $pdo->query("SELECT COUNT(*) FROM logs_email WHERE MONTH(data_envio) = MONTH(CURDATE())")->fetchColumn()
];
?>

<h1>Gerenciar Emails</h1>

<!-- Tabs -->
<div class="tabs-container">
    <a href="?acao=emails&tab=logs" class="tab-button <?php echo $tab === 'logs' ? 'active' : ''; ?>">
        <i class="fas fa-history"></i> Logs de Email
    </a>
    <a href="?acao=emails&tab=massa" class="tab-button <?php echo $tab === 'massa' ? 'active' : ''; ?>">
        <i class="fas fa-envelope-open-text"></i> Email em Massa
    </a>
    <a href="?acao=emails&tab=boletim" class="tab-button <?php echo $tab === 'boletim' ? 'active' : ''; ?>">
        <i class="fas fa-newspaper"></i> Boletim Informativo
    </a>
    <a href="?acao=emails&tab=templates" class="tab-button <?php echo $tab === 'templates' ? 'active' : ''; ?>">
        <i class="fas fa-file-alt"></i> Templates
    </a>
</div>

<?php if ($tab === 'logs'): ?>
<!-- Logs de Email -->
<div class="stats-grid" style="margin-top: 2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--primary-color);">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-content">
            <h3>Emails Hoje</h3>
            <div class="stat-value"><?php echo number_format($stats_email['hoje'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--success-color);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3>Enviados com Sucesso</h3>
            <div class="stat-value"><?php echo number_format($stats_email['enviados'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--danger-color);">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-content">
            <h3>Falhas</h3>
            <div class="stat-value"><?php echo number_format($stats_email['falhas'], 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--secondary-color);">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="stat-content">
            <h3>Total no Mês</h3>
            <div class="stat-value"><?php echo number_format($stats_email['total_mes'], 0, ',', '.'); ?></div>
        </div>
    </div>
</div>

<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Histórico de Emails</h2>
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="acao" value="emails">
                <input type="hidden" name="tab" value="logs">
                
                <select name="tipo">
                    <option value="">Todos os tipos</option>
                    <option value="cadastro" <?php echo $filtro_tipo === 'cadastro' ? 'selected' : ''; ?>>Cadastro</option>
                    <option value="recuperar_senha" <?php echo $filtro_tipo === 'recuperar_senha' ? 'selected' : ''; ?>>Recuperar Senha</option>
                    <option value="pagamento_pendente" <?php echo $filtro_tipo === 'pagamento_pendente' ? 'selected' : ''; ?>>Pagamento Pendente</option>
                    <option value="assinatura_ativada" <?php echo $filtro_tipo === 'assinatura_ativada' ? 'selected' : ''; ?>>Assinatura Ativada</option>
                    <option value="boletim" <?php echo $filtro_tipo === 'boletim' ? 'selected' : ''; ?>>Boletim</option>
                </select>
                
                <select name="status">
                    <option value="">Todos os status</option>
                    <option value="enviado" <?php echo $filtro_status === 'enviado' ? 'selected' : ''; ?>>Enviados</option>
                    <option value="falha" <?php echo $filtro_status === 'falha' ? 'selected' : ''; ?>>Falhas</option>
                </select>
                
                <input type="text" name="busca" placeholder="Buscar..." value="<?php echo htmlspecialchars($busca); ?>">
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
                    <th>Data/Hora</th>
                    <th>Destinatário</th>
                    <th>Usuário</th>
                    <th>Tipo</th>
                    <th>Assunto</th>
                    <th>Status</th>
                    <th>Erro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($log['data_envio'])); ?></td>
                    <td><?php echo htmlspecialchars($log['destinatario']); ?></td>
                    <td><?php echo htmlspecialchars($log['nome_usuario'] ?? '-'); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php 
                            $tipos = [
                                'cadastro' => 'Cadastro',
                                'recuperar_senha' => 'Senha',
                                'pagamento_pendente' => 'Pag. Pendente',
                                'assinatura_ativada' => 'Ativação',
                                'boletim' => 'Boletim'
                            ];
                            echo $tipos[$log['tipo']] ?? $log['tipo'];
                            ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($log['assunto']); ?></td>
                    <td>
                        <?php if ($log['enviado']): ?>
                            <span class="badge badge-success">Enviado</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Falha</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$log['enviado'] && $log['erro']): ?>
                            <small><?php echo htmlspecialchars($log['erro']); ?></small>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$log['enviado']): ?>
                        <button class="btn btn-sm btn-warning" onclick="reenviarEmail(<?php echo $log['id']; ?>)">
                            <i class="fas fa-redo"></i> Reenviar
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'massa'): ?>
<!-- Email em Massa -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Enviar Email em Massa</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <div class="form-group">
                <label>Destinatários:</label>
                <select name="destinatarios" required onchange="mostrarEstatisticas(this.value)">
                    <option value="">Selecione...</option>
                    <option value="todos">Todos os usuários ativos</option>
                    <option value="ativos">Clientes com assinatura ativa</option>
                    <option value="expirados">Clientes com assinatura expirada</option>
                    <option value="expirando">Clientes expirando (7 dias)</option>
                    <option value="pendentes">Clientes com pagamento pendente</option>
                    <option value="sem_assinatura">Usuários sem assinatura</option>
                    <?php if (isset($_SESSION['assinaturas_selecionadas'])): ?>
                    <option value="selecionados">Usuários selecionados (<?php echo count($_SESSION['assinaturas_selecionadas']); ?>)</option>
                    <?php endif; ?>
                </select>
                <div id="estatisticas-destinatarios" style="margin-top: 0.5rem; color: var(--text-light);"></div>
            </div>
            
            <div class="form-group">
                <label>Assunto:</label>
                <input type="text" name="assunto" required placeholder="Digite o assunto do email">
            </div>
            
            <div class="form-group">
                <label>Conteúdo do Email:</label>
                <div style="margin-bottom: 0.5rem;">
                    <small>Variáveis disponíveis: {nome}, {email}</small>
                </div>
                <textarea name="conteudo" rows="10" required style="width: 100%; font-family: monospace;">
<h2>Olá {nome}!</h2>

<p>Digite aqui o conteúdo do seu email...</p>

<p>Atenciosamente,<br>
Equipe Canais.net</p>
                </textarea>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" name="enviar_massa" class="btn btn-primary" 
                        onclick="return confirm('Confirma o envio de email em massa?')">
                    <i class="fas fa-paper-plane"></i> Enviar Emails
                </button>
                <button type="button" class="btn btn-outline" onclick="previewEmail()">
                    <i class="fas fa-eye"></i> Pré-visualizar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Preview -->
<div id="modal-preview" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Pré-visualização do Email</h3>
            <button class="close-modal" onclick="fecharModal('modal-preview')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="preview-content" style="border: 1px solid var(--border-color); padding: 1rem; margin-top: 1rem;">
            <!-- Preview será inserido aqui -->
        </div>
    </div>
</div>

<?php elseif ($tab === 'boletim'): ?>
<!-- Boletim Informativo -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Criar Boletim Informativo</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <input type="hidden" name="destinatarios" value="ativos">
            
            <div class="form-group">
                <label>Título do Boletim:</label>
                <input type="text" name="assunto" required 
                       placeholder="Ex: Novidades de Janeiro - Canais.net">
            </div>
            
            <div class="form-group">
                <label>Conteúdo do Boletim:</label>
                <div class="editor-toolbar" style="margin-bottom: 0.5rem;">
                    <button type="button" onclick="inserirModelo('novidades')" class="btn btn-sm btn-outline">
                        <i class="fas fa-file-alt"></i> Modelo Novidades
                    </button>
                    <button type="button" onclick="inserirModelo('promocao')" class="btn btn-sm btn-outline">
                        <i class="fas fa-tag"></i> Modelo Promoção
                    </button>
                    <button type="button" onclick="inserirModelo('tutorial')" class="btn btn-sm btn-outline">
                        <i class="fas fa-graduation-cap"></i> Modelo Tutorial
                    </button>
                </div>
                <textarea name="conteudo" rows="15" required style="width: 100%;">
<h2>🎉 Novidades do mês!</h2>

<p>Olá {nome}!</p>

<p>Confira as principais novidades deste mês na Canais.net:</p>

<h3>📺 Novos Canais</h3>
<ul>
    <li>Canal exemplo 1</li>
    <li>Canal exemplo 2</li>
    <li>Canal exemplo 3</li>
</ul>

<h3>🎬 Filmes e Séries</h3>
<p>Adicionamos mais de 100 novos títulos ao nosso catálogo!</p>

<h3>🔧 Melhorias no Sistema</h3>
<p>Implementamos várias melhorias para garantir uma experiência ainda melhor.</p>

<center>
    <a href="<?php echo MEMBER_URL; ?>" class="button">Acessar Área do Cliente</a>
</center>

<p>Qualquer dúvida, entre em contato conosco!</p>

<p>Atenciosamente,<br>
Equipe Canais.net</p>
                </textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="salvar_rascunho"> 
                    Salvar como rascunho para enviar depois
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" name="enviar_massa" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Enviar Boletim
                </button>
                <button type="button" class="btn btn-outline" onclick="previewEmail()">
                    <i class="fas fa-eye"></i> Pré-visualizar
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'templates'): ?>
<!-- Templates de Email -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Templates de Email do Sistema</h2>
    </div>
    <div style="padding: 2rem;">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Atenção:</strong> Modificar os templates de email do sistema pode afetar a comunicação automática com os clientes.
        </div>
        
        <div class="templates-grid">
            <div class="template-card">
                <h3><i class="fas fa-user-plus"></i> Email de Cadastro</h3>
                <p>Enviado quando um novo usuário se cadastra</p>
                <button class="btn btn-primary btn-sm" onclick="editarTemplate('cadastro')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            
            <div class="template-card">
                <h3><i class="fas fa-key"></i> Recuperação de Senha</h3>
                <p>Enviado quando o usuário solicita nova senha</p>
                <button class="btn btn-primary btn-sm" onclick="editarTemplate('recuperar_senha')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            
            <div class="template-card">
                <h3><i class="fas fa-check-circle"></i> Assinatura Ativada</h3>
                <p>Enviado quando a assinatura é ativada</p>
                <button class="btn btn-primary btn-sm" onclick="editarTemplate('assinatura_ativada')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            
            <div class="template-card">
                <h3><i class="fas fa-clock"></i> Pagamento Pendente</h3>
                <p>Enviado quando há pagamento pendente</p>
                <button class="btn btn-primary btn-sm" onclick="editarTemplate('pagamento_pendente')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            
            <div class="template-card">
                <h3><i class="fas fa-bell"></i> Aviso de Expiração</h3>
                <p>Enviado quando a assinatura está próxima do vencimento</p>
                <button class="btn btn-primary btn-sm" onclick="editarTemplate('aviso_expiracao')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            
            <div class="template-card">
                <h3><i class="fas fa-times-circle"></i> Assinatura Expirada</h3>
                <p>Enviado quando a assinatura expira</p>
                <button class="btn btn-primary btn-sm" onclick="editarTemplate('assinatura_expirada')">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.tabs-container {
    display: flex;
    gap: 1rem;
    border-bottom: 2px solid var(--border-color);
    margin-top: 2rem;
}

.tab-button {
    padding: 1rem 2rem;
    background: none;
    border: none;
    color: var(--text-light);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-button:hover {
    color: var(--primary-color);
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom: 3px solid var(--primary-color);
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.template-card {
    background: var(--light-color);
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
}

.template-card h3 {
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.template-card p {
    color: var(--text-light);
    margin-bottom: 1rem;
}

.editor-toolbar {
    display: flex;
    gap: 0.5rem;
}
</style>

<script>
function mostrarEstatisticas(tipo) {
    const stats = {
        'todos': 'Todos os usuários ativos do sistema',
        'ativos': 'Clientes com assinatura ativa',
        'expirados': 'Clientes que precisam renovar',
        'expirando': 'Clientes que vencem nos próximos 7 dias',
        'pendentes': 'Clientes aguardando ativação',
        'sem_assinatura': 'Usuários cadastrados sem assinatura'
    };
    
    document.getElementById('estatisticas-destinatarios').innerHTML = stats[tipo] || '';
}

function previewEmail() {
    const assunto = document.querySelector('input[name="assunto"]').value;
    const conteudo = document.querySelector('textarea[name="conteudo"]').value;
    
    // Substituir variáveis de exemplo
    const conteudoFinal = conteudo
        .replace(/{nome}/g, 'João Silva')
        .replace(/{email}/g, 'joao@example.com');
    
    document.getElementById('preview-content').innerHTML = `
        <p><strong>Assunto:</strong> ${assunto}</p>
        <hr>
        ${conteudoFinal}
    `;
    
    mostrarModal('modal-preview');
}

function inserirModelo(tipo) {
    const modelos = {
        'novidades': `<h2>🎉 Novidades do mês!</h2>
<p>Olá {nome}!</p>
<p>Confira as principais novidades...</p>`,
        
        'promocao': `<h2>🎁 Promoção Especial!</h2>
<p>Olá {nome}!</p>
<p>Temos uma oferta imperdível para você...</p>`,
        
        'tutorial': `<h2>📚 Tutorial: Como usar nosso sistema</h2>
<p>Olá {nome}!</p>
<p>Preparamos um tutorial especial...</p>`
    };
    
    const textarea = document.querySelector('textarea[name="conteudo"]');
    textarea.value = modelos[tipo] || '';
}

function editarTemplate(tipo) {
    alert('Funcionalidade em desenvolvimento. Para editar templates, modifique o arquivo email-templates.php');
}

function reenviarEmail(id) {
    if (confirm('Reenviar este email?')) {
        // Implementar reenvio
        alert('Email reenviado!');
    }
}
</script>