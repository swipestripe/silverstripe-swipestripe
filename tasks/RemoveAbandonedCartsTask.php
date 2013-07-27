<?php
/**
 * Remove abandoned carts that have not been active for a certain period of time
 * So that stock that is tied up in the carts can be released back if the customers
 * do not go through the checkout process.
 * 
 *  e.g: use crontab -e with a directive like below for task to run every minute
 * * /1 * * * * php /var/www/path/to/project/sapphire/cli-script.php /RemoveAbandonedCartsTask > /var/log/swipestripe.log
 * 
 * Note: remove the space between the * and /1
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tasks
 */
class RemoveAbandonedCartsTask extends QuarterHourlyTask {
	
	/**
	 * Remove {@link Order}s that have not been active for a certain period of time,
	 * do not have a {@link Payment} attached and have the status of 'Cart'.
	 * 
	 * @see Order::delete_abandoned()
	 * @see CliController::process()
	 */
	function process() {
		date_default_timezone_set('Pacific/Auckland');
		Order::delete_abandoned();
	} 
}
