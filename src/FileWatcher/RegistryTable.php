<?php

/***
 * @todo 注册表 单例模式
 * Class RegistryTable
 */
class RegistryTable{

    public const MAX_SIZE = 1024;
    protected $table = NULL;
    public static $_instance = NULL;

    public function __construct()
    {
        $table = new  swoole_table(self::MAX_SIZE);
        $table->column('host', swoole_table::TYPE_STRING, 50);
        $table->column('path', swoole_table::TYPE_STRING, 1024);
        $table->column('inode', swoole_table::TYPE_INT);
        $table->column('size', swoole_table::TYPE_INT);
        $table->column('start_time', swoole_table::TYPE_INT);
        $table->column('last_time', swoole_table::TYPE_INT);
        $table->column('counter', swoole_table::TYPE_INT);
        $table->crete();
        $this->table = $table;
    }

    public static function getInstance()
    {
        if(!isset(self::$_instance)){
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    public function add($data)
    {
        if(empty($data['host'])){
            $data['host'] = '127.0.0.1';
        }
        if(empty($data['path'])||empty($data['inode'])){
            return false;
        }
        $key = md5($data['host'].$data['path']);
        $this->table[$key] = [
            'host'=>$data['host'],
            'path'=>$data['path'],
            'inode'=>$data['inode'],
            'size'=>0,
            'counter'=>0,
            'start_time'=>time(),
            'last_time'=>0
        ];

        return true;
    }

    public function clear()
    {
        if(empty($this->table)){
            return true;
        }
        foreach($this->table as $k=>$item) {
            $this->table->del($k);
        }
        return true;
    }

    public function get($host,$path)
    {
        $key = md5($host.$path);
        return $this->table->get($key);
    }

    public function pop($host,$path)
    {
        $key = md5($host.$path);
        $table = $this->table->get($key);
        $this->table->del($key);
        return $table;
    }

    public function updateCounter($host,$path,$step=1)
    {
        $key = md5($host.$path);
        $table = $this->table->get($key);
        if(!isset($table)){
            return false;
        }
        $table = $this->table[$key];

        $table['counter'] += $step;
        $table['last_time'] = time();
        return $this->table->set($key, $table);
    }

    public function updateSize($host,$path,$step)
    {
        $key = md5($host.$path);
        $table = $this->table->get($key);
        if(!isset($table)){
            return false;
        }
        $table = $this->table[$key];
        $table['size'] += $step;
        $table['last_time'] = time();
        return $this->table->set($key, $table);
    }

}
?>