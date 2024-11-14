<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$error = '';
$success = '';

// Buscar dados do usuário
$usuario = $db->query(
    "SELECT * FROM clientes WHERE id = ?",
    [$_SESSION['user_id']]
)->fetch();

// Buscar pedidos do usuário
$pedidos = $db->query(
    "SELECT c.*, COUNT(ci.id) as total_itens
     FROM compras c
     LEFT JOIN compras_itens ci ON c.id = ci.compra_id
     WHERE c.cliente_id = ?
     GROUP BY c.id
     ORDER BY c.data_compra DESC",
    [$_SESSION['user_id']]
)->fetchAll();

// Atualizar dados do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $nome = $_POST['nome'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        
        if ($nova_senha) {
            // Verificar senha atual
            if (!password_verify($senha_atual, $usuario['senha'])) {
                $error = 'Senha atual incorreta.';
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $db->query(
                    "UPDATE clientes SET nome = ?, telefone = ?, senha = ? WHERE id = ?",
                    [$nome, $telefone, $senha_hash, $_SESSION['user_id']]
                );
                $success = 'Dados atualizados com sucesso!';
            }
        } else {
            $db->query(
                "UPDATE clientes SET nome = ?, telefone = ? WHERE id = ?",
                [$nome, $telefone, $_SESSION['user_id']]
            );
            $success = 'Dados atualizados com sucesso!';
        }
        
        // Recarregar dados do usuário
        $usuario = $db->query(
            "SELECT * FROM clientes WHERE id = ?",
            [$_SESSION['user_id']]
        )->fetch();
    }
}

require_once 'includes/header.php';
?>

<div class="account-container">
    <h1>Minha Conta</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="account-grid">
        <!-- Seção de Dados Pessoais -->
        <div class="account-section">
            <h2>Dados Pessoais</h2>
            <form method="POST" class="form" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" 
                           value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" value="<?= htmlspecialchars($usuario['email']) ?>" 
                           readonly class="readonly">
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" 
                           value="<?= htmlspecialchars($usuario['telefone']) ?>" required>
                </div>
                
                <h3>Alterar Senha</h3>
                <div class="form-group">
                    <label for="senha_atual">Senha Atual:</label>
                    <input type="password" id="senha_atual" name="senha_atual">
                </div>
                
                <div class="form-group">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha" 
                           minlength="6" 
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}"
                           title="A senha deve conter pelo menos 6 caracteres, incluindo maiúsculas, minúsculas e números">
                </div>
                
                <button type="submit" class="btn btn-primary">Atualizar Dados</button>
            </form>
        </div>
        
        <!-- Seção de Pedidos -->
        <div class="account-section">
            <h2>Meus Pedidos</h2>
            
            <?php if (empty($pedidos)): ?>
                <p>Você ainda não realizou nenhum pedido.</p>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <strong>Pedido #<?= $pedido['id'] ?></strong>
                                    <span class="order-date">
                                        <?= date('d/m/Y H:i', strtotime($pedido['data_compra'])) ?>
                                    </span>
                                </div>
                                <span class="order-status <?= $pedido['status'] ?>">
                                    <?= ucfirst($pedido['status']) ?>
                                </span>
                            </div>
                            
                            <div class="order-details">
                                <p>Itens: <?= $pedido['total_itens'] ?></p>
                                <p>Total: R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></p>
                                
                                <?php
                                $itens = $db->query(
                                    "SELECT ci.*, p.nome
                                     FROM compras_itens ci
                                     JOIN produtos p ON ci.produto_id = p.id
                                     WHERE ci.compra_id = ?",
                                    [$pedido['id']]
                                )->fetchAll();
                                ?>
                                
                                <div class="order-items">
                                    <?php foreach ($itens as $item): ?>
                                        <div class="order-item">
                                            <span><?= htmlspecialchars($item['nome']) ?></span>
                                            <span><?= $item['quantidade'] ?>x</span>
                                            <span>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="order-address">
                                    <strong>Endereço de entrega:</strong>
                                    <p>
                                        <?= htmlspecialchars($pedido['endereco']) ?>, 
                                        <?= htmlspecialchars($pedido['numero']) ?>
                                        <?= $pedido['complemento'] ? ' - ' . htmlspecialchars($pedido['complemento']) : '' ?>
                                        <br>
                                        <?= htmlspecialchars($pedido['bairro']) ?> - 
                                        <?= htmlspecialchars($pedido['cidade']) ?>/<?= htmlspecialchars($pedido['estado']) ?>
                                        <br>
                                        CEP: <?= htmlspecialchars($pedido['cep']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Máscara para telefone
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        e.target.value = value;
    }
});

// Validação de senha
function validateForm(form) {
    const senhaAtual = form.senha_atual.value;
    const novaSenha = form.nova_senha.value;
    
    if (novaSenha && !senhaAtual) {
        alert('Para alterar a senha, preencha a senha atual.');
        return false;
    }
    
    if (senhaAtual && !novaSenha) {
        alert('Para alterar a senha, preencha a nova senha.');
        return false;
    }
    
    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>