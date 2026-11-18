# Dữ liệu mẫu cho Koobecaf
# Mật khẩu chung cho tất cả tài khoản mẫu: Password@123

-- Bắt buộc: client phải giao tiếp với server bằng utf8mb4, nếu không dữ liệu
-- tiếng Việt sẽ bị mã hoá lại theo bảng mã console (CP437/CP1252) khi import.
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `messages`;
TRUNCATE TABLE `conversation_members`;
TRUNCATE TABLE `conversations`;
TRUNCATE TABLE `friendships`;
TRUNCATE TABLE `follows`;
TRUNCATE TABLE `shares`;
TRUNCATE TABLE `comments`;
TRUNCATE TABLE `likes`;
TRUNCATE TABLE `post_images`;
TRUNCATE TABLE `posts`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Password hash của "Password@123" (bcrypt, cost 10)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `display_name`, `bio`, `theme`) VALUES
(1, 'an',        'an@koobecaf.test',        '$2y$10$yeLMlkmoT7osnKpQ7mciseDAhY/3OhPiWhqpci.Qhbzf7UOmtpMaO', 'An Nguyễn', 'Yêu cà phê và code. Backend PHP chính.', 'light'),
(2, 'binh',      'binh@koobecaf.test',      '$2y$10$yeLMlkmoT7osnKpQ7mciseDAhY/3OhPiWhqpci.Qhbzf7UOmtpMaO', 'Bình Trần', 'Chơi guitar cuối tuần, chạy bộ buổi sáng.', 'light'),
(3, 'chi',       'chi@koobecaf.test',       '$2y$10$yeLMlkmoT7osnKpQ7mciseDAhY/3OhPiWhqpci.Qhbzf7UOmtpMaO', 'Chi Lê', 'Thiết kế giao diện và chụp ảnh.', 'dark'),
(4, 'dung',      'dung@koobecaf.test',      '$2y$10$yeLMlkmoT7osnKpQ7mciseDAhY/3OhPiWhqpci.Qhbzf7UOmtpMaO', 'Dũng Phạm', 'Đam mê du lịch và ẩm thực đường phố.', 'light'),
(5, 'em',        'em@koobecaf.test',        '$2y$10$yeLMlkmoT7osnKpQ7mciseDAhY/3OhPiWhqpci.Qhbzf7UOmtpMaO', 'Em Võ', 'Mới tham gia Koobecaf!', 'light');

-- Bài viết
INSERT INTO `posts` (`id`, `user_id`, `content`, `privacy`, `created_at`) VALUES
(1, 1, 'Chào mừng đến với Koobecaf! 🎉 Hãy đăng bài viết, thích và bình luận để bắt đầu.', 'public', '2026-11-10 09:00:00'),
(2, 2, 'Sáng nay chạy 5km, gió mát lắm. Ai cũng đi chạy sáng thứ Bảy không?', 'public', '2026-11-11 07:30:00'),
(3, 3, 'Bộ ảnh mới chụp hôm qua ở Hội An 📷 Ánh sáng chiều rất đẹp.', 'public', '2026-11-12 16:20:00'),
(4, 4, 'Có ai biết quán phở nào ngon ở Quận 3 không? Đang cần gợi ý gấp.', 'public', '2026-11-13 11:05:00'),
(5, 1, 'Bài viết này chỉ người theo dõi mới xem được.', 'followers', '2026-11-14 08:00:00'),
(6, 2, 'Ghi chú riêng cho mình.', 'private', '2026-11-14 08:30:00'),
(7, 4, 'Cuối tuần này đi Đà Lạt, ai đi chung không? 🍃', 'public', '2026-11-15 20:10:00'),
(8, 3, 'Khoảnh khắc yên bình ở Đà Lạt 🌿', 'public', '2026-11-16 18:45:00');

-- Chia sẻ nội bộ: bài 8 chia sẻ bài 3
INSERT INTO `posts` (`id`, `user_id`, `content`, `privacy`, `shared_post_id`, `share_content`, `created_at`) VALUES
(9, 5, '', 'public', 3, 'Bộ ảnh này đẹp quá, mọi người xem đi!', '2026-11-16 19:10:00');

-- Lượt thích
INSERT INTO `likes` (`post_id`, `user_id`, `created_at`) VALUES
(1, 2, '2026-11-10 09:10:00'),
(1, 3, '2026-11-10 09:15:00'),
(1, 4, '2026-11-10 09:20:00'),
(2, 1, '2026-11-11 07:45:00'),
(2, 3, '2026-11-11 08:00:00'),
(3, 1, '2026-11-12 16:30:00'),
(3, 2, '2026-11-12 16:40:00'),
(3, 4, '2026-11-12 17:00:00'),
(4, 5, '2026-11-13 11:10:00'),
(7, 1, '2026-11-15 20:15:00'),
(7, 3, '2026-11-15 20:20:00');

-- Bình luận (bình luận trả lời có parent_id)
INSERT INTO `comments` (`id`, `post_id`, `user_id`, `parent_id`, `content`, `created_at`) VALUES
(1, 1, 2, NULL, 'Chào mừng luôn! Giao diện đẹp quá.', '2026-11-10 09:12:00'),
(2, 1, 1, 1, 'Cảm ơn bạn nhiều!', '2026-11-10 09:18:00'),
(3, 2, 1, NULL, '5km là ngon rồi, sức bền tốt vậy!', '2026-11-11 07:50:00'),
(4, 2, 4, 3, 'Chạy cùng nhé, sáng thứ Bảy nhé!', '2026-11-11 08:10:00'),
(5, 3, 4, NULL, 'Ảnh đẹp quá, màu sắc dịu dàng ghê.', '2026-11-12 17:05:00'),
(6, 4, 1, NULL, 'Quán Phở Hòa ở đường Trần Quốc Toản ngon lắm nhé.', '2026-11-13 11:20:00'),
(7, 7, 2, NULL, 'Đi chung với! 🎉', '2026-11-15 20:30:00'),
(8, 8, 1, NULL, 'Đà Lạt mùa này đẹp vậy luôn.', '2026-11-16 19:00:00');

-- Chia sẻ
INSERT INTO `shares` (`post_id`, `user_id`, `content`, `created_at`) VALUES
(3, 5, 'Bộ ảnh này đẹp quá, mọi người xem đi!', '2026-11-16 19:10:00'),
(7, 1, 'Ai lên Đà Lạt cùng đi không?', '2026-11-15 21:00:00');

-- Theo dõi
INSERT INTO `follows` (`follower_id`, `following_id`, `created_at`) VALUES
(1, 2, '2026-11-10 09:05:00'),
(1, 3, '2026-11-10 09:06:00'),
(2, 1, '2026-11-11 07:35:00'),
(2, 3, '2026-11-11 07:36:00'),
(3, 1, '2026-11-12 16:25:00'),
(3, 4, '2026-11-12 16:26:00'),
(4, 1, '2026-11-13 11:02:00'),
(4, 2, '2026-11-13 11:03:00'),
(5, 1, '2026-11-16 19:05:00'),
(5, 3, '2026-11-16 19:06:00');

-- Kết bạn (accepted)
INSERT INTO `friendships` (`user_id`, `friend_id`, `status`, `created_at`) VALUES
(1, 2, 'accepted', '2026-11-10 09:07:00'),
(1, 3, 'accepted', '2026-11-10 09:08:00'),
(1, 4, 'accepted', '2026-11-13 11:04:00'),
(2, 4, 'accepted', '2026-11-11 08:05:00');

-- Lời mời kết bạn đang chờ (user 1 nhận được từ user 5)
INSERT INTO `friendships` (`user_id`, `friend_id`, `status`, `created_at`) VALUES
(5, 1, 'pending', '2026-11-17 10:00:00'),
(4, 5, 'pending', '2026-11-17 10:30:00');

-- Cuộc trò chuyện
INSERT INTO `conversations` (`id`, `created_at`, `updated_at`) VALUES
(1, '2026-11-11 08:00:00', '2026-11-15 21:30:00'),
(2, '2026-11-12 17:00:00', '2026-11-16 10:00:00');

INSERT INTO `conversation_members` (`conversation_id`, `user_id`, `last_read_at`) VALUES
(1, 1, '2026-11-15 21:31:00'),
(1, 2, '2026-11-15 21:25:00'),
(2, 1, '2026-11-16 10:05:00'),
(2, 3, '2026-11-16 09:55:00');

-- Tin nhắn
INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `body`, `read_at`, `created_at`) VALUES
(1, 1, 1, 'Ê Bình, sáng thứ Bảy đi chạy nhé!', '2026-11-11 08:02:00', '2026-11-11 08:00:00'),
(2, 1, 2, 'Ok luôn! Mấy giờ tập hợp?', '2026-11-11 08:03:00', '2026-11-11 08:02:00'),
(3, 1, 1, '6h sáng ở công viên trung tâm nhé.', '2026-11-11 08:04:00', '2026-11-11 08:03:00'),
(4, 1, 2, 'Deal. Hẹn gặp bạn!', '2026-11-15 21:31:00', '2026-11-15 21:30:00'),
(5, 2, 3, 'An ơi, ảnh Hội An hôm qua đẹp quá!', '2026-11-12 17:05:00', '2026-11-12 17:00:00'),
(6, 2, 1, 'Cảm ơn Chi! Lần sau cho mình đi nhé 😄', '2026-11-16 10:05:00', '2026-11-16 10:00:00');

-- Thông báo
INSERT INTO `notifications` (`user_id`, `actor_id`, `type`, `entity_id`, `is_read`, `created_at`) VALUES
(1, 2, 'like', 1, 0, '2026-11-10 09:10:00'),
(1, 3, 'like', 1, 0, '2026-11-10 09:15:00'),
(1, 4, 'like', 1, 1, '2026-11-10 09:20:00'),
(1, 2, 'comment', 1, 0, '2026-11-10 09:12:00'),
(1, 3, 'comment', 2, 0, '2026-11-11 08:00:00'),
(1, 4, 'friend_request', 0, 0, '2026-11-17 10:00:00'),
(1, 2, 'friend_accepted', 0, 1, '2026-11-10 09:09:00'),
(2, 1, 'message', 1, 0, '2026-11-15 21:30:00'),
(3, 1, 'follow', 0, 0, '2026-11-12 16:25:00');
