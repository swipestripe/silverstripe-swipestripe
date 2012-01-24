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
 * @package swipestripe
 * @subpackage tasks
 * @version 1.0
 */
class RemoveAbandonedCartsTask extends QuarterHourlyTask {
	
	function process() {

	  //Get orders that:
	  //are last active over an hour ago
	  //do NOT have a payment
	  //are of status 'Cart'
	  
	  date_default_timezone_set('Pacific/Auckland');
	  Order::delete_abandoned();
	} 
}
