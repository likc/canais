<?php
require_once 'config.php';

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Detectar região do usuário (opcional)
$regiao_padrao = 'BRL';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <img src="../assets/img/logo.png" alt="Logo" class="auth-logo">
                <h1>Área do Cliente</h1>
                <p>Faça login ou cadastre-se para continuar</p>
            </div>

            <!-- Mensagens de erro/sucesso -->
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-error">
                    <?php 
                    switch($_GET['erro']) {
                        case 'login': echo 'Email ou senha incorretos!'; break;
                        case 'campos': echo 'Preencha todos os campos!'; break;
                        case 'email_existe': echo 'Este email já está cadastrado!'; break;
                        case 'usuario_existe': echo 'Este nome de usuário já está em uso!'; break;
                        case 'cadastro': echo 'Erro ao cadastrar. Tente novamente!'; break;
                        default: echo 'Ocorreu um erro. Tente novamente!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch($_GET['sucesso']) {
                        case 'cadastro': echo 'Cadastro realizado com sucesso! Faça login para continuar.'; break;
                        case 'senha': echo 'Senha redefinida com sucesso!'; break;
                        case 'logout': echo 'Você saiu com sucesso!'; break;
                        default: echo 'Operação realizada com sucesso!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="tab-btn active" onclick="mostrarTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button class="tab-btn" onclick="mostrarTab('cadastro')">
                    <i class="fas fa-user-plus"></i> Cadastro
                </button>
            </div>

            <!-- Formulário de Login -->
            <div id="login-form" class="tab-content active">
                <form action="processar.php" method="POST">
                    <input type="hidden" name="acao" value="login">
                    
                    <div class="form-group">
                        <label for="login-email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="login-email" name="email" required 
                               placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="login-senha">
                            <i class="fas fa-lock"></i> Senha
                        </label>
                        <input type="password" id="login-senha" name="senha" required 
                               placeholder="Sua senha">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>

                    <div class="form-footer">
                        <a href="recuperar-senha.php" class="link">
                            <i class="fas fa-key"></i> Esqueceu sua senha?
                        </a>
                    </div>
                </form>
            </div>

            <!-- Formulário de Cadastro -->
            <div id="cadastro-form" class="tab-content">
                <form action="processar.php" method="POST">
                    <input type="hidden" name="acao" value="cadastro">
                    
                    <div class="form-group">
                        <label for="cadastro-nome">
                            <i class="fas fa-user"></i> Nome de Usuário
                        </label>
                        <input type="text" id="cadastro-nome" name="nome_usuario" required 
                               placeholder="Escolha um nome de usuário" 
                               pattern="[a-zA-Z0-9_]{3,50}"
                               title="Use apenas letras, números e underscore. Mínimo 3 caracteres.">
                    </div>

                    <div class="form-group">
                        <label for="cadastro-email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="cadastro-email" name="email" required 
                               placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="cadastro-telefone">
                            <i class="fab fa-whatsapp"></i> Telefone/WhatsApp
                        </label>
                        <input type="tel" id="cadastro-telefone" name="telefone" required 
                               placeholder="(00) 00000-0000">
                    </div>

                    <div class="form-group">
                        <label for="cadastro-senha">
                            <i class="fas fa-lock"></i> Senha
                        </label>
                        <input type="password" id="cadastro-senha" name="senha" required 
                               placeholder="Mínimo 6 caracteres"
                               minlength="6">
                        <small class="form-hint">
                            <i class="fas fa-info-circle"></i> 
                            A senha será armazenada sem criptografia para integração com outros sistemas
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="cadastro-regiao">
                            <i class="fas fa-globe"></i> Região
                        </label>
                        <select id="cadastro-regiao" name="regiao" required>
                            <option value="BRL">🇧🇷 Brasil (R$)</option>
                            <option value="USD">🇺🇸 Estados Unidos ($)</option>
                            <option value="EUR">🇪🇺 Europa (€)</option>
                            <option value="JPY">🇯🇵 Japão (¥)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Criar Conta
                    </button>

                    <div class="form-footer">
                        <small>
                            Ao criar uma conta, você concorda com nossos 
                            <a href="<?php echo SITE_URL; ?>/termos" target="_blank">Termos de Serviço</a> e 
                            <a href="<?php echo SITE_URL; ?>/privacidade" target="_blank">Política de Privacidade</a>
                        </small>
                    </div>
                </form>
            </div>

            <!-- Links adicionais -->
            <div class="auth-footer">
                <p>Precisa de ajuda?</p>
                <a href="https://wa.me/819042662408" target="_blank" class="btn btn-outline">
                    <i class="fab fa-whatsapp"></i> Falar com Suporte
                </a>
            </div>
        </div>
    </div>

    <script>
        // Função para alternar entre tabs
        function mostrarTab(tab) {
            // Remove active de todos os botões e conteúdos
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Adiciona active ao botão e conteúdo selecionados
            if (tab === 'login') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('cadastro-form').classList.add('active');
            }
        }

        // Máscara para telefone
        document.getElementById('cadastro-telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 6) {
                value = `(${value.slice(0,2)}) ${value.slice(2,7)}-${value.slice(7)}`;
            } else if (value.length > 2) {
                value = `(${value.slice(0,2)}) ${value.slice(2)}`;
            } else if (value.length > 0) {
                value = `(${value}`;
            }
            
            e.target.value = value;
        });

        // Detectar região automaticamente (opcional)
        fetch('https://ipapi.co/json/')
            .then(response => response.json())
            .then(data => {
                const countryCode = data.country_code;
                const select = document.getElementById('cadastro-regiao');
                
                if (countryCode === 'JP') {
                    select.value = 'JPY';
                } else if (countryCode === 'US') {
                    select.value = 'USD';
                } else if (['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 
                           'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 
                           'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'GB', 'NO', 'CH'].includes(countryCode)) {
                    select.value = 'EUR';
                }
            })
            .catch(() => {
                // Se falhar, mantém Brasil como padrão
            });
    </script>
</body>
</html>