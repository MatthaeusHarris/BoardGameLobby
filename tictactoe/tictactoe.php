<?php
require_once("../lobby/engine.php");

class tictactoe {
  public $name;
  public $numPlayers = array();
  public $thumbnail;
  public $description;
  public $gameSWF;
  
  function __construct() {
    $this->path = "tictactoe";
    $this->name = "Tic Tac Toe";
    $this->numPlayers = array(2);
    $this->thumbnail = "tictactoe.png";
    $this->description = "The old simple classic.";
    $this->gameSWF = "tictactoe.swf";
  }
}

class tictactoe_engine extends NetEngine {
  
}