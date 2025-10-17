<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<p style="padding:10px;">Faça login para ver notificações.</p>';
    exit();
}

// Exemplo: notificações de curtidas e favoritos recentes
$user_id = $_SESSION['user_id'];
$notificacoes = [];

// Curtidas em posts do usuário
$sql = "SELECT u.nome, p.conteudo, c.data_curtida
        FROM curtidas c
        JOIN usuarios u ON c.id_usuario = u.id
        JOIN postagens p ON c.id_postagem = p.id
        WHERE p.id_usuario = ?
        ORDER BY c.data_curtida DESC
        LIMIT 10";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notificacoes[] = "<b>" . htmlspecialchars($row['nome']) . "</b> curtiu sua postagem: <span style='color:#007bff'>" . htmlspecialchars(mb_strimwidth($row['conteudo'],0,30,'...')) . "</span> <small style='color:#888;'>(" . date('d/m H:i', strtotime($row['data_curtida'])) . ")</small>";
}
$stmt->close();

// Favoritos em posts do usuário
$sql = "SELECT u.nome, p.conteudo, f.data_favorito
        FROM favoritos f
        JOIN usuarios u ON f.id_usuario = u.id
        JOIN postagens p ON f.id_postagem = p.id
        WHERE p.id_usuario = ?
        ORDER BY f.data_favorito DESC
        LIMIT 10";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notificacoes[] = "<b>" . htmlspecialchars($row['nome']) . "</b> favoritou sua postagem: <span style='color:#007bff'>" . htmlspecialchars(mb_strimwidth($row['conteudo'],0,30,'...')) . "</span> <small style='color:#888;'>(" . date('d/m H:i', strtotime($row['data_favorito'])) . ")</small>";
}
$stmt->close();

if (empty($notificacoes)) {
    echo "<p style='padding:10px;'>Nenhuma notificação recente.</p>";
} else {
    echo "<ul style='padding:10px 20px 10px 20px;list-style:none;margin:0;'>";
    foreach ($notificacoes as $n) {
        echo "<li style='margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:8px;'>$n</li>";
    }
    echo "</ul>";
}
?>
