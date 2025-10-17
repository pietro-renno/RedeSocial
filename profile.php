<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$profile_id = $_GET['id'] ?? $_SESSION['user_id'];
$is_own_profile = ($profile_id == $_SESSION['user_id']);
$view = $_GET['view'] ?? 'posts'; // 'posts' ou 'saved'

$sql_user = "SELECT nome, foto_perfil FROM usuarios WHERE id = ?";
$stmt_user = $mysqli->prepare($sql_user);
$stmt_user->bind_param("i", $profile_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_info = $result_user->fetch_assoc();

if (!$user_info) {
    echo "Usuário não encontrado.";
    exit();
}

if ($view === 'saved' && $is_own_profile) {
    // Query para buscar posts favoritados
    $sql_posts = "SELECT p.id, p.conteudo, p.imagem, p.data_postagem, 
                  (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id) as total_curtidas,
                  (SELECT COUNT(*) FROM respostas WHERE id_postagem = p.id) as total_comentarios
                  FROM postagens p
                  JOIN favoritos f ON p.id = f.id_postagem
                  WHERE f.id_usuario = ?
                  ORDER BY f.data_favorito DESC";
} else {
    // Query original para buscar posts do usuário
    $sql_posts = "SELECT id, conteudo, imagem, data_postagem,
                  (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id) as total_curtidas,
                  (SELECT COUNT(*) FROM respostas WHERE id_postagem = p.id) as total_comentarios
                  FROM postagens p WHERE id_usuario = ? ORDER BY data_postagem DESC";
}
$stmt_posts = $mysqli->prepare($sql_posts);
$stmt_posts->bind_param("i", $profile_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($user_info['nome']); ?> - helveticNDS</title>
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
                    <li><a href="profile.php" class="nav-icon-link"><i class="fas fa-user"></i> Meu Perfil</a></li>
                    <li style="position:relative;">
                        <a href="#" id="notifications-bell" class="nav-icon-link">
                            <i class="fas fa-bell"></i> Notificações <span id="notifications-count" class="notification-badge">!</span>
                        </a>
                        <div id="notifications-dropdown-content">
                            <div id="notifications-list" style="padding:10px 0;text-align:center;color:#888;">Carregando...</div>
                        </div>
                    </li>
                    <li><a href="chat.php" class="nav-icon-link"><i class="fas fa-comment"></i> Chat</a></li>
                    <li><a href="logout.php" class="nav-icon-link logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="main-profile-content">
        <div class="profile-card">
            <div class="profile-header-section">
                <img src="<?php echo !empty($user_info['foto_perfil']) ? htmlspecialchars($user_info['foto_perfil']) : 'https://via.placeholder.com/150/0095f6/ffffff?text=U'; ?>" alt="Foto de Perfil" class="profile-pic-large responsive-img">
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user_info['nome']); ?></h1>
                    
                    <?php if ($is_own_profile): ?>
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn-secondary"><i class="fas fa-edit"></i> Editar Perfil</a>
                            <!-- Exemplo de como adicionar outros botões, se necessário -->
                            <!-- <button class="btn-primary">Ver Insights</button> -->
                        </div>
                    <?php else: ?>
                        <div class="profile-actions">
                            <!-- Botões para seguir/deixar de seguir, enviar mensagem, etc. -->
                            <button class="btn-primary">Seguir</button>
                            <button class="btn-secondary">Enviar Mensagem</button>
                        </div>
                    <?php endif; ?>

                    <!-- <div class="profile-stats">
                        <span><strong>
                           <?php // echo $post_count; ?>
                        </strong> publicações</span> -->
                        <!-- Adicione aqui contagens de seguidores/seguindo se tiver implementado -->
                        <!-- <span><strong>1.2K</strong> seguidores</span>
                        <span><strong>500</strong> seguindo</span>
                    </div> -->
                    
                    <div class="profile-bio">
                        <p>Biografia do usuário :P </p>
                    </div>
                </div>
            </div>

            <div class="profile-gallery-nav">
                <a href="profile.php?id=<?php echo $profile_id; ?>&view=posts" class="profile-gallery-item <?php echo ($view === 'posts' ? 'active' : ''); ?>"><i class="fas fa-th"></i> Publicações</a>
                <?php if ($is_own_profile): ?>
                <a href="profile.php?id=<?php echo $profile_id; ?>&view=saved" class="profile-gallery-item <?php echo ($view === 'saved' ? 'active' : ''); ?>"><i class="far fa-bookmark"></i> Salvos</a>
                <?php endif; ?>
            </div>

            <div class="profile-posts-grid">
                <?php if ($result_posts->num_rows > 0): ?>
                    <?php while($post = $result_posts->fetch_assoc()): ?>
                        <div class="profile-post-item">
                            <div class="post-overlay">
                                <div class="overlay-icons">
                                    <span><i class="fas fa-heart"></i> <?php echo $post['total_curtidas'] ?? 0; ?></span>
                                    <span><i class="fas fa-comment"></i> <?php echo $post['total_comentarios'] ?? 0; ?></span>
                                </div>
                            </div>
                            <?php if (!empty($post['imagem'])): ?>
                                <img src="<?php echo htmlspecialchars($post['imagem']); ?>" alt="Imagem do Post" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <div class="post-content-placeholder">
                                    <p><?php echo nl2br(htmlspecialchars(substr($post['conteudo'], 0, 100))) . (strlen($post['conteudo']) > 100 ? '...' : ''); ?></p>
                                    <small class="post-timestamp-grid"><?php echo date('d/m/Y H:i', strtotime($post['data_postagem'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-posts-message col-span-3"> <!-- col-span-3 para ocupar as 3 colunas -->
                        <?php if ($view === 'saved'): ?>
                            <p>Você ainda não salvou nenhuma publicação.</p>
                        <?php else: ?>
                            <p><?php echo htmlspecialchars($user_info['nome']); ?> ainda não fez nenhuma postagem.</p>
                            <?php if ($is_own_profile): ?>
                                 <button class="btn-primary" onclick="window.location.href='home.php'">Crie sua primeira publicação!</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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