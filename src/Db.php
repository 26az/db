<?php

namespace Az26\Util;

use Exception;
use \PDO;

class Db
{
    private $dbh;
    private $tbl;
    private $conf;
    private $debug;
    private $limit;
    private $conditions = [];
    private $orders = [];
    private $driver;
    private $fields;

    function __construct(array $conf)
    {
        $this->driver = $conf['driver'];
        $this->conf = $conf;
        if ('sqlsrv' == $conf['driver']) {
            $dsn = sprintf('sqlsrv:Server=%s;Database=%s;', $conf['host'], $conf['name']);
        } else {
            $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;', $conf['driver'], $conf['host'], $conf['port'], $conf['name']);
        }
        $this->dbh = new PDO($dsn, $conf['user'], $conf['pass']);
        if (isset($conf['schema'])) {
            $this->dbh->exec("SET search_path TO {$conf['schema']}");
        }
        if ('mysql' == $conf['driver']) {
            $this->dbh->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        $this->dbh->query("set names {$conf['char']}");
    }

    function fetchAll($sql, $mode = PDO::FETCH_ASSOC): array
    {
        if (empty($sql)) {
            die('error sql: ' . $sql);
        }
        $sth = $this->dbh->prepare($sql);
        $sth->execute();
        return $sth->fetchAll($mode);
    }

    function fetchOne($sql = null, $mode = PDO::FETCH_ASSOC): array
    {
        return $this->fetchAll($sql, $mode)[0] ?? [];
    }

    function limit($num)
    {
        $this->limit = $num;
        return $this;
    }

    function pluck($value, $key): array
    {
        $rows = $this->fetchAll($this->sql());
        foreach ($rows as $row) {
            $data[$row[$key]] = $row[$value];
        }
        return $data??[];
    }

    public function debug()
    {
        $this->debug = true;
        return $this;
    }

    function exec($sql)
    {
        $affectedNum = $this->dbh->exec($sql);
        $error = $this->dbh->errorInfo();
        if (empty($error[2])) {
            return $affectedNum;
        } else {
            $this->info(sprintf('error: code:%s, error:%s', $error[0], $error[2]));
        }
    }

    function table($tbl): Db
    {
        $this->tbl = $tbl;
        return $this;
    }

    function where()
    {
        $args = func_get_args();
        $delimiter = $this->delimiter();
        if (count($args) > 1) {
            $this->conditions[] = sprintf("%s='%s'", $delimiter . $args[0] . $delimiter, $args[1]);
        } else {
            if (is_array($args[0])) {
                foreach ($args[0] as $f => $v) {
                    $this->conditions[] = sprintf("%s='%s'", $delimiter . $f . $delimiter, $v);
                }
            } else {
                $this->conditions[] = $args[0];
            }
        }
        return $this;
    }

    private function getConditionStr(): string
    {
        if (!empty($this->conditions)) {
            $sql = ' WHERE ';
            $cnt = count($this->conditions);
            $i = 0;
            foreach ($this->conditions as $v) {
                $sql .= sprintf('%s %s', $v, ++$i < $cnt ? 'and ' : '');
            }
        }
        return $sql ?? '';
    }

    private function getOrderStr()
    {
        if (!empty($this->orders)) {
            $i = 0;
            $cnt = count($this->orders);
            $sql = 'order by ';
            foreach ($this->orders as $order) {
                $sql .= sprintf('%s%s', $order, ++$i < $cnt ? ',' : '');
            }
        }
        return $sql ?? '';
    }

    function select()
    {
        $args = func_get_args();
        if (func_num_args() > 1) {
            $this->fields = implode(',', $args);
        } else if (is_array($args[0])) {
            $this->fields = implode(',', $args[0]);
        }
        return $this;
    }

    function sql(): string
    {
        if (!empty($this->tbl)) {
            $fields = $this->fields ?: '*';
            $sql = sprintf("select %s from %s%s", $fields, $this->getDelimiterStr($this->tbl), $this->getConditionStr());
            $sql .= $this->getOrderStr();
            if ($this->limit) {
                $sql .= sprintf(' limit %s', $this->limit);
            }
            $this->debug && $this->info($sql);
            $this->clearCondition();
        } else {
            $this->info('please use table(xx) first');
        }

        return $sql ?? '';
    }

    function order($field, $sort): Db
    {
        $this->orders[] = sprintf('%s %s', $field, $sort);
        return $this;
    }

    function info($msg)
    {
        echo sprintf("%s : %s\n", date('Y-m-d H:i:s'), is_scalar($msg) ? $msg : var_export($msg, true));
    }

    function get(): array
    {
        return $this->fetchAll($this->sql());
    }

    function first(): array
    {
        return $this->fetchOne($this->sql());
    }

    function delimiter(): string
    {
        $delimiter = '`';
        if ($this->driver == 'pgsql') {
            $delimiter = '"';
        }
        return $delimiter;
    }

    function getDelimiterStr($str): string
    {
        return sprintf('%s%s%s', $this->delimiter(), $str, $this->delimiter());
    }

    function getSql($tbl, $data, $new = [], $type = 'mysql'): string
    {
        $data = array_merge($data, $new);
        foreach ($data as $k => $v) {
            if (is_null($v)) {
                unset($data[$k]);
                continue;
            }
            $data[$k] = trim($v);
        }
        $delimiter = '`';
        if ($type == 'pgsql') {
            $delimiter = '"';
        }

        $fields = $delimiter . implode($delimiter . ',' . $delimiter, array_keys($data)) . $delimiter;
        $values = "'" . implode("','", array_values($data)) . "'";
        return sprintf("insert into %s (%s) values (%s);", $tbl, $fields, $values);
    }

    function update(array $rows, $field = '')
    {
        $sql = sprintf('UPDATE %s SET ', $this->getDelimiterStr($this->tbl));
        $delimiter = $this->delimiter();
        if ($field) {
            $ids = array_column($rows, $field);
            $keys = array_diff(array_keys($rows[0]), [$field]);
            foreach ($rows as $up) {
                foreach ($keys as $key) {
                    $data[$key][$up[$field]] = $up[$key];
                }
            }
            $num = count($data);
            foreach ($data as $key => $vals) {
                $sql .= sprintf(' %s%s%s = CASE %s', $delimiter, $key, $delimiter, $field);
                foreach ($vals as $id => $val) {
                    $sql .= sprintf(" WHEN %d THEN %s", $id, $this->getColVal($key, $val));
                }
                $sql .= sprintf(' END %s', --$num === 0 ? '' : ',');
            }
            $sql .= sprintf('WHERE %s in (%s)', $field, implode(',', $ids));
        } else {
            foreach ($rows as $k => $v) {
                if (is_scalar($v)) {
                    $update[] = sprintf("%s = %s", $this->getDelimiterStr($k), $this->getColVal($k, $v));
                }
            }
            $sql .= !empty($update) ? implode(',', $update) : '';
            $sql .= $this->getConditionStr();
        }
        $this->clearCondition();
        if ($this->debug) {
            $this->info($sql);
        }
        return $this->exec($sql);
    }

    function insert(array $rows)
    {
        if (!empty($rows)) {
            if (empty($rows[0])) {
                $rows = [$rows];
            }
//            vd($rows);die;
            $columns = array_keys($rows[0]);
            $delimiter = $this->delimiter();
            $fields = sprintf('%s%s%s', $delimiter, implode($delimiter . ',' . $delimiter, $columns), $delimiter);
            $values = '';
            $cnt = count($rows);
            foreach ($rows as $i => $row) {
                $values .= sprintf("('%s')%s", implode("','", array_values($row)), $cnt - 1 == $i ? '' : ',');
            }
            $sql = sprintf('INSERT INTO %s (%s) VALUES %s', $this->getDelimiterStr($this->tbl), $fields, $values);
            $this->debug && $this->info($sql);
            return $this->exec($sql);
        }
    }

    function getPgTblColType($tbl): array
    {
        static $cols = [];
        if (empty($cols[$tbl])) {
            $sql = "SELECT column_name,data_type FROM information_schema.columns WHERE table_name = '{$tbl}'";
            $rows = $this->fetchAll($sql);
            foreach ($rows as $v) {
                $cols[$tbl][$v['column_name']] = $v['data_type'];
            }
        }
        return $cols[$tbl] ?? [];
    }

    private function clearCondition()
    {
        $this->conditions = [];
        $this->orders = [];
        $this->limit = null;
        $this->fields = null;
    }

    /**
     * 根据表字段类型为传入的值进行修改以便入库
     * @param $col string 字段
     * @param $val string|integer|null 值
     * @return string
     * @author lxw 2021.05.26
     */
    function getColVal(string $col, $val)
    {
        if ($this->driver === 'pgsql') {
            static $data;
            $tbl = $this->tbl;
            if (empty($data[$tbl][$col])) {
                // 定义数字类型
                $numeric = ['smallint', 'integer', 'bigint', 'interval', 'real', 'double precision', 'money', 'numeric'];
                $cols = $this->getPgTblColType($tbl);
                if (!in_array($cols[$col], $numeric, true)) {
                    $val = "'$val'";
                }
                $data[$tbl][$col] = $val;
            }
            return $data[$tbl][$col] ?? $val;
        } else {
            return sprintf("'%s'", $val);
        }
    }

    function uuid($prefix = ''): string
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 12);
        return $prefix . $uuid;
    }

    function getTables(): array
    {
        if ($this->driver == 'pgsql') {
            $sql = "SELECT * FROM information_schema.tables WHERE table_schema = '{$this->conf['schema']}'";
        } else {
            $sql = "SHOW TABLES";
        }
        return $this->fetchAll($sql, PDO::FETCH_NUM);
    }

    function getFields($tbl = ''): array
    {
        if ($this->driver == 'pgsql') {
            $sql = sprintf("SELECT column_name FROM information_schema.columns WHERE table_name = '%s'", $tbl ?: $this->tbl);
        } else {
            $sql = sprintf("DESCRIBE %s", $tbl ?: $this->tbl);
        }
        $this->debug && $this->info($sql);
        return $this->fetchAll($sql, PDO::FETCH_COLUMN);
    }

    function errorInfo(): array
    {
        return $this->dbh->errorInfo();
    }

    function __call($method, $param)
    {
        return $this->dbh->$method($param);
    }

}
