<?php
// Funções para envio de emails - VERSÃO FINAL
// Configurada para PHPMailer em /membro/phpmailer/src/

// Função principal para enviar emails
function enviarEmail($destinatario, $tipo, $dados = []) {
    // Verificar se PHPMailer está disponível
    $phpmailer_path = __DIR__ . '/phpmailer/src/PHPMailer.php';
    
    if (file_exists($phpmailer_path)) {
        // Usar PHPMailer (preferencial)
        return enviarEmailPHPMailer($destinatario, $tipo, $dados);
    } else {
        // Fallback para mail() nativo
        return enviarEmailNativo($destinatario, $tipo, $dados);
    }
}

// Enviar email usando função mail() nativa
function enviarEmailNativo($destinatario, $tipo, $dados = []) {
    $template = obterTemplateEmail($tipo, $dados);
    
    $headers = [
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>',
        'Reply-To: ' . SMTP_FROM,
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0'
    ];
    
    // Tentar enviar o email
    $resultado = @mail(
        $destinatario,
        $template['assunto'],
        $template['corpo'],
        implode("\r\n", $headers)
    );
    
    // Log do resultado
    if ($resultado) {
        error_log("Email enviado com mail() para: $destinatario - Tipo: $tipo");
    } else {
        error_log("Erro ao enviar email com mail() para: $destinatario - Tipo: $tipo");
    }
    
    return $resultado;
}

// Enviar email usando PHPMailer com SMTP
function enviarEmailPHPMailer($destinatario, $tipo, $dados = []) {
    // Incluir arquivos do PHPMailer
    require_once __DIR__ . '/phpmailer/src/Exception.php';
    require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/src/SMTP.php';
    
    // Criar instância do PHPMailer
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $template = obterTemplateEmail($tipo, $dados);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        
        // Descomente a linha abaixo para debug detalhado
        // $mail->SMTPDebug = 2;
        
        // Timeout para conexão SMTP
        $mail->Timeout = 30;
        
        // Configurações do remetente e destinatário
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($destinatario);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
        
        // Conteúdo do email
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $template['assunto'];
        $mail->Body    = $template['corpo'];
        $mail->AltBody = strip_tags($template['corpo']); // Versão texto
        
        // Enviar
        $mail->send();
        error_log("Email enviado via PHPMailer/SMTP para: $destinatario - Tipo: $tipo");
        return true;
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Erro PHPMailer: " . $mail->ErrorInfo . " - Destinatário: $destinatario");
        
        // Se falhar com SMTP, tentar com mail() nativo
        error_log("Tentando fallback com mail() nativo...");
        return enviarEmailNativo($destinatario, $tipo, $dados);
        
    } catch (Exception $e) {
        error_log("Erro geral ao enviar email: " . $e->getMessage());
        
        // Fallback para mail() nativo
        return enviarEmailNativo($destinatario, $tipo, $dados);
    }
}

// Registrar log de email no banco de dados
function registrarLogEmail($usuario_id, $tipo, $destinatario, $assunto, $status = 'enviado', $erro = null) {
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("
            INSERT INTO logs_email (usuario_id, tipo, destinatario, assunto, status, erro_mensagem) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $tipo, $destinatario, $assunto, $status, $erro]);
        return true;
    } catch (Exception $e) {
        error_log("Erro ao registrar log de email: " . $e->getMessage());
        return false;
    }
}

// Função auxiliar para enviar email com log automático
function enviarEmailComLog($destinatario, $tipo, $dados = [], $usuario_id = null) {
    $template = obterTemplateEmail($tipo, $dados);
    
    // Tentar enviar o email
    $enviado = enviarEmail($destinatario, $tipo, $dados);
    
    // Registrar no log se tiver usuario_id
    if ($usuario_id) {
        registrarLogEmail(
            $usuario_id,
            $tipo,
            $destinatario,
            $template['assunto'],
            $enviado ? 'enviado' : 'erro',
            $enviado ? null : 'Falha no envio'
        );
    }
    
    return $enviado;
}

// Função para verificar configuração de email (útil para debug)
function verificarConfiguracaoEmail() {
    $info = [
        'mail_disponivel' => function_exists('mail'),
        'phpmailer_disponivel' => file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php'),
        'phpmailer_caminho' => __DIR__ . '/phpmailer/src/',
        'smtp_configurado' => defined('SMTP_HOST') && defined('SMTP_USER'),
        'openssl_disponivel' => extension_loaded('openssl')
    ];
    
    // Verificar versão do PHPMailer se disponível
    if ($info['phpmailer_disponivel']) {
        require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $info['phpmailer_versao'] = $mail::VERSION;
    }
    
    return $info;
}

// Função para testar configuração SMTP (útil para debug)
function testarSMTP() {
    if (!file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
        return ['sucesso' => false, 'erro' => 'PHPMailer não encontrado'];
    }
    
    require_once __DIR__ . '/phpmailer/src/Exception.php';
    require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/src/SMTP.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPDebug  = 0; // Sem debug
        
        // Apenas conectar, não enviar
        if ($mail->smtpConnect()) {
            $mail->smtpClose();
            return ['sucesso' => true, 'mensagem' => 'Conexão SMTP bem-sucedida'];
        } else {
            return ['sucesso' => false, 'erro' => 'Falha na conexão SMTP'];
        }
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}
?>