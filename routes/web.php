<?php

declare(strict_types=1);

use App\Core\Router;

/**
 * Định tuyến ứng dụng.
 */
return static function (Router $router): void {
    // ---------- Xác thực ----------
    $router->group(['GuestMiddleware'], static function (Router $router): void {
        $router->get('/login', [\App\Controllers\AuthController::class, 'showLogin'], 'login');
        $router->post('/login', [\App\Controllers\AuthController::class, 'login'], 'login.post');
        $router->get('/register', [\App\Controllers\AuthController::class, 'showRegister'], 'register');
        $router->post('/register', [\App\Controllers\AuthController::class, 'register'], 'register.post');
        $router->get('/forgot-password', [\App\Controllers\PasswordController::class, 'showForgotForm'], 'password.forgot');
        $router->post('/forgot-password', [\App\Controllers\PasswordController::class, 'sendResetLink'], 'password.forgot.post');
        $router->get('/reset-password', [\App\Controllers\PasswordController::class, 'showResetForm'], 'password.reset');
        $router->post('/reset-password', [\App\Controllers\PasswordController::class, 'reset'], 'password.reset.post');
    });

    $router->post('/logout', [\App\Controllers\AuthController::class, 'logout'], 'logout');

    // ---------- Trang công khai ----------
    $router->get('/', [\App\Controllers\FeedController::class, 'index'], 'home');
    $router->get('/post/{id}', [\App\Controllers\PostController::class, 'show'], 'post.show');
    $router->get('/u/{username}', [\App\Controllers\ProfileController::class, 'show'], 'profile.show');
    $router->get('/search', [\App\Controllers\SearchController::class, 'index'], 'search');
    $router->get('/p/{id}', [\App\Controllers\PostController::class, 'show'], 'post.permalink');

    // Tìm kiếm là tính năng công khai nên API gợi ý cũng cho phép khách truy cập
    $router->get('/api/search/users', [\App\Controllers\ApiController::class, 'searchUsers'], 'api.search.users');
    $router->get('/api/search/posts', [\App\Controllers\ApiController::class, 'searchPosts'], 'api.search.posts');

    // ---------- Yêu cầu đăng nhập ----------
    $router->group(['AuthMiddleware'], static function (Router $router): void {
        // Feed & bài viết
        $router->get('/feed', [\App\Controllers\FeedController::class, 'index'], 'feed');
        $router->get('/posts/create', [\App\Controllers\PostController::class, 'create'], 'posts.create');
        $router->post('/posts', [\App\Controllers\PostController::class, 'store'], 'posts.store');
        $router->get('/posts/{id}/edit', [\App\Controllers\PostController::class, 'edit'], 'posts.edit');
        $router->post('/posts/{id}/update', [\App\Controllers\PostController::class, 'update'], 'posts.update');
        $router->post('/posts/{id}/delete', [\App\Controllers\PostController::class, 'destroy'], 'posts.destroy');
        $router->post('/posts/{id}/like', [\App\Controllers\LikeController::class, 'toggle'], 'posts.like');
        $router->get('/posts/{id}/likes', [\App\Controllers\LikeController::class, 'users'], 'posts.likes');
        $router->post('/posts/{id}/comment', [\App\Controllers\CommentController::class, 'store'], 'comments.store');
        $router->post('/comments/{id}/update', [\App\Controllers\CommentController::class, 'update'], 'comments.update');
        $router->post('/comments/{id}/delete', [\App\Controllers\CommentController::class, 'destroy'], 'comments.delete');
        $router->post('/posts/{id}/share', [\App\Controllers\ShareController::class, 'store'], 'posts.share');

        // Hồ sơ
        $router->get('/settings/profile', [\App\Controllers\ProfileController::class, 'edit'], 'profile.edit');
        $router->post('/settings/profile', [\App\Controllers\ProfileController::class, 'update'], 'profile.update');
        $router->post('/settings/password', [\App\Controllers\ProfileController::class, 'updatePassword'], 'profile.password');
        $router->post('/settings/theme', [\App\Controllers\ProfileController::class, 'updateTheme'], 'profile.theme');

        // Bạn bè & theo dõi
        $router->get('/friends', [\App\Controllers\FriendController::class, 'index'], 'friends');
        $router->get('/friends/requests', [\App\Controllers\FriendController::class, 'requests'], 'friends.requests');
        $router->post('/friends/request/{id}', [\App\Controllers\FriendController::class, 'request'], 'friends.request');
        $router->post('/friends/{id}/accept', [\App\Controllers\FriendController::class, 'accept'], 'friends.accept');
        $router->post('/friends/{id}/decline', [\App\Controllers\FriendController::class, 'decline'], 'friends.decline');
        $router->post('/friends/{id}/cancel', [\App\Controllers\FriendController::class, 'cancel'], 'friends.cancel');
        $router->post('/friends/{id}/remove', [\App\Controllers\FriendController::class, 'remove'], 'friends.remove');
        $router->post('/follow/{id}', [\App\Controllers\FollowController::class, 'toggle'], 'follow.toggle');

        // Nhắn tin
        $router->get('/chat', [\App\Controllers\ChatController::class, 'index'], 'chat');
        $router->get('/chat/{id}', [\App\Controllers\ChatController::class, 'show'], 'chat.show');
        $router->post('/chat/{id}/send', [\App\Controllers\ChatController::class, 'send'], 'chat.send');
        $router->get('/chat/{id}/messages', [\App\Controllers\ChatController::class, 'messages'], 'chat.messages');
        $router->get('/chat/start/{id}', [\App\Controllers\ChatController::class, 'start'], 'chat.start');
        $router->post('/chat/start/{id}', [\App\Controllers\ChatController::class, 'start'], 'chat.start.post');

        // WebSocket (realtime chat + feed + thông báo)
        $router->get('/api/ws/session', [\App\Controllers\WebSocketController::class, 'session'], 'api.ws.session');
        $router->get('/api/ws/ticket/{id}', [\App\Controllers\WebSocketController::class, 'ticket'], 'api.ws.ticket');

        // Thông báo
        $router->get('/notifications', [\App\Controllers\NotificationController::class, 'index'], 'notifications');
        $router->post('/notifications/{id}/read', [\App\Controllers\NotificationController::class, 'markRead'], 'notifications.read');
        $router->post('/notifications/read-all', [\App\Controllers\NotificationController::class, 'markAllRead'], 'notifications.read-all');
    });

    // ---------- API AJAX ----------
    $router->group(['AuthMiddleware'], static function (Router $router): void {
        $router->get('/api/notifications/unread', [\App\Controllers\ApiController::class, 'unreadNotifications'], 'api.notifications.unread');
        $router->get('/api/messages/unread', [\App\Controllers\ApiController::class, 'unreadMessages'], 'api.messages.unread');
        $router->get('/api/feed', [\App\Controllers\ApiController::class, 'feed'], 'api.feed');
        $router->get('/api/posts/{id}/comments', [\App\Controllers\ApiController::class, 'comments'], 'api.posts.comments');

        // Render HTML server-side cho client realtime
        $router->get('/api/posts/{id}/card', [\App\Controllers\ApiController::class, 'postCard'], 'api.posts.card');
        $router->get('/api/notifications/{id}/item', [\App\Controllers\ApiController::class, 'notificationItem'], 'api.notifications.item');
    });
};
