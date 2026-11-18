'use strict';
const { execFileSync } = require('child_process');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');

function php(script) {
    return execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
}

const out = php(
    "require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env');" +
    "$id = \\App\\Models\\Post::create(2, 'REALTIME-TEST-' . time(), 'public');" +
    "\\App\\Services\\RealtimeService::postCreated($id);" +
    "echo $id;"
);
console.log('post id:', out);
