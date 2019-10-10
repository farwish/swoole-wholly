<?php
/**
 * process.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

for ($i = 0; $i < 5; $i++) {
    $p = new Swoole\Process(function (Swoole\Process $p) {
        $p->name('process child');
        sleep(5);
    });

    $p->name('process master');
    $p->start();
}

sleep(10);
