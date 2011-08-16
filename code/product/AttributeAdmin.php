<?php
class AttributeAdmin extends ModelAdmin {
   
  public static $managed_models = array( 
    'Attribute'
  );
  
  static $url_segment = 'attributes';
  static $menu_title = 'Product Attributes';
  static $default_model   = 'Attribute'; 
  public $showImportForm = false;
  
  function getEditForm(){ 
    return $this->bindModelController('Attribute')->ResultsForm(array()); 
  }
  
}