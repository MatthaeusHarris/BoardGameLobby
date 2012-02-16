package com.uberduck.orpheus.gameclient
{
	import flash.events.*;

	public class WaitEvent extends Event {
		public static const WAIT:String = "wait";
		public var waitVar:String;
		
		public function WaitEvent(type:String, bubbles:Boolean = false, cancelable:Boolean = false, waitVar:String = "unknown") {
			super(type, bubbles, cancelable);
			this.waitVar = waitVar;
		}
		
		public override function clone():Event {
			return new WaitEvent(type, bubbles, cancelable, waitVar);
		}
		
		public override function toString():String {
			return formatToString("WaitEvent", "type", "bubbles", "cancelable", "eventPhase", "waitVar");
		}
	}
}