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
    'Images' => 'ProductImage',
    'Options' => 'Option',
    'Variations' => 'Variation'
  );
  
  public static $many_many = array(
    'Attributes' => 'Attribute'
  );
  
  static $allowed_children = array(
  	//'ProductVariation'
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
    
	public function getCMSFields() {
    $fields = parent::getCMSFields();
    
    //Basic db fields
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
		
		
		//Attributes selection
		$tablefield = new ManyManyComplexTableField(
      $this,
      'Attributes',
      'Attribute',
      array('Title' => 'Title'),
      'getCMSFields'
    );
    $tablefield->setPermissions(array());
    $fields->addFieldToTab("Root.Attributes", $tablefield);
    
    $variationFieldList = array(
    	'ID' => 'ID',
    	'SummaryStock' => 'Stock'
    );
    
    //Options selection
    $attributes = $this->Attributes();
    if ($attributes && $attributes->exists()) {
      
      $fields->addFieldToTab("Root.Content", new TabSet('Options'));
      $fields->addFieldToTab("Root.Content", new Tab('Variations'));

      foreach ($attributes as $attribute) {

        $variationFieldList['AttributeValue_'.$attribute->ID] = $attribute->Title;

        //If there aren't any existing options for this attribute on this product,
        //populate with the default options
        $defaultOptions = DataObject::get('Option', "ProductID = 0 AND AttributeID = $attribute->ID");
        $existingOptions = DataObject::get('Option', "ProductID = $this->ID AND AttributeID = $attribute->ID");
        if (!$existingOptions || !$existingOptions->exists()) {
          if ($defaultOptions && $defaultOptions->exists()) {
            foreach ($defaultOptions as $option) {
              $newOption = $option->duplicate(false);
              $newOption->ProductID = $this->ID;
              $newOption->write();
            }
          }
        }

        $fields->addFieldToTab("Root.Content.Options", new Tab($attribute->Title));
        $manager = new OptionComplexTableField(
          $this,
          $attribute->Title,
          'Option',
          array(
            'Title' => 'Title',
          ),
          'getCMSFields_forPopup',
          "AttributeID = $attribute->ID"
        );
        $manager->setAttributeID($attribute->ID);
        $fields->addFieldToTab("Root.Content.Options.".$attribute->Title, $manager);
      }
    }

    $manager = new VariationComplexTableField(
      $this,
      'Variations',
      'Variation',
      $variationFieldList,
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Content.Variations", $manager);

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
  
  function AddToCartForm($quantity = null, $redirectURL = null) {

    //$fields = $this->getProductFields($quantity, $redirectURL);
    $fields = new FieldSet(
      new TextField('ProductClass', 'ProductClass', $this->ClassName),
      new TextField('ProductID', 'ProductID', $this->ID),
      new TextField('ProductVariationID', 'ProductVariationID', 0),
      new HiddenField('Redirect', 'Redirect', $redirectURL),
      new TextField('Quantity', 'Quantity', $quantity)
    );

    //Get the options for this product
    $optionGroupField = new OptionGroupField('OptionGroup', $this);
    $fields->push($optionGroupField);
    
    $actions = new FieldSet(
      new FormAction('add', 'Add To Cart')
    );
    $validator = new RequiredFields(
    	'ProductClass', 
    	'ProductID'
    );
    
    $controller = Controller::curr();
    return new Form($controller, 'AddToCartForm', $fields, $actions, $validator);
	}
	
  protected function getProductFields($quantity = null, $redirectURL = null) {

	  return new FieldSet(
      new TextField('ProductClass', 'ProductClass', $this->ClassName),
      new TextField('ProductID', 'ProductID', $this->ID),
      new TextField('ProductVariationID', 'ProductVariationID', 0),
      new HiddenField('Redirect', 'Redirect', $redirectURL),
      new TextField('Quantity', 'Quantity', $quantity)
    );
	}
}
class Product_Controller extends Page_Controller {
  
  public static $allowed_actions = array (
  	'add',
    'options'
  );

	/**
   * Add an item to the cart
   */
  function add() {
    
    SS_Log::log(new Exception(print_r('we are getting into here', true)), SS_Log::NOTICE);
    
    self::get_current_order()->addItem($this->getProduct(), $this->getQuantity(), $this->getProductOptions());
    $this->goToNextPage();
  }
  
	/**
   * Find a product based on current request
   * 
   * @see SS_HTTPRequest
   * @return DataObject 
   */
  private function getProduct() {
    $request = $this->getRequest();
    return DataObject::get_by_id($request->requestVar('ProductClass'), $request->requestVar('ProductID'));
  }
  
  /**
   * Get product options based on current request
   * 
   * @see SS_HTTPRequest
   * @return DataObject 
   */
  private function getProductOptions() {
    
    $options = new DataObjectSet();
    $request = $this->getRequest();
    $options = $request->requestVar('Options');

    if ($options) foreach ($options as $optionClassName => $optionID) {
      $options->push(DataObject::get_by_id($optionClassName, $optionID));
    }
    return $options;
  }
  
  /**
   * Find the quantity based on current request
   * 
   * @return Int
   */
  private function getQuantity() {
    $quantity = $this->getRequest()->requestVar('Quantity');
    return ($quantity) ?$quantity :1;
  }
  
  /**
   * Send user to next page based on current request vars,
   * if no redirect is specified redirect back.
   * 
   * TODO make this work with AJAX
   */
  private function goToNextPage() {
    $redirectURL = $this->getRequest()->requestVar('Redirect');

    //Check if on site URL, if so redirect there, else redirect back
    if ($redirectURL && Director::is_site_url($redirectURL)) Director::redirect(Director::absoluteURL(Director::baseURL() . $redirectURL));
    else Director::redirectBack();
  }
  
	/**
   * Get the current order from the session, if order does not exist
   * John Connor it (create a new order)
   * 
   * @return Order
   */
  static function get_current_order() {

    $orderID = Session::get('Cart.OrderID');
    
    if ($orderID) {
      $order = DataObject::get_by_id('Order', $orderID);
    }
    else {
      $order = new Order();
      $order->write();
      Session::set('Cart', array(
        'OrderID' => $order->ID
      ));
      Session::save();
    }
    
    return $order;
  }
  
  /**
   * AJAX action to get options for a product and return for use in the form
   */
  public function options(SS_HTTPRequest $request) {
    SS_Log::log(new Exception(print_r('getting in to options', true)), SS_Log::NOTICE);
    SS_Log::log(new Exception(print_r($request, true)), SS_Log::NOTICE);
    
    $attributeID = $request->getVar('attributeID');
    $optionID = $request->getVar('optionID');
    
    //Need to get the options for the next attribute basically, don't really know what the next attribute is
    
    return 'hello world of options';
  }
}