<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $stmt = $db->query(
                    "UPDATE clientes SET nome = ?, email = ?, telefone = ?, status = ? WHERE id = ?",
                    [$_POST['nome'], $_POST['email'], $_POST['telefone'], $_POST['status'], $_POST['id']]
                );
                break;
                
            case 'delete':
                $db->query("DELETE FROM clientes WHERE id = ?", [$_POST['id']]);
                break;
        }
        
        header('Location: clientes.php');
        exit();
    }
}

// Get all customers with their orders count
$clientes = $db->query("
    SELECT c.*, 
           COUNT(DISTINCT co.id) as total_compras,
           SUM(co.valor_total) as valor_total_compras
    FROM clientes c
    LEFT JOIN compras co ON c.id = co.cliente_id
    GROUP BY c.id
    ORDER BY c.id DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-header">
    <h1>Gerenciar Clientes</h1>
</div>

<div class="admin-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Total Compras</th>
                <th>Valor Total</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?= $cliente['id'] ?></td>
                    <td><?= htmlspecialchars($cliente['nome']) ?></td>
                    <td><?= htmlspecialchars($cliente['email']) ?></td>
                    <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                    <td><?= $cliente['total_compras'] ?></td>
                    <td>R$ <?= number_format($cliente['valor_total_compras'] ?? 0, 2, ',', '.') ?></td>
                    <td><?= $cliente['status'] ?></td>
                    <td>
                        <button onclick="showForm('update', <?= htmlspecialchars(json_encode($cliente)) ?>)">Editar</button>
                        <button onclick="deleteCliente(<?= $cliente['id'] ?>)">Excluir</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Form Modal -->
<div id="formModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form id="clienteForm" method="POST">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="id" id="clienteId">
            
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" required>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="bloqueado">Bloqueado</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>

<script>
function showForm(action, cliente = null) {
    document.getElementById('formAction').value = action;
    const form = document.getElementById('clienteForm');
    
    if (action === 'update' && cliente) {
        document.getElementById('clienteId').value = cliente.id;
        document.getElementById('nome').value = cliente.nome;
        document.getElementById('email').value = cliente.email;
        document.getElementById('telefone').value = cliente.telefone;
        document.getElementById('status').value = cliente.status;
    } else {
        form.reset();
    }
    
    document.getElementById('formModal').style.display = 'block';
}

function deleteCliente(id) {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Modal close button
document.querySelector('.close').onclick = function() {
    document.getElementById('formModal').style.display = 'none';
}

// Phone mask
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        e.target.value = value;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>