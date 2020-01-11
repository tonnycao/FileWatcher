<?php
namespace Xcrms\FileWatcher;

/****
 * @todo 观察者
 * Class WatchMaster
 */
class WatchWorker
{
    const WATCH_EVENT = [
        IN_MODIFY ,
        IN_CREATE ,
        IN_DELETE
    ];
    const STATUS_MAP = [
        'ready',
        'start',
        'running',
        'complete',
        'closed'
    ];
    protected $host = '';
    protected $path = '';
    protected $event = '';
    protected $registry = NULL;

    public function __construct(String $host,Int $max_sleep)
    {
        $this->host = $host;
        $this->event = IN_MODIFY;
        $this->status = 'ready';
        $fd = inotify_init();
        $this->fd = $fd;
        $this->max_sleep = $max_sleep;

    }
    public function start(String $path, Int $event)
    {
        $this->status = 'start';
        $this->path = $path;
        $this->inode = fileinode($this->path);
        if(in_array($event,self::WATCH_EVENT)){
            $this->event = $event;
        }
        $this->registry = RegistryTable::getInstance();
        $watch_descriptor = inotify_add_watch($this->fd, $this->path, $this->event);
        $this->watch_desciptor = $watch_descriptor;
        $data = $this->_formatTable();
        $this->registry->add($data);
    }

    protected function _formatTable()
    {
        $data = [
            'host'=>$this->host,
            'path'=>$this->path,
            'inode'=>fileinode($this->path),
        ];
        return $data;
    }

    protected function _getTable()
    {
        $table = $this->registry->get($this->host,$this->path);
        return $table;
    }

    public function run(WatchHandle $handler)
    {
        stream_set_blocking($this->fd, 0);
        $file_handle = @fopen($this->path, "r");
        if($file_handle)
        {
            $offset = 0;
            fseek($file_handle,$offset);
            while (true){
                $content = fgets($file_handle);
                if($content && strlen($content)>0){
                    $handler->process($content);
                    $this->registry->updateSize($this->host,$this->path, strlen($content));
                }else{
                    $this->registry->updateCounter($this->host,$this->path,1);
                    $table = $this->_getTable();
                    if($table['counter']>$this->max_sleep){
                        $this->close();
                    }
                }
                sleep($this->max_sleep);
            }
        }
    }

    public function run1($handler)
    {
        stream_set_blocking($this->fd, 0);

        while (true) {
            $events = inotify_read($this->fd);
            if($events){

                $handler->process();
            }
        }
    }

    public function close()
    {
        $this->status = 'closed';
        inotify_rm_watch($this->fd, $this->watch_descriptor);
    }
}
?>