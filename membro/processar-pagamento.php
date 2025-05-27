<?php
require_once 'config.php';
require_once 'email-templates.php';
require_once 'funcoes-email.php';

// Verificar se está logado
verificarLogin();

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pagamento.php');
    exit;
}

// Obter dados do formulário
$plano = $_POST['plano'] ?? '';
$valor = floatval($_POST['valor'] ?? 0);
$metodo = $_POST['metodo'] ?? '';
$moeda = $_POST['moeda'] ?? 'BRL';

// Validar dados
if (!in_array($plano, ['mensal', 'semestral', 'anual']) || 
    $valor <= 0 || 
    !in_array($metodo, ['pix', 'cartao', 'boleto', 'transferencia']) ||
    !in_array($moeda, ['BRL', 'USD', 'EUR', 'JPY'])) {
    header('Location: pagamento.php?erro=dados_invalidos');
    exit;
}

// Obter dados do usuário
$usuario = obterUsuario($_SESSION['usuario_id']);

// Calcular datas da assinatura
$data_inicio = date('Y-m-d');
$meses = ['mensal' => 1, 'semestral' => 6, 'anual' => 12];
$data_fim = date('Y-m-d', strtotime("+{$meses[$plano]} months"));

$pdo = conectarDB();

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Criar assinatura com status pendente
    $stmt = $pdo->prepare("
        INSERT INTO assinaturas (usuario_id, plano, valor, moeda, data_inicio, data_fim, status, metodo_pagamento) 
        VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?)
    ");
    $stmt->execute([$usuario['id'], $plano, $valor, $moeda, $data_inicio, $data_fim, $metodo]);
    $assinatura_id = $pdo->lastInsertId();
    
    // Gerar ID da transação
    $transacao_id = 'TRX' . date('YmdHis') . $usuario['id'];
    
    // Criar registro de pagamento
    $stmt = $pdo->prepare("
        INSERT INTO pagamentos (usuario_id, assinatura_id, valor, moeda, metodo, status, transacao_id) 
        VALUES (?, ?, ?, ?, ?, 'pendente', ?)
    ");
    $stmt->execute([$usuario['id'], $assinatura_id, $valor, $moeda, $metodo, $transacao_id]);
    
    // Confirmar transação
    $pdo->commit();
    
    // Enviar email de pagamento pendente
    enviarEmailComLog($usuario['email'], 'pagamento_pendente', [
        'nome_usuario' => $usuario['nome_usuario'],
        'plano' => $plano,
        'valor_formatado' => formatarMoeda($valor, $moeda),
        'metodo_pagamento' => ucfirst($metodo)
    ], $usuario['id']);
    
    // Redirecionar para página de instruções de pagamento
    $_SESSION['pagamento_info'] = [
        'transacao_id' => $transacao_id,
        'plano' => $plano,
        'valor' => $valor,
        'moeda' => $moeda,
        'metodo' => $metodo
    ];
    
    header('Location: instrucoes-pagamento.php');
    exit;
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    header('Location: pagamento.php?erro=processamento');
    exit;
}
?>