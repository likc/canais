<?php
// test-path.php - Coloque este arquivo na pasta /membro/admin/ para testar os caminhos

echo "<h2>Teste de Caminhos</h2>";

echo "<h3>Informações do Servidor:</h3>";
echo "Arquivo atual: " . __FILE__ . "<br>";
echo "Diretório atual: " . __DIR__ . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "<br><br>";

echo "<h3>Testando caminhos para config.php:</h3>";

$test_paths = [
    '../../../config.php' => 'Três níveis acima',
    '../../config.php' => 'Dois níveis acima',
    dirname(dirname(dirname(__FILE__))) . '/config.php' => 'dirname() 3x',
    $_SERVER['DOCUMENT_ROOT'] . '/config.php' => 'DOCUMENT_ROOT/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/canais.net/config.php' => 'DOCUMENT_ROOT/canais.net/config.php',
    '/home2/minec761/public_html/canais.net/config.php' => 'Caminho absoluto'
];

foreach ($test_paths as $path => $description) {
    echo "<strong>$description:</strong><br>";
    echo "Caminho: $path<br>";
    
    if (file_exists($path)) {
        echo "✅ <span style='color: green;'>ARQUIVO ENCONTRADO!</span><br>";
        echo "Caminho real: " . realpath($path) . "<br>";
    } else {
        echo "❌ <span style='color: red;'>Arquivo não encontrado</span><br>";
    }
    echo "<br>";
}

echo "<h3>Estrutura de diretórios:</h3>";
echo "<pre>";
$dir = dirname(__FILE__);
for ($i = 0; $i < 4; $i++) {
    echo str_repeat("  ", $i) . "└─ " . basename($dir) . "/\n";
    $dir = dirname($dir);
}
echo "</pre>";

echo "<h3>Recomendação:</h3>";
echo "Use o caminho que mostra '✅ ARQUIVO ENCONTRADO!' no seu configuracoes.php";
?>