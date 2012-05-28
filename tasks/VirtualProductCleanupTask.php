<?php
/**
 * Remove virtual products that are older than the 
 * download window, cleaning up the filesystem and 
 * preventing linking directly to product downloads 
 * for extended periods of time.
 * 
 * This is a remnant of a previous cart which had Virtual Products
 * 
 * @see VirutalProductDecorator::$downloadWindow
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tasks
 * @deprecated
 */
class VirtualProductCleanupTask extends HourlyTask {
	
	function process(){

	  $dir = Director::getAbsFile(VirutalProductDecorator::$downloadFolder);
	  $files = scandir($dir);
	  
	  foreach ($files as $file) {

	    $filelastmodified = filemtime($dir . $file);
	    
	    //Skip ., .. and .htaccess files
	    if (strpos($file, '.') !== 0) {
	      
  	    if($filelastmodified < strtotime('-'.VirutalProductDecorator::$downloadWindow)) {
          unlink($dir . $file);
        }
	    }
	  }
	} 
}
