<?php
/**
 * Mixin for other data objects that are to represent virtual
 * products, this should be used in conjunction with ProductDecorator,
 * this simply adds some functionality for virtual products.
 * 
 * @author frankmullenger
 */
class VirutalProductDecorator extends DataObjectDecorator {
  
  public $downloadFolder;
  
  /**
   * Add fields for virtual products
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {
		return array(
			'db' => array(
				'FileLocation' => 'Varchar',
		    'TotalDownloadCount' => 'Int'
			),
			'defaults' => array(
			  'TotalDownloadCount' => 0
			)
		);
	}
	
	/**
	 * Update the CMS with form fields for extra db fields above
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
	function updateCMSFields(&$fields) {
		$fields->addFieldToTab('Root.Content.Main', new TextField('FileLocation', 'Physical location of this virtual product'), 'Content');
	}
	
	function downloadLocation() {
	  
	  return false;
	  
	  //TODO create a new download file and return the path to it
	  
	  //TODO set the download folder from somewhere central
	  $origin = 
	  
	  $this->downloadFolder = dirname(__FILE__) . '../../downloads/';
	  $destination = $this->downloadFolder;
	  
  	if (!copy($file, $newfile)) {
      echo "failed to copy $file...\n";
    }
	  
	  Page::log($this->owner->FileLocation);
	  
	  
	  
	  return false;
	}
	
}