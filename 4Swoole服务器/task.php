<?php
/**
 * task.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$server = new Swoole\Server("0.0.0.0", 7749, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set([
    'worker_num' => 2,
    'task_worker_num' => 2,
]);

$server->on('WorkerStart', function ($server, $workerId) {
    if ($workerId == 0) {
        $data = [1, 2, 3, 4, 5];
        foreach ($data as $value) {
            echo "Send task data {$value}\n";
            $server->task($value);
        }
    }
});

$server->on("Receive", function ($server, $fd, $reactorId, $data) {
});

$server->on('Task', function ($server, $taskId, $srcWorkerId, $data) {
    sleep(1);
    echo "Task#{$taskId} execute task, data is {$data}\n";
    return "aaa{$data}";
});

$server->on('Finish', function ($server, $taskId, $data) {
    echo "Task{$taskId} execute finish, data is {$data}\n";
});

$server->start();
