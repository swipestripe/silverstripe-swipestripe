<?php
/**
 * Represents a Product category, Products can be added to many categories and they 
 * can have a ProductCategory as a parent in the site tree. 
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 * @version 1.0
 */
class ProductCategory extends Page {

  /**
   * Many many relations for a ProductCategory
   * 
   * @var Array
   */
	public static $many_many = array(
    'Products' => 'Product'
  );
  
  /**
   * Summary fields for viewing categories in the CMS
   * 
   * @var Array
   */
  public static $summary_fields = array(
	  'MenuTitle' => 'Name'
	);
    
	/**
	 * Can add products to the category straight from the ProductCategory page
	 * TODO remove this, its not useful. And change the direction of the many_many relation so that patched version of CTF not needed
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldSet
	 */
	function getCMSFields() {
    $fields = parent::getCMSFields();
    
    /*
    //Product categories
    $manager = new ManyManyComplexTableField(
      $this,
      'Products',
      'Product',
      array(),
      'getCMSFields_forPopup'
    );
    $manager->setPermissions(array());
    $fields->addFieldToTab("Root.Content.Products", $manager);
		*/
    
    return $fields;
	}
}

/**
 * Controller to display a ProductCategory and retrieve its Products. 
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 * @version 1.0
 */
class ProductCategory_Controller extends Page_Controller {

  /**
   * Get Products that have this ProductCategory set or have this ProductCategory as a parent in site tree.
   * Supports pagination.
   * 
   * @see Page_Controller::Products()
   * @return FieldSet
   */  
  public function Products() {

    if(!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) $_GET['start'] = 0;
      
    $SQL_start = (int)$_GET['start'];
    $products = DataObject::get( 
       'Product', 
       "\"ProductCategory_Products\".\"ProductCategoryID\" = '".$this->ID."' OR \"ParentID\" = '".$this->ID."'", 
       "Created DESC", 
       "LEFT JOIN \"ProductCategory_Products\" ON \"ProductCategory_Products\".\"ProductID\" = \"Product\".\"ID\"",
       "{$SQL_start}, 9"
    ); 
    
    $this->extend('updateCategoryProducts', $products);

    return $products ? $products : false;
  }

}