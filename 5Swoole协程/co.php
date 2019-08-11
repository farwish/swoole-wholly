<?php
/**
 * co.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

Swoole\Coroutine::set([
    'max_coroutine' => 2000,
]);

for ($i = 0; $i < 1000; $i++) {
    go(function () {
        echo 'A';
        co::sleep(5);
        echo 'B';
    });
}
