<?php
session_start();
require_once 'config.php';
// Proteção da página
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - helveticNDS</title>
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
                        <li><a href="profile.php" class="nav-icon-link"><i class="fas fa-user"></i> Meu Perfil</a></li>
                        <li style="position:relative;">
                            <a href="#" id="notifications-bell" class="nav-icon-link">
                                <i class="fas fa-bell"></i> Notificações <span id="notifications-count" class="notification-badge">!</span>
                            </a>
                            <div id="notifications-dropdown-content">
                                <div id="notifications-list" style="padding:10px 0;text-align:center;color:#888;">Carregando...</div>
                            </div>
                        </li>
                        <li><a href="chat.php" class="nav-icon-link active"><i class="fas fa-comment"></i> Chat</a></li>
                        <li><a href="logout.php" class="nav-icon-link logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                    </ul>
                </div>
            </nav>
        </header>
        
    <main class="container main-chat-content-wrapper">
        <div class="chat-main-card">
            <h1>Chat</h1>
            <div class="chat-container chat-interface">
                <div class="user-list">
                    <p class="loading-message"><i class="fas fa-spinner fa-spin"></i> Carregando usuários...</p>
                </div>
                <div class="chat-window ">
                    <div class="chat-header chat-header-window">
                        <h3 id="chat-partner-name"><i class="fas fa-comments"></i> Selecione um usuário para conversar</h3>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <p class="chat-empty-message">Inicie uma conversa ou selecione um usuário.</p>
                        </div>
                    <div class="chat-form">
                        <form id="message-form" style="display: none;">
                            <input type="text" id="message-input" placeholder="Digite sua mensagem..." autocomplete="off" required>
                            <button type="submit" class="send-message-btn"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> helveticNDS. Todos os direitos reservados.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Notificações dropdown
            const bell = document.getElementById('notifications-bell');
            const dropdown = document.getElementById('notifications-dropdown-content');
            const notifList = document.getElementById('notifications-list');

            if (bell && dropdown && notifList) {
                bell.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (dropdown.style.display === 'block') {
                        dropdown.style.display = 'none';
                    } else {
                        dropdown.style.display = 'block';
                        notifList.innerHTML = "Carregando...";
                        fetch('notificacoes.php')
                            .then(r => r.text())
                            .then(html => {
                                notifList.innerHTML = html;
                            })
                            .catch(() => notifList.innerHTML = "Erro ao carregar notificações.");
                    }
                });
                dropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                window.addEventListener('click', function() {
                    if (dropdown.style.display === 'block') {
                        dropdown.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>