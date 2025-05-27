<?php
// Script para atualizar o banco de dados
require_once '../config.php';

echo "<h2>Atualizando Banco de Dados...</h2>";

$pdo = conectarDB();
$erros = [];
$sucessos = [];

// 1. Verificar e adicionar coluna 'campanha'
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM logs_email LIKE 'campanha'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE logs_email ADD COLUMN campanha VARCHAR(100) AFTER tipo");
        $sucessos[] = "✅ Coluna 'campanha' adicionada à tabela logs_email";
    } else {
        $sucessos[] = "✓ Coluna 'campanha' já existe";
    }
} catch (Exception $e) {
    $erros[] = "❌ Erro ao adicionar coluna 'campanha': " . $e->getMessage();
}

// 2. Criar tabela configuracoes
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT,
            descricao VARCHAR(255),
            tipo VARCHAR(50) DEFAULT 'text',
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $sucessos[] = "✅ Tabela 'configuracoes' criada/verificada";
} catch (Exception $e) {
    $erros[] = "❌ Erro ao criar tabela 'configuracoes': " . $e->getMessage();
}

// 3. Criar tabela boletins
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS boletins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            conteudo TEXT NOT NULL,
            status ENUM('rascunho', 'enviado') DEFAULT 'rascunho',
            destinatarios VARCHAR(50),
            total_enviados INT DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_envio TIMESTAMP NULL
        )
    ");
    $sucessos[] = "✅ Tabela 'boletins' criada/verificada";
} catch (Exception $e) {
    $erros[] = "❌ Erro ao criar tabela 'boletins': " . $e->getMessage();
}

// 4. Inserir configurações padrão
$configs = [
    [
        'chave' => 'precos',
        'valor' => '{"BRL":{"simbolo":"R$","mensal":25,"semestral":125,"anual":250},"USD":{"simbolo":"$","mensal":5,"semestral":25,"anual":50},"EUR":{"simbolo":"€","mensal":4.5,"semestral":22,"anual":45},"JPY":{"simbolo":"¥","mensal":700,"semestral":3500,"anual":7000}}',
        'descricao' => 'Preços por região',
        'tipo' => 'json'
    ],
    [
        'chave' => 'dashboard_custom',
        'valor' => '{"cor_primaria":"#2563eb","cor_secundaria":"#3b82f6","logo_url":"","mensagem_boas_vindas":"Bem-vindo ao melhor serviço de IPTV!"}',
        'descricao' => 'Customizações da dashboard',
        'tipo' => 'json'
    ],
    [
        'chave' => 'iptv_config',
        'valor' => '{"url":"http://dns.appcanais.net:80","tipo":"xtream","app":"IPTV Smarters Pro","conexoes":1}',
        'descricao' => 'Configurações do servidor IPTV',
        'tipo' => 'json'
    ],
    [
        'chave' => 'whatsapp_number',
        'valor' => '819042662408',
        'descricao' => 'Número do WhatsApp de suporte',
        'tipo' => 'text'
    ]
];

foreach ($configs as $config) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO configuracoes (chave, valor, descricao, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$config['chave'], $config['valor'], $config['descricao'], $config['tipo']]);
        if ($stmt->rowCount() > 0) {
            $sucessos[] = "✅ Configuração '{$config['chave']}' inserida";
        }
    } catch (Exception $e) {
        $erros[] = "❌ Erro ao inserir configuração '{$config['chave']}': " . $e->getMessage();
    }
}

// 5. Criar índices
$indices = [
    ['tabela' => 'logs_email', 'nome' => 'idx_logs_email_campanha', 'coluna' => 'campanha'],
    ['tabela' => 'assinaturas', 'nome' => 'idx_assinaturas_status_data', 'coluna' => 'status, data_fim'],
    ['tabela' => 'pagamentos', 'nome' => 'idx_pagamentos_data_status', 'coluna' => 'data_pagamento, status']
];

foreach ($indices as $indice) {
    try {
        // Verificar se índice já existe
        $stmt = $pdo->prepare("SHOW INDEX FROM {$indice['tabela']} WHERE Key_name = ?");
        $stmt->execute([$indice['nome']]);
        
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE INDEX {$indice['nome']} ON {$indice['tabela']}({$indice['coluna']})");
            $sucessos[] = "✅ Índice '{$indice['nome']}' criado";
        } else {
            $sucessos[] = "✓ Índice '{$indice['nome']}' já existe";
        }
    } catch (Exception $e) {
        $erros[] = "❌ Erro ao criar índice '{$indice['nome']}': " . $e->getMessage();
    }
}

// Exibir resultados
echo "<h3>Resultados:</h3>";

if (!empty($sucessos)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0;'>";
    echo "<h4>✅ Sucesso:</h4>";
    foreach ($sucessos as $sucesso) {
        echo "<p>$sucesso</p>";
    }
    echo "</div>";
}

if (!empty($erros)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0;'>";
    echo "<h4>❌ Erros:</h4>";
    foreach ($erros as $erro) {
        echo "<p>$erro</p>";
    }
    echo "</div>";
}

echo "<p><strong>Atualização concluída!</strong></p>";
echo "<p><a href='index.php'>Voltar ao Painel Admin</a></p>";
?>