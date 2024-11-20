<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$error = '';
$success = '';

// Get user data
$cliente = $db->query(
    "SELECT * FROM clientes WHERE id = ?",
    [$_SESSION['user_id']]
)->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->getConnection()->beginTransaction();
        
        // Create order
        $db->query(
            "INSERT INTO compras (
                cliente_id, valor_produtos, valor_frete, valor_total,
                cep, endereco, numero, complemento, bairro, cidade, estado,
                status, data_compra
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())",
            [
                $_SESSION['user_id'],
                $_POST['valor_produtos'],
                $_POST['valor_frete'],
                $_POST['valor_total'],
                $_POST['cep'],
                $_POST['endereco'],
                $_POST['numero'],
                $_POST['complemento'],
                $_POST['bairro'],
                $_POST['cidade'],
                $_POST['estado']
            ]
        );
        
        $compra_id = $db->getConnection()->lastInsertId();
        
        // Add order items
        $cart_items = json_decode($_POST['cart_items'], true);
        foreach ($cart_items as $item) {
            $db->query(
                "INSERT INTO compras_itens (compra_id, produto_id, quantidade, preco_unitario)
                 VALUES (?, ?, ?, ?)",
                [$compra_id, $item['productId'], $item['quantity'], $item['price']]
            );
            
            // Update stock
            $db->query(
                "UPDATE produtos SET estoque = estoque - ? WHERE id = ?",
                [$item['quantity'], $item['productId']]
            );
        }
        
        $db->getConnection()->commit();
        $success = 'Pedido realizado com sucesso!';
        
        // Clear cart via JavaScript
        echo "<script>localStorage.removeItem('cart');</script>";
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $error = 'Erro ao processar pedido. Tente novamente.';
    }
}

require_once 'includes/header.php';
?>

<div class="checkout-container">
    <h1>Finalizar Compra</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
            <a href="minha-conta.php">Ver meus pedidos</a>
        </div>
    <?php else: ?>
        <form method="POST" id="checkout-form" onsubmit="return validateCheckout()">
            <div class="checkout-grid">
                <div class="shipping-address">
                    <h2>Endereço de Entrega</h2>
                    
                    <div class="form-group">
                        <label for="cep">CEP:</label>
                        <input type="text" id="cep" name="cep" required>
                        <button type="button" onclick="buscarCEP(document.getElementById('cep').value)">
                            Buscar CEP
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco">Endereço:</label>
                        <input type="text" id="endereco" name="endereco" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero">Número:</label>
                            <input type="text" id="numero" name="numero" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="complemento">Complemento:</label>
                            <input type="text" id="complemento" name="complemento">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bairro">Bairro:</label>
                        <input type="text" id="bairro" name="bairro" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cidade">Cidade:</label>
                            <input type="text" id="cidade" name="cidade" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <input type="text" id="estado" name="estado" required>
                        </div>
                    </div>
                </div>
                
                <div class="order-summary">
                    <h2>Resumo do Pedido</h2>
                    <div id="order-items"></div>
                    
                    <div class="order-totals">
                        <input type="hidden" name="cart_items" id="cart-items-input">
                        <input type="hidden" name="valor_produtos" id="valor-produtos-input">
                        <input type="hidden" name="valor_frete" id="valor-frete-input">
                        <input type="hidden" name="valor_total" id="valor-total-input">
                        
                        <table>
                            <tr>
                                <td>Produtos:</td>
                                <td id="valor-produtos">R$ 0,00</td>
                            </tr>
                            <tr>
                                <td>Frete:</td>
                                <td id="valor-frete">R$ 0,00</td>
                            </tr>
                            <tr class="total">
                                <td>Total:</td>
                                <td id="valor-total">R$ 0,00</td>
                            </tr>
                        </table>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        Confirmar Pedido
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function validateCheckout() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (cart.length === 0) {
        showNotification('Seu carrinho está vazio', 'error');
        return false;
    }
    
    document.getElementById('cart-items-input').value = JSON.stringify(cart);
    return validateForm(document.getElementById('checkout-form'));
}

function updateOrderSummary() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const orderItems = document.getElementById('order-items');
    let subtotal = 0;
    
    let html = '';
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        html += `
            <div class="order-item">
                <span>${item.name} x ${item.quantity}</span>
                <span>R$ ${itemTotal.toFixed(2)}</span>
            </div>
        `;
    });
    
    orderItems.innerHTML = html;
    
    document.getElementById('valor-produtos').textContent = `R$ ${subtotal.toFixed(2)}`;
    document.getElementById('valor-produtos-input').value = subtotal;
    
    const frete = parseFloat(document.getElementById('valor-frete').textContent.replace('R$ ', '')) || 0;
    document.getElementById('valor-frete-input').value = frete;
    
    const total = subtotal + frete;
    document.getElementById('valor-total').textContent = `R$ ${total.toFixed(2)}`;
    document.getElementById('valor-total-input').value = total;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateOrderSummary();
    
    // CEP mask
    document.getElementById('cep').addEventListener('input', function(e) {
        e.target.value = e.target.value
            .replace(/\D/g, '')
            .replace(/(\d{5})(\d)/, '$1-$2')
            .substring(0, 9);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>