<?php
/**
 * table.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

$table = new Swoole\Table(1024);

$table->column('id', Swoole\Table::TYPE_INT, 2);
$table->column('name', Swoole\Table::TYPE_STRING, 2);
$table->column('age', Swoole\Table::TYPE_INT, 2);

$bool = $table->create();

if (!$bool) {
    echo "Create swoole table failed\n";
} else {
    $table->set('user1', [
        'id' => 1,
        'name' => 'Jack',
        'age' => '18',
    ]);

    $table->set('user2', [
        'id' => 3,
        'name' => 'Tom',
        'age' => '19',
    ]);

    $user1 = $table->get('user1');
    $user2 = $table->get('user2');

    print_r($user1);
    print_r($user2);
}

/*
[2019-09-07 07:33:41 @12319.0] WARNING swTableRow_set_value: [key=user1,field=nameH]string value is too long.
[2019-09-07 07:33:41 @12319.0] WARNING swTableRow_set_value: [key=user2,field=nameH]string value is too long.
Array
(
    [id] => 1
    [name] => Ja
    [age] => 18
)
Array
(
    [id] => 3
    [name] => To
    [age] => 19
)
*/

