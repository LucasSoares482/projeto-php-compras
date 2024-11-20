<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if ($email && $senha) {
        $db = Database::getInstance();
        
        $user = $db->query(
            "SELECT * FROM clientes WHERE email = ? LIMIT 1",
            [$email]
        )->fetch();
        
        if ($user && password_verify($senha, $user['senha'])) {
            if ($user['status'] === 'ativo') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                
                // Update last login
                $db->query(
                    "UPDATE clientes SET ultimo_login = NOW() WHERE id = ?",
                    [$user['id']]
                );
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Sua conta está inativa. Entre em contato com o suporte.';
            }
        } else {
            $error = 'Email ou senha incorretos.';
        }
    } else {
        $error = 'Por favor, preencha todos os campos.';
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Login</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" onsubmit="return validateForm(this)">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
        
        <div class="auth-links">
            <a href="esqueci-senha.php">Esqueci minha senha</a>
            <span>|</span>
            <a href="registro.php">Criar conta</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>