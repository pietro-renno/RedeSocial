<?php
session_start();
require_once 'config.php';


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - helveticNDS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="global-background">
    <div class="container">
        <div class="card">
            <h1>Crie sua Conta</h1>

            <?php if (!empty($erros)): ?>
                <div class="error-message">
                    <?php foreach ($erros as $erro): ?>
                        <p><?php echo $erro; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="./requisicoes.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha (mín. 6 caracteres):</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" name='cadastrar_user' class="btn-primary">Cadastrar</button>
            </form>
            <p class="login-link">Já tem uma conta? <a href="login.php">Faça login aqui</a>.</p>
        </div>
    </div>
</body>
</html>