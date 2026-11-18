'use strict';

/**
 * Đọc file .env của Koobecaf (không phụ thuộc thêm package dotenv).
 *
 * Hỗ trợ: KEY=value, KEY="value", KEY='value', comment bắt đầu bằng #.
 * Biến môi trường thật (process.env) luôn được ưu tiên cao hơn.
 */

const fs = require('fs');
const path = require('path');

function parseEnv(content) {
    const result = {};

    for (const rawLine of content.split(/\r?\n/)) {
        const line = rawLine.trim();
        if (line === '' || line.startsWith('#')) {
            continue;
        }
        const separator = line.indexOf('=');
        if (separator === -1) {
            continue;
        }

        const key = line.slice(0, separator).trim();
        let value = line.slice(separator + 1).trim();
        if (
            (value.startsWith('"') && value.endsWith('"') && value.length >= 2) ||
            (value.startsWith("'") && value.endsWith("'") && value.length >= 2)
        ) {
            value = value.slice(1, -1);
        }
        result[key] = value;
    }

    return result;
}

function loadEnv(rootDir) {
    const envPath = path.join(rootDir, '.env');
    if (!fs.existsSync(envPath)) {
        return {};
    }

    return parseEnv(fs.readFileSync(envPath, 'utf8'));
}

module.exports = { loadEnv, parseEnv };
