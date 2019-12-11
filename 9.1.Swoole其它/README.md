## Swoole 其它

### 守护进程常用数据结构

SplQueue

```
PHP 的 SPL 标准库中提供了 SplQueue 内置的队列数据结构。
使用队列（Queue）实现生产者消费者模型，解决并发排队问题。
大并发服务器程序建议使用 SplQueue 作为队列数据结构，性能比 Array 模拟的队列高。
```

SplHeap

```
PHP 的 SPL 标准库中提供了 SplQueue 内置的队列数据结构。
使用队列（Queue）实现生产者消费者模型，解决并发排队问题。
大并发服务器程序建议使用 SplQueue 作为队列数据结构，性能比 Array 模拟的队列高。
```

SplFixedArray

```
PHP 的 SPL 标准库中提供了一个定长数组结构。
和普通 PHP 数组不同，定长数组读写性能更好，但只支持数字索引的访问方式，可以使用 setSize 方法动态改变定长数组尺寸。
```

### 日志等级控制

日志等级设置

```
通过使用 Server 的 set 方法设置 log_level 和 trace_flags 选项来控制日志等级。

$server->set([
    `log_level` => `SWOOLE_LOG_ERROR`,
    `trace_flags` => `SWOOLE_TRACE_SERVER | SWOOLE_TRACE_HTTP2`
])
```

日志级别 log_level

```
SWOOLE_LOG_DEBUG        调试日志，编译开启 --enable-swoole-debug
SWOOLE_LOG_TRACE        跟踪日志，编译开启 --enable-trace-log
SWOOLE_LOG_INFO         普通信息
SWOOLE_LOG_NOTICE       提示信息
SWOOLE_LOG_WARNING      警告信息
SWOOLE_LOG_ERROR        错误信息
```

跟踪标签 trace_flags

```
设置跟踪日志的标签，多个使用 | 操作符，可使用 SWOOLE_TRACE_ALL 跟踪所有项目。

SWOOLE_TRACE_SERVER, SWOOLE_TRACE_CLIENT, SWOOLE_TRACE_BUFFER, SWOOLE_TRACE_CONN, ……
```

@doc https://wiki.swoole.com/wiki/page/936.html

### Swoole辅助函数

php.ini 选项


### PHP选项与内核参数

php.ini 选项

```
swoole.enable_coroutine     使用 On、Off 开关内置协程，默认开启
swoole.display_errors       用于关闭、开启 Swoole 错误信息，默认开启
swoole.use_shortname        是否启用短别名，默认开启
swoole.socket_buffer_size   设置进程间通信 socket 缓存区尺寸，默认为8M
```

ulimit 设置

```
ulimit –n 调整为 100000 或更大，或通过编辑文件  /etc/security/limits.conf，
修改文件需要重启系统生效。
```

三种方式设置内核参数

```
1.修改 /etc/sysctl.conf 加入配置选项
    保存后调用 sysctl -p/-f 加载新配置，操作系统重启后自动生效。
2.使用 sysctl 命令临时修改
    如 sysctl -w net.ipv4.tcp_mem=379008，操作系统重启后失效。
3.修改 /proc/sys/ 目录中的文件
    如 echo 379008 > /proc/sys/net/ipv4/tcp_mem，操作系统重启后失效。
```

内核参数：net.unix.max_dgram_qlen

```
控制数据报套接字接收队列最大长度，Swoole 进程间通信使用 Unix Socket Dgram，
请求量大需要调大此参数，系统默认为 10。
```

网络内核设置：调整缓冲区大小

```
net.core.rmem_default=262144    默认的 socket 接收缓冲区大小
net.core.wmem_default=262144    默认的 socket 发送缓冲区大小
net.core.rmem_max=262144        最大的 socket 接收缓冲区大小
net.core.wmem_max=262144        最大的 socket 发送缓冲区大小

根据网络延迟情况适当调大这些值
```

网络内核设置：使用 TCP keepalive

```
net.ipv4.tcp_keepalive_time
net.ipv4.tcp_keepalive_intvl
net.ipv4.tcp_retries2
net.ipv4.tcp_syn_retries
```

内核参数：net.ipv4.tcp_tw_reuse

```
Server 重启时，允许将 TIME-WAIT 的 socket 重新用于新的 TCP 连接。
默认 0 表示关闭。
```

消息队列设置

```
kernel.ksgmnb = 4203520     消息队列的最大字节数
kernel.msgmni = 64          最多允许创建多少个消息队列
kernel.msgmax = 8192        消息队列单条数据最大的长度

如果 Swoole Server 使用了消息队列作为通信方式，建议适当调大这些值
```

