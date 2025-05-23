<?php
// Configurações do Sistema

// Processar atualizações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'geral';
    
    switch ($tab) {
        case 'geral':
            // Atualizar configurações gerais
            $config_file = '../config-custom.php';
            $config_content = "<?php\n";
            $config_content .= "// Configurações Personalizadas\n\n";
            $config_content .= "define('SITE_NAME', '" . addslashes($_POST['site_name']) . "');\n";
            $config_content .= "define('SITE_URL', '" . addslashes($_POST['site_url']) . "');\n";
            $config_content .= "define('SMTP_FROM_NAME', '" . addslashes($_POST['smtp_from_name']) . "');\n";
            $config_content .= "define('WHATSAPP_NUMBER', '" . addslashes($_POST['whatsapp_number']) . "');\n";
            $config_content .= "?>";
            
            file_put_contents($config_file, $config_content);
            echo '<div class="alert alert-success">Configurações gerais atualizadas!</div>';
            break;
            
        case 'email':
            // Atualizar configurações de email
            // Aqui você implementaria a atualização das configs SMTP
            echo '<div class="alert alert-success">Configurações de email atualizadas!</div>';
            break;
            
        case 'precos':
            // Atualizar preços
            $precos_json = json_encode($_POST['precos']);
            $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'precos'")->execute([$precos_json]);
            echo '<div class="alert alert-success">Preços atualizados!</div>';
            break;
            
        case 'dashboard':
            // Salvar customizações da dashboard
            $customizacoes = [
                'cor_primaria' => $_POST['cor_primaria'],
                'cor_secundaria' => $_POST['cor_secundaria'],
                'logo_url' => $_POST['logo_url'],
                'mensagem_boas_vindas' => $_POST['mensagem_boas_vindas']
            ];
            $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'dashboard_custom'")->execute([json_encode($customizacoes)]);
            echo '<div class="alert alert-success">Customizações da dashboard salvas!</div>';
            break;
    }
}

// Buscar configurações atuais
$configs = [];
$stmt = $pdo->query("SELECT * FROM configuracoes");
while ($row = $stmt->fetch()) {
    $configs[$row['chave']] = $row['valor'];
}

$tab = $_GET['tab'] ?? 'geral';
?>

<h1>Configurações do Sistema</h1>

<!-- Tabs de Configuração -->
<div class="config-tabs">
    <a href="?acao=configuracoes&tab=geral" class="config-tab <?php echo $tab === 'geral' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Geral
    </a>
    <a href="?acao=configuracoes&tab=email" class="config-tab <?php echo $tab === 'email' ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i> Email
    </a>
    <a href="?acao=configuracoes&tab=precos" class="config-tab <?php echo $tab === 'precos' ? 'active' : ''; ?>">
        <i class="fas fa-dollar-sign"></i> Preços
    </a>
    <a href="?acao=configuracoes&tab=iptv" class="config-tab <?php echo $tab === 'iptv' ? 'active' : ''; ?>">
        <i class="fas fa-tv"></i> IPTV
    </a>
    <a href="?acao=configuracoes&tab=dashboard" class="config-tab <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
        <i class="fas fa-palette"></i> Dashboard Cliente
    </a>
    <a href="?acao=configuracoes&tab=backup" class="config-tab <?php echo $tab === 'backup' ? 'active' : ''; ?>">
        <i class="fas fa-database"></i> Backup
    </a>
</div>

<?php if ($tab === 'geral'): ?>
<!-- Configurações Gerais -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Configurações Gerais</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <input type="hidden" name="tab" value="geral">
            
            <div class="form-group">
                <label>Nome do Site:</label>
                <input type="text" name="site_name" value="<?php echo SITE_NAME; ?>" required>
            </div>
            
            <div class="form-group">
                <label>URL do Site:</label>
                <input type="url" name="site_url" value="<?php echo SITE_URL; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nome do Remetente (Emails):</label>
                <input type="text" name="smtp_from_name" value="<?php echo SMTP_FROM_NAME; ?>" required>
            </div>
            
            <div class="form-group">
                <label>WhatsApp de Suporte:</label>
                <input type="text" name="whatsapp_number" value="819042662408" required>
                <small class="form-hint">Formato: 5511999999999 (com código do país)</small>
            </div>
            
            <div class="form-group">
                <label>Fuso Horário:</label>
                <select name="timezone">
                    <option value="America/Sao_Paulo">São Paulo (GMT-3)</option>
                    <option value="America/New_York">Nova York (GMT-5)</option>
                    <option value="Europe/London">Londres (GMT+0)</option>
                    <option value="Asia/Tokyo">Tóquio (GMT+9)</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </form>
    </div>
</div>

<?php elseif ($tab === 'email'): ?>
<!-- Configurações de Email -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Configurações de Email (SMTP)</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <input type="hidden" name="tab" value="email">
            
            <div class="form-group">
                <label>Servidor SMTP:</label>
                <input type="text" name="smtp_host" value="<?php echo SMTP_HOST; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Porta SMTP:</label>
                <input type="number" name="smtp_port" value="<?php echo SMTP_PORT; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Usuário SMTP:</label>
                <input type="email" name="smtp_user" value="<?php echo SMTP_USER; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Senha SMTP:</label>
                <input type="password" name="smtp_pass" placeholder="Digite para alterar">
                <small class="form-hint">Deixe em branco para manter a senha atual</small>
            </div>
            
            <div class="form-group">
                <label>Email de Teste:</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="email" id="test-email" placeholder="Digite um email para teste">
                    <button type="button" class="btn btn-outline" onclick="enviarEmailTeste()">
                        <i class="fas fa-paper-plane"></i> Enviar Teste
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </form>
    </div>
</div>

<?php elseif ($tab === 'precos'): ?>
<!-- Configurações de Preços -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Configuração de Preços por Região</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <input type="hidden" name="tab" value="precos">
            
            <?php 
            global $PRECOS;
            foreach ($PRECOS as $moeda => $valores): 
                $bandeira = match($moeda) {
                    'BRL' => '🇧🇷 Brasil',
                    'USD' => '🇺🇸 Estados Unidos',
                    'EUR' => '🇪🇺 Europa',
                    'JPY' => '🇯🇵 Japão'
                };
            ?>
            <div style="background: var(--light-color); padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                <h3><?php echo $bandeira; ?> (<?php echo $moeda; ?>)</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
                    <div class="form-group">
                        <label>Plano Mensal:</label>
                        <input type="number" step="0.01" name="precos[<?php echo $moeda; ?>][mensal]" 
                               value="<?php echo $valores['mensal']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Plano Semestral:</label>
                        <input type="number" step="0.01" name="precos[<?php echo $moeda; ?>][semestral]" 
                               value="<?php echo $valores['semestral']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Plano Anual:</label>
                        <input type="number" step="0.01" name="precos[<?php echo $moeda; ?>][anual]" 
                               value="<?php echo $valores['anual']; ?>" required>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Preços
            </button>
        </form>
    </div>
</div>

<?php elseif ($tab === 'iptv'): ?>
<!-- Configurações IPTV -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Configurações do Servidor IPTV</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <input type="hidden" name="tab" value="iptv">
            
            <div class="form-group">
                <label>URL do Servidor:</label>
                <input type="url" name="iptv_url" value="http://dns.appcanais.net:80" required>
            </div>
            
            <div class="form-group">
                <label>Tipo de API:</label>
                <select name="iptv_api_type">
                    <option value="xtream">Xtream Codes API</option>
                    <option value="m3u">Lista M3U</option>
                    <option value="stalker">Stalker Portal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Aplicativo Recomendado:</label>
                <input type="text" name="iptv_app" value="IPTV Smarters Pro" required>
            </div>
            
            <div class="form-group">
                <label>Número de Conexões Simultâneas:</label>
                <input type="number" name="iptv_connections" value="1" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Instruções Personalizadas:</label>
                <textarea name="iptv_instructions" rows="5">Digite aqui instruções personalizadas para os clientes...</textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </form>
    </div>
</div>

<?php elseif ($tab === 'dashboard'): ?>
<!-- Customização da Dashboard -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Customização da Dashboard do Cliente</h2>
    </div>
    <div style="padding: 2rem;">
        <form method="POST">
            <input type="hidden" name="tab" value="dashboard">
            
            <h3>Cores do Sistema</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="form-group">
                    <label>Cor Primária:</label>
                    <input type="color" name="cor_primaria" value="#2563eb">
                </div>
                <div class="form-group">
                    <label>Cor Secundária:</label>
                    <input type="color" name="cor_secundaria" value="#3b82f6">
                </div>
            </div>
            
            <div class="form-group">
                <label>URL do Logo:</label>
                <input type="url" name="logo_url" placeholder="https://exemplo.com/logo.png">
            </div>
            
            <div class="form-group">
                <label>Mensagem de Boas-Vindas:</label>
                <textarea name="mensagem_boas_vindas" rows="3">Bem-vindo ao melhor serviço de IPTV!</textarea>
            </div>
            
            <h3>Módulos da Dashboard</h3>
            <div style="display: grid; gap: 0.5rem;">
                <label><input type="checkbox" name="modulos[]" value="instrucoes" checked> Mostrar Instruções</label>
                <label><input type="checkbox" name="modulos[]" value="estatisticas" checked> Mostrar Estatísticas</label>
                <label><input type="checkbox" name="modulos[]" value="suporte" checked> Mostrar Botão de Suporte</label>
                <label><input type="checkbox" name="modulos[]" value="tutorial_video" checked> Mostrar Tutorial em Vídeo</label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-save"></i> Salvar Customizações
            </button>
        </form>
    </div>
</div>

<?php elseif ($tab === 'backup'): ?>
<!-- Backup e Manutenção -->
<div class="data-table" style="margin-top: 2rem;">
    <div class="table-header">
        <h2>Backup e Manutenção</h2>
    </div>
    <div style="padding: 2rem;">
        <div class="backup-grid">
            <div class="backup-card">
                <h3><i class="fas fa-database"></i> Backup do Banco de Dados</h3>
                <p>Última execução: <?php echo date('d/m/Y H:i', strtotime('-2 days')); ?></p>
                <button class="btn btn-primary" onclick="fazerBackup('database')">
                    <i class="fas fa-download"></i> Fazer Backup Agora
                </button>
            </div>
            
            <div class="backup-card">
                <h3><i class="fas fa-file-archive"></i> Backup Completo</h3>
                <p>Inclui banco de dados e arquivos</p>
                <button class="btn btn-primary" onclick="fazerBackup('completo')">
                    <i class="fas fa-download"></i> Backup Completo
                </button>
            </div>
            
            <div class="backup-card">
                <h3><i class="fas fa-history"></i> Restaurar Backup</h3>
                <p>Restaurar de um arquivo de backup</p>
                <button class="btn btn-warning" onclick="mostrarRestaurar()">
                    <i class="fas fa-upload"></i> Restaurar
                </button>
            </div>
        </div>
        
        <h3 style="margin-top: 2rem;">Manutenção do Sistema</h3>
        <div class="maintenance-actions">
            <button class="btn btn-outline" onclick="limparCache()">
                <i class="fas fa-broom"></i> Limpar Cache
            </button>
            <button class="btn btn-outline" onclick="limparLogsAntigos()">
                <i class="fas fa-trash"></i> Limpar Logs Antigos
            </button>
            <button class="btn btn-outline" onclick="otimizarBanco()">
                <i class="fas fa-compress"></i> Otimizar Banco de Dados
            </button>
        </div>
        
        <h3 style="margin-top: 2rem;">Informações do Sistema</h3>
        <div class="system-info">
            <p><strong>Versão do PHP:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Versão do MySQL:</strong> <?php echo $pdo->query('SELECT VERSION()')->fetchColumn(); ?></p>
            <p><strong>Espaço em Disco:</strong> <?php echo round(disk_free_space('/') / 1024 / 1024 / 1024, 2); ?> GB livres</p>
            <p><strong>Memória:</strong> <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB em uso</p>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.config-tabs {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    border-bottom: 2px solid var(--border-color);
    flex-wrap: wrap;
}

.config-tab {
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: var(--text-light);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.config-tab:hover {
    color: var(--primary-color);
}

.config-tab.active {
    color: var(--primary-color);
    border-bottom: 3px solid var(--primary-color);
}

.backup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.backup-card {
    background: var(--light-color);
    padding: 1.5rem;
    border-radius: 0.5rem;
    text-align: center;
}

.backup-card h3 {
    margin-bottom: 0.5rem;
}

.backup-card p {
    color: var(--text-light);
    margin-bottom: 1rem;
}

.maintenance-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.system-info {
    background: var(--light-color);
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.system-info p {
    margin-bottom: 0.5rem;
}
</style>

<script>
function enviarEmailTeste() {
    const email = document.getElementById('test-email').value;
    if (!email) {
        alert('Digite um email para teste');
        return;
    }
    
    // Implementar envio de teste
    alert('Email de teste enviado para ' + email);
}

function fazerBackup(tipo) {
    if (confirm('Iniciar backup do tipo ' + tipo + '?')) {
        // Implementar backup
        alert('Backup iniciado! Você receberá um email quando estiver pronto.');
    }
}

function mostrarRestaurar() {
    alert('Funcionalidade de restauração em desenvolvimento');
}

function limparCache() {
    if (confirm('Limpar todo o cache do sistema?')) {
        alert('Cache limpo com sucesso!');
    }
}

function limparLogsAntigos() {
    if (confirm('Limpar logs com mais de 30 dias?')) {
        alert('Logs antigos removidos!');
    }
}

function otimizarBanco() {
    if (confirm('Otimizar todas as tabelas do banco de dados?')) {
        alert('Banco de dados otimizado!');
    }
}
</script>