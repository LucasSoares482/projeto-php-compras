<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

// Get featured products
$produtos = $db->query("
    SELECT *
    FROM produtos
    WHERE estoque > 0
    ORDER BY id DESC
    LIMIT 8
")->fetchAll();

// Get product categories
$categorias = $db->query("
    SELECT DISTINCT categoria
    FROM produtos
    WHERE categoria IS NOT NULL
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="hero">
    <h1>Bem-vindo à <?= SITE_NAME ?></h1>
    <p>Encontre os melhores produtos pelos melhores preços</p>
</div>

<section class="categories">
    <h2>Categorias</h2>
    <div class="categories-grid">
        <?php foreach ($categorias as $categoria): ?>
            <a href="produtos.php?categoria=<?= urlencode($categoria['categoria']) ?>" class="category-card">
                <?= htmlspecialchars($categoria['categoria']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="featured-products">
    <h2>Produtos em Destaque</h2>
    <div class="products-grid">
        <?php foreach ($produtos as $produto): ?>
            <div class="product-card">
                <?php if ($produto['imagem']): ?>
                    <img src="assets/images/produtos/<?= $produto['imagem'] ?>" 
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
</section>

<?php require_once 'includes/footer.php'; ?>