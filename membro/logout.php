<?php
require_once 'config.php';

// Destruir sessão
session_destroy();

// Limpar cookies se existirem
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirecionar para login com mensagem de sucesso
header('Location: index.php?sucesso=logout');
exit;
?>