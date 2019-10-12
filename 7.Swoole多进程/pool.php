<?php
/**
 * pool.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$pool = new Swoole\Process\Pool(5);

$pool->on('workerStart', function (Swoole\Process\Pool $pool, $workerId) {
    echo "\n $workerId \n";
    sleep(rand(2, 6));
});

$pool->start();
