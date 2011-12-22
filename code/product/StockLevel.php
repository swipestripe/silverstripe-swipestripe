<?php 
/**
 * Stock level for products and variations, keeping stock levels out of versioning system
 * 
 */
class StockLevel extends DataObject {
  
  public static $db = array(
    'Level' => 'Int'
  );
  
  public static $defaults = array(
    'Level' => -1
  );
  
  
}
