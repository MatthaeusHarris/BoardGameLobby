package com.uberduck.game.lobby
{
	import com.uberduck.game.NotifyEvent;
	
	import flash.events.*;
	import flash.net.XMLSocket;
	
	import mx.collections.ArrayCollection;
	import mx.controls.List;

	public class ChatInterface extends EventDispatcher
	{
		
		public var host:String;
		private var port:int;
		
		public var username:String;
		
		protected var sock:XMLSocket;
		protected var xml:XML;
		
		private var userList:ArrayCollection;
		
		private var users:Array;
		private var userListDisplay:List;
		
		private var chatText:String;
		
		public function ChatInterface(host:String, port:int, username:String, userList:ArrayCollection)
		{
			super();
			this.host = host;
			this.port = port;
			this.username = username;
			this.userList = userList;
			this.users = new Array();
			
			this.chatText = "";
			
			trace("Chat object initiated.");
			trace("Trying to connect.");
			
			sock = new XMLSocket();
			sock.addEventListener(Event.CONNECT, connectHandler);
			sock.addEventListener(Event.CLOSE, closeHandler);
			sock.addEventListener(DataEvent.DATA, dataHandler);
			

		}
		
		public function connect():void {
			try {
				sock.connect(host, port);
			} catch(error:Error) {
				trace("Error connecting.");
				sock.close();
			}
		}
		
		public function close():void {
			sock.close();
		}
		
		public function say(text:String):void {
			sock.send(<xml><say>{text}</say></xml>);
		}
		
		public function msg(text:String):void {
				chatText += text + "<br \>";
				dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "chat"));
		}
		
		public function getChatText():String {
			var tmpText:String = chatText;
			chatText = "";
			return(tmpText);
		}
				
		private function connectHandler(event:Event):void {
			if (sock.connected) {
				trace("Connected.");
			} else {
				trace("Unable to connect.");
			}
		}		
		
		private function closeHandler(event:Event):void {
			trace("Connection closed.");
			dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "disconnected"));
		}
		
		protected function dataHandler(event:DataEvent):void {
			trace(event.data);
			xml = new XML(event.data);
			if (xml.notifications.length()) {
				for each (var notification:XML in xml.notifications.children()) {
					trace("Notification name: " + notification.name());
					switch(notification.toString()) {
						case "nameok":
							dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "nameok"));
							break;
						case "connected":
							break;
						
						case "part":
							break;
						case "keepalive":
							sock.send(<xml><keepalive/></xml>);
							trace("Heartbeat.");
							break;
					}
					switch(notification.name().toString()) {
						case "join":
							for each(var joined:XML in xml.notifications.join.children()) {
								trace(joined.toString() + " joined.");
								users.push(joined.toString());
								userList.source = users;
								msg('<font color="#007700">' + joined.toString() + " has joined the server.</font>");
							}
							break;
						case "part":
							for each(var parted:XML in xml.notifications.part.children()) {
								var nameIndex:int = searchArray(users,parted.toString());
								if (nameIndex != -1) {
									delete(users[nameIndex]);
									users = condenseArray(users);
									userList.source = users;
									msg('<font color="#007700">' + parted.toString() + " has left the server.</font>");
								} else {
									trace("Error: ghost " + parted.toString() + " parted!");
								}
							} 
							break;
						case "said":
							for each(var said:XML in xml.notifications.said.children()) {
								var name:String = said.name.toString();
								var utterance:String = said.utterance.toString();
								msg(name + ": " + utterance);
							}
							break;	
					}
				}
			}
			if (xml.wait.length()) {
				for each (var wait:XML in xml.wait.children()) {
					trace(wait.toString());
					switch(wait.toString()) {
						case "login":
							sock.send(<xml><name>{username}</name></xml>);
							break;
					}
				}
			}
			if (xml.errors.length()) {
				for each (var error:XML in xml.errors.children()) {
					switch (error.toString()) {
						case "That name is already taken.":
							dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "nametaken"));
							break;
						case "That name is not valid.":
							dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "nameinvalid"));
							break;
					}
				}
			}
		}
		
		public function searchArray(theArray:Array, searchElement:Object):int {
			for (var i:int = 0; i < theArray.length; i++) {
				if (theArray[i] == searchElement) {
					return i;
				}
			}
			return -1;
		}
		
		public function condenseArray(theArray:Array):Array {
			var returnArray:Array = new Array();
			for each(var thing:Object in theArray) {
				returnArray.push(thing);
			}
			return returnArray;
		}
	}
}