<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$erro_post = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['conteudo'])) {
    $conteudo = trim($_POST['conteudo']);
    $id_usuario = $_SESSION['user_id'];
    $imagem_path = null;

    // Processa upload de imagem, se houver
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $permitidas)) {
            $nome_arquivo = uniqid('img_') . '.' . $ext;
            $destino = 'uploads/' . $nome_arquivo;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $imagem_path = $destino;
            } else {
                $erro_post = "Erro ao salvar a imagem.";
            }
        } else {
            $erro_post = "Formato de imagem não permitido.";
        }
    }

    if (empty($conteudo) && !$imagem_path) {
        $erro_post = "Você não pode criar uma postagem vazia.";
    } elseif (!$erro_post) {
        $sql = "INSERT INTO postagens (id_usuario, conteudo, imagem) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iss", $id_usuario, $conteudo, $imagem_path);

        if ($stmt->execute()) {
            
        } else {
            $erro_post = "Houve um erro ao tentar publicar. Tente novamente.";
        }
        $stmt->close();
    }
}

// Favoritar/desfavoritar post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_post_id'])) {
    $post_id = intval($_POST['favorite_post_id']);
    $user_id = $_SESSION['user_id'];
    // Verifica se já está favoritado
    $check = $mysqli->prepare("SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_postagem = ?");
    $check->bind_param("ii", $user_id, $post_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        // Já favoritado, desfavorita
        $del = $mysqli->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_postagem = ?");
        $del->bind_param("ii", $user_id, $post_id);
        $del->execute();
        $del->close();
    } else {
        // Não favoritado, adiciona
        $add = $mysqli->prepare("INSERT INTO favoritos (id_usuario, id_postagem, data_favorito) VALUES (?, ?, NOW())");
        $add->bind_param("ii", $user_id, $post_id);
        $add->execute();
        $add->close();
    }
    $check->close();
    // Redireciona para evitar repost
    header("Location: home.php");
    exit();
}

// Busca todos os posts
$sql_select = "SELECT 
                    p.id,
                    p.id_usuario, 
                    p.conteudo, 
                    p.imagem,
                    p.data_postagem, 
                    u.nome,
                    u.foto_perfil,
                    (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id) as total_curtidas
                FROM postagens p
                JOIN usuarios u ON p.id_usuario = u.id
                ORDER BY p.data_postagem DESC";
$resultado_posts = $mysqli->query($sql_select);

// Busca favoritos do usuário logado
$favoritos_usuario = [];
$fav_query = $mysqli->prepare("SELECT id_postagem FROM favoritos WHERE id_usuario = ?");
$fav_query->bind_param("i", $_SESSION['user_id']);
$fav_query->execute();
$fav_result = $fav_query->get_result();
while ($fav = $fav_result->fetch_assoc()) {
    $favoritos_usuario[] = $fav['id_postagem'];
}
$fav_query->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - helveticNDS</title>
    <link rel="stylesheet" href="style.css">
    <!-- Ícones Font Awesome para um visual mais moderno (opcional, mas recomendado) -->
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

    <main class="main-content-wrapper">
        <div class="feed-container">
            <aside class="sidebar-left">
                <!-- Conteúdo opcional para a barra lateral esquerda, talvez sugestões de amigos, etc. -->
                <div class="sidebar-card">
                    <h3>Explore</h3>
                    <ul>
                        <li><a href="#"><i class="fas fa-gamepad"></i> Musicas</a></li>
                        <li><a href="#"><i class="fas fa-hashtag"></i> Tendências</a></li>
                        <li><a href="#"><i class="fas fa-users"></i> Comunidades</a></li>
                    </ul>
                </div>
            </aside>

            <section class="feed-central-column">
                <div class="post-create-card">
                    <h2>Criar Nova Publicação</h2>
                    <form action="" method="POST" class="post-form" enctype="multipart/form-data">
                        <textarea name="conteudo" placeholder="No que você está pensando, <?php echo htmlspecialchars($_SESSION['user_name']); ?>?" class="post-textarea"></textarea>
                        <input type="file" name="imagem" accept="image/*" style="margin-top:10px;">
                        <?php if ($erro_post): ?>
                            <p class="error-message"><?php echo $erro_post; ?></p>
                        <?php endif; ?>
                        <button type="submit" class="btn-primary post-btn">Publicar</button>
                    </form>
                </div>

                <div class="feed-posts-section">
                    <h2>Últimas Postagens</h2>
                    <?php if ($resultado_posts && $resultado_posts->num_rows > 0): ?>
                        <?php while($post = $resultado_posts->fetch_assoc()): ?>
                            <?php
                                $fotoPerfil = !empty($post['foto_perfil']) ? $post['foto_perfil'] : 'https://via.placeholder.com/32/0095f6/ffffff?text=U';
                                $is_favorited = in_array($post['id'], $favoritos_usuario);
                            ?>
                            <div class="post-card" id="post-<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <div class="post-user-info">
                                        <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Foto de Perfil" class="profile-picture-small">
                                        <a href="profile.php?id=<?php echo $post['id_usuario']; ?>" class="post-username">
                                            <strong><?php echo htmlspecialchars($post['nome']); ?></strong>
                                        </a>
                                    </div>
                                    <small class="post-timestamp"><?php echo date('d/m/Y H:i', strtotime($post['data_postagem'])); ?></small>
                                </div>
                                <div class="post-body">
                                    <p><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>
                                    <?php if (!empty($post['imagem'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['imagem']); ?>" alt="Imagem do Post" class="post-image" style="margin-top:10px;max-width:100%;border-radius:8px;">
                                    <?php endif; ?>
                                </div>
                                <div class="post-actions">
                                    <button class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="far fa-heart"></i> Curtir
                                    </button>
                                    <span class="like-count"><?php echo $post['total_curtidas']; ?></span>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="favorite_post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="action-btn favorite-btn<?php echo $is_favorited ? ' active' : ''; ?>">
                                            <i class="far fa-star"></i> <?php echo $is_favorited ? 'Desfavoritar' : 'Favoritar'; ?>
                                        </button>
                                    </form>
                                    <button class="action-btn comment-toggle-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="far fa-comment"></i> Comentar
                                    </button>
                                </div>
                                <div class="post-comments-section" style="display: none;"> <!-- Escondido por padrão, para ser ativado por JS -->
                                     <div class="comments-list">
                                        <?php
                                        // O código PHP para buscar comentários permanece o mesmo
                                        $id_postagem_atual = $post['id'];
                                        $sql_comentarios = "SELECT r.conteudo, u.nome FROM respostas r JOIN usuarios u ON r.id_usuario = u.id WHERE r.id_postagem = ? ORDER BY r.data_resposta ASC";
                                        $stmt_comentarios = $mysqli->prepare($sql_comentarios);
                                        $stmt_comentarios->bind_param("i", $id_postagem_atual);
                                        $stmt_comentarios->execute();
                                        $resultado_comentarios = $stmt_comentarios->get_result();
                                        if ($resultado_comentarios->num_rows > 0) {
                                            while($comentario = $resultado_comentarios->fetch_assoc()) {
                                                echo "<div class='comment'><img src='https://via.placeholder.com/24/cccccc/ffffff?text=U' class='profile-picture-small-comment'> <strong>" . htmlspecialchars($comentario['nome']) . ":</strong> " . htmlspecialchars($comentario['conteudo']) . "</div>";
                                            }
                                        } else {
                                            echo "<p class='no-comments-message'>Seja o primeiro a comentar!</p>";
                                        }
                                        $stmt_comentarios->close(); // Fechar o statement
                                        ?>
                                    </div>
                                    <form class="comment-form" method="POST">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="text" name="comment_text" placeholder="Adicione um comentário..." class="comment-input" required>
                                        <button type="submit" class="comment-btn"><i class="fas fa-paper-plane"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-posts-message">
                            <p>Ainda não há nenhuma postagem. Seja o primeiro a publicar!</p>
                            <button class="btn-primary">Compartilhe sua primeira ideia!</button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="sidebar-right">
                <!-- Conteúdo opcional para a barra lateral direita, talvez "Quem seguir", anúncios, etc. -->
                <div class="sidebar-card">
                    <h3>Sugestões para Você</h3>
                    <ul>
                        <li><a href="#"><img src="https://via.placeholder.com/24/cccccc/ffffff?text=P1" class="profile-picture-small-sidebar"> Perfil 1 <button class="btn-follow">Seguir</button></a></li>
                        <li><a href="#"><img src="https://via.placeholder.com/24/cccccc/ffffff?text=P2" class="profile-picture-small-sidebar"> Perfil 2 <button class="btn-follow">Seguir</button></a></li>
                        <li><a href="#"><img src="https://via.placeholder.com/24/cccccc/ffffff?text=P3" class="profile-picture-small-sidebar"> Perfil 3 <button class="btn-follow">Seguir</button></a></li>
                    </ul>
                </div>
            </aside>
        </div>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> helveticNDS. Todos os direitos reservados.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // JS para alternar a exibição da seção de comentários
            document.querySelectorAll('.comment-toggle-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    const commentsSection = document.querySelector(`#post-${postId} .post-comments-section`);
                    if (commentsSection) {
                        commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
                    }
                });
            });
            // Notificações dropdown
            const bell = document.getElementById('notifications-bell');
            const dropdown = document.getElementById('notifications-dropdown-content');
            const notifList = document.getElementById('notifications-list');

            if (bell && dropdown && notifList) {
                bell.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Fecha outros dropdowns se necessário
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
<?php
$mysqli->close();
?>