<?php
class dominion {
  public $name;
  public $numPlayers = array();
  public $thumbnail;
  public $description;
  public $gameSWF;
  
  function __construct() {
    $this->path = "dominion";
    $this->name = "Dominion";
    $this->numPlayers = array(2,3,4,5);
    $this->thumbnail = "dominion.png";
    $this->description = "Build your deck with victory, kingdom, and treasure cards.  At the end, the player with the most victory points in his deck is the winner.";
    $this->gameSWF = "dominion.swf";
  }
}

class dominion_engine extends NetEngine {
  
}