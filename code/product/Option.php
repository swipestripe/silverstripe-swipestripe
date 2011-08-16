<?php
/**
 * TODO add versioning to these options
 * 
 * @author frankmullenger
 *
 */
class Option extends DataObject {

  public static $db = array(
    'Title' => 'Varchar(255)',
    'Description' => 'Text'
  );

  public static $has_one = array(
    'Attribute' => 'Attribute',
    'Product' => 'Product'
  );
  
  static $belongs_many_many = array(    
    'Variations' => 'Variation'
  );
  
  public function getCMSFields_forPopup() {
    return new FieldSet(
      new TextField('Title'),
      new TextareaField('Description')
    );
  }

}