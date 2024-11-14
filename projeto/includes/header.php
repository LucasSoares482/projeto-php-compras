<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="<?= SITE_URL ?>" class="logo"><?= SITE_NAME ?></a>
            <ul class="nav-links">
                <li><a href="<?= SITE_URL ?>">Home</a></li>
                <li><a href="<?= SITE_URL ?>/produtos.php">Produtos</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?= SITE_URL ?>/minha-conta.php">Minha Conta</a></li>
                    <li><a href="<?= SITE_URL ?>/logout.php">Sair</a></li>
                <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= SITE_URL ?>/registro.php">Cadastro</a></li>
                <?php endif; ?>
                <li>
                    <a href="<?= SITE_URL ?>/carrinho.php">
                        Carrinho <span id="cart-count">0</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>
    <main class="container">