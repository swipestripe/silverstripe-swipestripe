<?php
/**
 * Concrete class for ProductDecorator for use in unit tests
 * 
 * @author frankmullenger
 */
class DummyProductPage extends Page implements HiddenClass
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

class DummyProductPage_Controller extends Page_Controller {

}