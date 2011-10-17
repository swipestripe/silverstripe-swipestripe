<?php
class ProductCategory extends Page {

	public static $db = array(
	  //'Test' => 'Varchar'
	);

	public static $many_many = array(
    'Products' => 'Product'
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
    
    return;
    
    
    if(!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) $_GET['start'] = 0;
      
    $SQL_start = (int)$_GET['start'];
    $doSet = DataObject::get(
      $callerClass = "Product",
      $filter = "`ParentID` = '".$this->ID."'",
      $sort = "",
      $join = "",
      $limit = "{$SQL_start}, 2"
    );
   
    return $doSet ? $doSet : false;
  }

}