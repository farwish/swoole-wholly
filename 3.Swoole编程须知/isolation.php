<?php
/**
 * isolation.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$i = 0;

$server = new Swoole\Server("0.0.0.0", 7749);

$server->set([
    'worker_num' => 2,
]);

// global 和 &引用 在子进程中都是隔离的
$server->on("Receive", function ($server, $fd, $reactorId, $data) use (&$i) {
    //global $i;

    $i++;

    // 验证有没有接收到客户端请求
    $server->send($fd, "{$i}\n");
});

$server->start();
