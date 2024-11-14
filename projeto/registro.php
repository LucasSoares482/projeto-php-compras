<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    
    if ($senha !== $confirma_senha) {
        $error = 'As senhas não conferem.';
    } else {
        $db = Database::getInstance();
        
        // Check if email already exists
        $existingUser = $db->query(
            "SELECT id FROM clientes WHERE email = ?",
            [$email]
        )->fetch();
        
        if ($existingUser) {
            $error = 'Este email já está cadastrado.';
        } else {
            $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);
            
            try {
                $db->query(
                    "INSERT INTO clientes (nome, email, senha, telefone, status, data_cadastro) 
                     VALUES (?, ?, ?, ?, 'ativo', NOW())",
                    [$nome, $email, $hashedPassword, $telefone]
                );
                
                $success = 'Cadastro realizado com sucesso! Você já pode fazer login.';
            } catch (Exception $e) {
                $error = 'Erro ao realizar cadastro. Tente novamente.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Criar Conta</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
                <a href="login.php">Fazer login</a>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" onsubmit="return validateForm(this)">
            <div class="form-group">
                <label for="nome">Nome completo:</label>
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
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" 
                       required minlength="6" 
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}"
                       title="A senha deve conter pelo menos 6 caracteres, incluindo letras maiúsculas, minúsculas e números">
            </div>
            
            <div class="form-group">
                <label for="confirma_senha">Confirmar senha:</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
        
        <div class="auth-links">
            <a href="login.php">Já tem uma conta? Faça login</a>
        </div>
    </div>
</div>

<script>
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        e.target.value = value;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>