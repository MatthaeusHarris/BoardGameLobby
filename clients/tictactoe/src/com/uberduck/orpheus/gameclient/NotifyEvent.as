package com.uberduck.orpheus.gameclient
{
	import flash.events.Event;

	public class NotifyEvent extends Event	{
		public static const NOTIFY:String = "notify";
		public var notifyVar:String;
		
		public function NotifyEvent(type:String, bubbles:Boolean=false, cancelable:Boolean=false, notifyVar:String = "unknown") {
			super(type, bubbles, cancelable);
			this.notifyVar = notifyVar;
		}
		
		public override function clone():Event {
			return new NotifyEvent(type, bubbles, cancelable, notifyVar);
		}
		
		public override function toString():String {
			return formatToString("NotifyEvent", "type", "bubbles", "cancelable", "eventPhase", "notifyVar");
		}
		
	}
}