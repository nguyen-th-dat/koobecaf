'use strict';
const { execFileSync } = require('child_process');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');

function php(script) {
    return execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
}

const out = php(
    "require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env');" +
    "$rows = \\App\\Core\\Database::fetchAll('SELECT id, actor_id, type, entity_id FROM notifications WHERE user_id = 1 ORDER BY id DESC LIMIT 5');" +
    "echo json_encode($rows);"
);
console.log(out);
