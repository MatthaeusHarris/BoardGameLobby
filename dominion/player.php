<?php
require_once("card.php");
require_once("deck.php");

define(PLAYERSTATE_CONNECTED, 1);
define(PLAYERSTATE_NAMED, 2);
define(PLAYERSTATE_COLOR, 3);
define(PLAYERSTATE_READY, 4);

class Player {
  public $name;
  public $sock;
  public $ipAddress;
  public $state = PLAYERSTATE_CONNECTED;
  public $color;
  
  public function write($message) {
    $message .= "\0";
    socket_write($this->sock, $message, strlen($message)) or die("Error:  tried to write to a dead socket.\n");
  }
  
  public function __destruct() {
    echo "Player removed.\n";
  }
  
  public function __toString() {
    return $this->name;
  }
}

class DominionPlayer extends Player {
  public $buys;
  public $actions;
  public $treasure;
  
}