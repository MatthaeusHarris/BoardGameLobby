package com.uberduck.components
{
	import flash.events.KeyboardEvent;
	import flash.events.MouseEvent;
	
	import mx.controls.List;

	public class ToggleList extends List
	{
		public var selectMultipleOnClick:Boolean;
		
		public function ToggleList()
		{
			super();
			
			selectMultipleOnClick = false;
		}
		
		override protected function keyDownHandler(event:KeyboardEvent):void {
			if (selectMultipleOnClick) event.ctrlKey = true;
			
			super.keyDownHandler(event);
		}
		
		override protected function mouseDownHandler(event:MouseEvent):void {
			if (selectMultipleOnClick) event.ctrlKey = true;
			
			super.mouseDownHandler(event);
		}
	}
}