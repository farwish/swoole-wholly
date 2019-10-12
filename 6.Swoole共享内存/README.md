# Swoole 共享内存

## 共享内存 Table（table.php）

Swoole\Table

```
Table 是一个基于共享内存和锁实现的超高性能，并发数据结构。用于解决多进程/多线程数据共享和同步加锁问题。特点如下：

性能高；
内置行锁，非全局锁；
多线程/多进程安全；
可用于多进程间共享数据；
实现了迭代器和 Coutable 接口，可遍历和使用 count 计算行数。
```

创建内存表(1)

```
Table->construct(int $size, float $conflict_proportion = 0.2)

$size 参数指定表格的最大行数，如果 $size 不是为 2 的 N 次方，底层会自动调整为接近的一个数字，
小于 1024 则默认成 1024，最小值是 1024。

Table 占用内存总数 = ( 结构体长度 + key长度64字节 + 行尺寸 ) * 1.2 (预留的 20% 作为 hash 冲突) * 列尺寸，
如果机器内存不足会创建失败。

(set 操作)能存储的最大行数与 $size 正相关，但不完全一致，实际小于 $size。
```

创建内存表(2)

```
Table->create(): bool

定义好表的结构后，执行 create 向操作系统申请内存，创建表。
    调用 create 前 **不能** 使用 set/get 等数据读写操作方法。
    调用 create 后 **不能** 使用 column 方法添加新字段
    系统内存不足会申请失败，返回 false，申请成功返回 true。
    create 必须在创建子进程之前 和 Server start 之前。
```

内存表增加一列

```
Table->column(string $name, int $type, int $size = 0)
$name   字段名。
$type   字段类型，支持 3 种类型，
        Table::TYPE_INT, Table::TYPE_FLOAT, Table::TYPE_STRING
$size   指定字符串字段的最大长度，单位是字节。
```

设置行数据

```
Table->set(string $key, array $value): bool
$key    数据的key, 相同的 $key 对应同一行数据，所以相同的 key 后设置的会覆盖上一次。
$value  必须是一个数组，必须与字段(column)定义的 $name 完全相同，允许只修改部分；
        若传入字符串长度超过字段设置的最大尺寸 ($size)，底层会自动截断 ( 并提示 WARNING )；
        自带行锁。
```

其余操作

```
incr    key 原子自增
decr    key 原子自减
exist   检查 key 是否存在
del     删除指定 key 的数据
count   返回 table 中存在的总行数
```

@doc https://wiki.swoole.com/wiki/page/260.html

## 原子计数器 Atomic

Swoole\Atomic

```
Atomic 是 Swoole 提供的原子计数操作类，可以方便整数的无锁原子增减。
使用共享内存实现，可跨进程之间操作计数，无需加锁。
在 Server start 前创建才能在 worker 进程中使用。
默认使用 32 位无符号类型，64 位有符号整型可使用 Swoole\Atomic\Long。
注意：勿在 onReceive 等回调中创建，否则底层内存会持续增长，造成泄漏。
```

创建一个原子计数对象

```
Atomic->construct(int $init_value = 0)
Atomic 只能操作 32 位无符号整数，最大支持 42 亿，不支持负数。
$init_value 可以指定初始化的数值，默认为 0。
```

操作方法

```
add     增加计数 ( 作用相当于 Table 的 incr )
sub     减少计数 ( 作用相当于 Table 的 decr )
get     获取当前计数的值
set     将当前值设置为指定的数字
cmpset  如果当前数值等于参数一，则将当前数值设置为参数二
wait    原子计数的值为 0 时，程序进入等待状态阻塞整个进程而非协程
wakeup  原子计数的值为 1 时，唤醒处于 wait 状态的其它进程
```

总结下：

虽然 Swoole\Table 也可用于原子计数，但是需要设置字段，主要还是用在复杂数据通信；所以 Swoole\Atomic 更适用于计数场合。

@doc https://wiki.swoole.com/wiki/page/466.html

## 同步锁 Lock

Swoole\Lock

```
Lock 是 Swoole 提供的锁，用来实现数据同步 (协程中无法使用锁)，支持 5 种锁的类型：
文件锁  SWOOLE_FILELOCK
读写锁  SWOOLE_RWLOCK
信号量  SWOOLE_SEM
互斥锁  SWOOLE_MUTEX
自旋锁  SWOOLE_SPINLOCK
```

创建一个锁

```
Lock->construct(int $type = SWOOLE_MUTEX, string $lockfile = '')
$type       锁的类型，默认为互斥锁
$lockfile   在当类型为 SWOOLE__FILELOCK 时必须传入，指定文件锁的路径
注意：
每一种类型的锁，支持的方法都不一样；
另外除文件锁外，其它类型的锁必须在父进程内创建，这样子进程之前才可以互相争抢锁。
```

操作方法

```
lock            加锁操作，其它进程持有锁时，当前会阻塞。
trylock         加锁操作，不会阻塞，它会立即返回。
unlock          释放锁。
lock_read       只读加锁，表示只锁定读。
                ( 只有 SWOOLE_RWLOCK 和 SWOOLE__FILELOCK 支持此方法 )
trylock_read    只读加锁，与 lock_read 相同，但是非阻塞的。
lockwait        加锁操作，与 lock 作用一致，但是可以支持超时时间。
```

总结下：

lock 主进程第一次使用一定是可以获得锁的，那么子进程在使用 lock 时就会阻塞，直到这把锁 unlock。

