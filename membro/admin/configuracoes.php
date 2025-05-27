<?php
// Incluir arquivos necessários
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: ../index.php');
    exit;
}

// Conectar ao banco de dados
$pdo = conectarDB();

// Inicializar mensagem
$mensagem = '';
$tipo_mensagem = '';

// Função para atualizar o arquivo config.php
function atualizarConfigFile($configs) {
    $config_path = '../config.php';
    
    // Ler o arquivo atual
    $config_content = file_get_contents($config_path);
    
    // Atualizar cada configuração
    foreach ($configs as $key => $value) {
        // Escapar valor para PHP
        $escaped_value = addslashes($value);
        
        // Padrões de busca para diferentes tipos de definição
        $patterns = [
            "/define\s*\(\s*['\"]" . $key . "['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
            "/define\s*\(\s*['\"]" . $key . "['\"]\s*,\s*[0-9]+\s*\)/"
        ];
        
        // Nova definição
        $replacement = "define('" . $key . "', '" . $escaped_value . "')";
        
        // Tentar substituir com cada padrão
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $config_content)) {
                $config_content = preg_replace($pattern, $replacement, $config_content);
                break;
            }
        }
    }
    
    // Salvar o arquivo
    return file_put_contents($config_path, $config_content) !== false;
}

// Função para atualizar preços no config.php
function atualizarPrecosConfigFile($precos) {
    $config_path = '../config.php';
    $config_content = file_get_contents($config_path);
    
    // Converter array de preços para código PHP
    $precos_code = var_export($precos, true);
    
    // Padrão para encontrar a variável $PRECOS
    $pattern = '/\$PRECOS\s*=\s*\[[^\]]+\];/s';
    
    // Nova definição
    $replacement = '$PRECOS = ' . $precos_code . ';';
    
    // Substituir
    if (preg_match($pattern, $config_content)) {
        $config_content = preg_replace($pattern, $replacement, $config_content);
        return file_put_contents($config_path, $config_content) !== false;
    }
    
    return false;
}

// Processar atualizações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'geral';
    
    switch ($tab) {
        case 'geral':
            try {
                // Configurações para atualizar no arquivo
                $file_configs = [
                    'SITE_NAME' => $_POST['site_name'],
                    'SITE_URL' => $_POST['site_url'],
                    'SMTP_FROM_NAME' => $_POST['smtp_from_name']
                ];
                
                // Atualizar arquivo config.php
                if (atualizarConfigFile($file_configs)) {
                    // Também salvar no banco para ter histórico
                    $configs = [
                        'site_name' => $_POST['site_name'],
                        'site_url' => $_POST['site_url'],
                        'smtp_from_name' => $_POST['smtp_from_name'],
                        'whatsapp_number' => $_POST['whatsapp_number'],
                        'timezone' => $_POST['timezone'] ?? 'America/Sao_Paulo'
                    ];
                    
                    foreach ($configs as $chave => $valor) {
                        $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) 
                                             ON DUPLICATE KEY UPDATE valor = ?");
                        $stmt->execute([$chave, $valor, $valor]);
                    }
                    
                    $mensagem = 'Configurações gerais atualizadas com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    throw new Exception('Não foi possível atualizar o arquivo config.php. Verifique as permissões.');
                }
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configurações: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'email':
            try {
                // Configurações de email para o arquivo
                $email_configs = [
                    'SMTP_HOST' => $_POST['smtp_host'],
                    'SMTP_PORT' => $_POST['smtp_port'],
                    'SMTP_USER' => $_POST['smtp_user'],
                    'SMTP_FROM' => $_POST['smtp_user'] // Geralmente é o mesmo
                ];
                
                // Se uma nova senha foi fornecida
                if (!empty($_POST['smtp_pass'])) {
                    $email_configs['SMTP_PASS'] = $_POST['smtp_pass'];
                }
                
                // Atualizar arquivo config.php
                if (atualizarConfigFile($email_configs)) {
                    // Também salvar no banco
                    foreach ($email_configs as $chave => $valor) {
                        $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) 
                                             ON DUPLICATE KEY UPDATE valor = ?");
                        $stmt->execute([strtolower($chave), $valor, $valor]);
                    }
                    
                    $mensagem = 'Configurações de email atualizadas com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    throw new Exception('Não foi possível atualizar o arquivo config.php.');
                }
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configurações de email: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'precos':
            try {
                // Atualizar preços no arquivo config.php
                if (atualizarPrecosConfigFile($_POST['precos'])) {
                    // Também salvar no banco
                    $precos_json = json_encode($_POST['precos']);
                    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, descricao) 
                                         VALUES ('precos', ?, 'Preços dos planos') 
                                         ON DUPLICATE KEY UPDATE valor = ?");
                    $stmt->execute([$precos_json, $precos_json]);
                    
                    // Salvar taxas de câmbio no banco
                    $taxas_json = json_encode($_POST['taxas']);
                    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, descricao) 
                                         VALUES ('taxas_cambio', ?, 'Taxas de câmbio para conversão') 
                                         ON DUPLICATE KEY UPDATE valor = ?");
                    $stmt->execute([$taxas_json, $taxas_json]);
                    
                    $mensagem = 'Preços e taxas atualizados com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    throw new Exception('Não foi possível atualizar os preços no arquivo config.php.');
                }
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar preços: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'iptv':
            try {
                // IPTV configs são apenas no banco
                $configs_iptv = [
                    'iptv_url' => $_POST['iptv_url'],
                    'iptv_api_type' => $_POST['iptv_api_type'],
                    'iptv_app' => $_POST['iptv_app'],
                    'iptv_connections' => $_POST['iptv_connections'],
                    'iptv_instructions' => $_POST['iptv_instructions']
                ];
                
                foreach ($configs_iptv as $chave => $valor) {
                    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) 
                                         ON DUPLICATE KEY UPDATE valor = ?");
                    $stmt->execute([$chave, $valor, $valor]);
                }
                
                $mensagem = 'Configurações IPTV atualizadas com sucesso!';
                $tipo_mensagem = 'success';
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configurações IPTV: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'dashboard':
            try {
                // Dashboard configs são apenas no banco
                $customizacoes = [
                    'cor_primaria' => $_POST['cor_primaria'],
                    'cor_secundaria' => $_POST['cor_secundaria'],
                    'logo_url' => $_POST['logo_url'],
                    'mensagem_boas_vindas' => $_POST['mensagem_boas_vindas'],
                    'modulos' => $_POST['modulos'] ?? []
                ];
                
                $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES ('dashboard_custom', ?) 
                                     ON DUPLICATE KEY UPDATE valor = ?");
                $stmt->execute([json_encode($customizacoes), json_encode($customizacoes)]);
                
                $mensagem = 'Customizações da dashboard salvas com sucesso!';
                $tipo_mensagem = 'success';
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar customizações: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
    }
}

// Buscar todas as configurações do banco
$configs = [];
try {
    // Criar tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(255) UNIQUE NOT NULL,
        valor TEXT,
        descricao TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->query("SELECT * FROM configuracoes");
    while ($row = $stmt->fetch()) {
        $configs[$row['chave']] = $row['valor'];
    }
} catch (Exception $e) {
    // Tabela não existe ou erro
}

// Valores atuais (do arquivo config.php ou banco)
$site_name = defined('SITE_NAME') ? SITE_NAME : ($configs['site_name'] ?? 'Meu Site IPTV');
$site_url = defined('SITE_URL') ? SITE_URL : ($configs['site_url'] ?? 'https://meusite.com');
$smtp_from_name = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : ($configs['smtp_from_name'] ?? 'Suporte');
$whatsapp_number = $configs['whatsapp_number'] ?? '819042662408';
$timezone = $configs['timezone'] ?? 'America/Sao_Paulo';

// Configurações de email
$smtp_host = defined('SMTP_HOST') ? SMTP_HOST : ($configs['smtp_host'] ?? 'smtp.gmail.com');
$smtp_port = defined('SMTP_PORT') ? SMTP_PORT : ($configs['smtp_port'] ?? '587');
$smtp_user = defined('SMTP_USER') ? SMTP_USER : ($configs['smtp_user'] ?? '');

// Configurações IPTV
$iptv_url = $configs['iptv_url'] ?? 'http://dns.appcanais.net:80';
$iptv_api_type = $configs['iptv_api_type'] ?? 'xtream';
$iptv_app = $configs['iptv_app'] ?? 'IPTV Smarters Pro';
$iptv_connections = $configs['iptv_connections'] ?? '1';
$iptv_instructions = $configs['iptv_instructions'] ?? '';

// Taxas de câmbio e preços
$taxas_cambio = isset($configs['taxas_cambio']) ? json_decode($configs['taxas_cambio'], true) : [
    'USD' => 5.00,
    'EUR' => 5.50,
    'JPY' => 0.033
];

// Usar preços do config.php (arquivo)
global $PRECOS;

// Dashboard customizations
$dashboard_custom = isset($configs['dashboard_custom']) ? json_decode($configs['dashboard_custom'], true) : [
    'cor_primaria' => '#2563eb',
    'cor_secundaria' => '#3b82f6',
    'logo_url' => '',
    'mensagem_boas_vindas' => 'Bem-vindo ao melhor serviço de IPTV!',
    'modulos' => ['instrucoes', 'estatisticas', 'suporte', 'tutorial_video']
];

$tab = $_GET['tab'] ?? 'geral';
?>

<h1>Configurações do Sistema</h1>

<?php if ($mensagem): ?>
<div class="alert alert-<?php echo $tipo_mensagem; ?>">
    <?php echo $mensagem; ?>
</div>
<?php endif; ?>

<!-- Verificar permissões do arquivo -->
<?php 
$config_file = '../config.php';
if (!is_writable($config_file)): 
?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Atenção:</strong> O arquivo config.php não tem permissão de escrita. 
    Execute: <code>chmod 666 <?php echo realpath($config_file); ?></code>
</div>
<?php endif; ?>

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
                <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
            </div>
            
            <div class="form-group">
                <label>URL do Site:</label>
                <input type="url" name="site_url" value="<?php echo htmlspecialchars($site_url); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nome do Remetente (Emails):</label>
                <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp_from_name); ?>" required>
            </div>
            
            <div class="form-group">
                <label>WhatsApp de Suporte:</label>
                <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($whatsapp_number); ?>" required>
                <small class="form-hint">Formato: 5511999999999 (com código do país)</small>
            </div>
            
            <div class="form-group">
                <label>Fuso Horário:</label>
                <select name="timezone">
                    <option value="America/Sao_Paulo" <?php echo $timezone === 'America/Sao_Paulo' ? 'selected' : ''; ?>>São Paulo (GMT-3)</option>
                    <option value="America/New_York" <?php echo $timezone === 'America/New_York' ? 'selected' : ''; ?>>Nova York (GMT-5)</option>
                    <option value="Europe/London" <?php echo $timezone === 'Europe/London' ? 'selected' : ''; ?>>Londres (GMT+0)</option>
                    <option value="Asia/Tokyo" <?php echo $timezone === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tóquio (GMT+9)</option>
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
                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Porta SMTP:</label>
                <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Usuário SMTP:</label>
                <input type="email" name="smtp_user" value="<?php echo htmlspecialchars($smtp_user); ?>" required>
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
            
            <!-- Taxas de Câmbio -->
            <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                <h3><i class="fas fa-exchange-alt"></i> Taxas de Câmbio (1 moeda = X BRL)</h3>
                <p style="color: var(--text-light); margin-bottom: 1rem;">Configure as taxas para conversão automática nos relatórios</p>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>1 USD = R$</label>
                        <input type="number" step="0.01" name="taxas[USD]" value="<?php echo $taxas_cambio['USD']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>1 EUR = R$</label>
                        <input type="number" step="0.01" name="taxas[EUR]" value="<?php echo $taxas_cambio['EUR']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>1 JPY = R$</label>
                        <input type="number" step="0.001" name="taxas[JPY]" value="<?php echo $taxas_cambio['JPY']; ?>" required>
                    </div>
                </div>
            </div>
            
            <?php 
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
                    <!-- Campo oculto para o símbolo -->
                    <input type="hidden" name="precos[<?php echo $moeda; ?>][simbolo]" value="<?php echo $valores['simbolo']; ?>">
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
                <input type="url" name="iptv_url" value="<?php echo htmlspecialchars($iptv_url); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Tipo de API:</label>
                <select name="iptv_api_type">
                    <option value="xtream" <?php echo $iptv_api_type === 'xtream' ? 'selected' : ''; ?>>Xtream Codes API</option>
                    <option value="m3u" <?php echo $iptv_api_type === 'm3u' ? 'selected' : ''; ?>>Lista M3U</option>
                    <option value="stalker" <?php echo $iptv_api_type === 'stalker' ? 'selected' : ''; ?>>Stalker Portal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Aplicativo Recomendado:</label>
                <input type="text" name="iptv_app" value="<?php echo htmlspecialchars($iptv_app); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Número de Conexões Simultâneas:</label>
                <input type="number" name="iptv_connections" value="<?php echo htmlspecialchars($iptv_connections); ?>" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Instruções Personalizadas:</label>
                <textarea name="iptv_instructions" rows="5"><?php echo htmlspecialchars($iptv_instructions); ?></textarea>
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
                    <input type="color" name="cor_primaria" value="<?php echo $dashboard_custom['cor_primaria']; ?>">
                </div>
                <div class="form-group">
                    <label>Cor Secundária:</label>
                    <input type="color" name="cor_secundaria" value="<?php echo $dashboard_custom['cor_secundaria']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>URL do Logo:</label>
                <input type="url" name="logo_url" value="<?php echo htmlspecialchars($dashboard_custom['logo_url']); ?>" placeholder="https://exemplo.com/logo.png">
            </div>
            
            <div class="form-group">
                <label>Mensagem de Boas-Vindas:</label>
                <textarea name="mensagem_boas_vindas" rows="3"><?php echo htmlspecialchars($dashboard_custom['mensagem_boas_vindas']); ?></textarea>
            </div>
            
            <h3>Módulos da Dashboard</h3>
            <div style="display: grid; gap: 0.5rem;">
                <?php 
                $modulos_disponiveis = [
                    'instrucoes' => 'Mostrar Instruções',
                    'estatisticas' => 'Mostrar Estatísticas',
                    'suporte' => 'Mostrar Botão de Suporte',
                    'tutorial_video' => 'Mostrar Tutorial em Vídeo'
                ];
                
                foreach ($modulos_disponiveis as $modulo => $label): 
                    $checked = in_array($modulo, $dashboard_custom['modulos']) ? 'checked' : '';
                ?>
                <label>
                    <input type="checkbox" name="modulos[]" value="<?php echo $modulo; ?>" <?php echo $checked; ?>>
                    <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
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
.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

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
    
    fetch('ajax.php?action=test_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email de teste enviado com sucesso para ' + email);
        } else {
            alert('Erro ao enviar email: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        alert('Erro ao enviar email de teste');
    });
}

function fazerBackup(tipo) {
    if (confirm('Iniciar backup do tipo ' + tipo + '?')) {
        window.location.href = 'backup.php?tipo=' + tipo;
    }
}

function mostrarRestaurar() {
    alert('Funcionalidade de restauração em desenvolvimento');
}

function limparCache() {
    if (confirm('Limpar todo o cache do sistema?')) {
        fetch('ajax.php?action=clear_cache', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        });
    }
}

function limparLogsAntigos() {
    if (confirm('Limpar logs com mais de 30 dias?')) {
        fetch('ajax.php?action=clear_logs', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        });
    }
}

function otimizarBanco() {
    if (confirm('Otimizar todas as tabelas do banco de dados?')) {
        fetch('ajax.php?action=optimize_db', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        });
    }
}
</script>