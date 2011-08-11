<?php
class Product extends Page {
  
  protected $firstWrite = false;
  
  /**
   * Currency allowed to be used for products
   * Code match Payment::$site_currency
   * Only once currency site wide allowed
   * 
   * @var Array Currency code indexes currency name
   */
  public static $allowed_currency = array(
    'NZD' => 'New Zealand Dollar'
  );

  public static $db = array(
    'Amount' => 'Money'
  );

  public static $has_one = array(
  );
  
  public static $has_many = array(
    'Images' => 'ProductImage'
  );
  
  static $allowed_children = array(
  	'ProductVariation'
  );
  
	/**
	 * Set the currency for all products.
	 * Must match site curency
	 * 
	 * @param array $currency
	 */
	public static function set_allowed_currency(Array $currency) {
	  if (count($currency) && array_key_exists(Payment::site_currency(), $currency)) {
	    self::$allowed_currency = $currency;
	  }
	  else {
	    user_error("Cannot set allowed currency. Currency must match: ".Payment::site_currency(), E_USER_WARNING);
	  }
	}
    
	function getCMSFields() {
    $fields = parent::getCMSFields();
    
    $manager = new ImageDataObjectManager(
      $this,
      'Images',
      'ProductImage',
      'Image',
      array(
        'Caption' => 'Caption'
      ),
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Content.Gallery",$manager);
    
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(self::$allowed_currency);	
		$fields->addFieldToTab('Root.Content.Main', $amountField, 'Content');
    
    return $fields;
	}

  function onBeforeWrite() {
    parent::onBeforeWrite();
    if (!$this->ID) $this->firstWrite = true;
  }
 
  /**
   * Copy the original product options or generate the default product 
   * options
   * 
   * @see SiteTree::onAfterWrite()
   */
  function onAfterWrite() {
    parent::onAfterWrite();
    
    if ($this->firstWrite) {
      
      $original = DataObject::get_by_id($this->class, $this->original['ID']);
      if ($original) {
        $images = $original->Images();
        $this->duplicateProductImages($images);
      }
    }
  }
  
  protected function duplicateProductImages(DataObjectSet $images) {
    
    foreach ($images as $productImage) {
      $newImage = $productImage->duplicate(false);
      $newImage->ProductID = $this->ID;
      $newImage->write();
    }
  }
  
  public function FirstImage() {
    $images = $this->Images();
    $images->sort('SortOrder', 'ASC');
    return $images->First();
  }
}
class Product_Controller extends Page_Controller {

}