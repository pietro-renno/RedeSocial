<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['user_id'];
$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Atualizar o nome
    if (!empty($_POST['nome'])) {
        $novo_nome = trim($_POST['nome']);
        $sql_update_nome = "UPDATE usuarios SET nome = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql_update_nome);
        $stmt->bind_param("si", $novo_nome, $id_usuario);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $novo_nome;
            $mensagem_sucesso .= "Nome atualizado com sucesso.<br>";
        }
    }

    if (!empty($_POST['senha'])) {
        if (strlen($_POST['senha']) < 6) {
            $mensagem_erro .= "A nova senha deve ter no mínimo 6 caracteres.<br>";
        } else {
            $nova_senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sql_update_senha = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql_update_senha);
            $stmt->bind_param("si", $nova_senha_hash, $id_usuario);
            if ($stmt->execute()) {
                $mensagem_sucesso .= "Senha atualizada com sucesso.<br>";
            }
        }
    }

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        $foto = $_FILES['foto_perfil'];
        $diretorio = "uploads/profile_pics/";
        $nome_arquivo = uniqid() . '_' . basename($foto['name']);
        $caminho_completo = $diretorio . $nome_arquivo;

        if (move_uploaded_file($foto['tmp_name'], $caminho_completo)) {
            $sql_update_foto = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql_update_foto);
            $stmt->bind_param("si", $caminho_completo, $id_usuario);
            if ($stmt->execute()) {
                $mensagem_sucesso .= "Foto de perfil atualizada com sucesso.<br>";
            }
        } else {
            $mensagem_erro .= "Erro ao fazer upload da imagem.<br>";
        }
    }
}

$sql_user = "SELECT nome, email FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($sql_user);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - deepBlue</title>
    <link rel="stylesheet" href="style.css">
    <!-- Ícones Font Awesome para um visual mais moderno -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="global-background">
    <header class="main-header">
        <nav class="navbar">
            <div class="navbar-left">
                <a href="home.php" class="logo">helveticNDS</a>
            </div>
            <div class="navbar-right">
                <ul class="nav-links">
                    <li><span class="welcome-message">Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span></li>
                    <li><a href="home.php" class="nav-icon-link"><i class="fas fa-home"></i> Feed</a></li>
                    <li><a href="profile.php" class="nav-icon-link active"><i class="fas fa-user"></i> Meu Perfil</a></li>
                    <li class="notifications-dropdown">
                        <a href="#" id="notifications-bell" class="nav-icon-link">
                            <i class="fas fa-bell"></i> Notificações <span id="notifications-count" class="notification-badge">0</span>
                        </a>
                        <div id="notifications-dropdown-content" class="dropdown-content">
                            <p class="dropdown-empty-message">Nenhuma notificação nova.</p>
                        </div>
                    </li>
                    <li><a href="chat.php" class="nav-icon-link"><i class="fas fa-comment"></i> Chat</a></li>
                    <li><a href="logout.php" class="nav-icon-link logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="main-content-edit-profile">
        <div class="edit-profile-card">
            <h1>Editar Perfil</h1>
            
            <?php if($mensagem_sucesso): ?>
                <div class="success-message"><p><?php echo $mensagem_sucesso; ?></p></div>
            <?php endif; ?>
            <?php if($mensagem_erro): ?>
                <div class="error-message"><p><?php echo $mensagem_erro; ?></p></div>
            <?php endif; ?>

            <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="edit-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                    <small class="form-help-text">O email não pode ser alterado.</small>
                </div>
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user_data['nome']); ?>">
                </div>
                <div class="form-group">
                    <label for="senha">Nova Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Deixe em branco para não alterar">
                </div>
                <div class="form-group file-upload-group">
                    <label for="foto_perfil">Foto de Perfil</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" class="file-input">
                    <!-- Preview da imagem atual, se houver -->
                    <?php if (!empty($user_data['foto_perfil'])): ?>
                        <div class="current-profile-pic">
                            <img src="<?php echo htmlspecialchars($user_data['foto_perfil']); ?>" alt="Foto de Perfil Atual" class="profile-pic-preview">
                            <small>Foto de perfil atual</small>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn-primary edit-save-btn">Salvar Alterações</button>
            </form>
            <div class="back-link-wrapper">
                <a href="profile.php" class="btn-link"><i class="fas fa-arrow-left"></i> Voltar ao Perfil</a>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> helveticNDS. Todos os direitos reservados.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        // JS para toggle de dropdowns ou outras interações
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para dropdown de notificações (reuso do home.php)
            document.getElementById('notifications-bell').addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = document.getElementById('notifications-dropdown-content');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });
            window.addEventListener('click', function(e) {
                if (!e.target.matches('#notifications-bell') && !e.target.matches('#notifications-bell *')) {
                    const dropdown = document.getElementById('notifications-dropdown-content');
                    if (dropdown && dropdown.style.display === 'block') {
                        dropdown.style.display = 'none';
                    }
                }
            });

            // Opcional: Pré-visualização da imagem de perfil antes do upload
            const inputFotoPerfil = document.getElementById('foto_perfil');
            if (inputFotoPerfil) {
                inputFotoPerfil.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            let previewContainer = document.querySelector('.current-profile-pic');
                            if (!previewContainer) {
                                previewContainer = document.createElement('div');
                                previewContainer.className = 'current-profile-pic';
                                const fileUploadGroup = document.querySelector('.file-upload-group');
                                if (fileUploadGroup) {
                                    fileUploadGroup.appendChild(previewContainer);
                                }
                            }
                            let imgPreview = previewContainer.querySelector('.profile-pic-preview');
                            if (!imgPreview) {
                                imgPreview = document.createElement('img');
                                imgPreview.className = 'profile-pic-preview';
                                imgPreview.alt = 'Nova Foto de Perfil';
                                previewContainer.innerHTML = ''; // Limpa o conteúdo existente
                                previewContainer.appendChild(imgPreview);
                                const smallText = document.createElement('small');
                                smallText.textContent = 'Prévia da nova foto';
                                previewContainer.appendChild(smallText);
                            }
                            imgPreview.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>