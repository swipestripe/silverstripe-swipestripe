<?php

class VariationOption extends DataObject {

  public static $db = array(
  );

  public static $has_one = array(
    'Variation' => 'Variation',
    'Option' => 'Option'
  );
  
  public static $has_many = array(
  );

}