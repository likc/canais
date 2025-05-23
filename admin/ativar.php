<?php
// Ativar Assinaturas
require_once '../funcoes-email.php';

// Processar ativação
if (isset($_POST['ativar'])) {
    $assinatura_id = intval($_POST['assinatura_id']);
    $usuario_iptv = $_POST['usuario_iptv'];
    $senha_iptv = $_POST['senha_iptv'];
    
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
        $pdo->prepare("UPDATE assinaturas SET status = 'ativa' WHERE id = ?")->execute([$assinatura_id]);
        $pdo->prepare("UPDATE pagamentos SET status = 'aprovado' WHERE assinatura_id = ?")->execute([$assinatura_id]);
        
        // Enviar email
        $enviado = enviarEmailComLog($assinatura['email'], 'assinatura_ativada', [
            'nome_usuario' => $assinatura['nome_usuario'],
            'usuario_iptv' => $usuario_iptv,
            'senha_iptv' => $senha_iptv,
            'url_servidor' => 'http://dns.appcanais.net:80',
            'plano' => $assinatura['plano'],
            'data_fim' => date('d/m/Y', strtotime($assinatura['data_fim']))
        ], $assinatura['usuario_id']);
        
        if ($enviado) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Assinatura ativada e email enviado para ' . $assinatura['email'] . '!
                  </div>';
        } else {
            echo '<div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Assinatura ativada, mas falha no envio do email. 
                    Envie as credenciais manualmente.
                  </div>';
        }
    }
}

// Buscar assinaturas pendentes
$pendentes = $pdo->query("
    SELECT a.*, u.nome_usuario, u.email, u.telefone, p.metodo, p.transacao_id 
    FROM assinaturas a 
    JOIN usuarios u ON a.usuario_id = u.id 
    LEFT JOIN pagamentos p ON p.assinatura_id = a.id
    WHERE a.status = 'pendente' 
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
                    <td>#<?php echo $pendente['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($pendente['nome_usuario']); ?></strong><br>
                        <small><?php echo htmlspecialchars($pendente['email']); ?></small>
                    </td>
                    <td>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pendente['telefone']); ?>" 
                           target="_blank" class="btn btn-sm btn-success">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </td>
                    <td><?php echo ucfirst($pendente['plano']); ?></td>
                    <td><?php echo formatarMoeda($pendente['valor'], $pendente['moeda']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php 
                            $metodos = [
                                'pix' => 'PIX',
                                'cartao' => 'Cartão',
                                'boleto' => 'Boleto',
                                'transferencia' => 'Transferência'
                            ];
                            echo $metodos[$pendente['metodo']] ?? $pendente['metodo'];
                            ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($pendente['data_criacao'])); ?></td>
                    <td>
                        <?php if ($pendente['transacao_id']): ?>
                            <code style="font-size: 0.75rem;"><?php echo $pendente['transacao_id']; ?></code>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <button onclick="mostrarModal('modal-ativar-<?php echo $pendente['id']; ?>')" 
                                class="btn btn-primary btn-sm">
                            <i class="fas fa-check"></i> Ativar
                        </button>
                    </td>
                </tr>
                
                <!-- Modal de ativação -->
                <div id="modal-ativar-<?php echo $pendente['id']; ?>" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Ativar Assinatura #<?php echo $pendente['id']; ?></h3>
                            <button class="close-modal" onclick="fecharModal('modal-ativar-<?php echo $pendente['id']; ?>')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="assinatura_id" value="<?php echo $pendente['id']; ?>">
                            
                            <div style="background: var(--light-color); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                                <p><strong>Usuário:</strong> <?php echo htmlspecialchars($pendente['nome_usuario']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($pendente['email']); ?></p>
                                <p><strong>Plano:</strong> <?php echo ucfirst($pendente['plano']); ?></p>
                                <p><strong>Valor:</strong> <?php echo formatarMoeda($pendente['valor'], $pendente['moeda']); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Usuário IPTV:</label>
                                <input type="text" name="usuario_iptv" required 
                                       placeholder="Ex: user123" 
                                       value="<?php echo strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $pendente['nome_usuario'])) . rand(100, 999); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Senha IPTV:</label>
                                <input type="text" name="senha_iptv" required 
                                       placeholder="Ex: pass123" 
                                       value="<?php echo substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8); ?>">
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
                                <button type="button" onclick="fecharModal('modal-ativar-<?php echo $pendente['id']; ?>')" 
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
</div>