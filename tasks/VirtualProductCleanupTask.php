<?php
/**
 * TODO clean up the filesystem, remove virtual products older than 1 day to 
 * prevent hotlinking to the files directly and bypassing download limits
 * 
 * @author frankmullenger
 *
 */
class VirtualProductCleanupTask extends HourlyTask{
	
  /*
	static $cleardays = 90;
	
	function set_clear_days($days = 90){
		self::$cleardays = $days;
	}
	
	//Find and remove carts older than X days
	function process(){
		
		$time = date('Y-m-d H:i:s', strtotime("-".self::$cleardays." days"));
		if($oldcarts = DataObject::get('Order',"\"Status\" = 'Cart' AND \"LastEdited\" < '$time'")){
			foreach($oldcarts as $cart){
				echo "deleting ".$cart->Title."  </br>\n";
				$cart->delete();
				$cart->destroy();
			}
		}
		
	} 
	*/
}
