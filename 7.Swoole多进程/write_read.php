<?php
/**
 * write_read.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$p = new Swoole\Process(function (Swoole\Process $p) {
    $p->name('process child');

    echo "\n child send start\n";
    $p->write('from child, hello');
    echo "\n child send end\n";

    echo "\n received from master : " . $p->read() . PHP_EOL;
});

$p->name('process master');
$p->start();

echo "\n received from child : " . $p->read() . PHP_EOL;

$p->write('from master, hello ~');

sleep(10);
