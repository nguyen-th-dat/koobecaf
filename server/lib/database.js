'use strict';

/**
 * Lớp truy cập MySQL cho WebSocket server (mysql2/promise + connection pool).
 *
 * Tất cả câu truy vấn đều dùng prepared statement — không nối chuỗi dữ liệu
 * người dùng vào SQL.
 */

const mysql = require('mysql2/promise');

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
     */
    async createMessage(conversationId, senderId, body) {
        const connection = await this.pool.getConnection();
        try {
            await connection.beginTransaction();

            const [result] = await connection.execute(
                'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)',
                [conversationId, senderId, body]
            );
            await connection.execute(
                'UPDATE conversations SET updated_at = NOW() WHERE id = ?',
                [conversationId]
            );

            await connection.commit();

            return result.insertId;
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

    /** Tạo thông báo nếu chưa tồn tại (unique key trong DB bảo vệ lần nữa). */
    async notify(userId, actorId, type, entityId) {
        const existing = await this.one(
            'SELECT id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = ? AND entity_id = ?',
            [userId, actorId, type, entityId]
        );
        if (existing) {
            return;
        }

        await this.query(
            'INSERT IGNORE INTO notifications (user_id, actor_id, type, entity_id, data) VALUES (?, ?, ?, ?, ?)',
            [userId, actorId, type, entityId, '']
        );
    }

    async user(userId) {
        return this.one('SELECT id, username, display_name, avatar_path FROM users WHERE id = ? LIMIT 1', [userId]);
    }

    async close() {
        await this.pool.end();
    }
}

module.exports = { Database };
