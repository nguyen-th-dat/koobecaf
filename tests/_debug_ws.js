'use strict';
const { execFileSync } = require('child_process');
const WebSocket = require('ws');

const ROOT_DIR = 'd:/www/koobecaf';

function php(script) {
    return execFileSync('php', ['-r', script], { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
}

const token = php("require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env'); echo \\App\\Core\\WebSocketToken::issue(1);");

const s = new WebSocket('ws://127.0.0.1:8080/ws');
s.on('open', () => {
    s.send(JSON.stringify({ type: 'auth', token }));
    setTimeout(() => {
        const ok = php("require 'app/bootstrap.php'; \\App\\Core\\Env::load('.env'); $r=\\App\\Core\\WsPublisher::publish('post.liked', ['audience'=>[1],'post_id'=>12345,'actor'=>['id'=>2],'stats'=>['like_count'=>7]]); echo $r?'ok':'fail';");
        console.log('publish:', ok);
    }, 400);
});
s.on('message', (raw) => console.log('FRAME:', raw.toString()));
setTimeout(() => process.exit(0), 2500);
