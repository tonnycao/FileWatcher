<?php
namespace Xcrms\FileWatcher;

/***
 * @todo 处理员接口
 * Class FileHandle
 */
interface WatchHandle
{
    public function start();
    public function receiveMsg();
    public function process();
    public function sendMsg();
    public function reStart();
    public function close();
}
?>