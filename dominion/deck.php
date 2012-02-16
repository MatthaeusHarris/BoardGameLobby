<?php
require_once("card.php");

class Deck {
  private $cards;
  
  public function __construct($cards) {
    $this->cards = $cards;
  }
  
  public function shuffle() {
    shuffle($this->cards);
  }
  
  public function push($deck) {
    $this->deck = $this->deck + (array) $deck;
  }
}