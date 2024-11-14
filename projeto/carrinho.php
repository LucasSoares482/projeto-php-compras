<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = Database::getInstance();

require_once 'includes/header.php';
?>

<div class="cart-container">
    <h1>Carrinho de Compras</h1>
    
    <div id="cart-items">
        <!-- Items will be loaded dynamically -->
    </div>
    
    <div class="cart-summary">
        <div class="shipping-calculator">
            <h3>Calcular Frete</h3>
            <div class="form-group">
                <input type="text" id="cep" name="cep" placeholder="Digite seu CEP">
                <button onclick="buscarCEP(document.getElementById('cep').value)" class="btn">
                    Calcular
                </button>
            </div>
            <div id="shipping-result"></div>
        </div>
        
        <div class="cart-totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td id="subtotal">R$ 0,00</td>
                </tr>
                <tr>
                    <td>Frete:</td>
                    <td id="valor-frete">R$ 0,00</td>
                </tr>
                <tr class="total">
                    <td>Total:</td>
                    <td id="total">R$ 0,00</td>
                </tr>
            </table>
            
            <button onclick="window.location.href='checkout.php'" 
                    class="btn btn-primary btn-large"
                    id="checkout-button"
                    disabled>
                Finalizar Compra
            </button>
        </div>
    </div>
</div>

<script>
function renderCart() {
    const cartItems = document.getElementById('cart-items');
    let subtotal = 0;
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="empty-cart">Seu carrinho está vazio</p>';
        document.getElementById('checkout-button').disabled = true;
        updateTotals(0);
        return;
    }
    
    let html = '';
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        html += `
            <div class="cart-item">
                <img src="assets/images/produtos/${item.image}" 
                     alt="${item.name}" 
                     class="cart-item-image">
                     
                <div class="cart-item-details">
                    <h3>${item.name}</h3>
                    <p>Preço: R$ ${item.price.toFixed(2)}</p>
                    
                    <div class="quantity-controls">
                        <button onclick="updateQuantity(${item.productId}, ${item.quantity - 1})">-</button>
                        <input type="number" 
                               value="${item.quantity}" 
                               min="1" 
                               onchange="updateQuantity(${item.productId}, this.value)">
                        <button onclick="updateQuantity(${item.productId}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                
                <div class="cart-item-total">
                    <p>Total: R$ ${itemTotal.toFixed(2)}</p>
                    <button onclick="removeFromCart(${item.productId})" class="btn-remove">
                        Remover
                    </button>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    document.getElementById('checkout-button').disabled = false;
    updateTotals(subtotal);
}

function updateTotals(subtotal) {
    document.getElementById('subtotal').textContent = `R$ ${subtotal.toFixed(2)}`;
    
    const freteElement = document.getElementById('valor-frete');
    const frete = parseFloat(freteElement.textContent.replace('R$ ', '')) || 0;
    
    const total = subtotal + frete;
    document.getElementById('total').textContent = `R$ ${total.toFixed(2)}`;
}

// Initialize cart
document.addEventListener('DOMContentLoaded', () => {
    renderCart();
});
</script>

<?php require_once 'includes/footer.php'; ?>