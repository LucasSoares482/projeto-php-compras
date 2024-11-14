<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

// Get all products or filter by category
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

if ($categoria) {
    $produtos = $db->query(
        "SELECT * FROM produtos WHERE categoria = ? AND estoque > 0 ORDER BY nome",
        [$categoria]
    )->fetchAll();
} else {
    $produtos = $db->query(
        "SELECT * FROM produtos WHERE estoque > 0 ORDER BY nome"
    )->fetchAll();
}

require_once 'includes/header.php';
?>

<div class="products-page">
    <h1>Nossos Produtos</h1>
    
    <div class="products-grid">
        <?php foreach ($produtos as $produto): ?>
            <div class="product-card">
                <?php if ($produto['imagem']): ?>
                    <img src="<?= SITE_URL ?>/assets/images/produtos/<?= $produto['imagem'] ?>" 
                         alt="<?= htmlspecialchars($produto['nome']) ?>"
                         class="product-image">
                <?php endif; ?>
                
                <div class="product-info">
                    <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                    <p><?= htmlspecialchars(substr($produto['descricao'], 0, 100)) ?>...</p>
                    <p class="price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                    
                    <button class="btn btn-primary"
                            onclick="addToCart(<?= $produto['id'] ?>, 
                                            '<?= htmlspecialchars(addslashes($produto['nome'])) ?>', 
                                            <?= $produto['preco'] ?>, 
                                            '<?= $produto['imagem'] ?>')">
                        Adicionar ao Carrinho
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>