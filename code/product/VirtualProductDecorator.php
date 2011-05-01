<?php
/**
 * Mixin for other data objects that are to represent virtual
 * products, this should be used in conjunction with ProductDecorator,
 * this simply adds some functionality for virtual products.
 * 
 * @author frankmullenger
 */
class VirutalProductDecorator extends DataObjectDecorator {
  
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
	  //TODO create a new download file and return the path to it
	  
	  Page::log($this->owner->FileLocation);
	  
	  return false;
	}
	
}