'use strict';
const { execFileSync } = require('child_process');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');

function php(script) {
    return execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
}

const out = php(
    "require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env');" +
    "$ok = \\App\\Core\\WsPublisher::publish('post.created', ['audience' => [1], 'post' => ['id' => 999, 'content' => 'test', 'author' => ['id' => 2, 'display_name' => 'B']]]);" +
    "echo $ok ? 'ok' : 'fail';"
);
console.log('publish:', out);
console.log('url:', php("require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env'); echo \\App\\Core\\WsPublisher::url();"));
