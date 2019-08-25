<?php
/**
 * udp.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

// 对比Swoole TCP Server, UDP Server 第四个参数为 SWOOLE_SOCK_UDP
$server = new Swoole\Server("0.0.0.0", 7748, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

$server->set([
    'worker_num' => 2,
]);

// 没有 connect 和 close 事件，接收消息监听 packet 事件
$server->on('Packet', function ($server, $data, $clientInfo) {
    print_r($clientInfo);
    $server->sendto($clientInfo['address'], $clientInfo['port'], $data);
});

$server->start();
