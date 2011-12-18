<?php
/**
 * Remove abandoned carts that have not been active in the last hour +- 15 mins
 * So that stock that is tied up in the carts can be released back if the customers
 * do not go through the checkout process.
 * 
 *  * /1 * * * *  www-data /var/www/raddad.co.nz/sapphire/cli-script.php /RemoveAbandonedCartsTask
 *  
 *  * /1 * * * *  frankmullenger php /var/www/raddad.co.nz/sapphire/cli-script.php /RemoveAbandonedCartsTask > /var/log/swipestripe.log
 *  
 *  php /var/www/raddad.co.nz/sapphire/cli-script.php /RemoveAbandonedCartsTask
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage tasks
 * @version 1.0
 */
class RemoveAbandonedCartsTask extends QuarterHourlyTask {
	
	function process() {
	  
	  SS_Log::log(new Exception(print_r('not processing task at the moment', true)), SS_Log::NOTICE);
	  return;
	  
	  //Get orders that:
	  //are last active over an hour ago
	  //do NOT have a payment
	  //are of status 'Cart'
	  
	  date_default_timezone_set('Pacific/Auckland');
	  $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));

	  //Get orders that were last active over an hour ago and have not been paid at all
	  $orders = DataObject::get(
	  	'Order',
	    "Order.LastActive < '$oneHourAgo' AND Order.Status = 'Cart' AND Payment.ID IS NULL",
	    '',
	    "LEFT JOIN Payment ON Payment.OrderID = Order.ID"
	  );
	  
	  SS_Log::log(new Exception(print_r($oneHourAgo, true)), SS_Log::NOTICE);
	  SS_Log::log(new Exception(print_r($orders->map(), true)), SS_Log::NOTICE);
	  
	  foreach ($orders as $order) {

	    //Delete the order AND return the stock to the Product/Variation
	    //Should be done in a transaction really
	    //use onAfterDelete() on Order to return stock to products 
	    
	    //SS_Log::log(new Exception(print_r('About to DELETE order ' . $order->ID, true)), SS_Log::NOTICE);
	    if ($order->ID == 33) {
	      $order->delete();
	      $order->destroy();
	    }
	    
	  }
	  
	  echo 'Time one hour ago is: ' . $oneHourAgo . "\n";
	} 
}
