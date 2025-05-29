<?php
/**
 * CORRETOR DE ERROS DO ADMIN - CANAIS.NET
 * 
 * Este script corrige os erros específicos encontrados no sistema
 */

// Função para exibir status
function exibirStatus($mensagem, $tipo = 'success') {
    $icon = $tipo == 'success' ? '✅' : ($tipo == 'error' ? '❌' : '⚠️');
    $cor = $tipo == 'success' ? '#4caf50' : ($tipo == 'error' ? '#f44336' : '#ff9800');
    echo "<div style='padding: 10px; margin: 5px 0; color: $cor; font-family: Arial, sans-serif;'>$icon $mensagem</div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corretor de Erros - Admin Canais.net</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #e74c3c;
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 10px;
        }
        .error-box {
            background: #fee;
            border: 1px solid #fcc;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover {
            background: #229954;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Corretor de Erros do Admin</h1>
    
    <div class="error-box">
        <h3>Erros Detectados:</h3>
        <ol>
            <li><strong>Erro no configuracoes.php</strong> - Undefined array key "modulos" (linha 518)</li>
            <li><strong>Função duplicada</strong> - formatarMoeda() já declarada em config.php</li>
        </ol>
    </div>

<?php

if (isset($_POST['corrigir'])) {
    echo '<h2>Aplicando Correções...</h2>';
    
    // CORREÇÃO 1: Erro no configuracoes.php
    echo '<h3>1. Corrigindo erro no configuracoes.php...</h3>';
    
    $arquivo_config = 'membro/admin/configuracoes.php';
    if (file_exists($arquivo_config)) {
        $conteudo = file_get_contents($arquivo_config);
        
        // Procurar pela linha problemática (linha 518 ou próxima)
        // O erro está em: in_array('instrucoes', $_SESSION['modulos'])
        
        // Substituir todas as ocorrências de in_array sem verificação
        $padrao = '/in_array\s*\(\s*[\'"](\w+)[\'"]\s*,\s*\$_SESSION\[[\'"]modulos[\'"]\]\s*\)/';
        $substituicao = '(isset($_SESSION["modulos"]) && is_array($_SESSION["modulos"]) && in_array("$1", $_SESSION["modulos"]))';
        
        $conteudo_novo = preg_replace($padrao, $substituicao, $conteudo);
        
        // Também verificar por variações
        $conteudo_novo = str_replace(
            'in_array(\'instrucoes\', $_SESSION[\'modulos\'])',
            '(isset($_SESSION[\'modulos\']) && is_array($_SESSION[\'modulos\']) && in_array(\'instrucoes\', $_SESSION[\'modulos\']))',
            $conteudo_novo
        );
        
        // Verificar outras possíveis ocorrências
        $patterns = [
            '/\$_SESSION\[[\'"]modulos[\'"]\]/' => 'array()',
            '/\$_SESSION\[\'modulos\'\]/' => 'array()'
        ];
        
        // Adicionar verificação antes de usar $_SESSION['modulos']
        if (strpos($conteudo_novo, 'if (!isset($_SESSION[\'modulos\']))') === false) {
            // Adicionar no início do arquivo após session_start
            $init_modulos = '
// Inicializar modulos se não existir
if (!isset($_SESSION[\'modulos\']) || !is_array($_SESSION[\'modulos\'])) {
    $_SESSION[\'modulos\'] = array();
}
';
            $conteudo_novo = preg_replace('/session_start\(\);/', "session_start();\n" . $init_modulos, $conteudo_novo, 1);
        }
        
        if (file_put_contents($arquivo_config, $conteudo_novo)) {
            exibirStatus("configuracoes.php corrigido - erro de 'modulos' resolvido");
        } else {
            exibirStatus("Erro ao salvar configuracoes.php", "error");
        }
    } else {
        exibirStatus("Arquivo configuracoes.php não encontrado", "error");
    }
    
    // CORREÇÃO 2: Função duplicada formatarMoeda
    echo '<h3>2. Corrigindo função duplicada formatarMoeda...</h3>';
    
    $arquivo_funcoes = 'membro/admin/funcoes-extras.php';
    if (file_exists($arquivo_funcoes)) {
        $conteudo = file_get_contents($arquivo_funcoes);
        
        // Verificar se a função existe
        if (strpos($conteudo, 'function formatarMoeda') !== false) {
            // Renomear a função ou removê-la
            // Vamos verificar primeiro se ela já existe em config.php
            if (file_exists('membro/config.php')) {
                $config_content = file_get_contents('membro/config.php');
                if (strpos($config_content, 'function formatarMoeda') !== false) {
                    // Remover a função do funcoes-extras.php
                    $pattern = '/function formatarMoeda\s*\([^{]*\{[^}]*\}/s';
                    $conteudo = preg_replace($pattern, '// Função formatarMoeda removida - já existe em config.php', $conteudo);
                    
                    if (file_put_contents($arquivo_funcoes, $conteudo)) {
                        exibirStatus("Função duplicada formatarMoeda removida de funcoes-extras.php");
                    }
                } else {
                    exibirStatus("Função formatarMoeda não encontrada em config.php", "warning");
                }
            }
        } else {
            exibirStatus("Função formatarMoeda não encontrada em funcoes-extras.php", "warning");
        }
    } else {
        // Se o arquivo não existe, vamos criá-lo sem a função duplicada
        $funcoes_content = '<?php
// FUNÇÕES EXTRAS PARA O SISTEMA

// Função para calcular receita convertida
function calcularReceitaMesConvertida($pdo) {
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = \'taxas_cambio\'");
    $taxas_json = $stmt->fetchColumn();
    
    $taxas = $taxas_json ? json_decode($taxas_json, true) : [
        "USD" => 5.00,
        "EUR" => 5.50,
        "JPY" => 0.033
    ];
    
    $taxas["BRL"] = 1;
    
    $stmt = $pdo->query("
        SELECT moeda, SUM(valor) as total 
        FROM pagamentos 
        WHERE status = \'aprovado\' 
        AND MONTH(data_pagamento) = MONTH(CURDATE())
        AND YEAR(data_pagamento) = YEAR(CURDATE())
        GROUP BY moeda
    ");
    
    $total_brl = 0;
    
    while ($row = $stmt->fetch()) {
        $moeda = $row["moeda"] ?? "BRL";
        $valor = $row["total"];
        
        if (isset($taxas[$moeda])) {
            $total_brl += $valor * $taxas[$moeda];
        } else {
            $total_brl += $valor;
        }
    }
    
    return $total_brl;
}

// Função para converter moeda
function converterMoeda($valor, $de_moeda, $para_moeda = "BRL") {
    if ($de_moeda === $para_moeda) {
        return $valor;
    }
    
    global $pdo;
    
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = \'taxas_cambio\'");
    $taxas_json = $stmt->fetchColumn();
    
    $taxas = $taxas_json ? json_decode($taxas_json, true) : [
        "USD" => 5.00,
        "EUR" => 5.50,
        "JPY" => 0.033
    ];
    
    $taxas["BRL"] = 1;
    
    if (!isset($taxas[$de_moeda]) || !isset($taxas[$para_moeda])) {
        return $valor;
    }
    
    $valor_brl = $valor * $taxas[$de_moeda];
    return $valor_brl / $taxas[$para_moeda];
}

// NOTA: A função formatarMoeda() já existe em config.php

// Função para validar WhatsApp
function validarWhatsApp($numero) {
    $numero = preg_replace("/[^0-9]/", "", $numero);
    
    if (strlen($numero) < 10) {
        return false;
    }
    
    if (substr($numero, 0, 2) == "55" && strlen($numero) == 13) {
        return $numero;
    }
    
    if (strlen($numero) >= 10 && strlen($numero) <= 15) {
        return $numero;
    }
    
    return false;
}

// Função para gerar código de teste
function gerarCodigoTeste($usuario_id) {
    $codigo = "TESTE" . str_pad($usuario_id, 4, "0", STR_PAD_LEFT) . rand(1000, 9999);
    return $codigo;
}
?>';
        
        if (file_put_contents($arquivo_funcoes, $funcoes_content)) {
            exibirStatus("funcoes-extras.php criado sem função duplicada");
        }
    }
    
    // CORREÇÃO 3: Verificar se há outros problemas comuns
    echo '<h3>3. Verificações adicionais...</h3>';
    
    // Verificar se session_start() existe no início dos arquivos admin
    $arquivos_verificar = [
        'membro/admin/index.php',
        'membro/admin/configuracoes.php',
        'membro/admin/usuarios.php',
        'membro/admin/pagamentos.php'
    ];
    
    foreach ($arquivos_verificar as $arquivo) {
        if (file_exists($arquivo)) {
            $conteudo = file_get_contents($arquivo);
            if (strpos($conteudo, 'session_start()') === false) {
                // Adicionar session_start() no início
                $conteudo = "<?php\nsession_start();\n" . substr($conteudo, 5);
                file_put_contents($arquivo, $conteudo);
                exibirStatus("session_start() adicionado em $arquivo");
            }
        }
    }
    
    echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    echo '<h2>✅ Correções Aplicadas!</h2>';
    echo '<p>Os erros foram corrigidos. Por favor, teste o admin novamente.</p>';
    echo '<p><a href="membro/admin/" target="_blank" class="button">Testar Admin</a></p>';
    echo '</div>';
    
    // Opção para deletar este arquivo
    echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    echo '<h3>🗑️ Segurança</h3>';
    echo '<p>Por segurança, delete este arquivo após o uso.</p>';
    echo '<form method="post" onsubmit="return confirm(\'Tem certeza que deseja deletar este arquivo?\');" style="display:inline;">';
    echo '<button type="submit" name="deletar" class="button" style="background: #e74c3c;">Deletar Este Arquivo</button>';
    echo '</form>';
    echo '</div>';
    
} elseif (isset($_POST['deletar'])) {
    if (unlink(__FILE__)) {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;">';
        echo '<h2>✅ Arquivo deletado com sucesso!</h2>';
        echo '</div>';
        echo '<script>setTimeout(function() { window.location.href = "/"; }, 2000);</script>';
    }
} else {
    // Tela inicial
    ?>
    <p>Este corretor irá resolver os seguintes problemas:</p>
    
    <div class="code">
        <strong>Erro 1:</strong> Undefined array key "modulos" in configuracoes.php<br>
        <strong>Erro 2:</strong> Cannot redeclare formatarMoeda() in funcoes-extras.php
    </div>
    
    <h3>O que será feito:</h3>
    <ol>
        <li>Adicionar verificação para $_SESSION['modulos'] antes de usar</li>
        <li>Remover função duplicada formatarMoeda()</li>
        <li>Verificar e corrigir outros possíveis problemas</li>
    </ol>
    
    <form method="post">
        <button type="submit" name="corrigir" class="button">Aplicar Correções</button>
    </form>
    
    <p style="color: #666; margin-top: 20px;">
        <small>💡 Dica: Faça um backup antes de aplicar as correções.</small>
    </p>
    <?php
}
?>

</div>
</body>
</html>