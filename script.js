document.addEventListener('DOMContentLoaded', function() { 

    const likeButtons = document.querySelectorAll('.like-btn');

    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            
            toggleLike(postId, this);
        });
    });

    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            toggleFavorite(postId, this);
        });
    });

    const commentForms = document.querySelectorAll('.comment-form');
    commentForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const postId = this.querySelector('input[name="post_id"]').value;
            const commentInput = this.querySelector('input[name="comment_text"]');
            const commentText = commentInput.value.trim();

            if (commentText) {
                addComment(postId, commentText, this);
            }
        });
    });

    /**
     * Função que envia a requisição para a API para curtir/descurtir um post. // se remover isso crasha samuel( sério, os comentarios)
     * @param {string} postId - O ID do post.
     * @param {HTMLElement} buttonElement - O elemento do botão que foi clicado.
     */

    function toggleLike(postId, buttonElement) {
        const formData = new FormData();
        formData.append('action', 'like_post');
        formData.append('post_id', postId);

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) 
        .then(data => {
            if (data.status === 'success') {
                const likeCountElement = buttonElement.nextElementSibling;
                if (likeCountElement && likeCountElement.classList.contains('like-count')) {
                    likeCountElement.textContent = data.newLikeCount;
                }
            } else {
                console.error('Erro ao curtir:', data.message);
            }
        })
        .catch(error => console.error('Erro na requisição:', error));
    }
    
    /**
     * Função para favoritar/desfavoritar um post.
     * @param {string} postId - O ID do post.
     * @param {HTMLElement} buttonElement - O elemento do botão que foi clicado.
     */
    function toggleFavorite(postId, buttonElement) {
        const formData = new FormData();
        formData.append('action', 'favorite_post');
        formData.append('post_id', postId);

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const icon = buttonElement.querySelector('i');
                if (data.action === 'favorited') {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    buttonElement.classList.add('active');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    buttonElement.classList.remove('active');
                }
            } else {
                console.error('Erro ao favoritar:', data.message);
            }
        })
        .catch(error => console.error('Erro na requisição:', error));
    }

    /**
     * Função que envia a requisição para a API para adicionar um comentário.
     * @param {string} postId - O ID do post.
     * @param {string} commentText - O texto do comentário.
     * @param {HTMLElement} formElement - O elemento do formulário que foi enviado.
     */
    function addComment(postId, commentText, formElement) {
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('post_id', postId);
        formData.append('comment_text', commentText);

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const commentsList = formElement.previousElementSibling;

                const noCommentsMsg = commentsList.querySelector('.no-comments');
                if (noCommentsMsg) {
                    noCommentsMsg.remove();
                }

                const newComment = document.createElement('div');
                newComment.classList.add('comment');
                newComment.innerHTML = `<strong>${data.comment.author}:</strong> ${data.comment.content}`;

                commentsList.appendChild(newComment);

                formElement.querySelector('input[name="comment_text"]').value = '';

            } else {
                console.error('Erro ao comentar:', data.message);
                alert('Não foi possível adicionar seu comentário.');
            }
        })
        .catch(error => console.error('Erro na requisição:', error));
    }

    // Lógica de Notificações
    const notificationsBell = document.getElementById('notifications-bell');
    const notificationsDropdown = document.getElementById('notifications-dropdown-content');
    const notificationsCountBadge = document.getElementById('notifications-count');

    function fetchNotifications() {
        fetch('api.php', { method: 'POST', body: new URLSearchParams('action=get_notifications') })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    updateNotificationsDropdown(data.notifications);
                    updateNotificationsCount(data.notifications.length);
                }
            });
    }

    function updateNotificationsDropdown(notifications) {
        notificationsDropdown.innerHTML = '';
        if (notifications.length === 0) {
            notificationsDropdown.innerHTML = '<p class="dropdown-empty-message">Nenhuma notificação nova.</p>';
            return;
        }
        notifications.forEach(notif => {
            const notifElement = document.createElement('a');
            notifElement.href = '#'; // Idealmente, link para o post/perfil
            notifElement.classList.add('notification-item');
            let text = '';
            switch (notif.tipo) {
                case 'curtida': text = `<strong>${notif.nome_origem}</strong> curtiu sua publicação.`; break;
                case 'resposta': text = `<strong>${notif.nome_origem}</strong> comentou em sua publicação.`; break;
                case 'mensagem': text = `<strong>${notif.nome_origem}</strong> te enviou uma mensagem.`; break;
            }
            notifElement.innerHTML = text;
            notificationsDropdown.appendChild(notifElement);
        });
    }

    function updateNotificationsCount(count) {
        notificationsCountBadge.textContent = count;
        if (count > 0) {
            notificationsCountBadge.style.display = 'inline-block';
        } else {
            notificationsCountBadge.style.display = 'none';
        }
    }

    if (notificationsBell) {
        notificationsBell.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdown = document.getElementById('notifications-dropdown-content');
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                fetchNotifications();
            }
        });
    }

    // Fetch inicial de notificações para contagem
    fetchNotifications();


    if (document.querySelector('.chat-container')) {
    
    let currentPartnerId = null;
    let pollingInterval = null;

    const userListDiv = document.querySelector('.user-list');
    const chatPartnerNameH3 = document.getElementById('chat-partner-name');
    const chatMessagesDiv = document.getElementById('chat-messages');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');

    fetchUsers();

    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageText = messageInput.value.trim();
        if (messageText && currentPartnerId) {
            sendMessage(currentPartnerId, messageText);
        }
    });

    function fetchUsers() {
        fetch('api.php', { method: 'POST', body: new URLSearchParams('action=get_users') })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    userListDiv.innerHTML = '';
                    data.users.forEach(user => {
                        const userElement = document.createElement('div');
                        userElement.classList.add('user-item');
                        userElement.textContent = user.nome;
                        userElement.dataset.userId = user.id;
                        userElement.dataset.userName = user.nome;
                        userListDiv.appendChild(userElement);

                        userElement.addEventListener('click', () => selectUser(user.id, user.nome));
                    });
                }
            });
    }

    function selectUser(userId, userName) {
        currentPartnerId = userId;
        chatPartnerNameH3.textContent = `Conversando com ${userName}`;
        messageForm.style.display = 'flex';

        if (pollingInterval) clearInterval(pollingInterval);

        fetchMessages(userId);

        pollingInterval = setInterval(() => fetchMessages(userId), 3000);
    }

    function fetchMessages(partnerId) {
        const formData = new URLSearchParams();
        formData.append('action', 'get_messages');
        formData.append('partner_id', partnerId);

        fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    chatMessagesDiv.innerHTML = '';
                    data.messages.forEach(msg => {
                        const messageElement = document.createElement('p');
                        const messageClass = msg.id_remetente == partnerId ? 'theirs' : 'mine';
                        messageElement.classList.add(messageClass);
                        messageElement.textContent = msg.mensagem;
                        chatMessagesDiv.appendChild(messageElement);
                    });
                        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
                    }
                });
        }
        }
    });

    function sendMessage(recipientId, messageText) {
        const formData = new URLSearchParams();
        formData.append('action', 'send_message');
        formData.append('recipient_id', recipientId);
        formData.append('message_text', messageText);

        fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    messageInput.value = '';
                    const messageElement = document.createElement('p');
                    messageElement.classList.add('mine');
                    messageElement.textContent = messageText;
                    chatMessagesDiv.appendChild(messageElement);
                    chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
                }
            });
    }