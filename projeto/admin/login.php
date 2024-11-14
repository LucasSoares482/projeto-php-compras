<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: produtos.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if ($email && $senha) {
        $db = Database::getInstance();
        
        $admin = $db->query(
            "SELECT * FROM admin WHERE email = ? AND status = 'ativo' LIMIT 1",
            [$email]
        )->fetch();
        
        if ($admin && password_verify($senha, $admin['senha'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            
            // Atualizar último acesso
            $db->query(
                "UPDATE admin SET ultimo_acesso = NOW() WHERE id = ?",
                [$admin['id']]
            );
            
            header('Location: produtos.php');
            exit();
        } else {
            $error = 'Email ou senha incorretos.';
        }
    } else {
        $error = 'Por favor, preencha todos os campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
        }
        
        .admin-login-card {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .admin-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .admin-login-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .admin-login-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .admin-login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .admin-login-form input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
        }
        
        .admin-login-form button {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <h1><?= SITE_NAME ?></h1>
                <p>Área Administrativa</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" class="admin-login-form">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>