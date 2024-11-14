<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Verificar se está logado como admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $stmt = $db->query(
                        "INSERT INTO produtos (nome, descricao, preco, estoque, categoria, data_cadastro) 
                         VALUES (?, ?, ?, ?, ?, NOW())",
                        [
                            $_POST['nome'],
                            $_POST['descricao'],
                            $_POST['preco'],
                            $_POST['estoque'],
                            $_POST['categoria']
                        ]
                    );
                    
                    $produto_id = $db->getConnection()->lastInsertId();
                    
                    // Upload da imagem
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
                        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
                        $filename = "produto_{$produto_id}.{$ext}";
                        $upload_dir = "../assets/images/produtos/";
                        
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        move_uploaded_file(
                            $_FILES['imagem']['tmp_name'],
                            $upload_dir . $filename
                        );
                        
                        $db->query(
                            "UPDATE produtos SET imagem = ? WHERE id = ?",
                            [$filename, $produto_id]
                        );
                    }
                    
                    $success = "Produto cadastrado com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao cadastrar produto: " . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $db->query(
                        "UPDATE produtos 
                         SET nome = ?, descricao = ?, preco = ?, estoque = ?, categoria = ? 
                         WHERE id = ?",
                        [
                            $_POST['nome'],
                            $_POST['descricao'],
                            $_POST['preco'],
                            $_POST['estoque'],
                            $_POST['categoria'],
                            $_POST['id']
                        ]
                    );
                    
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
                        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
                        $filename = "produto_{$_POST['id']}.{$ext}";
                        $upload_dir = "../assets/images/produtos/";
                        
                        move_uploaded_file(
                            $_FILES['imagem']['tmp_name'],
                            $upload_dir . $filename
                        );
                        
                        $db->query(
                            "UPDATE produtos SET imagem = ? WHERE id = ?",
                            [$filename, $_POST['id']]
                        );
                    }
                    
                    $success = "Produto atualizado com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao atualizar produto: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    // Buscar imagem antes de deletar
                    $produto = $db->query(
                        "SELECT imagem FROM produtos WHERE id = ?",
                        [$_POST['id']]
                    )->fetch();
                    
                    // Deletar produto
                    $db->query("DELETE FROM produtos WHERE id = ?", [$_POST['id']]);
                    
                    // Remover imagem se existir
                    if ($produto['imagem']) {
                        $image_path = "../assets/images/produtos/" . $produto['imagem'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    $success = "Produto removido com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao remover produto: " . $e->getMessage();
                }
                break;
        }
    }
}

// Buscar todos os produtos
$produtos = $db->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Produtos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Gerenciar Produtos</h1>
            <nav class="admin-nav">
                <a href="produtos.php" class="active">Produtos</a>
                <a href="clientes.php">Clientes</a>
                <a href="../logout.php">Sair</a>
            </nav>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="admin-actions">
            <button onclick="showModal('createModal')" class="btn btn-primary">Novo Produto</button>
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
                                    <img src="../assets/images/produtos/<?= $produto['imagem'] ?>" 
                                         alt="<?= htmlspecialchars($produto['nome']) ?>"
                                         width="50">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                            <td><?= $produto['estoque'] ?></td>
                            <td><?= htmlspecialchars($produto['categoria']) ?></td>
                            <td>
                                <button onclick='editProduto(<?= json_encode($produto) ?>)'>
                                    Editar
                                </button>
                                <button onclick="deleteProduto(<?= $produto['id'] ?>)">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Criar/Editar Produto -->
    <div id="produtoModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Novo Produto</h2>
            
            <form method="POST" enctype="multipart/form-data" id="produtoForm">
                <input type="hidden" name="action" id="formAction" value="create">
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
        function showModal(modalId) {
            document.getElementById('produtoModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Novo Produto';
            document.getElementById('formAction').value = 'create';
            document.getElementById('produtoForm').reset();
        }

        function editProduto(produto) {
            document.getElementById('produtoModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Editar Produto';
            document.getElementById('formAction').value = 'update';
            
            document.getElementById('produtoId').value = produto.id;
            document.getElementById('nome').value = produto.nome;
            document.getElementById('descricao').value = produto.descricao;
            document.getElementById('preco').value = produto.preco;
            document.getElementById('estoque').value = produto.estoque;
            document.getElementById('categoria').value = produto.categoria;
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

        // Fechar modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('produtoModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('produtoModal')) {
                document.getElementById('produtoModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>