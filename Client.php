<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord;

if(file_exists(__DIR__.'/vendor/autoload.php')) {
    include_once(__DIR__.'/vendor/autoload.php');
}

class Client extends \League\Event\Emitter {
    public $channels;
    public $guilds;
    public $users;
    
    public $pings = array();
    public $readyTimestamp = NULL;
    
    private $loop;
    private $options = array();
    public $token;
    private $ws;
    private $user;
    
    function __construct(array $options = array(), \React\EventLoop\LoopInterface $loop = null) {
        if(!$loop) {
            $loop = \React\EventLoop\Factory::create();
        }
        
        $this->loop = $loop;
        $this->ws = new \CharlotteDunois\NekoCord\WebSocket\WSManager($this);
        
        $this->channels = \CharlotteDunois\Collect\Collection::create(array());
        $this->guilds = \CharlotteDunois\Collect\Collection::create(array());
        $this->users = \CharlotteDunois\Collect\Collection::create(array());
    }
    
    function getLoop() {
        return $this->loop;
    }
    
    function getClientUser() {
        return $this->user;
    }
    
    function getWSManager() {
        return $this->ws;
    }
    
    function getOption($name, $default = NULL) {
        if(isset($this->options[$name])) {
            return $this->options[$name];
        }
        
        return $default;
    }
    
    function getPing() {
        $cpings = count($this->pings);
        if($cpings === 0) {
            return \NAN;
        }
        
        return ceil(array_sum($this->pings) / $cpings);
    }
    
    function login(string $token) {
        $this->token = $token;
        
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $connect = $this->ws->connect('wss://gateway.discord.gg/?v=6&encoding=json');
            if(!$connect) {
                echo 'WARNING: WSManager::connect returned falsy value'.PHP_EOL;
            } else {
                $connect->then($resolve, $reject);
                $resolve = function () { };
            }
            
            $this->ws->once('ready', function () use ($resolve, &$listener) {
                $resolve();
                $this->emit('ready');
            });
        });
    }
    
    function setClientUser(array $user) {
        $this->user = new \CharlotteDunois\NekoCord\Structures\ClientUser($user);
    }
    
    function _pong($end) {
        $time = ceil(($end - $this->wsmanager->wsHeartbeat['dateline']) * 1000000);
        $this->pings[] = $time;
        
        if(count($this->pings) > 3) {
            $this->pings = array_slice($this->pings, 0, 3);
        }
    }
    
    function on($name, $listener) {
        return $this->addListener($name, $listener);
    }
    
    function once($name, $listener) {
        return $this->addOneTimeListener($name, $listener);
    }
    
    function emit($name, ...$args) {
        $event = new \CharlotteDunois\NekoCord\Event($name, ...$args);
        $event->setEmitter($this);
        return parent::emit($event);
    }
}
