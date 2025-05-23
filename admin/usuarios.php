<?php
// Gerenciamento de Usuários

// Processar ações
if (isset($_POST['acao'])) {
    switch ($_POST['acao']) {
        case 'desativar':
            $pdo->prepare("UPDATE usuarios SET status = 'inativo' WHERE id = ?")->execute([$_POST['usuario_id']]);
            echo '<div class="alert alert-success">Usuário desativado!</div>';
            break;
        case 'ativar':
            $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?")->execute([$_POST['usuario_id']]);
            echo '<div class="alert alert-success">Usuário ativado!</div>';
            break;
        case 'resetar_senha':
            $nova_senha = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$nova_senha, $_POST['usuario_id']]);
            echo '<div class="alert alert-success">Nova senha: <strong>' . $nova_senha . '</strong></div>';
            break;
        case 'tornar_admin':
            $pdo->prepare("UPDATE usuarios SET is_admin = 1 WHERE id = ?")->execute([$_POST['usuario_id']]);
            echo '<div class="alert alert-success">Usuário agora é administrador!</div>';
            break;
        case 'remover_admin':
            $pdo->prepare("UPDATE usuarios SET is_admin = 0 WHERE id = ?")->execute([$_POST['usuario_id']]);
            echo '<div class="alert alert-success">Privilégios de admin removidos!</div>';
            break;
        case 'editar_usuario':
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome_usuario = ?, email = ?, telefone = ?, regiao = ?, senha = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['nome_usuario'],
                $_POST['email'],
                $_POST['telefone'],
                $_POST['regiao'],
                $_POST['senha'],
                $_POST['usuario_id']
            ]);
            echo '<div class="alert alert-success">Usuário atualizado!</div>';
            break;
        case 'editar_assinatura':
            $stmt = $pdo->prepare("
                UPDATE assinaturas 
                SET plano = ?, data_inicio = ?, data_fim = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['plano'],
                $_POST['data_inicio'],
                $_POST['data_fim'],
                $_POST['status'],
                $_POST['assinatura_id']
            ]);
            echo '<div class="alert alert-success">Assinatura atualizada!</div>';
            break;
    }
}

// Buscar usuários
$busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_regiao = $_GET['regiao'] ?? '';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM assinaturas WHERE usuario_id = u.id AND status = 'ativa') as assinaturas_ativas,
        COALESCE(u.is_admin, 0) as is_admin
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
                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                </select>
                <select name="regiao">
                    <option value="">Todas Regiões</option>
                    <option value="BRL" <?php echo $filtro_regiao === 'BRL' ? 'selected' : ''; ?>>🇧🇷 Brasil</option>
                    <option value="USD" <?php echo $filtro_regiao === 'USD' ? 'selected' : ''; ?>>🇺🇸 EUA</option>
                    <option value="EUR" <?php echo $filtro_regiao === 'EUR' ? 'selected' : ''; ?>>🇪🇺 Europa</option>
                    <option value="JPY" <?php echo $filtro_regiao === 'JPY' ? 'selected' : ''; ?>>🇯🇵 Japão</option>
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
                    <th>Admin</th>
                    <th>Assinaturas</th>
                    <th>Cadastro</th>
                    <th>Último Login</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo $usuario['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($usuario['nome_usuario']); ?></strong></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                    <td>
                        <?php 
                        $bandeiras = ['BRL' => '🇧🇷', 'USD' => '🇺🇸', 'EUR' => '🇪🇺', 'JPY' => '🇯🇵'];
                        echo $bandeiras[$usuario['regiao']] . ' ' . $usuario['regiao'];
                        ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $usuario['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($usuario['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($usuario['is_admin']): ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-crown"></i> Admin
                            </span>
                        <?php else: ?>
                            <span class="badge badge-secondary">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($usuario['assinaturas_ativas'] > 0): ?>
                            <span class="badge badge-info"><?php echo $usuario['assinaturas_ativas']; ?> ativa(s)</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Nenhuma</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></td>
                    <td><?php echo $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="mostrarModal('modal-usuario-<?php echo $usuario['id']; ?>')" class="btn btn-sm btn-primary" title="Ver/Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="resetar_senha">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-warning" 
                                        onclick="return confirmarAcao('Resetar senha deste usuário?')" title="Resetar Senha">
                                    <i class="fas fa-key"></i>
                                </button>
                            </form>
                            
                            <?php if (!$usuario['is_admin']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="tornar_admin">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-info" 
                                        onclick="return confirmarAcao('Tornar este usuário administrador?')" title="Tornar Admin">
                                    <i class="fas fa-user-shield"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="remover_admin">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" 
                                        onclick="return confirmarAcao('Remover privilégios de admin?')" title="Remover Admin">
                                    <i class="fas fa-user"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($usuario['status'] === 'ativo'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="desativar">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirmarAcao('Desativar este usuário?')" title="Desativar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="ativar">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Ativar">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- Modal detalhes -->
                <div id="modal-usuario-<?php echo $usuario['id']; ?>" class="modal">
                    <div class="modal-content" style="max-width: 800px;">
                        <div class="modal-header">
                            <h3>Detalhes e Edição do Usuário</h3>
                            <button class="close-modal" onclick="fecharModal('modal-usuario-<?php echo $usuario['id']; ?>')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <!-- Coluna 1: Dados do Usuário -->
                            <div>
                                <h4>Dados do Usuário</h4>
                                <form method="POST">
                                    <input type="hidden" name="acao" value="editar_usuario">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>ID:</label>
                                        <input type="text" value="<?php echo $usuario['id']; ?>" disabled>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Nome de Usuário:</label>
                                        <input type="text" name="nome_usuario" value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Email:</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Senha:</label>
                                        <input type="text" name="senha" value="<?php echo htmlspecialchars($usuario['senha']); ?>" required>
                                        <small class="form-hint" style="color: var(--danger-color);">
                                            <i class="fas fa-exclamation-triangle"></i> Senha em texto plano
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Telefone:</label>
                                        <input type="text" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Região:</label>
                                        <select name="regiao" required>
                                            <option value="BRL" <?php echo $usuario['regiao'] === 'BRL' ? 'selected' : ''; ?>>🇧🇷 Brasil</option>
                                            <option value="USD" <?php echo $usuario['regiao'] === 'USD' ? 'selected' : ''; ?>>🇺🇸 EUA</option>
                                            <option value="EUR" <?php echo $usuario['regiao'] === 'EUR' ? 'selected' : ''; ?>>🇪🇺 Europa</option>
                                            <option value="JPY" <?php echo $usuario['regiao'] === 'JPY' ? 'selected' : ''; ?>>🇯🇵 Japão</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Status:</label>
                                        <input type="text" value="<?php echo ucfirst($usuario['status']); ?>" disabled>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Administrador:</label>
                                        <input type="text" value="<?php echo $usuario['is_admin'] ? 'Sim' : 'Não'; ?>" disabled>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Data de Cadastro:</label>
                                        <input type="text" value="<?php echo date('d/m/Y H:i:s', strtotime($usuario['data_cadastro'])); ?>" disabled>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Último Login:</label>
                                        <input type="text" value="<?php echo $usuario['ultimo_login'] ? date('d/m/Y H:i:s', strtotime($usuario['ultimo_login'])) : 'Nunca'; ?>" disabled>
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
                                $assinaturas->execute([$usuario['id']]);
                                $assinaturas = $assinaturas->fetchAll();
                                
                                if (empty($assinaturas)):
                                ?>
                                    <p>Nenhuma assinatura encontrada.</p>
                                    <button class="btn btn-success" onclick="criarAssinatura(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-plus"></i> Criar Assinatura
                                    </button>
                                <?php else: ?>
                                    <?php foreach ($assinaturas as $assinatura): ?>
                                    <div style="border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                                        <form method="POST">
                                            <input type="hidden" name="acao" value="editar_assinatura">
                                            <input type="hidden" name="assinatura_id" value="<?php echo $assinatura['id']; ?>">
                                            
                                            <div class="form-group">
                                                <label>Plano:</label>
                                                <select name="plano" required>
                                                    <option value="mensal" <?php echo $assinatura['plano'] === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                                                    <option value="semestral" <?php echo $assinatura['plano'] === 'semestral' ? 'selected' : ''; ?>>Semestral</option>
                                                    <option value="anual" <?php echo $assinatura['plano'] === 'anual' ? 'selected' : ''; ?>>Anual</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select name="status" required>
                                                    <option value="ativa" <?php echo $assinatura['status'] === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                                                    <option value="pendente" <?php echo $assinatura['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                                    <option value="cancelada" <?php echo $assinatura['status'] === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                                    <option value="expirada" <?php echo $assinatura['status'] === 'expirada' ? 'selected' : ''; ?>>Expirada</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Data Início:</label>
                                                <input type="date" name="data_inicio" value="<?php echo $assinatura['data_inicio']; ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Data Fim:</label>
                                                <input type="date" name="data_fim" value="<?php echo $assinatura['data_fim']; ?>" required>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-save"></i> Salvar
                                            </button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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

<script>
function criarAssinatura(usuarioId) {
    if (confirm('Criar nova assinatura para este usuário?')) {
        // Implementar criação de assinatura
        window.location.href = '?acao=ativar&usuario_id=' + usuarioId;
    }
}
</script>