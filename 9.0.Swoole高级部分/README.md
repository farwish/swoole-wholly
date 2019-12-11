## Swoole 高级部分

### 架构与实现

Swoole 架构

```
Master 进程（运行多线程 Reactor）
    Manager 进程（fork 并管理 Worker, Task 进程）
        Worker 进程（监听回调，执行业务逻辑，同步阻塞/异步非阻塞）
        Task 进程（接受 Worker 投递的任务，处理完返回，完全同步阻塞）
```

Reactor 线程

```
维护 TCP 连接，处理网络 IO，协议解析，发送给 Worker 数据 和 接收 Worker 数据，与 Worker 之间使用 UnixSocket 通信。

TCP 与 UDP 差异：
TCP 客户端，Worker 进程处理完请求，发送给 Reactor 线程，Reactor 发送给客户端。
UDP 客户端，Worker 进程处理完请求，直接发送给客户端。
```

Reactor, Worker, TaskWorker 之间关系

```
Reactor 可以理解成 Nginx，Worker 就是 php-fpm，TaskWorker 就是数据的异步消费进程。
```

Swoole 实现

```
编写语言                C / C++
Socket 实现             Socket 系统调用
IO 事件循环             Linux epoll / Mac kqueue
多进程                  fork 系统调用
多线程                  pthread 线程库
线程/进程间消息通知机制 eventfd
信号屏蔽和处理          signalfd
```

### 高可用与自启动

高可用

```
每分钟定时脚本执行进程监控，Master 进程存活则跳过，如果发现 Master 进程退出了，执行重启逻辑：
先 kill 所有残留子进程，然后重新启动 Server。

有哪些监控形式？
检测进程名是否存在；
检测端口是否在监听；
发送请求探测服务器是否有响应；
用supervisor工具监控进程；
docker 容器中运行设置参数 --restart=always
```

自启动

```
通过 systemd 管理服务，编写 service 配置。
需要运行 systemctl --system daemon-reload 重载守护进程生效。
之后可以使用 systemctl 命令管理服务。
```

@doc https://wiki.swoole.com/wiki/page/699.html

### MySQL 长连接与连接池

MySQL 短连接

```
请求时连接 MySQL，使用完就释放，不占用 MySQL 服务器连接资源。

程序存在每次请求时连接 MySQL 服务器的开销。php-fpm 模式的应用程序一般是使用短连接。
```

MySQL 长连接

```
请求完成不释放 MySQL 服务器连接资源。

减少了与 MySQL 服务器建立连接与断开的次数，节省了时间和 IO 消耗，提升了 PHP 程序的性能。
PHP 与 MySQL 建立长连接是使用 pconnect。
```

断线重连

```
长连接需要配合断线重连。
PHP 程序长时间运行，客户端与 MySQL 服务器之间的 TCP 连接是不稳定的。
客户端与 MySQL 服务器的连接会在一些情况下被切断，如：MySQL 自动切断连接，回收空闲连接资源，MySQL 重启 等。

当 query 返回连接失败（2006/2013）错误码时，执行一次 connect，这样后续的连接已建立，操作就能够执行成功。
```

MySQL 连接池

```
连接池可以有效降低 MySQL 服务器负载。
原理是共享连接资源，当程序执行完数据库操作，连接会释放给其他的请求使用。

连接池仅在大型应用中才有价值，普通的应用采用 MySQL 长连接方案即可满足需要：
假设一台机器开启 100 个 php-fpm 进程，并发 100，总共 10 台机器，那么需要 1000 个 MySQL 连接就可以满足需要，压力并不大。
如果服务器数量达到上百，这时候使用连接池就可以大大降低数据库连接数。
```
