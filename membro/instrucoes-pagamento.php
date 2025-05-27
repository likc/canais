<?php
require_once 'config.php';

// Verificar se está logado
verificarLogin();

// Verificar se tem informações de pagamento na sessão
if (!isset($_SESSION['pagamento_info'])) {
    header('Location: pagamento.php');
    exit;
}

$info = $_SESSION['pagamento_info'];
$usuario = obterUsuario($_SESSION['usuario_id']);

// Limpar informações da sessão após usar
unset($_SESSION['pagamento_info']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruções de Pagamento - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="payment-instructions-container">
        <div class="instructions-box">
            <div class="instructions-header">
                <i class="fas fa-check-circle"></i>
                <h1>Pedido Realizado com Sucesso!</h1>
                <p>ID da Transação: <strong><?php echo $info['transacao_id']; ?></strong></p>
            </div>

            <div class="order-summary">
                <h2>Resumo do Pedido</h2>
                <div class="summary-details">
                    <div class="detail-row">
                        <span>Plano:</span>
                        <strong><?php echo ucfirst($info['plano']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Valor:</span>
                        <strong><?php echo formatarMoeda($info['valor'], $info['moeda']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Método:</span>
                        <strong><?php echo ucfirst($info['metodo']); ?></strong>
                    </div>
                </div>
            </div>

            <?php
            // Mostrar instruções específicas baseadas no método de pagamento
            switch($info['metodo']):
                case 'pix':
            ?>
                <div class="payment-method-instructions">
                    <h2><i class="fas fa-qrcode"></i> Pagamento via PIX</h2>
                    
                    <div class="pix-container">
                        <div class="qr-code-placeholder">
                            <i class="fas fa-qrcode"></i>
                            <p>QR Code PIX</p>
                        </div>
                        
                        <div class="pix-details">
                            <h3>Como pagar:</h3>
                            <ol>
                                <li>Abra o app do seu banco</li>
                                <li>Acesse a área PIX</li>
                                <li>Escaneie o QR Code ou copie o código abaixo</li>
                                <li>Confirme o pagamento</li>
                            </ol>
                            
                            <div class="pix-code">
                                <label>Código PIX (copia e cola):</label>
                                <div class="code-box">
                                    <code id="pix-code">00020126330014BR.GOV.BCB.PIX0111<?php echo str_pad(rand(1, 99999999999), 11, '0', STR_PAD_LEFT); ?></code>
                                    <button onclick="copiarCodigo()" class="btn-copy">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Este código PIX é válido por 24 horas. Após o pagamento, sua assinatura será ativada em até 1 hora.
                    </div>
                </div>
            <?php
                break;
                
                case 'boleto':
            ?>
                <div class="payment-method-instructions">
                    <h2><i class="fas fa-barcode"></i> Pagamento via Boleto</h2>
                    
                    <div class="boleto-info">
                        <p>Seu boleto foi enviado para o email: <strong><?php echo $usuario['email']; ?></strong></p>
                        
                        <div class="boleto-code">
                            <label>Linha digitável:</label>
                            <div class="code-box">
                                <code>34191.79001 01043.510047 91020.150008 <?php echo rand(1, 9); ?> <?php echo str_pad(rand(1, 999999999999999), 15, '0', STR_PAD_LEFT); ?></code>
                            </div>
                        </div>
                        
                        <div class="boleto-buttons">
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir Boleto
                            </button>
                            <button class="btn btn-outline">
                                <i class="fas fa-download"></i> Baixar PDF
                            </button>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Atenção:</strong> O boleto tem vencimento em 3 dias úteis. 
                            Após o pagamento, aguarde 1 a 2 dias úteis para ativação da assinatura.
                        </div>
                    </div>
                </div>
            <?php
                break;
                
                case 'cartao':
            ?>
                <div class="payment-method-instructions">
                    <h2><i class="fas fa-credit-card"></i> Pagamento via Cartão de Crédito</h2>
                    
                    <div class="card-info">
                        <p>Você está sendo redirecionado para nossa página segura de pagamento...</p>
                        
                        <div class="redirect-notice">
                            <div class="loading-spinner"></div>
                            <p>Aguarde alguns segundos...</p>
                        </div>
                        
                        <div class="manual-redirect">
                            <p>Se não for redirecionado automaticamente:</p>
                            <a href="#" class="btn btn-primary">
                                Clique aqui para continuar
                            </a>
                        </div>
                    </div>
                    
                    <script>
                        // Simular redirecionamento (em produção, redirecionar para gateway real)
                        setTimeout(function() {
                            alert('Em produção, você seria redirecionado para o gateway de pagamento.');
                        }, 3000);
                    </script>
                </div>
            <?php
                break;
                
                case 'transferencia':
            ?>
                <div class="payment-method-instructions">
                    <h2><i class="fas fa-university"></i> Pagamento via Transferência Bancária</h2>
                    
                    <div class="bank-info">
                        <h3>Dados para transferência:</h3>
                        
                        <div class="bank-details">
                            <div class="bank-item">
                                <label>Banco:</label>
                                <strong>Banco do Brasil</strong>
                            </div>
                            <div class="bank-item">
                                <label>Agência:</label>
                                <strong>1234-5</strong>
                            </div>
                            <div class="bank-item">
                                <label>Conta Corrente:</label>
                                <strong>12345-6</strong>
                            </div>
                            <div class="bank-item">
                                <label>CNPJ:</label>
                                <strong>12.345.678/0001-90</strong>
                            </div>
                            <div class="bank-item">
                                <label>Razão Social:</label>
                                <strong>Canais.net Ltda</strong>
                            </div>
                            <div class="bank-item highlight">
                                <label>Valor:</label>
                                <strong><?php echo formatarMoeda($info['valor'], $info['moeda']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Importante:</strong> Após realizar a transferência, envie o comprovante via WhatsApp 
                            para <a href="https://wa.me/819042662408" target="_blank">+81 90-4266-2408</a> 
                            informando o ID da transação: <strong><?php echo $info['transacao_id']; ?></strong>
                        </div>
                    </div>
                </div>
            <?php
                break;
            endswitch;
            ?>

            <div class="next-steps">
                <h3>Próximos Passos</h3>
                <div class="steps-timeline">
                    <div class="timeline-item active">
                        <div class="timeline-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Pedido Realizado</h4>
                            <p>Seu pedido foi registrado em nosso sistema</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Aguardando Pagamento</h4>
                            <p>Realize o pagamento conforme instruções acima</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Processamento</h4>
                            <p>Confirmaremos seu pagamento em até 24h</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Assinatura Ativa</h4>
                            <p>Você receberá suas credenciais por email</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Voltar ao Dashboard
                </a>
                <a href="https://wa.me/819042662408?text=Olá, fiz um pedido com ID: <?php echo $info['transacao_id']; ?>" 
                   target="_blank" class="btn btn-primary">
                    <i class="fab fa-whatsapp"></i> Falar com Suporte
                </a>
            </div>
        </div>
    </div>

    <style>
        .payment-instructions-container {
            min-height: 100vh;
            background: var(--light-color);
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .instructions-box {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .instructions-header {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .instructions-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .instructions-header h1 {
            margin-bottom: 0.5rem;
        }

        .order-summary {
            padding: 2rem;
            background: var(--light-color);
            border-bottom: 1px solid var(--border-color);
        }

        .summary-details {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .detail-row:not(:last-child) {
            border-bottom: 1px solid var(--border-color);
        }

        .payment-method-instructions {
            padding: 2rem;
        }

        .pix-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        .qr-code-placeholder {
            background: var(--light-color);
            border: 2px dashed var(--border-color);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-code-placeholder i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .code-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .code-box code {
            flex: 1;
            font-family: monospace;
            font-size: 0.875rem;
            word-break: break-all;
        }

        .btn-copy {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-copy:hover {
            background: var(--secondary-color);
        }

        .bank-details {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .bank-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .bank-item:last-child {
            border-bottom: none;
        }

        .bank-item.highlight {
            background: rgba(37, 99, 235, 0.1);
            padding: 0.75rem;
            margin: 0 -1rem;
            border-radius: 0.25rem;
        }

        .steps-timeline {
            display: grid;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            opacity: 0.5;
        }

        .timeline-item.active {
            opacity: 1;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .timeline-item.active .timeline-icon {
            background: var(--success-color);
        }

        .timeline-content h4 {
            margin-bottom: 0.25rem;
            color: var(--text-dark);
        }

        .timeline-content p {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .next-steps {
            padding: 2rem;
            background: var(--light-color);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .actions {
            padding: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .redirect-notice {
            text-align: center;
            padding: 2rem;
        }

        .manual-redirect {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .pix-container {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>

    <script>
        function copiarCodigo() {
            const codigo = document.getElementById('pix-code').textContent;
            navigator.clipboard.writeText(codigo).then(function() {
                alert('Código PIX copiado!');
            });
        }
    </script>
</body>
</html>