# Swoole 客户端

## 同步阻塞与异步非阻塞

Swoole\Client

```
Client 提供 TCP/UDP socket 客户端封装，使用时仅需 new Swoole\Client 。
```

Swoole\Async\Client

```
异步非阻塞客户端，回调式编程的风格。异步客户端只能使用在 cli 环境。

Swoole-4.4.8 版本开始，移除了对异步回调的支持，迁移至 ext-async 扩展中，独立为 Swoole\Async\Client 类。
异步回调模块已过时，建议使用协程客户端。

@link https://wiki.swoole.com/wiki/page/1254.html
```

Swoole\Coroutine\Client

```
提供 TCP、UDP、UnixSocket 传输协议的 Socket 客户端封装。

底层实现协程调度，业务层无感知。
与 Client 并不是继承关系，使用方法和 Client 同步模式方法完全一致。
除了正常的调用外，还支持并行请求。

@link https://wiki.swoole.com/wiki/page/p-coroutine_client.html
```

Client 常量

```
Client::MSG_WAITALL，用于 Client->recv() 第二个参数，阻塞等待直到收到指定长度的数据后返回。

Client::MSG_DONTWAIT，非阻塞接收数据，无论是否有数据都会立即返回。

Client::MSG_PEEK，recv读取数据不会修改指针，下次调用 recv 仍然会从上一次的位置起返回数据。

Client::MSG_OOB，读取带外数据。
```

Client 属性

```
Client->errCode     int 类型，当 connect/send/recv/close 失败时，会自动设置。可使用 socket_strerror 将错误码转为错误信息。

Client->sock        int 类型，此 socket 的文件描述符，在 connect 后才能取到。

Client->reuse       bool 类型，表示此连接是新创建的还是复用已存在的。
```

客户端初始化

```
Client->__construct(int $sock_type, int $is_sync=SWOOLE_SOCK_SYNC, string $key);

$sock_type  socket类型，如 TCP/UDP，$sock_type | SWOOLE_SSL 启用加密
$is_sync    同步阻塞还是异步非阻塞，默认同步阻塞
$key        用于长连接的 key，默认使用 IP:PORT 作为key，相同的key会复用
```

客户端参数设置

```
Client->set(array $settings);

设置客户端参数必须在 connect 前执行。

可用的配置选项参考： https://wiki.swoole.com/wiki/page/p-client.html
```

连接到远程服务器

```
Client->connect(string $host, int $port, float $timeout = 0.5, int $flag = 0): bool

$host       远程服务器地址，可传入域名。
$port       远程服务器端口。
$timeout    网络IO的超时，包括 connect/send/recv，单位是秒。
$flag       UDP类型时，表示是否启用 udp_connect
            TCP类型时，1 表示设置为非阻塞。
```

发送数据到远程服务器

```
Client->send(string $data): int | bool

必须在建立连接后，才可向 Server 发送数据。

$data 参数为字符串，支持二进制数据。成功发送返回已发送数据长度，失败返回 false，并设置 errCode 属性。
```

从服务端接收数据

```
Client->recv(int $size = 65535, int $flags = 0): string | bool

$size   接收数据的缓存区最大长度，此参数不要设置过大，否则会占用较大内存。
$flags  设置额外的参数，如 Client::MSG_WAITALL 是否等待所有数据到后返回，Client::MSG_PEEK 不会从缓存区中清空，下一次调用 recv 时依然会读取到。
```

关闭连接

```
Client->close(bool $force = false): bool

操作成功返回 true。当一个 Client 连接被 close 后不要再次发起 connect。
正确的做法是销毁当前 Client，重新创建一个 Client 发起新的连接。

$force 设置为 true 表示强制关闭连接，可用于关闭 SWOOLE_KEEP 长连接。
```

其他：https://wiki.swoole.com/wiki/page/27.html

## 长连接与并行

建立 TCP 长连接

```
$client = new Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);

启用 SWOOLE_KEEP 选项后，一个请求结束不会关闭 socket，下一次再进行 connect 时会自动复用上次创建的连接。
如果连接已经关闭，那么 connect 会创建新的连接。

TCP 长连接可以减少 connect、close 带来的额外 IO 消耗，降低服务端 connect、close 次数。
```

Client 并行处理

```
Swoole\Client 的并行处理中用了 select 来做 IO 事件循环。

int swoole_client_select(array &$read, array &$write, array &$error, float $timeout);

$read, $write, $error 分别是可读、可写、错误的文件描述符；是数组变量的引用，元素必须是 Swoole\Client 对象。此方法基于 select 系统调用，最大支持 1024 个 socket。
$timeout 是 select 系统调用的超时时间，单位秒。
```
