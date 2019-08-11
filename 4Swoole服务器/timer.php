<?php
/**
 * timer.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

use Swoole\Timer;

// 一次性定时器
Timer::after(2000, function () {
    echo "this is a after timer\n";
});

$i = 0;

// 间隔时钟定时器
Timer::tick(2000, function ($timerId, $param1, $param2) use (&$i) {

    $i++;

    echo $i . PHP_EOL;

    echo $param1 . ' - ' . $param2 . PHP_EOL;

    if ($i == 5) {
        // 清除指定定时器
        Timer::clear($timerId);
    }

}, 'A', 'B');
