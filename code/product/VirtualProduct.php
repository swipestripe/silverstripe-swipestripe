<?php
class VirtualProduct extends Product {
  
  /**
   * Download folder relative to site root
   * 
   * @var String
   */
  public static $downloadFolder = 'simplecart/downloads/';
  
  /**
   * Number of times the product can be downloaded
   * 
   * @var Int
   */
  public static $downloadLimit = 3;
  
  /**
   * Window of time product can be downloaded
   * Should be a relative unit (http://nz.php.net/manual/en/datetime.formats.relative.php)
   * 
   * @var String
   */
  public static $downloadWindow = '1 day';

  public static $db = array(
    'FileLocation' => 'Varchar',
  	'TotalDownloadCount' => 'Int'
  );

  public static $has_one = array(
  );
  
  public static $has_many = array(
  );
  
  public static $defaults = array(
    'TotalDownloadCount' => 0
  );
    
	function getCMSFields() {
    $fields = parent::getCMSFields();
    
    $fields->addFieldToTab('Root.Content.Main', new TextField('FileLocation', 'Physical location of this virtual product (relative to root)'), 'Content');

    return $fields;
	}

	/**
	 * Copy the downloadable file to another location on the server and
	 * redirect browser to that location.
	 * 
	 * Files are removed from new location after a certain amount of time.
	 * 
	 * @see VirutalProductDecorator::downloadFolder
	 * @see VirtualProductCleanupTask
	 */
	function downloadLocation() {

	  if (Director::fileExists($this->owner->FileLocation)) {
	    
	    $downloadFolder = Director::getAbsFile(self::$downloadFolder);
	    
	    $origin = Director::getAbsFile($this->owner->FileLocation);
	    $destination = $downloadFolder . mt_rand(100000, 999999) .'_'. date('H-d-m-y') .'_'. basename($this->owner->FileLocation);

  	  if (copy($origin, $destination)) {
        return Director::absoluteURL(Director::baseURL() . Director::makeRelative($destination));
      }
	  }
	  return false;
	}
}
class VirtualProduct_Controller extends Page_Controller {

}