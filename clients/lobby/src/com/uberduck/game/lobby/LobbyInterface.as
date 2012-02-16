package com.uberduck.game.lobby
{
	import com.uberduck.game.NotifyEvent;
	
	import flash.events.DataEvent;
	
	import mx.collections.ArrayCollection;
	import mx.collections.XMLListCollection;
	import mx.controls.Alert;
	import mx.events.CloseEvent;
	import mx.managers.PopUpManager;

	public class LobbyInterface extends ChatInterface
	{
		protected var games:Array;
		protected var gameList:XMLListCollection;
		protected var gameXML:XML;
		
		public var gameId:int;
		public var gamePort:int;
		public var gameSWF:String;
		
		protected var availableUserList:ArrayCollection;
		protected var availableUsers:Array;
		
		private var inviteAlert:Alert;
		private var inviteAlertShowing:Boolean;
		
		
		public function LobbyInterface(host:String, port:int, username:String, userList:ArrayCollection, availableUserList:ArrayCollection, gameList:XMLListCollection)
		{
			super(host, port, username, userList);
			//this.gameXML = gameXML;
			games = new Array();
			this.gameList = gameList;
			this.availableUserList = availableUserList;
			this.availableUsers = new Array();
			this.gameSWF = new String();
		}
		
		override protected function dataHandler(event:DataEvent):void {
			super.dataHandler(event);
			if (xml.gamelist.length()) {
				trace("Game list received.");
				var gameXMLList:XMLList = new XMLList(xml.gamelist.children());
				gameList.source = gameXMLList;
			}
			if (xml.invite.length()) {
				trace("Invite received!");
				var inviter:String = xml.invite.by.toString();
				var gameName:String = xml.invite.name.toString();
				var gameType:String = xml.invite.game.toString();
				this.gameId = int(xml.invite.id);
				inviteAlertShowing = true;
				inviteAlert = Alert.show(inviter + " has invited you to play a game of " + gameType + ".  Do you want to accept?","Invitation",Alert.YES | Alert.NO,null,inviteResponseHandler);
				
			}
			if (xml.notifications.length()) {
				for each(var notification:XML in xml.notifications.children()) {
					switch(notification.toString()) {
						case "gamequit":
							trace("gamequit detected.");
							if (inviteAlertShowing) {
								inviteAlertShowing = false;
								PopUpManager.removePopUp(inviteAlert);
								Alert.show("The game was cancelled.","Invitation");
							}
							break;						
					}
					trace("Notification name: " + notification.name());
					switch(notification.name().toString()) {	
						case "available":
							trace("Found a new available list.");
							availableUsers = new Array();
							for each(var available:XML in xml.notifications.available.children()) {
								availableUsers.push(available.toString());
								trace(available.toString() + " has become available.");
							}
							availableUserList.source = availableUsers;
							break;
					}
				} 
			}
			if (xml.gamestart.length()) {
				trace("gamestart detected.");
				gamePort = int(xml.gamestart.port);
				gameSWF = xml.gamestart.swf.toString();
				trace("Need to load " + gameSWF + " and connect it to port " + gamePort);
				dispatchEvent(new NotifyEvent(NotifyEvent.NOTIFY, true, false, "gamestart"));
			}							
		}
		
		private function inviteResponseHandler(event:CloseEvent):void {
			inviteAlertShowing = false;
			if (event.detail == Alert.YES) {
				sock.send(<xml><accept>{gameId}</accept></xml>);
			} else {
				sock.send(<xml><reject>{gameId}</reject></xml>);
			}
		}
		
		public function requestGameList():void {
			sock.send(<xml><gamelist /></xml>);
		}
		
		public function invitePlayers(gameName:String, players:Array, gameType:String):void {
			var playerList:String = "<item>" + players.join("</item><item>") + "</item>";
			sock.send(XML("<xml><newgame><players>" + playerList + "</players><name>" + gameName + "</name><game>" + gameType + "</game></newgame></xml>"));
			
		}
		
		
		
	}
}