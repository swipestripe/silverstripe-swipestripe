<?php
/**
 * Mixin for other data objects that are to represent virtual
 * products, this should be used in conjunction with ProductDecorator,
 * this simply adds some functionality for virtual products.
 * 
 * @author frankmullenger
 */
class VirutalProductDecorator extends ProductDecorator {
  
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
  
  /**
   * Add fields for virtual products
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {

	  return array_merge_recursive(
	    parent::extraStatics(),
	    array(
  			'db' => array(
  				'FileLocation' => 'Varchar',
  		    'TotalDownloadCount' => 'Int'
  			),
  			'defaults' => array(
  			  'TotalDownloadCount' => 0
  			)
  		)
	  );
	}
	
	/**
	 * Update the CMS with form fields for extra db fields above
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
	function updateCMSFields(&$fields) {
	  
	  parent::updateCMSFields($fields);
		$fields->addFieldToTab('Root.Content.Main', new TextField('FileLocation', 'Physical location of this virtual product'), 'Content');
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