<?php

use Az26\Util\Db;

require_once '../vendor/autoload.php';
$conf = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'name' => 'test',
    'user' => 'root',
    'pass' => 'phpts',
    'char' => 'utf8',
];
$conf2 = [
    'driver' => 'pgsql',
    'host' => '192.168.0.106',
    'port' => 15432,
    'name' => 'test_log',
    'user' => 'postgres',
    'pass' => 'Z3Vhbnhpbg==',
    'char' => 'utf8',
];

$db = new Db($conf);
$db2 = new Db($conf2);

//$row = $db->fetchOne("select * from posts limit 1");
//vd($db->getSql('posts', $row));
//vd($db->getSql('posts', $row, [], 'pgsql'));
//vd($row);

//$r = $db->table('person')->where(['username' => 'test', 'sex' => 'man'])->get();
//vd($r);
//$r = $db->table('person')->where('username', 'test')->where('sex', 'man')->get();
//$r = $db->debug()->table('person')->where('username', 'test')->where('sex is null')->first();
//$r = $db->debug()->table('person')->where('sex', 'man')->order('username', 'desc')->order('user_id', 'asc')->first();

$rows = [
    [
        'name' => 'luke',
        'age' => 12,
        'id' => 1,
    ],
    [
        'name' => 'matt',
        'age' => 20,
        'id' => 2,
    ],
    [
        'name' => 'john',
        'age' => 23,
        'id' => 3,
    ],
];
//$r = $db->debug()->table('users')->update($update, 'id');
//$r = $db->debug()->table('users')->where('sex', 'm')->where('age', 12)->order('id', 'desc')->get();
//$rows = [['name' => 'dd', 'sex' => 'f', 'age' => 12], ['name' => 'aa', 'sex' => 'm', 'age' => 12]];
//$r = $db2->debug()->table('users')->insert($rows);
//$r = $db2->debug()->table('users')->where('sex', 'm')->where('age', 12)->order('id', 'desc')->first();
//$r = $db2->debug()->table('users')->order('id', 'desc')->limit(2)->pluck('name', 'id');
//$r = $db2->debug()->table('users')->update($rows, 'id');
//$r = $db2->debug()->table('users')->where('id=3')->update(['name'=>'test','sex'=>'f']);
//vd($r);
//$r = $db2->debug()->table('users')->where('id<3')->update(['name'=>'test2','sex'=>'f']);
//vd($r);
//$r = $db->debug()->table('users')->getFields();
//vd($r);
//$r = $db2->debug()->table('users')->getFields();
//vd($r);
$r = $db2->debug()->table('users')->where('id', 21)->update(['name'=>'bob', 'age'=>21]);
vd($r);
$r = $db2->debug()->table('users')->where('name','luke')->order('id', 'desc')->limit(5)->pluck('name','id');
vd($r);