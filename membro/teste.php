<?php
// Teste final do sistema de emails
require_once 'config.php';
require_once 'email-templates.php';
require_once 'funcoes-email.php';

$resultado = '';

if (isset($_POST['testar'])) {
    $email = $_POST['email'];
    $metodo = $_POST['metodo'];
    
    // Dados de teste
    $dados = [
        'nome_usuario' => 'Usuário Teste',
        'email' => $email
    ];
    
    // Testar envio
    $inicio = microtime(true);
    
    if ($metodo === 'direto') {
        // Teste direto
        $enviado = enviarEmail($email, 'cadastro', $dados);
    } else {
        // Teste com log
        $enviado = enviarEmailComLog($email, 'cadastro', $dados, null);
    }
    
    $tempo = round(microtime(true) - $inicio, 2);
    
    if ($enviado) {
        $resultado = '<div style="background: #4caf50; color: white; padding: 20px; border-radius: 5px;">
                      <h2>✓ Email enviado com sucesso!</h2>
                      <p>Tempo de envio: ' . $tempo . ' segundos</p>
                      <p>Verifique sua caixa de entrada (e spam)</p>
                      </div>';
    } else {
        $resultado = '<div style="background: #f44336; color: white; padding: 20px; border-radius: 5px;">
                      <h2>✗ Erro ao enviar email</h2>
                      <p>Verifique os logs para mais detalhes</p>
                      </div>';
    }
}

// Verificar configuração
$config = verificarConfiguracaoEmail();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Final - Sistema de Emails</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        input[type="email"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #2563eb;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #1d4ed8;
        }
        .templates {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        code {
            background: #e3f2fd;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Teste Final - Sistema de Emails</h1>
        
        <div class="status">
            <h3>Status do Sistema:</h3>
            <p>• Função mail(): <?php echo $config['mail_disponivel'] ? '<span class="ok">✓ Disponível</span>' : '<span class="error">✗ Não disponível</span>'; ?></p>
            <p>• PHPMailer: <?php echo $config['phpmailer_disponivel'] ? '<span class="ok">✓ Instalado</span>' : '<span class="error">✗ Não encontrado</span>'; ?></p>
            <?php if ($config['phpmailer_disponivel'] && isset($config['phpmailer_versao'])): ?>
                <p>• Versão PHPMailer: <strong><?php echo $config['phpmailer_versao']; ?></strong></p>
            <?php endif; ?>
            <p>• SMTP Configurado: <?php echo $config['smtp_configurado'] ? '<span class="ok">✓ Sim</span>' : '<span class="error">✗ Não</span>'; ?></p>
            <p>• OpenSSL: <?php echo $config['openssl_disponivel'] ? '<span class="ok">✓ Disponível</span>' : '<span class="error">✗ Não disponível</span>'; ?></p>
        </div>
        
        <?php echo $resultado; ?>
        
        <form method="POST">
            <h3>Testar Envio de Email:</h3>
            
            <label>Email de destino:</label>
            <input type="email" name="email" required placeholder="seu@email.com">
            
            <label>Método de teste:</label>
            <select name="metodo">
                <option value="direto">Envio Direto (sem log)</option>
                <option value="com_log">Envio com Log (registra no banco)</option>
            </select>
            
            <button type="submit" name="testar">Enviar Email de Teste</button>
        </form>
        
        <div class="templates">
            <h3>Templates Disponíveis:</h3>
            <p>O sistema enviará um email de boas-vindas (template <code>cadastro</code>)</p>
            <p>Outros templates disponíveis:</p>
            <ul>
                <li><code>recuperar_senha</code> - Recuperação de senha</li>
                <li><code>compra_realizada</code> - Confirmação de compra</li>
                <li><code>pagamento_pendente</code> - Pagamento pendente</li>
                <li><code>assinatura_ativada</code> - Assinatura ativada</li>
            </ul>
        </div>
        
        <?php if (isset($_POST['teste_smtp'])): ?>
            <?php
            $teste_smtp = testarSMTP();
            if ($teste_smtp['sucesso']) {
                echo '<div style="background: #4caf50; color: white; padding: 15px; border-radius: 5px; margin-top: 20px;">
                      <strong>✓ ' . $teste_smtp['mensagem'] . '</strong>
                      </div>';
            } else {
                echo '<div style="background: #f44336; color: white; padding: 15px; border-radius: 5px; margin-top: 20px;">
                      <strong>✗ Erro SMTP:</strong> ' . $teste_smtp['erro'] . '
                      </div>';
            }
            ?>
        <?php endif; ?>
        
        <form method="POST" style="margin-top: 20px;">
            <button type="submit" name="teste_smtp" style="background: #ff9800;">
                Testar Conexão SMTP
            </button>
        </form>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>⚠️ <strong>REMOVA</strong> este arquivo após os testes!</p>
        </div>
    </div>
</body>
</html>