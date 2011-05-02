<?php
/**
 * Remove virtual products that are older than the 
 * download window, cleaning up the filesystem and 
 * preventing linking directly to product downloads 
 * for extended periods of time.
 * 
 * @author frankmullenger
 * @see VirutalProductDecorator::$downloadWindow
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
