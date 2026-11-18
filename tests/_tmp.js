'use strict';
const { execFileSync } = require('child_process');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');

function php(script) {
    return execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
}

const out = php(
    "require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env');" +
    "$p = \\App\\Models\\Post::find(1);" +
    "echo json_encode(['id' => $p['id'], 'author' => $p['user_id'], 'privacy' => $p['privacy']]);"
);
console.log(out);
