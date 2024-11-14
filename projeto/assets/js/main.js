// Cart functionality
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function updateCart() {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    }
    localStorage.setItem('cart', JSON.stringify(cart));
}

function addToCart(productId, name, price, image) {
    const existingItem = cart.find(item => item.productId === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            productId,
            name,
            price,
            image,
            quantity: 1
        });
    }
    
    updateCart();
    showNotification('Produto adicionado ao carrinho');
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.productId !== productId);
    updateCart();
    if (window.location.pathname.includes('carrinho.php')) {
        renderCart();
    }
}

function updateQuantity(productId, newQuantity) {
    const item = cart.find(item => item.productId === productId);
    if (item) {
        item.quantity = Math.max(1, parseInt(newQuantity));
        updateCart();
        if (window.location.pathname.includes('carrinho.php')) {
            renderCart();
        }
    }
}

// CEP lookup
async function buscarCEP(cep) {
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (data.erro) {
            throw new Error('CEP não encontrado');
        }
        
        document.getElementById('rua').value = data.logradouro;
        document.getElementById('bairro').value = data.bairro;
        document.getElementById('cidade').value = data.localidade;
        document.getElementById('estado').value = data.uf;
        
        calcularFrete(cep);
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

function calcularFrete(cep) {
    // Simulação de cálculo de frete
    const valorBase = 15;
    const valorPorItem = cart.reduce((sum, item) => sum + item.quantity, 0) * 2;
    const frete = valorBase + valorPorItem;
    
    document.getElementById('valor-frete').textContent = `R$ ${frete.toFixed(2)}`;
    return frete;
}

// Notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Form validation
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            valid = false;
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
    });
    
    return valid;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateCart();
    
    // Setup CEP mask
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', (e) => {
            e.target.value = e.target.value
                .replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .substring(0, 9);
        });
    }
});