<?php
/**
 * Represents an Option for an Attribute, e.g: Small, Medium, Large, Red etc.
 * Default Options can be created for Attributes, they are pre populated and duplicated into the Product
 * when the Attribute is added to a Product. Options can be changed for each Product. 
 * Default Options will have a ProductID of 0.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 * @version 1.0
 */
class Option extends DataObject {

  /**
   * DB fields for this Option
   * 
   * @var Array
   */
  public static $db = array(
    'Title' => 'Varchar(255)',
    'Description' => 'Text'
  );

  /**
   * Has one relations for an Option
   * 
   * @var Array
   */
  public static $has_one = array(
    'Attribute' => 'Attribute',
    'Product' => 'Product'
  );
  
  /**
   * Belongs many many relations for an Option
   * 
   * @var Array
   */
  static $belongs_many_many = array(    
    'Variations' => 'Variation'
  );
  
  /**
   * Set fields for editing an Option in the CMS
   * 
   * @return FieldSet
   */
  public function getCMSFields_forPopup() {
    return new FieldSet(
      new TextField('Title'),
      new TextareaField('Description')
    );
  }

}