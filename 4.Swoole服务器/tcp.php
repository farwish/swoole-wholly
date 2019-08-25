<?php
/**
 * tcp.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

// 默认是 SWOOLE_PROCESS 模式，模式是 SWOOLE_SOCK_TCP 类型
$server = new Swoole\Server("0.0.0.0", 7749, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set([
    'worker_num' => 2,
]);

$server->on('Connect', function ($server, $fd) {
    echo "Client {$fd} connect\n";
});

$server->on("Receive", function ($server, $fd, $reactorId, $data) {
    $server->send($fd, "aaaa\n");
});

$server->on('Close', function ($server, $fd) {
    echo "Client {$fd} closed\n";
});

$server->start();
