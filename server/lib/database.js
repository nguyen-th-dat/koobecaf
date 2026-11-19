'use strict';

/**
 * Lớp truy cập MySQL cho WebSocket server (mysql2/promise + connection pool).
 *
 * Tất cả câu truy vấn đều dùng prepared statement — không nối chuỗi dữ liệu
 * người dùng vào SQL.
 */

const mysql = require('mysql2/promise');

/** Lỗi deadlock của InnoDB (ER_LOCK_DEADLOCK / 1213) — có thể thử lại an toàn. */
function isDeadlock(error) {
    return Boolean(error) && (error.code === 'ER_LOCK_DEADLOCK' || error.errno === 1213);
}

class Database {
    constructor(config) {
        this.pool = mysql.createPool({
            host: config.host,
            port: config.port,
            user: config.user,
            password: config.password,
            database: config.database,
            waitForConnections: true,
            connectionLimit: config.connectionLimit || 10,
            charset: 'utf8mb4_unicode_ci',
            dateStrings: true,
        });
    }

    async query(sql, params = []) {
        const [rows] = await this.pool.execute(sql, params);

        return rows;
    }

    async one(sql, params = []) {
        const rows = await this.query(sql, params);

        return rows.length > 0 ? rows[0] : null;
    }

    async insert(sql, params = []) {
        const [result] = await this.pool.execute(sql, params);

        return result.insertId;
    }

    /**
     * Ghi tin nhắn mới và cập nhật thời gian của cuộc trò chuyện trong 1 transaction.
     *
     * Trả về cả `created_at` lấy trực tiếp từ DB: mốc thời gian của MySQL mới
     * là nguồn chuẩn (khớp với PHP và với các bản ghi khác), trong khi `NOW()`
     * phía Node chạy theo múi giờ của máy chạy Node chứ không phải của DB.
     *
     * @return array{id:int,created_at:string}
     */
    async createMessage(conversationId, senderId, body) {
        // InnoDB có thể báo deadlock khi nhiều tin nhắn cùng gửi song song:
        // mỗi transaction khoá dòng conversations mà chúng cùng UPDATE. Đây là
        // xung đột tạm thời nên thử lại là an toàn (transaction đã rollback).
        const maxAttempts = 3;
        let lastError = null;

        for (let attempt = 1; attempt <= maxAttempts; attempt++) {
            try {
                return await this.insertMessage(conversationId, senderId, body);
            } catch (error) {
                lastError = error;
                if (!isDeadlock(error) || attempt === maxAttempts) {
                    throw error;
                }

                // Chờ ngẫu nhiên ngắn để hai transaction không lặp lại xung đột.
                await new Promise((resolve) => setTimeout(resolve, 20 * attempt + Math.random() * 30));
            }
        }

        throw lastError;
    }

    async insertMessage(conversationId, senderId, body) {
        const connection = await this.pool.getConnection();
        try {
            await connection.beginTransaction();

            // Phải UPDATE conversations TRƯỚC khi INSERT messages.
            //
            // INSERT vào messages sẽ kiểm tra khoá ngoại và giữ shared-lock lên
            // dòng conversations; nếu UPDATE sau đó thì hai transaction gửi tin
            // song song sẽ đợi exclusive-lock chéo nhau và MySQL báo deadlock.
            // UPDATE trước giữ exclusive-lock ngay từ đầu nên không xung đột.
            await connection.execute(
                'UPDATE conversations SET updated_at = NOW() WHERE id = ?',
                [conversationId]
            );

            const [result] = await connection.execute(
                'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)',
                [conversationId, senderId, body]
            );

            const [rows] = await connection.execute(
                'SELECT created_at FROM messages WHERE id = ?',
                [result.insertId]
            );

            await connection.commit();

            return {
                id: result.insertId,
                created_at: rows.length > 0 ? String(rows[0].created_at) : '',
            };
        } catch (error) {
            await connection.rollback();
            throw error;
        } finally {
            connection.release();
        }
    }

    async isMember(conversationId, userId) {
        const row = await this.one(
            'SELECT COUNT(*) AS total FROM conversation_members WHERE conversation_id = ? AND user_id = ?',
            [conversationId, userId]
        );

        return Number(row ? row.total : 0) > 0;
    }

    async memberIds(conversationId) {
        const rows = await this.query(
            'SELECT user_id FROM conversation_members WHERE conversation_id = ?',
            [conversationId]
        );

        return rows.map((row) => Number(row.user_id));
    }

    /** Tổng số tin nhắn chưa đọc của một user trong mọi cuộc trò chuyện. */
    async unreadCount(userId) {
        const row = await this.one(
            `SELECT COUNT(*) AS total FROM messages m
             JOIN conversation_members cm ON cm.conversation_id = m.conversation_id AND cm.user_id = ?
             WHERE m.sender_id <> ? AND m.read_at IS NULL`,
            [userId, userId]
        );

        return Number(row ? row.total : 0);
    }

    /** Tổng số thông báo chưa đọc của một user. */
    async unreadNotificationCount(userId) {
        const row = await this.one(
            'SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0',
            [userId]
        );

        return Number(row ? row.total : 0);
    }

    /** Danh sách cuộc trò chuyện mà user đang là thành viên. */
    async conversationIds(userId) {
        const rows = await this.query(
            'SELECT conversation_id FROM conversation_members WHERE user_id = ?',
            [userId]
        );

        return rows.map((row) => Number(row.conversation_id));
    }

    /**
     * Danh sách (conversation_id, peer_id) của các cuộc trò chuyện mà user
     * tham gia — dùng để phát trạng thái online/offline.
     *
     * @returns {Promise<Array<{conversation_id:number, peer_id:number}>>}
     */
    async conversationPeers(userId) {
        const rows = await this.query(
            `SELECT cm.conversation_id, other.user_id AS peer_id
             FROM conversation_members cm
             JOIN conversation_members other
               ON other.conversation_id = cm.conversation_id AND other.user_id <> cm.user_id
             WHERE cm.user_id = ?`,
            [userId]
        );

        return rows.map((row) => ({
            conversation_id: Number(row.conversation_id),
            peer_id: Number(row.peer_id),
        }));
    }

    /** Đánh dấu đã đọc và trả về id các tin nhắn vừa được đánh dấu. */
    async markRead(conversationId, readerId) {
        const rows = await this.query(
            `SELECT id FROM messages
             WHERE conversation_id = ? AND sender_id <> ? AND read_at IS NULL
             ORDER BY id ASC`,
            [conversationId, readerId]
        );

        if (rows.length === 0) {
            return [];
        }

        await this.query(
            `UPDATE messages SET read_at = NOW()
             WHERE conversation_id = ? AND sender_id <> ? AND read_at IS NULL`,
            [conversationId, readerId]
        );

        return rows.map((row) => Number(row.id));
    }

    /**
     * Tạo thông báo nếu chưa tồn tại (unique key trong DB bảo vệ lần nữa).
     *
     * @returns {Promise<number>} Id của thông báo (0 nếu không tạo được).
     */
    async notify(userId, actorId, type, entityId) {
        const existing = await this.one(
            'SELECT id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = ? AND entity_id = ?',
            [userId, actorId, type, entityId]
        );
        if (existing) {
            return Number(existing.id);
        }

        const result = await this.query(
            'INSERT IGNORE INTO notifications (user_id, actor_id, type, entity_id, data) VALUES (?, ?, ?, ?, ?)',
            [userId, actorId, type, entityId, '']
        );

        return Number(result.insertId) || 0;
    }

    async user(userId) {
        return this.one('SELECT id, username, display_name, avatar_path FROM users WHERE id = ? LIMIT 1', [userId]);
    }

    async close() {
        await this.pool.end();
    }
}

module.exports = { Database };
