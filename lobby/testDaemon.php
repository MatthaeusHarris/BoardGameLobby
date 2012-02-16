<?php
require_once("lobbyEngine.php");
$debug = true;
debug("####################################################################################");
debug("#####                                                                          #####");
debug("#####                          testDaemon.php started                          #####");
debug("#####                                                                          #####");
debug("####################################################################################");
$engine = new LobbyEngine(9050);
//daemonize();
$engine->Run();
