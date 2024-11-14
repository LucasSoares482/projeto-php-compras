<?php
require_once 'includes/config.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpar todas as variáveis da sessão
$_SESSION = array();

// Destruir o cookie da sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destruir a sessão
session_destroy();

// Limpar o carrinho do localStorage via JavaScript
echo "<script>
    localStorage.removeItem('cart');
    window.location.href = '" . SITE_URL . "';
</script>";

// Caso o JavaScript esteja desabilitado, redirecionar via PHP
header('Location: ' . SITE_URL);
exit();
?>