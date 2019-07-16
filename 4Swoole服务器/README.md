# Swoole 服务器

## TCP 服务器

什么是 TCP ?
```
TCP 即传输控制协议(Transmission Control Protocol)，是一个面向连接的协议，为用户进程提供可靠的全双工字节流。
TCP 套接字是一种流套接字。
TCP 关心"确认、超时和重传"之类的细节。
大多数因特网应用程序使用 TCP，TCP 既可以使用 IPv4，也可以使用 IPv6。
```

什么是 Swoole TCP Server ?
```
Swoole 底层实现的 TCP 协议的服务器，只能用于 cli 环境。
默认使用 SWOOLE_PROCESS 模式，因此除了 worker 进程外，会创建额外 master 和 manager 两个进程。
服务器启动后，通过 `kill 主进程id` 来结束所有工作进程。
```

Swoole TCP Server 特点
```
1. Swoole\Server 是异步服务器，所以是通过监听事件的方式来编写程序的。
2. 当有新的 TCP 连接进入时会执行 onConnect 事件回调，当某个连接向服务器发送数据时会回调 onReceive 事件回调，客户端断开会触发 onClose 事件回调。
3. $fd 是客户端连接的唯一标识。
4. 调用 $server->send() 向客户端连接发送数据。
5. 调用 $server->close() 可以强制关闭某个客户端连接。
```

Swoole TCP Server 参数
```
$host        监听的 ip 地址（支持IPv4 和 IPv6）
$port        监听的端口（监听小于1024端口需要root权限，0 表示随机）
$mode        运行模式（支持 SWOOLE_PROCESS 和 SWOOLE_BASE）
$socket_type Socket 的类型（$socket_type | SWOOLE_SSL 启用 SSL 加密，启用 SSL 后必须配置 ssl_key_file 和 ssl_cert_file）
```

更多信息@doc https://wiki.swoole.com/wiki/page/14.html

## UDP 服务器

什么是 UDP ?
```
UDP 即用户数据报协议(User Datagram Protocol)，是一个无连接协议。
UDP 套接字是一种数据报套接字。
UDP 数据报不能保证最终到达它们的目的地，不保证各个数据报的先后顺序跨网络后保持不不变，也不保证每个数据报只到达一次。
UDP 可以是全双工的。
与 TCP 一样，UDP 既可以使用 IPv4，也可以使用 IPv6。
```

什么是 Swoole UDP Server ?
```
Swoole 底层实现的 UDP 协议的服务器，只能用于 cli 环境。
使用方式和 Swoole TCP Server 基本一致，根据 UDP 的特点，Server 相关操作的 API 不一样。
```

Swoole UDP Server 特点
```
1. Swoole\Server 是异步服务器，所以是通过监听事件的方式来编写程序的。
2. 与 TCP Server 不同，UDP 没有监听的概念，启动 Server 后，客户端无需 Connect，直接可以向 Server 监听的端口发送数据包，Server 对应事件为 onPacket。
3. $clientInfo 是客户端的相关信息，是一个数组，有客户端 IP 和端口等内容。
4. 调用 $server->sendto() 向客户端发送数据。
```

Swoole UDP Server 参数
```
参数和 Swoole TCP Server 基本一致，只需要 $socket_type 指定为 SWOOLE_SOCK_UDP 类型。
```

备注
```
在容器里暴露 udp 端口需要指定类型，否则默认是 tcp 类型：
$ docker run -it -p 7748:7748/udp -v /home/ubuntu:/usr/share/nginx/html ImageID bash

终端连接 udp 服务器可以用 netcat 或 nc：
$ netcat -u ip port
```

更多信息@doc https://wiki.swoole.com/wiki/page/14.html
