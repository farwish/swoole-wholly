<?php
/**
 * rand.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$workerNum = 3;

// 主进程使用了会生成随机数种子的函数
srand();
//rand(1, 3);
//$arr = [1,2,3,4];
//shuffle($arr);
//array_rand($arr);

for ($i = 0; $i < $workerNum; $i++) {
    $process = new Swoole\Process('abc');
    $process->start();
}

function abc(Swoole\Process $process) {
    // 子进程必须重新生成随机数种子
    srand();

    echo PHP_EOL . rand(0, 10) . PHP_EOL;
    $process->exit();
}

sleep(1);
