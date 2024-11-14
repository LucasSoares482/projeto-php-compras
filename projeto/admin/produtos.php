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
            case 'create':
                $stmt = $db->query(
                    "INSERT INTO produtos (nome, descricao, preco, estoque, categoria) VALUES (?, ?, ?, ?, ?)",
                    [$_POST['nome'], $_POST['descricao'], $_POST['preco'], $_POST['estoque'], $_POST['categoria']]
                );
                
                $produto_id = $db->getConnection()->lastInsertId();
                
                // Handle image upload
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
                    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
                    $filename = "produto_{$produto_id}.{$ext}";
                    move_uploaded_file($_FILES['imagem']['tmp_name'], "../assets/images/produtos/{$filename}");
                    
                    $db->query(
                        "UPDATE produtos SET imagem = ? WHERE id = ?",
                        [$filename, $produto_id]
                    );
                }
                break;
                
            case 'update':
                $stmt = $db->query(
                    "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, estoque = ?, categoria = ? WHERE id = ?",
                    [$_POST['nome'], $_POST['descricao'], $_POST['preco'], $_POST['estoque'], $_POST['categoria'], $_POST['id']]
                );
                
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
                    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
                    $filename = "produto_{$_POST['id']}.{$ext}";
                    move_uploaded_file($_FILES['imagem']['tmp_name'], "../assets/images/produtos/{$filename}");
                    
                    $db->query(
                        "UPDATE produtos SET imagem = ? WHERE id = ?",
                        [$filename, $_POST['id']]
                    );
                }
                break;
                
            case 'delete':
                $db->query("DELETE FROM produtos WHERE id = ?", [$_POST['id']]);
                break;
        }
        
        header('Location: produtos.php');
        exit();
    }
}

// Get all products
$produtos = $db->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-header">
    <h1>Gerenciar Produtos</h1>
    <button class="btn btn-primary" onclick="showForm('create')">Novo Produto</button>
</div>

<div class="admin-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Categoria</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?= $produto['id'] ?></td>
                    <td>
                        <?php if ($produto['imagem']): ?>
                            <img src="../assets/images/produtos/<?= $produto['imagem'] ?>" alt="<?= $produto['nome'] ?>" width="50">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                    <td><?= $produto['estoque'] ?></td>
                    <td><?= htmlspecialchars($produto['categoria']) ?></td>
                    <td>
                        <button onclick="showForm('update', <?= htmlspecialchars(json_encode($produto)) ?>)">Editar</button>
                        <button onclick="deleteProduto(<?= $produto['id'] ?>)">Excluir</button>
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
        <form id="produtoForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="id" id="produtoId">
            
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="preco">Preço:</label>
                <input type="number" id="preco" name="preco" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="estoque">Estoque:</label>
                <input type="number" id="estoque" name="estoque" required>
            </div>
            
            <div class="form-group">
                <label for="categoria">Categoria:</label>
                <input type="text" id="categoria" name="categoria" required>
            </div>
            
            <div class="form-group">
                <label for="imagem">Imagem:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*">
            </div>
            
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>

<script>
function showForm(action, produto = null) {
    document.getElementById('formAction').value = action;
    const form = document.getElementById('produtoForm');
    
    if (action === 'update' && produto) {
        document.getElementById('produtoId').value = produto.id;
        document.getElementById('nome').value = produto.nome;
        document.getElementById('descricao').value = produto.descricao;
        document.getElementById('preco').value = produto.preco;
        document.getElementById('estoque').value = produto.estoque;
        document.getElementById('categoria').value = produto.categoria;
    } else {
        form.reset();
    }
    
    document.getElementById('formModal').style.display = 'block';
}

function deleteProduto(id) {
    if (confirm('Tem certeza que deseja excluir este produto?')) {
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
</script>

<?php require_once '../includes/footer.php'; ?>