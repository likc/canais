<?php
require_once 'config.php';

// Verificar se está logado
verificarLogin();

// Obter dados do usuário
$usuario = obterUsuario($_SESSION['usuario_id']);

// Verificar se já tem assinatura ativa
$assinatura = verificarAssinaturaAtiva($_SESSION['usuario_id']);
if ($assinatura) {
    header('Location: dashboard.php');
    exit;
}

// Obter preços baseados na região do usuário
global $PRECOS;
$moeda = $usuario['regiao'];
$precos = $PRECOS[$moeda];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu Plano - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="payment-container">
        <!-- Header -->
        <header class="payment-header">
            <div class="container">
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <img src="../assets/img/logo.png" alt="Logo" class="payment-logo">
            </div>
        </header>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <span>Escolha o Plano</span>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <span>Método de Pagamento</span>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <span>Confirmação</span>
            </div>
        </div>

        <!-- Main Content -->
        <main class="payment-main">
            <!-- Step 1: Choose Plan -->
            <section id="step-1" class="payment-step active">
                <div class="step-header">
                    <h1>Escolha o plano ideal para você</h1>
                    <p>Todos os planos incluem acesso completo ao nosso conteúdo</p>
                </div>

                <div class="plans-container">
                    <div class="plan-option" data-plano="mensal">
                        <div class="plan-header">
                            <h3>Plano Mensal</h3>
                            <div class="plan-price">
                                <span class="currency"><?php echo $precos['simbolo']; ?></span>
                                <span class="amount"><?php echo number_format($precos['mensal'], $moeda === 'JPY' ? 0 : 2, ',', '.'); ?></span>
                                <span class="period">/mês</span>
                            </div>
                        </div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> Mais de 5.000 canais</li>
                            <li><i class="fas fa-check"></i> Qualidade HD/4K</li>
                            <li><i class="fas fa-check"></i> Filmes e séries</li>
                            <li><i class="fas fa-check"></i> Suporte 24/7</li>
                            <li><i class="fas fa-check"></i> Sem fidelidade</li>
                        </ul>
                        <button class="btn btn-primary btn-block select-plan" data-plano="mensal" data-valor="<?php echo $precos['mensal']; ?>">
                            Selecionar Mensal
                        </button>
                    </div>

                    <div class="plan-option featured" data-plano="semestral">
                        <div class="plan-badge">Mais Popular</div>
                        <div class="plan-header">
                            <h3>Plano Semestral</h3>
                            <div class="plan-price">
                                <span class="currency"><?php echo $precos['simbolo']; ?></span>
                                <span class="amount"><?php echo number_format($precos['semestral'], $moeda === 'JPY' ? 0 : 2, ',', '.'); ?></span>
                                <span class="period">/6 meses</span>
                            </div>
                            <div class="plan-savings">Economize 1 mês!</div>
                        </div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> Mais de 5.000 canais</li>
                            <li><i class="fas fa-check"></i> Qualidade HD/4K</li>
                            <li><i class="fas fa-check"></i> Filmes e séries</li>
                            <li><i class="fas fa-check"></i> Suporte 24/7</li>
                            <li><i class="fas fa-check"></i> Sem fidelidade</li>
                        </ul>
                        <button class="btn btn-primary btn-block select-plan" data-plano="semestral" data-valor="<?php echo $precos['semestral']; ?>">
                            Selecionar Semestral
                        </button>
                    </div>

                    <div class="plan-option" data-plano="anual">
                        <div class="plan-header">
                            <h3>Plano Anual</h3>
                            <div class="plan-price">
                                <span class="currency"><?php echo $precos['simbolo']; ?></span>
                                <span class="amount"><?php echo number_format($precos['anual'], $moeda === 'JPY' ? 0 : 2, ',', '.'); ?></span>
                                <span class="period">/ano</span>
                            </div>
                            <div class="plan-savings">Economize 2 meses!</div>
                        </div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> Mais de 5.000 canais</li>
                            <li><i class="fas fa-check"></i> Qualidade HD/4K</li>
                            <li><i class="fas fa-check"></i> Filmes e séries</li>
                            <li><i class="fas fa-check"></i> Suporte 24/7</li>
                            <li><i class="fas fa-check"></i> Sem fidelidade</li>
                        </ul>
                        <button class="btn btn-primary btn-block select-plan" data-plano="anual" data-valor="<?php echo $precos['anual']; ?>">
                            Selecionar Anual
                        </button>
                    </div>
                </div>

                <div class="guarantee-box">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <h4>Garantia de Satisfação</h4>
                        <p>Teste grátis de 3 horas disponível. Cancele quando quiser, sem multas!</p>
                    </div>
                </div>
            </section>

            <!-- Step 2: Payment Method -->
            <section id="step-2" class="payment-step">
                <div class="step-header">
                    <h1>Escolha a forma de pagamento</h1>
                    <p>Processamento seguro e rápido</p>
                </div>

                <div class="payment-summary">
                    <h3>Resumo do Pedido</h3>
                    <div class="summary-item">
                        <span>Plano:</span>
                        <span id="summary-plano">-</span>
                    </div>
                    <div class="summary-item total">
                        <span>Total:</span>
                        <span id="summary-valor">-</span>
                    </div>
                </div>

                <div class="payment-methods-grid">
                    <div class="payment-method" data-metodo="pix">
                        <div class="method-icon">
                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSI+PHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiByeD0iMTAiIGZpbGw9IiM0REI2QUMiLz48dGV4dCB4PSIzMCIgeT0iMzgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjQiIGZvbnQtd2VpZ2h0PSJib2xkIj5QSVg8L3RleHQ+PC9zdmc+" alt="PIX">
                        </div>
                        <h4>PIX</h4>
                        <p>Pagamento instantâneo</p>
                        <span class="method-badge">Mais rápido</span>
                    </div>

                    <div class="payment-method" data-metodo="cartao">
                        <div class="method-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4>Cartão de Crédito</h4>
                        <p>Visa, Mastercard, Elo</p>
                    </div>

                    <div class="payment-method" data-metodo="boleto">
                        <div class="method-icon">
                            <i class="fas fa-barcode"></i>
                        </div>
                        <h4>Boleto Bancário</h4>
                        <p>Vencimento em 3 dias</p>
                    </div>

                    <div class="payment-method" data-metodo="transferencia">
                        <div class="method-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <h4>Transferência</h4>
                        <p>TED ou DOC</p>
                    </div>
                </div>

                <div class="payment-actions">
                    <button class="btn btn-outline" onclick="voltarEtapa(1)">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button class="btn btn-primary" id="btn-continuar-pagamento" disabled>
                        Continuar <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <!-- Step 3: Confirmation -->
            <section id="step-3" class="payment-step">
                <div class="step-header">
                    <h1>Confirme seu pedido</h1>
                    <p>Revise os detalhes antes de finalizar</p>
                </div>

                <div class="confirmation-container">
                    <div class="order-details">
                        <h3>Detalhes do Pedido</h3>
                        <div class="detail-item">
                            <label>Plano:</label>
                            <span id="confirm-plano">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Valor:</label>
                            <span id="confirm-valor">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Método de Pagamento:</label>
                            <span id="confirm-metodo">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($usuario['email']); ?></span>
                        </div>
                    </div>

                    <div class="payment-instructions" id="payment-instructions">
                        <!-- As instruções serão inseridas dinamicamente via JavaScript -->
                    </div>

                    <div class="terms-checkbox">
                        <label>
                            <input type="checkbox" id="accept-terms">
                            Li e aceito os <a href="<?php echo SITE_URL; ?>/termos" target="_blank">Termos de Serviço</a> 
                            e a <a href="<?php echo SITE_URL; ?>/privacidade" target="_blank">Política de Privacidade</a>
                        </label>
                    </div>

                    <div class="confirmation-actions">
                        <button class="btn btn-outline" onclick="voltarEtapa(2)">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </button>
                        <button class="btn btn-primary" id="btn-finalizar" disabled>
                            <i class="fas fa-check"></i> Finalizar Pedido
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="payment-footer">
            <p>
                <i class="fas fa-lock"></i> Pagamento 100% seguro | 
                <i class="fas fa-shield-alt"></i> Seus dados estão protegidos
            </p>
        </footer>
    </div>

    <!-- Loading Modal -->
    <div id="loading-modal" class="modal">
        <div class="modal-content">
            <div class="loading-spinner"></div>
            <p>Processando seu pedido...</p>
        </div>
    </div>

    <script>
        // Variáveis globais
        let planoSelecionado = null;
        let valorSelecionado = null;
        let metodoSelecionado = null;

        // Selecionar plano
        document.querySelectorAll('.select-plan').forEach(btn => {
            btn.addEventListener('click', function() {
                planoSelecionado = this.dataset.plano;
                valorSelecionado = this.dataset.valor;
                
                // Atualizar resumo
                document.getElementById('summary-plano').textContent = 
                    planoSelecionado.charAt(0).toUpperCase() + planoSelecionado.slice(1);
                document.getElementById('summary-valor').textContent = 
                    '<?php echo $precos['simbolo']; ?> ' + 
                    parseFloat(valorSelecionado).toLocaleString('pt-BR', {
                        minimumFractionDigits: <?php echo $moeda === 'JPY' ? 0 : 2; ?>,
                        maximumFractionDigits: <?php echo $moeda === 'JPY' ? 0 : 2; ?>
                    });
                
                // Ir para próxima etapa
                irParaEtapa(2);
            });
        });

        // Selecionar método de pagamento
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remover seleção anterior
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                
                // Adicionar seleção
                this.classList.add('selected');
                metodoSelecionado = this.dataset.metodo;
                
                // Habilitar botão
                document.getElementById('btn-continuar-pagamento').disabled = false;
            });
        });

        // Continuar para confirmação
        document.getElementById('btn-continuar-pagamento').addEventListener('click', function() {
            if (!metodoSelecionado) return;
            
            // Atualizar confirmação
            document.getElementById('confirm-plano').textContent = 
                planoSelecionado.charAt(0).toUpperCase() + planoSelecionado.slice(1);
            document.getElementById('confirm-valor').textContent = 
                '<?php echo $precos['simbolo']; ?> ' + 
                parseFloat(valorSelecionado).toLocaleString('pt-BR', {
                    minimumFractionDigits: <?php echo $moeda === 'JPY' ? 0 : 2; ?>,
                    maximumFractionDigits: <?php echo $moeda === 'JPY' ? 0 : 2; ?>
                });
            
            const metodosNomes = {
                'pix': 'PIX',
                'cartao': 'Cartão de Crédito',
                'boleto': 'Boleto Bancário',
                'transferencia': 'Transferência Bancária'
            };
            document.getElementById('confirm-metodo').textContent = metodosNomes[metodoSelecionado];
            
            // Mostrar instruções específicas
            mostrarInstrucoesPagamento(metodoSelecionado);
            
            // Ir para próxima etapa
            irParaEtapa(3);
        });

        // Aceitar termos
        document.getElementById('accept-terms').addEventListener('change', function() {
            document.getElementById('btn-finalizar').disabled = !this.checked;
        });

        // Finalizar pedido
        document.getElementById('btn-finalizar').addEventListener('click', function() {
            if (!document.getElementById('accept-terms').checked) return;
            
            // Mostrar loading
            document.getElementById('loading-modal').style.display = 'flex';
            
            // Criar formulário para enviar dados
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'processar-pagamento.php';
            
            const campos = {
                'plano': planoSelecionado,
                'valor': valorSelecionado,
                'metodo': metodoSelecionado,
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
        });

        // Funções auxiliares
        function irParaEtapa(etapa) {
            // Esconder todas as etapas
            document.querySelectorAll('.payment-step').forEach(step => {
                step.classList.remove('active');
            });
            
            // Mostrar etapa selecionada
            document.getElementById(`step-${etapa}`).classList.add('active');
            
            // Atualizar indicadores de progresso
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index < etapa) {
                    step.classList.add('completed');
                } else if (index === etapa - 1) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('completed', 'active');
                }
            });
            
            // Scroll para o topo
            window.scrollTo(0, 0);
        }

        function voltarEtapa(etapa) {
            irParaEtapa(etapa);
        }

        function mostrarInstrucoesPagamento(metodo) {
            const instrucoes = {
                'pix': `
                    <h4><i class="fas fa-qrcode"></i> Instruções para pagamento via PIX</h4>
                    <ol>
                        <li>Após finalizar o pedido, você receberá um QR Code por email</li>
                        <li>Abra o app do seu banco e selecione a opção PIX</li>
                        <li>Escaneie o QR Code ou copie o código PIX</li>
                        <li>Confirme o pagamento</li>
                        <li>Sua assinatura será ativada em até 24 horas</li>
                    </ol>
                `,
                'cartao': `
                    <h4><i class="fas fa-credit-card"></i> Instruções para pagamento via Cartão</h4>
                    <ol>
                        <li>Você será redirecionado para nossa página segura de pagamento</li>
                        <li>Insira os dados do seu cartão</li>
                        <li>Confirme o pagamento</li>
                        <li>Sua assinatura será ativada imediatamente</li>
                    </ol>
                `,
                'boleto': `
                    <h4><i class="fas fa-barcode"></i> Instruções para pagamento via Boleto</h4>
                    <ol>
                        <li>Após finalizar, você receberá o boleto por email</li>
                        <li>O boleto tem vencimento em 3 dias úteis</li>
                        <li>Pague em qualquer banco ou casa lotérica</li>
                        <li>Após compensação (1-2 dias), sua assinatura será ativada</li>
                    </ol>
                `,
                'transferencia': `
                    <h4><i class="fas fa-university"></i> Instruções para Transferência Bancária</h4>
                    <ol>
                        <li>Você receberá os dados bancários por email</li>
                        <li>Realize a transferência (TED ou DOC)</li>
                        <li>Envie o comprovante via WhatsApp</li>
                        <li>Sua assinatura será ativada em até 24 horas</li>
                    </ol>
                `
            };
            
            document.getElementById('payment-instructions').innerHTML = instrucoes[metodo];
        }
    </script>
</body>
</html>