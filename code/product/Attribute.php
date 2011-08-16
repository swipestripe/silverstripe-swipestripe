<?php

class Attribute extends DataObject {

  public static $db = array(
    'Title' => 'Varchar(255)',
    'Description' => 'Text'
  );

  public static $has_one = array(
  );
  
  public static $has_many = array(
    'Options' => 'Option'
  );
  
  static $belongs_many_many = array(    
    'Products' => 'Product'
  );
  
  function getCMSFields() {
    $fields = parent::getCMSFields();
    $fields->removeByName('Products');
    
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
    $fields->addFieldToTab("Root.Options", $manager);
    
    return $fields;
  }

}