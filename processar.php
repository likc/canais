<?php
require_once 'config.php';
require_once 'email-templates.php';
require_once 'funcoes-email.php';

// Processar ação baseada no POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$acao = $_POST['acao'] ?? '';

switch ($acao) {
    case 'login':
        processarLogin();
        break;
    
    case 'cadastro':
        processarCadastro();
        break;
    
    case 'recuperar_senha':
        processarRecuperacaoSenha();
        break;
    
    case 'redefinir_senha':
        processarRedefinicaoSenha();
        break;
    
    case 'atualizar_perfil':
        processarAtualizacaoPerfil();
        break;
    
    default:
        header('Location: index.php');
        exit;
}

// Função para processar login
function processarLogin() {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    
    if (!$email || !$senha) {
        header('Location: index.php?erro=campos');
        exit;
    }
    
    $pdo = conectarDB();
    
    // Buscar usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND senha = ? AND status = 'ativo'");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        header('Location: index.php?erro=login');
        exit;
    }
    
    // Atualizar último login
    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    
    // Criar sessão
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome_usuario'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_regiao'] = $usuario['regiao'];
    
    // Verificar se tem assinatura ativa
    $assinatura = verificarAssinaturaAtiva($usuario['id']);
    
    if ($assinatura) {
        header('Location: dashboard.php');
    } else {
        header('Location: pagamento.php');
    }
    exit;
}

// Função para processar cadastro
function processarCadastro() {
    $nome_usuario = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['nome_usuario'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $regiao = $_POST['regiao'] ?? 'BRL';
    
    // Validar campos
    if (!$nome_usuario || strlen($nome_usuario) < 3 || !$email || !$telefone || strlen($senha) < 6) {
        header('Location: index.php?erro=campos');
        exit;
    }
    
    // Validar região
    if (!in_array($regiao, ['BRL', 'USD', 'EUR', 'JPY'])) {
        $regiao = 'BRL';
    }
    
    $pdo = conectarDB();
    
    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: index.php?erro=email_existe');
        exit;
    }
    
    // Verificar se nome de usuário já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome_usuario = ?");
    $stmt->execute([$nome_usuario]);
    if ($stmt->fetch()) {
        header('Location: index.php?erro=usuario_existe');
        exit;
    }
    
    try {
        // Inserir novo usuário
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome_usuario, email, telefone, senha, regiao) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nome_usuario, $email, $telefone, $senha, $regiao]);
        
        $usuario_id = $pdo->lastInsertId();
        
        // Enviar email de boas-vindas
        enviarEmailComLog($email, 'cadastro', [
            'nome_usuario' => $nome_usuario,
            'email' => $email
        ], $usuario_id);
        
        header('Location: index.php?sucesso=cadastro');
        exit;
        
    } catch (Exception $e) {
        header('Location: index.php?erro=cadastro');
        exit;
    }
}

// Função para processar recuperação de senha
function processarRecuperacaoSenha() {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        header('Location: recuperar-senha.php?erro=email');
        exit;
    }
    
    $pdo = conectarDB();
    
    // Buscar usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        // Por segurança, não informamos se o email existe ou não
        header('Location: recuperar-senha.php?sucesso=enviado');
        exit;
    }
    
    // Gerar token de recuperação
    $token = gerarToken();
    $expiracao = date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    // Salvar token no banco
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET token_recuperacao = ?, token_expiracao = ? 
        WHERE id = ?
    ");
    $stmt->execute([$token, $expiracao, $usuario['id']]);
    
    // Enviar email
    enviarEmailComLog($email, 'recuperar_senha', [
        'nome_usuario' => $usuario['nome_usuario'],
        'link' => MEMBER_URL . '/recuperar-senha.php?token=' . $token
    ], $usuario['id']);
    
    header('Location: recuperar-senha.php?sucesso=enviado');
    exit;
}

// Função para processar redefinição de senha
function processarRedefinicaoSenha() {
    $token = $_POST['token'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    if (!$token || strlen($nova_senha) < 6) {
        header('Location: recuperar-senha.php?erro=campos');
        exit;
    }
    
    $pdo = conectarDB();
    
    // Buscar usuário pelo token
    $stmt = $pdo->prepare("
        SELECT * FROM usuarios 
        WHERE token_recuperacao = ? 
        AND token_expiracao > NOW()
    ");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        header('Location: recuperar-senha.php?erro=token');
        exit;
    }
    
    // Atualizar senha e limpar token
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET senha = ?, token_recuperacao = NULL, token_expiracao = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$nova_senha, $usuario['id']]);
    
    header('Location: index.php?sucesso=senha');
    exit;
}

// Função para processar atualização de perfil
function processarAtualizacaoPerfil() {
    verificarLogin();
    
    $usuario_id = $_SESSION['usuario_id'];
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $regiao = $_POST['regiao'] ?? '';
    
    if (!$telefone || !in_array($regiao, ['BRL', 'USD', 'EUR', 'JPY'])) {
        header('Location: dashboard.php?erro=campos');
        exit;
    }
    
    $pdo = conectarDB();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET telefone = ?, regiao = ? 
            WHERE id = ?
        ");
        $stmt->execute([$telefone, $regiao, $usuario_id]);
        
        $_SESSION['usuario_regiao'] = $regiao;
        
        header('Location: dashboard.php?sucesso=perfil');
        exit;
        
    } catch (Exception $e) {
        header('Location: dashboard.php?erro=atualizar');
        exit;
    }
}
?>