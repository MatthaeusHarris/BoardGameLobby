package com.uberduck.orpheus.gameclient
{
	import flash.events.*;
	import flash.net.XMLSocket;
	
	import mx.controls.TextArea;

	public class TicTacToeClient extends EventDispatcher
	{
		
		private var host:String;
		private var port:int;
		private var username:String;
		private var sock:XMLSocket;
		private var xml:XML;
		private var debugWindow:TextArea;
		private var board:Array;
		private var players:Array;
		
		public function TicTacToeClient(host:String, port:int, debugWindow:TextArea)
		{
			super();
			this.host = host;
			this.port = port;
			this.debugWindow = debugWindow;
			
			this.board = new Array();
			
			trace("Constructor!");
			sock = new XMLSocket();
			sock.addEventListener(Event.CONNECT, connectHandler);
			sock.addEventListener(Event.CLOSE, closeHandler);
			sock.addEventListener(DataEvent.DATA, dataHandler);
			sock.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			
			try {
				sock.connect(host,port);
			} catch(error:Error) {
				msg("Error opening socket to server.");
				sock.close();
			}									
		}
		
		private function ioErrorHandler(event:IOErrorEvent):void {
			msg("Socket error.  Please try again.");
		}
		
		private function connectHandler(event:Event):void {
			msg("Connected.");			
		}
		
		private function closeHandler(event:Event):void {
			msg("Socket closed.");
			dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "disconnected"));
		}
		
		private function dataHandler(event:DataEvent):void {
			xml = new XML(event.data);
			trace(xml.toString());
			if(xml.wait.length()) {
				dispatchEvent(new WaitEvent(WaitEvent.WAIT, true, false, xml.wait.toString()));
			}
			if(xml.errors.length()) {
				for each (var error:XML in xml.errors.children()) {
					msg(error.toString());
				}
			}
			if (xml.messages.length()) {
				for each (var message:XML in xml.messages.children()) {
					msg(message.toString());
				}
			}
			if (xml.gamestart.length()) {
				dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "gamestart"));
			}
			if (xml.abort.length()) {
				dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "abort"));
			}
			if (xml.board.length()) {
				trace("Board info received.");
				board = new Array();
				for each(var cell:XML in xml.board.children()) {
					var position:int = cell.position;
					trace("Have data for position " + position + ": " + cell.player.toString());
					board[position] = cell.player.toString();
				}
				trace(board);
				dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "boardinfo"));
			}
			if (xml.winner.length()) {
				if (xml.winner.toString() == "Nobody") {
					dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "tie"));
				} else if (xml.winner.toString() == username) {
					dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "win"));
				} else {
					dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "loss"));
				}
			}
			if (xml.players.length()) {
				players = new Array();
				for each(var player:XML in xml.players.children()) {
					players[player.color.toString()] = player.name.toString();
				}
				dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "playerinfo"));	
			}
		}
		
		public function getPlayer(color:String):String {
			return (players[color]);
		}
		
		public function getBoard():Array {
			return board;
		}
		
		public function msg(text:String):void {
			trace(text);
			debugWindow.text += text + "\n";
			debugWindow.verticalScrollPosition = debugWindow.maxVerticalScrollPosition;
		}
		
		public function setName(name:String):void {
			username = name;
			sock.send(<xml><name>{name}</name></xml>);	
		}
		
		public function setSide(side:String):void {
			sock.send(<xml><color>{side}</color></xml>);
		}
		
		public function ready():void {
			sock.send(<xml><ready /></xml>);	
		}
		
		public function move(position:int):void {
			sock.send(<xml><move>{position}</move></xml>);
		}
	}
}