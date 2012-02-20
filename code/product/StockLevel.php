<?php 
/**
 * Stock level for associating with {@link Product}s or {@link Variation}s.
 * Allows the stock level to be outside the versioning procedure.
 * 
 * Levels:
 * -1 = unlimited stock
 * 0 = out of stock
 * > 0 = actual stock level
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 * @version 1.0
 */
class StockLevel extends DataObject {
  
  /**
   * The stock level value (Int)
   * 
   * @var Array
   */
  public static $db = array(
    'Level' => 'Int'
  );
  
  /**
   * Default stock level is -1 = unlimited stock.
   * 
   * @var Array
   */
  public static $defaults = array(
    'Level' => -1
  );
}