<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro_login = "Por favor, preencha o email e a senha.";
    } else {

        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc(); 

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nome'];

            header("Location: ./home.php");
            exit();
        } else {
            $erro_login = "Email ou senha inválidos.";
        }
        $stmt->close();
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - helveticNDS</title>
    <link rel="stylesheet" href="style.css"> <!-- Importa seu arquivo CSS -->
</head>
<body class="global-background"> <!-- Adiciona a classe login-bg para a imagem de fundo -->
    <div class="container"> <!-- Container para centralizar o conteúdo -->
        <div class="card"> <!-- Card para o formulário, como no Instagram -->
            <h1>Entrar</h1>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'cadastrado'): ?>
                <div class="success-message"> <!-- Nova classe para mensagens de sucesso -->
                    <p>Cadastro realizado com sucesso! Faça o login.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($erro_login)): ?>
                <div class="error-message"> <!-- Usa a classe de erro já definida -->
                    <p><?php echo $erro_login; ?></p>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group"> <!-- Grupo para label e input -->
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group"> <!-- Grupo para label e input -->
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn-primary">Entrar</button> <!-- Botão com estilo primário -->
            </form>
            <p class="login-link">Não tem uma conta? <a href="index.php">Cadastre-se aqui</a>.</p>
        </div>
    </div>
</body>
</html>