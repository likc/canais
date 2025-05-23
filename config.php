<?php
// Configurações do Sistema
session_start();

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'minec761_canaisdb');
define('DB_USER', 'minec761_canaisdb');
define('DB_PASS', '4nzch1qbws5c');

// Configurações do Site
define('SITE_URL', 'https://canais.net');
define('SITE_NAME', 'Canais.net');
define('MEMBER_URL', SITE_URL . '/membro');

// Configurações de Email (Mailgun)
define('SMTP_HOST', 'smtp.mailgun.org');
define('SMTP_PORT', 465);
define('SMTP_USER', 'contato@canais.net');
define('SMTP_PASS', '40ba49073ce506d64a3c4b284649d63b-f3238714-7a62cb5d');
define('SMTP_FROM', 'contato@canais.net');
define('SMTP_FROM_NAME', 'Canais.net');

// Configurações de Preços por Região
$PRECOS = [
    'BRL' => [
        'simbolo' => 'R$',
        'mensal' => 25.00,
        'semestral' => 125.00,
        'anual' => 250.00
    ],
    'USD' => [
        'simbolo' => '$',
        'mensal' => 5.00,
        'semestral' => 25.00,
        'anual' => 50.00
    ],
    'EUR' => [
        'simbolo' => '€',
        'mensal' => 4.50,
        'semestral' => 22.00,
        'anual' => 45.00
    ],
    'JPY' => [
        'simbolo' => '¥',
        'mensal' => 700.00,
        'semestral' => 3500.00,
        'anual' => 7000.00
    ]
];

// Função de Conexão com o Banco
function conectarDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}

// Função para verificar se usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . MEMBER_URL . '/index.php');
        exit;
    }
}

// Função para obter dados do usuário
function obterUsuario($id) {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Função para verificar assinatura ativa
function verificarAssinaturaAtiva($usuario_id) {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        SELECT * FROM assinaturas 
        WHERE usuario_id = ? 
        AND status = 'ativa' 
        AND data_fim >= CURDATE()
        ORDER BY data_fim DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch();
}

// Função para formatar moeda
function formatarMoeda($valor, $moeda) {
    global $PRECOS;
    $simbolo = $PRECOS[$moeda]['simbolo'];
    
    switch($moeda) {
        case 'BRL':
            return $simbolo . ' ' . number_format($valor, 2, ',', '.');
        case 'USD':
        case 'EUR':
            return $simbolo . number_format($valor, 2, '.', ',');
        case 'JPY':
            return $simbolo . number_format($valor, 0, '', ',');
        default:
            return $valor;
    }
}

// Função para calcular dias restantes
function calcularDiasRestantes($data_fim) {
    $hoje = new DateTime();
    $fim = new DateTime($data_fim);
    $intervalo = $hoje->diff($fim);
    return $intervalo->days;
}

// Função para gerar token único
function gerarToken($tamanho = 32) {
    return bin2hex(random_bytes($tamanho));
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');
?>