package com.uberduck.game.y
{
	import mx.collections.ArrayCollection;
	import com.uberduck.game.lobby.ChatInterface;

	public class YNetEngine extends ChatInterface
	{
		public function YNetEngine(host:String, port:int, username:String, userList:ArrayCollection)
		{
			super(host, port, username, userList);
		}
		
	}
}