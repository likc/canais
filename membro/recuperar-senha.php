<?php
require_once 'config.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Verificar se tem token na URL
$token = $_GET['token'] ?? '';
$modo = $token ? 'redefinir' : 'solicitar';

// Se tiver token, verificar se é válido
$usuario_token = null;
if ($token) {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        SELECT * FROM usuarios 
        WHERE token_recuperacao = ? 
        AND token_expiracao > NOW()
    ");
    $stmt->execute([$token]);
    $usuario_token = $stmt->fetch();
    
    if (!$usuario_token) {
        $modo = 'token_invalido';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <img src="../assets/img/logo.png" alt="Logo" class="auth-logo">
                <h1>Recuperar Senha</h1>
                <p>
                    <?php if ($modo === 'redefinir'): ?>
                        Crie uma nova senha para sua conta
                    <?php else: ?>
                        Informe seu email para receber as instruções
                    <?php endif; ?>
                </p>
            </div>

            <!-- Mensagens -->
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-error">
                    <?php 
                    switch($_GET['erro']) {
                        case 'email': echo 'Email inválido!'; break;
                        case 'campos': echo 'Preencha todos os campos!'; break;
                        case 'token': echo 'Link inválido ou expirado!'; break;
                        default: echo 'Ocorreu um erro. Tente novamente!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <?php 
                    if ($_GET['sucesso'] === 'enviado') {
                        echo 'Se o email estiver cadastrado, você receberá as instruções em breve.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="auth-content">
                <?php if ($modo === 'token_invalido'): ?>
                    <div class="token-invalid">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3>Link Inválido ou Expirado</h3>
                        <p>Este link de recuperação não é válido ou já expirou.</p>
                        <p>Os links de recuperação são válidos por apenas 2 horas.</p>
                        <a href="recuperar-senha.php" class="btn btn-primary">
                            Solicitar Novo Link
                        </a>
                    </div>
                
                <?php elseif ($modo === 'redefinir'): ?>
                    <!-- Formulário para redefinir senha -->
                    <form action="processar.php" method="POST">
                        <input type="hidden" name="acao" value="redefinir_senha">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="user-info">
                            <i class="fas fa-user-circle"></i>
                            <p>Redefinindo senha para: <strong><?php echo htmlspecialchars($usuario_token['email']); ?></strong></p>
                        </div>
                        
                        <div class="form-group">
                            <label for="nova-senha">
                                <i class="fas fa-lock"></i> Nova Senha
                            </label>
                            <input type="password" id="nova-senha" name="nova_senha" required 
                                   placeholder="Mínimo 6 caracteres" 
                                   minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar-senha">
                                <i class="fas fa-lock"></i> Confirmar Nova Senha
                            </label>
                            <input type="password" id="confirmar-senha" name="confirmar_senha" required 
                                   placeholder="Digite a senha novamente">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Salvar Nova Senha
                        </button>
                    </form>
                
                <?php else: ?>
                    <!-- Formulário para solicitar recuperação -->
                    <form action="processar.php" method="POST">
                        <input type="hidden" name="acao" value="recuperar_senha">
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Cadastrado
                            </label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="seu@email.com"
                                   value="<?php echo $_GET['email'] ?? ''; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Enviar Instruções
                        </button>
                    </form>
                <?php endif; ?>

                <div class="form-footer">
                    <a href="index.php" class="link">
                        <i class="fas fa-arrow-left"></i> Voltar ao Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .auth-content {
            padding: 2rem;
        }

        .token-invalid {
            text-align: center;
            padding: 2rem 0;
        }

        .token-invalid i {
            font-size: 3rem;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }

        .token-invalid h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .token-invalid p {
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .user-info {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .user-info i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .user-info p {
            color: var(--text-light);
            margin: 0;
        }
    </style>

    <script>
        // Validar senhas iguais
        document.getElementById('confirmar-senha')?.addEventListener('input', function() {
            const senha = document.getElementById('nova-senha').value;
            const confirmar = this.value;
            
            if (senha !== confirmar) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>