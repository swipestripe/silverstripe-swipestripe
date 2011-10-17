<?php
class ProductAdmin extends RemodelAdmin {
   
  public static $managed_models = array( 
    'Product',
    'Attribute',
    'SiteConfig'
  );
  
  static $url_segment = 'products';
  
  static $menu_title = 'Products';
  
  static $default_model   = 'Product'; 
  
  public $showImportForm = false;
  
  static $menu_priority = 6;
  
  function getEditForm(){ 
    return $this->bindModelController('Product')->ResultsForm(array()); 
  }
  
}