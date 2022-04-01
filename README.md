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