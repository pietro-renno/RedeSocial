<?php
session_start();
include './config.php';

if(isset($_POST['cadastrar_user'])){

    $erros = [];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        if (empty($nome)) $erros[] = "O campo nome é obrigatório.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "Formato de email inválido.";
        if (strlen($senha) < 6) $erros[] = "A senha deve ter no mínimo 6 caracteres.";

        if (empty($erros)) {
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $erros[] = "Este email já está cadastrado.";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $sql_insert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
                $stmt_insert = $mysqli->prepare($sql_insert);
                $stmt_insert->bind_param("sss", $nome, $email, $senha_hash);

                if ($stmt_insert->execute()) {
                    header("Location: login.php?status=cadastrado");
                    exit();
                } else {
                    $erros[] = "Erro ao cadastrar usuário: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>