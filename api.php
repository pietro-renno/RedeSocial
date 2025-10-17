<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado.']);
    exit();
}

$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['user_id'];
$nome_usuario = $_SESSION['user_name'];

switch ($action) {
    case 'like_post':
        $id_postagem = $_POST['post_id'] ?? 0;
        if (empty($id_postagem)) { exit(json_encode(['status' => 'error', 'message' => 'ID da postagem não fornecido.'])); }

        $sql_check = "SELECT * FROM curtidas WHERE id_usuario = ? AND id_postagem = ?";
        $stmt_check = $mysqli->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_usuario, $id_postagem);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Se já curtiu, remove a curtida (descurtir)
            $sql_delete = "DELETE FROM curtidas WHERE id_usuario = ? AND id_postagem = ?";
            $stmt_delete = $mysqli->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $id_usuario, $id_postagem);
            $stmt_delete->execute();
        } else {
            // Se não curtiu, insere a curtida
            $sql_insert = "INSERT INTO curtidas (id_usuario, id_postagem) VALUES (?, ?)";
            $stmt_insert = $mysqli->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $id_usuario, $id_postagem);
            $stmt_insert->execute();

            // GATILHO DE NOTIFICAÇÃO: Cria a notificação de curtida
            $sql_get_owner = "SELECT id_usuario FROM postagens WHERE id = ?";
            $stmt_owner = $mysqli->prepare($sql_get_owner);
            $stmt_owner->bind_param("i", $id_postagem);
            $stmt_owner->execute();
            $owner_id = $stmt_owner->get_result()->fetch_assoc()['id_usuario'];
            if ($owner_id != $id_usuario) {
                $sql_notify = "INSERT INTO notificacoes (id_usuario_destino, id_usuario_origem, tipo, id_referencia) VALUES (?, ?, 'curtida', ?)";
                $stmt_notify = $mysqli->prepare($sql_notify);
                $stmt_notify->bind_param("iii", $owner_id, $id_usuario, $id_postagem);
                $stmt_notify->execute();
            }
        }

        $sql_count = "SELECT COUNT(*) as total FROM curtidas WHERE id_postagem = ?";
        $stmt_count = $mysqli->prepare($sql_count);
        $stmt_count->bind_param("i", $id_postagem);
        $stmt_count->execute();
        $newLikeCount = $stmt_count->get_result()->fetch_assoc()['total'];
        echo json_encode(['status' => 'success', 'newLikeCount' => $newLikeCount]);
        break;

    case 'favorite_post':
        $id_postagem = $_POST['post_id'] ?? 0;
        if (empty($id_postagem)) { exit(json_encode(['status' => 'error', 'message' => 'ID da postagem não fornecido.'])); }

        $sql_check = "SELECT id FROM favoritos WHERE id_usuario = ? AND id_postagem = ?";
        $stmt_check = $mysqli->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_usuario, $id_postagem);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Se já favoritou, remove
            $sql_delete = "DELETE FROM favoritos WHERE id_usuario = ? AND id_postagem = ?";
            $stmt_delete = $mysqli->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $id_usuario, $id_postagem);
            $stmt_delete->execute();
            echo json_encode(['status' => 'success', 'action' => 'unfavorited']);
        } else {
            // Se não favoritou, insere
            $sql_insert = "INSERT INTO favoritos (id_usuario, id_postagem) VALUES (?, ?)";
            $stmt_insert = $mysqli->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $id_usuario, $id_postagem);
            $stmt_insert->execute();
            echo json_encode(['status' => 'success', 'action' => 'favorited']);
        }
        break;

    // ===================================================================
    // CASO DE AÇÃO: ADICIONAR COMENTÁRIO
    // ===================================================================
    case 'add_comment':
        $id_postagem = $_POST['post_id'] ?? 0;
        $conteudo = trim($_POST['comment_text'] ?? '');
        if (empty($id_postagem) || empty($conteudo)) { exit(json_encode(['status' => 'error', 'message' => 'Dados inválidos.'])); }

        $sql = "INSERT INTO respostas (id_postagem, id_usuario, conteudo) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iis", $id_postagem, $id_usuario, $conteudo);

        if ($stmt->execute()) {
            // GATILHO DE NOTIFICAÇÃO: Cria a notificação de comentário
            $sql_get_owner = "SELECT id_usuario FROM postagens WHERE id = ?";
            $stmt_owner = $mysqli->prepare($sql_get_owner);
            $stmt_owner->bind_param("i", $id_postagem);
            $stmt_owner->execute();
            $owner_id = $stmt_owner->get_result()->fetch_assoc()['id_usuario'];
            if ($owner_id != $id_usuario) {
                $sql_notify = "INSERT INTO notificacoes (id_usuario_destino, id_usuario_origem, tipo, id_referencia) VALUES (?, ?, 'resposta', ?)";
                $stmt_notify = $mysqli->prepare($sql_notify);
                $stmt_notify->bind_param("iii", $owner_id, $id_usuario, $id_postagem);
                $stmt_notify->execute();
            }
            echo json_encode(['status' => 'success', 'comment' => ['author' => htmlspecialchars($nome_usuario), 'content' => htmlspecialchars($conteudo)]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Falha ao salvar o comentário.']);
        }
        break;

    // ===================================================================
    // CASOS DE AÇÃO: CHAT
    // ===================================================================
    case 'get_users':
        $sql = "SELECT id, nome FROM usuarios WHERE id != ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'users' => $users]);
        break;

    case 'get_messages':
        $partner_id = $_POST['partner_id'] ?? 0;
        if (empty($partner_id)) { exit(); }
        $sql = "SELECT id, id_remetente, mensagem FROM mensagens_chat WHERE (id_remetente = ? AND id_destinatario = ?) OR (id_remetente = ? AND id_destinatario = ?) ORDER BY data_envio ASC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiii", $id_usuario, $partner_id, $partner_id, $id_usuario);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;

    case 'send_message':
        $recipient_id = $_POST['recipient_id'] ?? 0;
        $message_text = trim($_POST['message_text'] ?? '');
        if (empty($recipient_id) || empty($message_text)) { exit(json_encode(['status' => 'error', 'message' => 'Dados inválidos.'])); }

        $sql = "INSERT INTO mensagens_chat (id_remetente, id_destinatario, mensagem) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iis", $id_usuario, $recipient_id, $message_text);
        
        if ($stmt->execute()) {
            // GATILHO DE NOTIFICAÇÃO: Cria a notificação de mensagem
            $sql_notify = "INSERT INTO notificacoes (id_usuario_destino, id_usuario_origem, tipo, id_referencia) VALUES (?, ?, 'mensagem', ?)";
            $stmt_notify = $mysqli->prepare($sql_notify);
            $stmt_notify->bind_param("iii", $recipient_id, $id_usuario, $id_usuario);
            $stmt_notify->execute();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Falha ao enviar mensagem.']);
        }
        break;

    // ===================================================================
    // CASO DE AÇÃO: NOTIFICAÇÕES
    // ===================================================================
    case 'get_notifications':
        $sql = "SELECT n.tipo, u.nome as nome_origem FROM notificacoes n JOIN usuarios u ON n.id_usuario_origem = u.id WHERE n.id_usuario_destino = ? AND n.lida = 0 ORDER BY n.data_notificacao DESC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'notifications' => $notifications]);
        break;

    // ===================================================================
    // CASO PADRÃO: AÇÃO DESCONHECIDA
    // ===================================================================
    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida.']);
        break;
}

// 5. ENCERRAMENTO
$mysqli->close();
?>