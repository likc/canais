<?php
// Templates de Email

function obterTemplateEmail($tipo, $dados = []) {
    $templates = [
        'cadastro' => [
            'assunto' => 'Bem-vindo ao Canais.net!',
            'corpo' => gerarTemplateCadastro($dados)
        ],
        'recuperar_senha' => [
            'assunto' => 'Recuperação de Senha - Canais.net',
            'corpo' => gerarTemplateRecuperarSenha($dados)
        ],
        'compra_realizada' => [
            'assunto' => 'Compra Realizada - Canais.net',
            'corpo' => gerarTemplateCompraRealizada($dados)
        ],
        'pagamento_pendente' => [
            'assunto' => 'Pagamento Pendente - Canais.net',
            'corpo' => gerarTemplatePagamentoPendente($dados)
        ],
        'assinatura_ativada' => [
            'assunto' => 'Assinatura Ativada - Canais.net',
            'corpo' => gerarTemplateAssinaturaAtivada($dados)
        ]
    ];
    
    return $templates[$tipo] ?? ['assunto' => '', 'corpo' => ''];
}

// Template base HTML
function templateBase($conteudo) {
    return '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                background-color: #f4f4f4; 
                margin: 0; 
                padding: 0; 
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: #fff; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            .header { 
                background: linear-gradient(135deg, #2563eb, #3b82f6); 
                color: white; 
                padding: 30px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
            }
            .content { 
                padding: 30px; 
            }
            .button { 
                display: inline-block; 
                background: #2563eb; 
                color: white; 
                padding: 12px 30px; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0; 
                font-weight: bold; 
            }
            .footer { 
                background: #f8f9fa; 
                padding: 20px; 
                text-align: center; 
                font-size: 14px; 
                color: #666; 
            }
            .info-box { 
                background: #e3f2fd; 
                border: 1px solid #2196f3; 
                border-radius: 5px; 
                padding: 15px; 
                margin: 20px 0; 
            }
            .highlight { 
                color: #2563eb; 
                font-weight: bold; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Canais.net</h1>
            </div>
            <div class="content">
                ' . $conteudo . '
            </div>
            <div class="footer">
                <p>© 2025 Canais.net - Todos os direitos reservados</p>
                <p>Este é um email automático, por favor não responda.</p>
                <p>
                    <a href="' . SITE_URL . '" style="color: #2563eb;">www.canais.net</a> | 
                    <a href="https://wa.me/819042662408" style="color: #2563eb;">WhatsApp</a>
                </p>
            </div>
        </div>
    </body>
    </html>';
}

// Template de Cadastro
function gerarTemplateCadastro($dados) {
    $conteudo = '
        <h2>Bem-vindo ao Canais.net!</h2>
        <p>Olá <span class="highlight">' . ($dados['nome_usuario'] ?? '') . '</span>,</p>
        <p>Sua conta foi criada com sucesso! Agora você pode acessar nossa plataforma e escolher o plano ideal para você.</p>
        
        <div class="info-box">
            <p><strong>Seus dados de acesso:</strong></p>
            <p>Email: ' . ($dados['email'] ?? '') . '</p>
            <p>Usuário: ' . ($dados['nome_usuario'] ?? '') . '</p>
        </div>
        
        <p>Com o Canais.net você tem acesso a:</p>
        <ul>
            <li>+ de 5.000 canais ao vivo</li>
            <li>Filmes e séries em HD/4K</li>
            <li>Canais de esportes e PPV</li>
            <li>Suporte 24/7 via WhatsApp</li>
            <li>Compatível com todos os dispositivos</li>
        </ul>
        
        <center>
            <a href="' . MEMBER_URL . '" class="button">Acessar Área do Cliente</a>
        </center>
        
        <p><strong>Próximos passos:</strong></p>
        <ol>
            <li>Faça login na área do cliente</li>
            <li>Escolha seu plano (mensal, semestral ou anual)</li>
            <li>Realize o pagamento</li>
            <li>Receba suas credenciais e comece a assistir!</li>
        </ol>
        
        <p>Qualquer dúvida, estamos à disposição!</p>
    ';
    
    return templateBase($conteudo);
}

// Template de Recuperação de Senha
function gerarTemplateRecuperarSenha($dados) {
    $conteudo = '
        <h2>Recuperação de Senha</h2>
        <p>Olá <span class="highlight">' . ($dados['nome_usuario'] ?? '') . '</span>,</p>
        <p>Recebemos uma solicitação para redefinir sua senha. Se você não fez esta solicitação, pode ignorar este email.</p>
        
        <p>Para criar uma nova senha, clique no botão abaixo:</p>
        
        <center>
            <a href="' . ($dados['link'] ?? '') . '" class="button">Redefinir Senha</a>
        </center>
        
        <div class="info-box">
            <p><strong>Importante:</strong></p>
            <p>• Este link é válido por apenas 2 horas</p>
            <p>• Por segurança, não compartilhe este link com ninguém</p>
            <p>• Após redefinir, você poderá fazer login com sua nova senha</p>
        </div>
        
        <p>Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
        <p style="word-break: break-all; font-size: 12px;">' . ($dados['link'] ?? '') . '</p>
    ';
    
    return templateBase($conteudo);
}

// Template de Compra Realizada
function gerarTemplateCompraRealizada($dados) {
    $conteudo = '
        <h2>Compra Realizada com Sucesso!</h2>
        <p>Olá <span class="highlight">' . ($dados['nome_usuario'] ?? '') . '</span>,</p>
        <p>Recebemos seu pagamento com sucesso!</p>
        
        <div class="info-box">
            <p><strong>Detalhes da compra:</strong></p>
            <p>Plano: ' . ucfirst($dados['plano'] ?? '') . '</p>
            <p>Valor: ' . ($dados['valor_formatado'] ?? '') . '</p>
            <p>Método de pagamento: ' . ($dados['metodo_pagamento'] ?? '') . '</p>
            <p>ID da transação: ' . ($dados['transacao_id'] ?? '') . '</p>
        </div>
        
        <p><strong>Importante:</strong> Sua assinatura será ativada em até 24 horas úteis. Você receberá um email de confirmação assim que estiver tudo pronto!</p>
        
        <center>
            <a href="' . MEMBER_URL . '/dashboard.php" class="button">Acessar Área do Cliente</a>
        </center>
        
        <p>Enquanto isso, você pode:</p>
        <ul>
            <li>Baixar o aplicativo IPTV Smarters no seu dispositivo</li>
            <li>Ler nossas instruções de instalação</li>
            <li>Entrar em contato conosco se tiver dúvidas</li>
        </ul>
    ';
    
    return templateBase($conteudo);
}

// Template de Pagamento Pendente
function gerarTemplatePagamentoPendente($dados) {
    $conteudo = '
        <h2>Pagamento Pendente</h2>
        <p>Olá <span class="highlight">' . ($dados['nome_usuario'] ?? '') . '</span>,</p>
        <p>Identificamos que seu pedido está com pagamento pendente.</p>
        
        <div class="info-box">
            <p><strong>Detalhes do pedido:</strong></p>
            <p>Plano: ' . ucfirst($dados['plano'] ?? '') . '</p>
            <p>Valor: ' . ($dados['valor_formatado'] ?? '') . '</p>
            <p>Método escolhido: ' . ($dados['metodo_pagamento'] ?? '') . '</p>
        </div>
        
        <p>Para concluir sua compra, finalize o pagamento o quanto antes.</p>
        
        <center>
            <a href="' . MEMBER_URL . '/pagamento.php" class="button">Finalizar Pagamento</a>
        </center>
        
        <p><strong>Formas de pagamento disponíveis:</strong></p>
        <ul>
            <li>PIX (pagamento instantâneo)</li>
            <li>Cartão de crédito</li>
            <li>Boleto bancário</li>
            <li>Transferência bancária</li>
        </ul>
        
        <p>Após a confirmação do pagamento, sua assinatura será ativada em até 24 horas.</p>
    ';
    
    return templateBase($conteudo);
}

// Template de Assinatura Ativada
function gerarTemplateAssinaturaAtivada($dados) {
    $conteudo = '
        <h2>🎉 Assinatura Ativada!</h2>
        <p>Olá <span class="highlight">' . ($dados['nome_usuario'] ?? '') . '</span>,</p>
        <p>Ótimas notícias! Sua assinatura foi ativada com sucesso e você já pode começar a assistir!</p>
        
        <div class="info-box" style="background: #c8e6c9; border-color: #4caf50;">
            <p><strong>Suas credenciais de acesso:</strong></p>
            <p>Usuário: <span class="highlight">' . ($dados['usuario_iptv'] ?? '') . '</span></p>
            <p>Senha: <span class="highlight">' . ($dados['senha_iptv'] ?? '') . '</span></p>
            <p>URL do servidor: <span class="highlight">' . ($dados['url_servidor'] ?? 'http://dns.appcanais.net:80') . '</span></p>
        </div>
        
        <p><strong>Como acessar:</strong></p>
        <ol>
            <li>Baixe o app "IPTV Smarters" no seu dispositivo</li>
            <li>Abra o aplicativo e escolha "Fazer Login com Xtream Codes API"</li>
            <li>Insira as credenciais acima</li>
            <li>Pronto! Comece a assistir!</li>
        </ol>
        
        <center>
            <a href="' . MEMBER_URL . '/dashboard.php" class="button">Ver Instruções Completas</a>
        </center>
        
        <div class="info-box">
            <p><strong>Informações da assinatura:</strong></p>
            <p>Plano: ' . ucfirst($dados['plano'] ?? '') . '</p>
            <p>Válido até: ' . ($dados['data_fim'] ?? '') . '</p>
            <p>Dispositivos simultâneos: 1 tela</p>
        </div>
        
        <p><strong>Precisa de ajuda?</strong></p>
        <p>Nossa equipe está disponível 24/7 via WhatsApp: 
            <a href="https://wa.me/819042662408" style="color: #2563eb;">+81 90-4266-2408</a>
        </p>
        
        <p>Aproveite todo o conteúdo disponível!</p>
    ';
    
    return templateBase($conteudo);
}
?>