<?php
/**
 * Represents a {@link Product} Attribute, e.g: Size, Color, Material etc.
 * Attributes are created in the {@link ShopAdmin} where they can be set with default 
 * Options. They are then selected on each product they relate to. Once an attribute
 * is added to a Product, that Product needs to define some Options for that Attribute 
 * and also have some Variations. If the Product does not have Variations when it needs to
 * then it cannot be purchased.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 * @version 1.0
 */
class Attribute extends DataObject {

  /**
   * DB fields for the Attribute, Title acts as the label for the select field on the 
   * AddToCartForm - so it does not need to be unique.
   * 
   * @see Product_Controller::AddToCartForm()
   * @var Array
   */
  public static $db = array(
    'Title' => 'Varchar(255)',
    'Description' => 'Text'
  );
  
  /**
   * Has many relations for the Attribute
   * 
   * @var Array
   */
  public static $has_many = array(
    'Options' => 'Option'
  );
  
  /**
   * Belongs many many relations for the Attribute
   * 
   * @var Array
   */
  static $belongs_many_many = array(    
    'Products' => 'Product'
  );
  
  /**
   * Searchable fields for Attributes
   * 
   * @var Array
   */
  public static $searchable_fields = array(
	  'Title'
	);
  
	/**
	 * Add some fields to the CMS for managing Attributes.
	 * 
	 * @see DataObject::getCMSFields()
	 * @return FieldSet
	 */
  function getCMSFields() {
    $fields = parent::getCMSFields();
    $fields->removeByName('Products');
    $fields->removeByName('Options');
    
    //Add a manager for options
    $manager = new ComplexTableField(
      $this, 
      'Options', 
      'Option',
      array(
        'Title' => 'Title'
      ), 
      'getCMSFields_forPopup',
      'ProductID = 0'
    );
    $fields->addFieldToTab("Root.DefaultOptions", $manager);
    
    return $fields;
  }

}