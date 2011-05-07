<?php
/**
 * Concrete class for VirtualProductDecorator for use in unit tests
 * 
 * @author frankmullenger
 */
class DummyVirtualProductPage extends Page implements HiddenClass 
{
  static $has_many = array (
  );
  static $db = array(
  );
  static $has_one = array(
  );
  static $defaults = array(
  );

  public function getCMSFields() {
    $fields = parent::getCMSFields();
    return $fields;
  }
}

class DummyVirtualProductPage_Controller extends Page_Controller {

}