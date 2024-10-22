<?php
require_once("engine.php");
require_once("config.php");

class LobbyEngine extends NetEngine {
  private $gameList;
  private $config;
  private $activeGames;
  private $availablePlayers;
  
  function __construct($port) {
    parent::__construct($port);
  
    $this->config = new Config();
  
    $this->ScanGames();
//     debug($this->gameList);
    $this->userClass = "LobbyUser";
    
    /*Necessary for signal handling, php.net says.*/
    declare(ticks = 1);
    pcntl_signal(SIGCHLD, array($this, "sigChildHandler"));
  }
  
  public function sigChildHandler($signo) {
      debug("SIGCHLD received!");
      $pid = pcntl_wait($status);
      debug("Process $pid returned $status.");
      if (($gameKey = objectArraySearch($this->activeGames,"childPid",$pid)) !== false) {
        unset($this->activeGames[$gameKey]);
      } else {
        debug("A child with no associated game died.  WTF?");
      }
  }
  
  private function ScanGames() {
    $d = dir("../");
    while (false !== ($entry = $d->read())) {
      if ($entry != "." and $entry != ".." and $entry != "lobby") {
        $dirList[] = $entry;
      }
    }
    if (is_array($dirList)) {
      foreach($dirList as $dir) {
        if (file_exists("../$dir/$dir.php")) {
          require_once("../$dir/$dir.php");
          $this->gameList[] = new $dir();
        } else {
          debug("Error:  ../$dir/$dir.php does not exist.");
        }
      }
    }
  }

  protected function HandleData(&$xml, &$activeUser) {
    parent::HandleData($xml, $activeUser);
    if ($activeUser->name) {
      debug($xml);
      foreach($xml as $key => $value) {
        $valueString = (string) $value;
        switch($key) {
          case "gamelist":
//          	register_tick_function(debug,"tick");
            if (is_array($this->gameList)) {
              foreach($this->gameList as &$game) {
                $thumbnail = $this->config->httpPath . $game->path . "/" . $game->thumbnail;
                $xmlString .= "<item><path>{$game->path}</path><name>{$game->name}</name><players>" . implode(",",$game->numPlayers) . "</players><thumbnail>{$thumbnail}</thumbnail><description><![CDATA[{$game->description}]]></description></item>";
              }
              $gameListXML = "<xml><gamelist>$xmlString</gamelist></xml>";
              $activeUser->write($gameListXML);
            }
            break;
          case "newgame":
            debug("New game called for!");
            debug($value);
            $this->validateNewGameRequest($value,$activeUser);
            break;
          case "accept":
            debug("{$activeUser->name} accepted a game invite (id $valueString).");
            if ($activeUser->gameId == $valueString) {
              if (($gameKey = objectArraySearch($this->activeGames,"id",(int) $valueString)) !== false) {
                debug($gameKey);
                $activeUser->status = "waiting for game to start";
                $this->activeGames[$gameKey]->playerUpdateHandler();
              } else {
                debug("Whoa.  {$activeUser->name} accepted an invite to a game that should have existed, but it didn't.  I don't know how to handle this.");
              }             
            } else {
              debug("{$activeUser->name} tried to accept a game he wasn't invited to.  Shame!");
              //Just in case the client got confused, we'll send the gamequit to him anyway
              $activeUser->write("<xml><notifications><item>gamequit</item></notifications></xml>");
            }
            break;
          case "reject":
            if ($activeUser->gameId == $valueString) {
              $this->cancelGame($valueString);
            } else {
              debug("{$activeUser->name} tried to cancel a game he wasn't invited to.  Shame!");
            }
            break;
        }
      }
    }
  }
  
  protected function afterLoopHandler() {
    parent::afterLoopHandler();
    $this->updateAvailableList();
  }
  
  private function updateAvailableList() {
    debug("Updating available list.");
    $currentAvailablePlayers = objectArraySelect($this->users,"status","available");
    if ($currentAvailablePlayers != $this->availablePlayers) { //Array comparison here, see http://php.net/manual/en/language.operators.comparison.php
      debug("Available list has changed.  Sending notifies.");
      foreach($currentAvailablePlayers as $availablePlayer) {
        $this->setNotify("available",$availablePlayer->name);
      }
    }
    $this->availablePlayers = $currentAvailablePlayers;
  }
  
  
  /*This function is somewhat hairy.  It handles all of the validation to make sure that a user trying to start a new game is doing so properly.  I should probably add some rate-limiting stuff in here too, once I get the timeouts working.  I'm saving that for another day. */
  private function validateNewGameRequest($value, $activeUser) {
    if (count($value->players->item)) {
      foreach($value->players->item as $player) {
        $players[] = (string) $player;
      }
      //$players = $value->players->item;
      $gameName = (string) $value->name;
      $gameType = (string) $value->game;
      debug($players);
      if (($gameKey = objectArraySearch($this->gameList,"name",$gameType)) !== false) {  //Valid gameType?
        if (in_array(count($players),$this->gameList[$gameKey]->numPlayers)) { //Valid number of players?
          debug($players);
          if (in_array($activeUser->name,$players)) { //Requesting user put himself in the player list?
            $validated = true;
            foreach($players as $player) {
              if (($playerKey = objectArraySearch($this->users,"name",$player)) === false) { //All players valid players?
                $validated = false;
                $activeUser->write("<xml><errors><item>$player is not a valid player on the server.</item></errors></xml>");
                debug("{$activeUser->name} tried to include invalid player $player in the game.");
                break;
              } else {
                if ($this->users[$playerKey]->status != "available") { //All players available to be invited?
                  $validated = false;
                  $activeUser->write("<xml><errors><item>{$this->users[$playerKey]->name} is not ready.</item></errors></xml>");
                  debug("{$this->users[$playerKey]->name} was not ready to join a game.");
                } 
              }
            }
            if ($validated) { //Everything checked out, let's create the game and send the invites.
              $this->createNewGame($gameName, $this->gameList[$gameKey], $players, $activeUser);
            }
          } else {
            $activeUser->write("<xml><errors><item>Player must include self in player list.</item></errors></xml>");
            debug("{$activeUser->name} tried to start a game without himself!");
          }
        } else {
          $activeUser->write("<xml><errors><item>Invalid number of players.</item></errors></xml>");
          debug("Wrong number of players to create $gameType.");
        }
      } else {
        $activeUser->write("<xml><errors><item>Invalid game name.</item></errors></xml>");
        debug("Game $gameType not found in list:");
        debug($this->gameList);
      }
    } else {
      $activeUser->write("<xml><errors><item>Malformed newgame request.</item></errors></xml>");
      debug("Malformed newgame request.");
    }
  }
  
  private function createNewGame($name, $game, $players, $owner) {
    $this->activeGames[] = $thisGame = new GameInstance($name, $game->path);
    $thisGame->id = array_search($thisGame, $this->activeGames);
    debug("Successfully created game id {$thisGame->id}.");
    foreach($players as $player) {
      if (($playerKey = objectArraySearch($this->users,"name",$player)) !== false) {
        $thisGame->players[] = $this->users[$playerKey];
        if ($this->users[$playerKey] != $owner) {
          $this->users[$playerKey]->write("<xml><invite><id>{$thisGame->id}</id><by>{$owner->name}</by><name>$name</name><game>{$game->name}</game></invite></xml>");
        }
        $this->users[$playerKey]->status = "invited";
        $this->users[$playerKey]->gameId = $thisGame->id;
      
      }
    }
    $owner->status = "waiting for game to start";
  }
  
  function removeClient($dead_sock) {
    debug("GameLobby::removeClient() fired.");
    $userKey = objectArraySearch($this->users, "sock", $dead_sock);
    $quitUser =& $this->users[$userKey];
    
    if ($quitUser->gameId !== null) {
      $this->cancelGame($quitUser->gameId);
    }
    parent::removeClient($dead_sock);
  }
  
  function cancelGame($gameId) {
    $gameKey = objectArraySearch($this->activeGames,"id",$gameId);
    unset($this->activeGames[$gameKey]);
  }
}

class GameInstance {
  public $name;
  private $path;
  public $port;
  private $gameObject;
  public $id;
  public $players = array();
  public $childPid;
  private $gameDescription;

  function __construct($name, $path) {
    if (file_exists("../$path/$path.php")) {
      require_once("../$path/$path.php");
    } else {
      die("$path was not a valid game.");
    }
  
    $this->name = $name;
    $this->path = $path;
    $this->gameDescription = new $path();
  }
  
  function __destruct() {
    debug("Game instance destroyed.  Notifying players.");
    foreach($this->players as $player) {
      $player->status = "available";
      $player->gameId = null;
      $player->write("<xml><notifications><item>gamequit</item></notifications></xml>");
    }
  }
  
  public function playerUpdateHandler() {
    debug("A player was updated.  Let's see if that's all of them."); 
    if (objectArraySelect($this->players,"status","waiting for game to start") == $this->players) { /*FIXME What if multiple games are being started at once? */
      $this->start();
      $config = new config();
      $gameSWF = $config->httpPath . $this->path . "/" . $this->gameDescription->gameSWF;
      foreach($this->players as $player) {
        $player->write("<xml><gamestart><port>{$this->port}</port><id>{$this->id}</id><swf>{$gameSWF}</swf></gamestart></xml>");
      }
    }
  }
  
  public function start() {
    debug("Alright, we're starting a new game!");
    
    $path = $this->path;
    
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Error:  could not create socket.");
    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1) or die("Error:  could not set socket options");
    socket_bind($sock, 0, 0) or die("Error:  could not bind socket.");
    socket_listen($sock) or die("Error:  could not listen on socket.");
    socket_getsockname($sock,$socket_address,$this->port);
    $class = "{$path}_engine";
    $this->gameObject = new $class($sock);
    debug($this->gameObject);
    
    debug("Forking new daemon.");
    
    $pid = pcntl_fork();
    if ($pid < 0) die("Error spawning new process.");
    else if ($pid) {
      debug("The new daemon is listening on port {$this->port}.");
      $this->childPid = $pid;
    } else {
      global $debug;
      $debug = true;
      debug("I'm the child now.");
      $this->gameObject->run();
      unset($this->gameObject);
      /* If I call exit(), then all the destructors get called.  Since I'm the child, that would be bad.  So I force an abnormal program termination instead. */
      global $socket_keepalive;
      $socket_keepalive = true;
//      posix_kill(getmypid(),SIGKILL);
      die("Child exiting.");
    }
  }
  
  function getPort() {
    return($this->port);
  }
}

class LobbyUser extends User {
  public $status;
  public $gameId = null;
  
  function __construct($sock) {
    parent::__construct($sock);
    debug("LobbyUser created.");
    $this->status = 'connected';
  }
  
  function setName($name) {
    parent::setName($name);
    $this->status = "available";
  }
  
  function __destruct() {
    global $socket_keepalive;
    if (!$socket_keepalive) {
      parent::__destruct();
    }
  }
}