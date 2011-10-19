<?php
class ProductCategory extends Page {

	public static $db = array(
	);

	public static $many_many = array(
    'Products' => 'Product'
  );
  
  public static $summary_fields = array(
	  'MenuTitle' => 'Name'
	);
    
	function getCMSFields() {
    $fields = parent::getCMSFields();
    
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
    
    return $fields;
	}
}
class ProductCategory_Controller extends Page_Controller {

	public static $allowed_actions = array (
	);

	public function init() {
		parent::init();
	}
  
  public function Products() {

    if(!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) $_GET['start'] = 0;
      
    $SQL_start = (int)$_GET['start'];

    $doSet = DataObject::get( 
       'Product', 
       "`ProductCategory_Products`.`ProductCategoryID` = '".$this->ID."'", 
       "", 
       "LEFT JOIN `ProductCategory_Products` ON `ProductCategory_Products`.`ProductID` = `Product`.`ID`",
       "{$SQL_start}, 2"
    ); 
   
    return $doSet ? $doSet : false;
  }

}