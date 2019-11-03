<?php
/**
 * client.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$client = new Swoole\Client(SWOOLE_SOCK_TCP);

if (!$client->connect('0.0.0.0', 7749)) {
    echo "Connect failed, error code " . $client->errCode . PHP_EOL;
    die;
}

$client->send('hello');

echo $client->recv() . PHP_EOL;

$client->close();
