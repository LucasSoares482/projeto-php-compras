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
            case 'update':
                try {
                    $db->query(
                        "UPDATE clientes 
                         SET nome = ?, telefone = ?, status = ? 
                         WHERE id = ?",
                        [
                            $_POST['nome'],
                            $_POST['telefone'],
                            $_POST['status'],
                            $_POST['id']
                        ]
                    );
                    $success = "Cliente atualizado com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao atualizar cliente: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    // Verificar se tem compras
                    $compras = $db->query(
                        "SELECT COUNT(*) as total FROM compras WHERE cliente_id = ?",
                        [$_POST['id']]
                    )->fetch();
                    
                    if ($compras['total'] > 0) {
                        throw new Exception("Não é possível excluir cliente com compras realizadas.");
                    }
                    
                    $db->query("DELETE FROM clientes WHERE id = ?", [$_POST['id']]);
                    $success = "Cliente removido com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao remover cliente: " . $e->getMessage();
                }
                break;
        }
    }
}

// Buscar todos os clientes com suas estatísticas
$clientes = $db->query("
    SELECT 
        c.*,
        COUNT(DISTINCT co.id) as total_pedidos,
        SUM(co.valor_total) as valor_total_compras,
        MAX(co.data_compra) as ultima_compra
    FROM clientes c
    LEFT JOIN compras co ON c.id = co.cliente_id
    GROUP BY c.id
    ORDER BY c.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Clientes - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Gerenciar Clientes</h1>
            <nav class="admin-nav">
                <a href="produtos.php">Produtos</a>
                <a href="clientes.php" class="active">Clientes</a>
                <a href="../logout.php">Sair</a>
            </nav>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Total Pedidos</th>
                        <th>Total Compras</th>
                        <th>Última Compra</th>
                        <th>Ações</th>
                        </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= $cliente['id'] ?></td>
                            <td><?= htmlspecialchars($cliente['nome']) ?></td>
                            <td><?= htmlspecialchars($cliente['email']) ?></td>
                            <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                            <td>
                                <span class="status-badge <?= $cliente['status'] ?>">
                                    <?= ucfirst($cliente['status']) ?>
                                </span>
                            </td>
                            <td><?= $cliente['total_pedidos'] ?></td>
                            <td>
                                R$ <?= number_format($cliente['valor_total_compras'] ?? 0, 2, ',', '.') ?>
                            </td>
                            <td>
                                <?= $cliente['ultima_compra'] ? 
                                    date('d/m/Y H:i', strtotime($cliente['ultima_compra'])) : 
                                    'Nunca comprou' ?>
                            </td>
                            <td>
                                <button onclick='editCliente(<?= json_encode($cliente) ?>)'>
                                    Editar
                                </button>
                                <button onclick="deleteCliente(<?= $cliente['id'] ?>)"
                                        <?= $cliente['total_pedidos'] > 0 ? 'disabled' : '' ?>>
                                    Excluir
                                </button>
                                <button onclick="verPedidos(<?= $cliente['id'] ?>)">
                                    Ver Pedidos
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Editar Cliente -->
    <div id="clienteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Cliente</h2>
            
            <form method="POST" id="clienteForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="clienteId">
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" readonly class="readonly">
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
                
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Pedidos do Cliente -->
    <div id="pedidosModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Pedidos do Cliente</h2>
            <div id="pedidosLista"></div>
        </div>
    </div>

    <script>
        function editCliente(cliente) {
            document.getElementById('clienteModal').style.display = 'block';
            
            document.getElementById('clienteId').value = cliente.id;
            document.getElementById('nome').value = cliente.nome;
            document.getElementById('email').value = cliente.email;
            document.getElementById('telefone').value = cliente.telefone;
            document.getElementById('status').value = cliente.status;
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

        async function verPedidos(clienteId) {
            try {
                const response = await fetch(`get_pedidos.php?cliente_id=${clienteId}`);
                const pedidos = await response.json();
                
                let html = '<div class="pedidos-lista">';
                
                if (pedidos.length === 0) {
                    html += '<p>Este cliente ainda não realizou nenhum pedido.</p>';
                } else {
                    pedidos.forEach(pedido => {
                        html += `
                            <div class="pedido-card">
                                <div class="pedido-header">
                                    <strong>Pedido #${pedido.id}</strong>
                                    <span>${pedido.data_compra}</span>
                                    <span class="status ${pedido.status}">${pedido.status}</span>
                                </div>
                                <div class="pedido-body">
                                    <p>Total: R$ ${pedido.valor_total}</p>
                                    <p>Itens: ${pedido.total_itens}</p>
                                </div>
                            </div>
                        `;
                    });
                }
                
                html += '</div>';
                document.getElementById('pedidosLista').innerHTML = html;
                document.getElementById('pedidosModal').style.display = 'block';
                
            } catch (error) {
                alert('Erro ao carregar pedidos: ' + error.message);
            }
        }

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
                e.target.value = value;
            }
        });

        // Fechar modais
        document.querySelectorAll('.close').forEach(close => {
            close.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>