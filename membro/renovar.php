<?php
require_once 'config.php';

// Verificar se está logado
verificarLogin();

// Obter plano da URL
$plano = $_GET['plano'] ?? '';

// Validar plano
if (!in_array($plano, ['mensal', 'semestral', 'anual'])) {
    header('Location: dashboard.php#assinatura');
    exit;
}

// Obter dados do usuário e preços
$usuario = obterUsuario($_SESSION['usuario_id']);
$assinatura_atual = verificarAssinaturaAtiva($_SESSION['usuario_id']);

global $PRECOS;
$moeda = $usuario['regiao'];
$valor = $PRECOS[$moeda][$plano];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Assinatura - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="payment-container">
        <!-- Header -->
        <header class="payment-header">
            <div class="container">
                <a href="dashboard.php#assinatura" class="back-link">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <img src="../assets/img/logo.png" alt="Logo" class="payment-logo">
            </div>
        </header>

        <main class="payment-main">
            <div class="renewal-container">
                <div class="renewal-header">
                    <h1>Renovar Assinatura</h1>
                    <p>Continue aproveitando nossos serviços sem interrupção</p>
                </div>

                <?php if ($assinatura_atual): ?>
                <div class="current-subscription">
                    <h3>Assinatura Atual</h3>
                    <div class="subscription-details">
                        <div class="detail-item">
                            <span>Plano:</span>
                            <strong><?php echo ucfirst($assinatura_atual['plano']); ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Vencimento:</span>
                            <strong><?php echo date('d/m/Y', strtotime($assinatura_atual['data_fim'])); ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Dias restantes:</span>
                            <strong><?php echo calcularDiasRestantes($assinatura_atual['data_fim']); ?> dias</strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="renewal-plan">
                    <h3>Renovação Selecionada</h3>
                    <div class="plan-summary">
                        <div class="plan-info">
                            <h4>Plano <?php echo ucfirst($plano); ?></h4>
                            <div class="plan-price">
                                <?php echo formatarMoeda($valor, $moeda); ?>
                            </div>
                            <?php if ($plano === 'semestral'): ?>
                                <p class="plan-savings">Economize 1 mês!</p>
                            <?php elseif ($plano === 'anual'): ?>
                                <p class="plan-savings">Economize 2 meses!</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="plan-benefits">
                            <h5>Benefícios inclusos:</h5>
                            <ul>
                                <li><i class="fas fa-check"></i> Mais de 5.000 canais</li>
                                <li><i class="fas fa-check"></i> Qualidade HD/4K</li>
                                <li><i class="fas fa-check"></i> Filmes e séries</li>
                                <li><i class="fas fa-check"></i> Suporte 24/7</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="payment-methods">
                    <h3>Escolha a forma de pagamento</h3>
                    <div class="methods-grid">
                        <label class="method-option">
                            <input type="radio" name="metodo" value="pix" checked>
                            <div class="method-content">
                                <i class="fas fa-qrcode"></i>
                                <span>PIX</span>
                                <small>Instantâneo</small>
                            </div>
                        </label>
                        
                        <label class="method-option">
                            <input type="radio" name="metodo" value="cartao">
                            <div class="method-content">
                                <i class="fas fa-credit-card"></i>
                                <span>Cartão</span>
                                <small>Crédito</small>
                            </div>
                        </label>
                        
                        <label class="method-option">
                            <input type="radio" name="metodo" value="boleto">
                            <div class="method-content">
                                <i class="fas fa-barcode"></i>
                                <span>Boleto</span>
                                <small>3 dias</small>
                            </div>
                        </label>
                        
                        <label class="method-option">
                            <input type="radio" name="metodo" value="transferencia">
                            <div class="method-content">
                                <i class="fas fa-university"></i>
                                <span>Transferência</span>
                                <small>TED/DOC</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="renewal-notice">
                    <i class="fas fa-info-circle"></i>
                    <p>Sua renovação será aplicada automaticamente ao final do período atual. 
                       Não haverá perda de dias restantes.</p>
                </div>

                <div class="renewal-actions">
                    <button type="button" class="btn btn-outline" onclick="history.back()">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="processarRenovacao()">
                        <i class="fas fa-check"></i> Confirmar Renovação
                    </button>
                </div>
            </div>
        </main>
    </div>

    <style>
        .renewal-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .renewal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .renewal-header h1 {
            margin-bottom: 0.5rem;
        }

        .current-subscription,
        .renewal-plan,
        .payment-methods {
            padding: 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .subscription-details {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .detail-item:not(:last-child) {
            border-bottom: 1px solid var(--border-color);
        }

        .plan-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        .plan-info {
            text-align: center;
        }

        .plan-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 1rem 0;
        }

        .plan-savings {
            color: var(--success-color);
            font-weight: 600;
        }

        .plan-benefits ul {
            list-style: none;
            padding: 0;
        }

        .plan-benefits li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0;
        }

        .plan-benefits i {
            color: var(--success-color);
        }

        .methods-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .method-option {
            position: relative;
            cursor: pointer;
        }

        .method-option input {
            position: absolute;
            opacity: 0;
        }

        .method-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            padding: 1.5rem;
            background: var(--light-color);
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .method-option input:checked + .method-content {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .method-content i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .method-content small {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .renewal-notice {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: rgba(37, 99, 235, 0.05);
            border-left: 4px solid var(--primary-color);
        }

        .renewal-notice i {
            color: var(--primary-color);
            margin-top: 0.25rem;
        }

        .renewal-actions {
            padding: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        @media (max-width: 768px) {
            .plan-summary {
                grid-template-columns: 1fr;
            }
            
            .methods-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function processarRenovacao() {
            const metodo = document.querySelector('input[name="metodo"]:checked').value;
            
            // Criar formulário
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'processar-pagamento.php';
            
            const campos = {
                'plano': '<?php echo $plano; ?>',
                'valor': '<?php echo $valor; ?>',
                'metodo': metodo,
                'moeda': '<?php echo $moeda; ?>'
            };
            
            for (const [nome, valor] of Object.entries(campos)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = nome;
                input.value = valor;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>