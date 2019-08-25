<?php
/**
 * lifecycle.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

// 1.程序全局期
$a = 'A';

$server = new Swoole\Server("0.0.0.0", 7749, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set([
    'worker_num' => 2,
]);

$server->on('WorkerStart', function ($server, $workerId) {
    // 2.进程全局期
    echo "Worker {$workerId} started\n";
});

$server->on('Connect', function ($server, $fd) {
    // 3.会话期
    echo "Client {$fd} connect\n";
});

$server->on("Receive", function ($server, $fd, $reactorId, $data) {
    // 4.请求期
    $server->send($fd, "aaaa\n");
});

$server->on('Close', function ($server, $fd) {
    // 会话期结束
    echo "Client {$fd} closed\n";
});

$server->start();
