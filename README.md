# demo

```injectablephp
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

$db = new Db($conf);

$row = $db->fetchOne("select * from posts limit 1");
vd($db->getSql('posts', $row));
vd($db->getSql('posts', $row, [], 'pgsql'));
vd($row);


```
## batch insert
```injectablephp
$rows = [
    ['name' => 'dd', 'sex' => 'f', 'age' => 12], 
    ['name' => 'aa', 'sex' => 'm', 'age' => 12]
];
$db->table('users')->insert($rows);
```

## batch update
```injectablephp
$rows = [
    ['name' => 'dd', 'sex' => 'f', 'age' => 12], 
    ['name' => 'aa', 'sex' => 'm', 'age' => 12]
];
$db->table('users')->insert($rows);
```

## pluck
```injectablephp
$db->table('users')->order('id', 'desc')->limit(2)->pluck('name', 'id');
1# array (
  19 => 'aa',
  18 => 'dd',
)
```
