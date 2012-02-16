<?php
class y_game {
  public $name;
  public $numPlayers = array();
  public $thumbnail;
  public $description;
  public $gameSWF;
  
  function __construct() {
    $this->path = "y_game";
    $this->name = "Y";
    $this->numPlayers = array(2);
    $this->thumbnail = "y.png";
    $this->description = "Connect all three sides of the triangle while preventing your opponent from doing the same.";
    $this->gameSWF = "Y.swf";
  }
}

class y_game_engine extends NetEngine {
  function __construct($sock) {
    parent::__construct($sock);
  }
  
  function beforeLoopHandler() {
    global $debug;
    $debug = true;
    parent::beforeLoopHandler();
  }
  
  function afterLoopHandler() {
      parent::afterLoopHandler();
//    $this->quit = 1;
  }
}