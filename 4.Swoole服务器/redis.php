<?php
/**
 * redis.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

use Swoole\Redis\Server;

$serv = new Server('0.0.0.0', 7749);

$serv->strings = [];

// 设置命令字处理器
$serv->setHandler('swset', function ($fd, $data) use ($serv) {
    $key = $data[0];
    $val = $data[1];
    $serv->strings[$key] = $val;
    // 格式化返回数据
    $serv->send($fd, Server::format(Server::STRING, 'OK'));
});

$serv->setHandler('swget', function ($fd, $data) use ($serv) {
    $key = $data[0];
    $val = $serv->strings[$key];
    $serv->send($fd, Server::format(Server::STRING, $val));
});

$serv->start();
