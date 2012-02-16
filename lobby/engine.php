<?php
define(WELCOME_MESSAGE, "<xml><notifications><item>connected</item></notifications><wait><item>login</item></wait></xml>");

class NetEngine {
  private $state;
  protected $port;
  protected $clients; //Array of sockets
  protected $users; //Array of user objects
  protected $sock;
  protected $notifications;
  protected $quit;  //Initially set to false.  Exits the main loop in Run() when set to true.
  protected $userClass; //Class to use when creating new user objects.  Must extend User class.
  protected $timeouts; //Array of timeouts.
  protected $maxIdleTime;
  
  function __construct($port) {
    debug("NetEngine constructor called!");
    debug($port);
    if (is_numeric($port)) {
      $this->port = $port;
      $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Error:  could not create socket.");
      socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1) or die("Error:  could not set socket options.");
      socket_bind($this->sock, 0, $port) or die("Error:  could not bind port $port.");
      socket_listen($this->sock) or die("Error:  could not listen on socket.");
    } elseif (is_resource($port)) {
      $this->sock = $port;
      socket_getsockname($this->sock, $socket_address, $socket_port);
      $this->port = $socket_port;
      debug("Socket was already set up and passed to constructor.");
      debug("Listening on {$socket_address}:{$socket_port}");
    } else {
      debug("NetEngine constructor was passed something it didn't understand.");
      debug($port);
      die();
    }
    
    $this->users = array();
    
    $this->clients = array($this->sock);
    debug($this->clients);
    
    $this->quit = false;
    $this->timeouts = array();
    $this->userClass = "User";
    $this->maxIdleTime = 300;
    debug("constructor exiting.");
  }
  
  function __destruct() {
    debug("NetEngine object deleting.");
  }
  
  final function Run() {
    $this->beforeLoopHandler();
    while (!$this->quit) {
      debug("Starting main loop.");
      debug($this);
      $read = $this->clients;
//       debug($this->users);
      
//       debug($this->timeouts);
/*FIXME:  The timeout system currently works ONLY to make sure that socket_select doesn't block waiting for input indefinitely.  A more comprehensive timeout system would need to be written to make it reliable for calling a function after a given time.  In the meantime, use beforeLoopHandler and afterLoopHandler.*/
      if (count($this->timeouts)) {
        ksort($this->timeouts);
        $localTimeout = array_keys($this->timeouts);
//         debug($localTimeout);
        $localTimeout = $localTimeout[0];
        $waitTimeout = $localTimeout - microtime(true);
        if ($waitTimeout < 0) $waitTimeout = 0;
        $callback = array_pop($this->timeouts);
      }
      if ($waitTimeout > 0) {
        $tv_sec = floor($waitTimeout);
        $tv_usec = $waitTimeout - floor($waitTimeout);
      } else {
        $tv_sec = null;
        $tv_usec = null;
      }
      $doLoop = false;
      debug("Entering select");
      debug($read);
      if (($selectRet = socket_select($read, $write = NULL, $except = NULL, $tv_sec, $tv_usec)) < 1) {
//       if (($selectRet = socket_select($read, $write = NULL, $except = NULL, null)) < 1) {
        debug("socket_select returned:");
        debug($selectRet);
        if (is_numeric($selectRet) and $selectRet === 0) { //Triple =, don't want to catch a NULL here.
          debug("$localTimeout passed without anything interesting happening.  Calling $callback");
          $this->$callback();
          $doLoop = true;
        } else {
          //socket_select() returned something non-numeric, which means it was probably interrupted by a SIGCHLD.  Clear the $read array so we don't get confused.  We'll just loop back around and re-enter.
          debug("socket_select returned a non-numeric.  Resetting read array and re-entering loop.");
          $read = array();
        }
      } else {
        $doLoop = true;
      }
      if ($doLoop) {
        $this->beforeLoopHandler();
        //Check for new connections
        if (in_array($this->sock, $read)) {
          debug("Accepting new client.");
          $this->AcceptNewClient();
          $key = array_search($sock, $read);
          unset($read[$key]);
  //        die("debug die");
        }
        
        //Loop through the remaining connections
        foreach($read as &$read_sock) {
          $data = socket_read($read_sock, 10240, PHP_BINARY_READ);
          if ($data === false or $data == "") {
            debug("Client disconnected.");
            $this->RemoveClient($read_sock);
          } else {
            if (($userKey = objectArraySearch($this->users,"sock",$read_sock)) !== false) {
              $activeUser =& $this->users[$userKey];
              $activeUser->updateTimeout();
              $data = trim($data);
              
              if (!empty($data)) {
                if ($data == "die") {
                  $this->quit = true;
                } else {
                  try {
                    $xml = new SimpleXMLElement($data);
                    debug($activeUser);
                    $this->HandleData($xml, $activeUser);
                  } catch(Exception $e) {
                    echo "Exception caught: ", $e->getMessage(), "\n";
                  } //try/catch
                } //Data not kill command
              } //Data not empty
            } //Found proper user in userlist
            else {
              echo "Error, could not find " . print_r($read_sock,true) . " in " . print_r($this->users) . "\n";
            }
          } //Data handling 
        } //Read sockets 
        unset($activeUser);
      }
      $this->afterLoopHandler();
      $this->BroadcastNotifies();
    } //Main loop
  }
  
  protected function beforeLoopHandler() {
     $this->setTimeOut(150,'heartbeat');  
  }
  
  protected function afterLoopHandler() {
    $this->checkUserTimeouts();
  }
  
  protected function checkUserTimeouts() {
    debug("Checking user timeouts.");
    foreach($this->users as $user) {
      $idle = microtime(true) - $user->timestamp;
      if ($idle > $this->maxIdleTime / 2) {
        $user->write("<xml><notifications><item>keepalive</item></notifications></xml>");
      } elseif ($idle > $this->maxIdleTime) {
        debug("{$user->name} has been idle for too long.  Giving him teh boot.");
        $this->removeClient($user->sock);
      }
    }
  }
  
  private function heartbeat() {
    debug("Heartbeat!");
  }
  
  protected function setTimeOut($time,$callback) {
    if (method_exists($this,$callback)) {
      $this->timeouts[(string) (microtime(true) + $time)] = $callback;
    } else {
      debug("Bogus callback:  $callback");
    }
  }
  
  protected function HandleData(&$xml, &$activeUser) {
    foreach($xml as $key => $value) {
      $valueString = (string) $value;
      switch($key) {
        case 'name':
          debug("Name detected.");
          debug($activeUser);
          if ($valueString != "") {
            if (objectArraySearch($this->users,"name",$valueString) === false) {
              $activeUser->setName($valueString);
              $this->SetNotify("join",$valueString);
              $activeUser->write("<xml><notifications><item>nameok</item></notifications></xml>");
            } else {
              $activeUser->write("<xml><errors><item>That name is already taken.</item></errors></xml>");
            }
          } else {
            $activeUser->write("<xml><errors><item>That name is not valid.</item></errors></xml>");
          }
          break;
        case 'say':
          if ($activeUser->name) {
            $this->SetNotify("said","<name>{$activeUser->name}</name><utterance>$valueString</utterance>");
          }
          break;
        case 'password':
          break;
      }
    }
  }
  
  function AcceptNewClient() {
    $this->clients[] = $newsock = socket_accept($this->sock);
    $this->clients = array_values($this->clients);
    
    if (count($this->users)) {
      foreach($this->users as &$user) {
        if ($user->name) {
          $xml .= "<item>{$user->name}</item>";
        }
      }
      $userListXML = "<xml><notifications><join>$xml</join></notifications></xml>";
    }
    $class = $this->userClass;
    $this->users[] = $newUser = new $class($newsock);
    $this->users = array_values($this->users);
    
    debug($newUser);
    
    $newUser->write(WELCOME_MESSAGE);
    $newUser->write($userListXML);
    
  }
  
  function RemoveClient($dead_sock) {
    $clientKey = array_search($dead_sock, $this->clients);
    $userKey = objectArraySearch($this->users,"sock",$this->clients[$clientKey]);
    $quitUser =& $this->users[$userKey];
    
    unset($this->users[$userKey]);
    unset($this->clients[$clientKey]);
    
    if ($quitUser->name) {
      $this->SetNotify("part",$quitUser->name);
    }
    unset($quitUser);
  }
  
  function SetNotify($type, $data) {
    $this->notifications[$type][] = $data;
  }
  
  function BroadcastNotifies() {
    if(is_array($this->notifications)) {
      foreach($this->notifications as $type=>$notifications) {
        foreach($notifications as $item) {
          $xml .= "<item>$item</item>\n";
          
        }
        $xml2 .= "<$type>\n$xml</$type>\n";
        unset($xml);
      }
      $this->Broadcast("<xml><notifications>$xml2</notifications></xml>\n");
      unset($this->notifications);
    }
  }
  
  function Broadcast($message) {
    foreach($this->users as &$user) {
      $user->write($message);
    }
  }
}

class User {
  public $ipAddress;
  public $sock;
  public $name;
  public $timestamp;
  
  function __construct($sock) {
    $this->sock = $sock;
    socket_getpeername($sock, $ip);
    $this->ipAddress = $ip;
    $this->timestamp = microtime(true);
  }
  
  function __destruct() {
    debug("Closing user socket.");
    socket_shutdown($this->sock);
    socket_close($this->sock);
  }
  
  function setName($name) {
    $this->name = $name;
  }
  
  function write($message) {
    if ($message) {
      debug("Writing to {$this->name}: $message");
      $message .= "\0";
      socket_write($this->sock, $message, strlen($message)) or print("Error:  tried to write to a dead socket.\n");
    }
  }
  
  function updateTimeout() {
    $this->timestamp = microtime(true);
  }
}

function objectArraySearch($object_array, $attribute, $value) {
  if (is_array($object_array)) {
    foreach($object_array as $key => &$object) {
      if ($object->{$attribute} === $value) {
        return $key;
      }
    }
  }
  return false;
}

function objectArraySelect($object_array, $attribute, $value) {
  $ret = array();
  if (is_array($object_array)) {
    foreach($object_array as $key => &$object) {
      if ($object->{$attribute} === $value) {
        $ret[] =& $object;
      }
    }
  }
  return($ret);
}

function daemonize() {
  $pid = pcntl_fork(); // fork
  if ($pid < 0)
      exit;
  else if ($pid) // parent
      exit;
  else { // child
      $sid = posix_setsid();
      if ($sid < 0)
          exit;
  }
  echo "I'm a daemon!  Raaar!\n";
}

function debug($var) {
  global $debug;
  if ($debug) {
    $name = debug_backtrace();
    $name = microtime(true).":".getmypid().":".$name[0]["file"].":".$name[1]["function"]."(line ".$name[0]["line"].")";
    echo $name.": ".print_r($var,true)."\n";
  }
}