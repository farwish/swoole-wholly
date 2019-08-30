<?php
/**
 * runtime_co.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

Swoole\Runtime::enableCoroutine(true);

Swoole\Coroutine::set([
    'max_coroutine' => 2000,
]);

for ($i = 0; $i < 1000; $i++) {
    go(function () {
        echo 'A';
        sleep(5);
        echo 'B';
    });
}
