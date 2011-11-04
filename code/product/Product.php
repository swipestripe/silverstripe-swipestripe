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

  public static $has_many = array(
    'Images' => 'ProductImage',
    'Options' => 'Option',
    'Variations' => 'Variation'
  );
  
  public static $many_many = array(
    'Attributes' => 'Attribute'
  );
  
  static $belongs_many_many = array(    
    'ProductCategories' => 'ProductCategory'
  );
  
  public static $defaults = array(
    'ParentID' => -1
  );
  
  public static $summary_fields = array(
    'FirstImage' => 'Image',
	  'Title' => 'Name',
    'Status' => 'Status',
    'CategoriesSummary' => 'Categories'
	);
	
	public static $searchable_fields = array(
	  'Title' => array(
			'field' => 'TextField',
			'filter' => 'PartialMatchFilter',
			'title' => 'Name'
		),
		'Status' => array(
			'filter' => 'PublishedStatusSearchFilter',
			'title' => 'Status'
		),
		'Category' => array(
  		'filter' => 'ProductCategorySearchFilter',
  	)
	);
	
	public static $casting = array(
		'Category' => 'Varchar'
	);
	
	/**
	 * Filter for order admin area search.
	 * 
	 * @see DataObject::scaffoldSearchFields()
	 */
  function scaffoldSearchFields(){
		$fieldSet = parent::scaffoldSearchFields();

		$statusField = new DropdownField('Status', 'Status', array(
		  1 => "published", 
		  2 => "not published"
		));
		$statusField->setHasEmptyDefault(true);
		$fieldSet->push($statusField);
		
		$categories = DataObject::get('ProductCategory');
		$categoryOptions = array();
		if ($categories) foreach ($categories as $productCategory) {
		  $categoryOptions[$productCategory->ID] = $productCategory->MenuTitle;
		}

		if ($categoryOptions) {
		  $fieldSet->push(new CheckboxSetField('Category', 'Category', $categoryOptions));
		}

		return $fieldSet;
	}
	
	/**
	 * Unpublish products if they get deleted, such as in product admin area
	 * 
	 * @see SiteTree::onAfterDelete()
	 */
  function onAfterDelete() {
    parent::onAfterDelete();
  
    if ($this->isPublished()) {
      $this->doUnpublish();
    }
  }
  
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
	    //TODO return meaningful error to browser in case error not shown
	    return;
	  }
	}
    
	public function getCMSFields() {
    $fields = parent::getCMSFields();
    
    //Basic db fields
    $manager = new ComplexTableField(
      $this,
      'Images',
      'ProductImage',
      array(
        'ThumbnailSummary' => 'Thumbnail',
        'Caption' => 'Caption'
      ),
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Content.Gallery", $manager);
    
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(self::$allowed_currency);	
		$fields->addFieldToTab('Root.Content.Main', $amountField, 'Content');
		
		//Attributes selection
		$anyAttribute = DataObject::get_one('Attribute');
		if ($anyAttribute && $anyAttribute->exists()) {
  		$tablefield = new ManyManyComplexTableField(
        $this,
        'Attributes',
        'Attribute',
        array('Title' => 'Title'),
        'getCMSFields'
      );
      $tablefield->setPermissions(array());
      $fields->addFieldToTab("Root.Attributes", $tablefield);
		}

    //Options selection
    $attributes = $this->Attributes();
    if ($attributes && $attributes->exists()) {
      
      $variationFieldList = array();
      
      $fields->addFieldToTab("Root.Content", new TabSet('Options'));
      $fields->addFieldToTab("Root.Content", new Tab('Variations'));

      foreach ($attributes as $attribute) {

        $variationFieldList['AttributeValue_'.$attribute->ID] = $attribute->Title;

        //TODO refactor, this is a really dumb place to be writing default options I think
        
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
      
      //$variationFieldList['SummaryStock'] = 'Stock';
      $variationFieldList['SummaryPrice'] = 'Price Difference';
      $variationFieldList['Status'] = 'Status';
      
      $manager = new VariationComplexTableField(
        $this,
        'Variations',
        'Variation',
        $variationFieldList,
        'getCMSFields_forPopup'
      );
      $fields->addFieldToTab("Root.Content.Variations", $manager);
    }
    
    //Product categories
    $manager = new ManyManyComplexTableField(
      $this,
      'ProductCategories',
      'ProductCategory',
      array(),
      'getCMSFields_forPopup'
    );
    $manager->setPermissions(array());
    $fields->addFieldToTab("Root.Content.Categories", $manager);
    
    return $fields;
	}

  function onBeforeWrite() {
    parent::onBeforeWrite();
    if (!$this->ID) $this->firstWrite = true;
  }

  public function inheritedDatabaseFields() {

		$fields     = array();
		$currentObj = $this->class;
		
		while($currentObj != 'DataObject') {
			$fields     = array_merge($fields, self::custom_database_fields($currentObj));
			$currentObj = get_parent_class($currentObj);
		}

		//Add field names in for Money fields
		$fields['Amount'] = 0;
		
		return (array) $fields;
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

    //If the variation does not have a complete set of valid options, then disable it
    $variations = DataObject::get('Variation', "Variation.ProductID = " . $this->ID . " AND Variation.Status = 'Enabled'");

    if ($variations) foreach ($variations as $variation) {
      
      if (!$variation->hasValidOptions()) {
        $variation->Status = 'Disabled';
        $variation->write();
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
  
  /**
   * Add to cart form for products
   * TODO validation broken due to overriding the Actions
   * 
   * @param unknown_type $quantity
   * @param unknown_type $redirectURL
   */
  function AddToCartForm($quantity = null, $redirectURL = null) {
    
    $fields = new FieldSet(
      new HiddenField('ProductClass', 'ProductClass', $this->ClassName),
      new HiddenField('ProductID', 'ProductID', $this->ID),
      new HiddenField('ProductVariationID', 'ProductVariationID', 0),
      new HiddenField('Redirect', 'Redirect', $redirectURL)
    );

    //Get the options for this product
    $optionGroupField = new OptionGroupField('OptionGroup', $this);
    $fields->push($optionGroupField);
    
    $fields->push(new TextField('Quantity', 'Quantity', $quantity));
    
    $actions = new FieldSet(
      new FormAction('add', 'Add To Cart')
    );
    $validator = new RequiredFields(
    	'ProductClass', 
    	'ProductID',
      'Quantity'
    );
    $validator->setJavascriptValidationHandler('none'); 
    
    $controller = Controller::curr();
    $form = new Form($controller, 'AddToCartForm', $fields, $actions, $validator);
    $form->disableSecurityToken();
    
    //Change the action to accommodate product pages not in the site tree (ParentID = -1)
	  $form->setFormAction('/product/'.$this->URLSegment.'/add');
    
    return $form;
	}
	
	function CategoriesSummary() {
	  $summary = array();
	  $categories = $this->ProductCategories();
	  
	  if ($categories) foreach ($categories as $productCategory) {
	    $summary[] = $productCategory->Title;
	  } 
	  
	  return implode(', ', $summary);
	}
	
	function Link($action = null) {
	  
	  if ($this->ParentID > -1) {
	    return Controller::join_links(Director::baseURL() . 'product/', $this->URLSegment .'/');
	    //return parent::Link($action);
	  }
	  return Controller::join_links(Director::baseURL() . 'product/', $this->RelativeLink($action));
	}
	
	/**
   * A product is required to be added to a cart with a variation if it has attributes
   * 
   * @return Boolean
   */
  public function requiresVariation() {
    $attributes = $this->Attributes();
    return $attributes && $attributes->exists();
  }
  
  public function getOptionsForAttribute($attributeID) {
    
    $options = new DataObjectSet();
    $variations = $this->Variations();
    
    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      if ($variation->isEnabled()) {
        $option = $variation->getAttributeOption($attributeID);
        if ($option) $options->push($option); 
      }
    }
    return $options;
  }

}
class Product_Controller extends Page_Controller {
  
  public static $allowed_actions = array (
  	'add',
    'options',
    'AddToCartForm',
    'variationprice',
    'show'
  );

  public static $url_handlers = array( 
    '$ID!/add' => 'add',
    '$ID!/options' => 'options',
    '$ID!/variationprice' => 'variationprice',
  	'$ID!' => 'show'
  );
  
  function init() {
    parent::init();
    
    Requirements::css('stripeycart/css/StripeyCart.css');
    
    //Get current product page for products that are not part of the site tree
    //and do not have a ParentID set, they are accessed via this controller using
    //Director rules
    if ($this->dataRecord->ID == -1) {
      
      $params = $this->getURLParams();
      
      if ($urlSegment = $params['ID']) {
        $product = DataObject::get_one('Product', "URLSegment = '" . convert::raw2sql($urlSegment) . "'");
        
        if ($product && $product->exists()) {
          $this->dataRecord = $product; 
          $this->failover = $this->dataRecord;
        }
      }
    }
  }
  
	/**
   * Add an item to the cart
   */
  function add() {
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
   * Get product variations based on current request, check that options in request
   * correspond to a variation
   * 
   * @see SS_HTTPRequest
   * @return DataObject 
   */
  private function getProductOptions() {
    
    $productVariations = new DataObjectSet();
    $request = $this->getRequest();
    $options = $request->requestVar('Options');
    $product = $this->data();
    $variations = $product->Variations();

    if ($variations && $variations->exists()) foreach ($variations as $variation) {
      
      $variationOptions = $variation->Options()->map('AttributeID', 'ID');
      if ($options == $variationOptions && $variation->isEnabled()) {
        $productVariations->push($variation);
      }
    }
    return $productVariations;
  }
  
  /**
   * Find the quantity based on current request
   * 
   * @return Int
   */
  private function getQuantity() {
    $quantity = $this->getRequest()->requestVar('Quantity');
    return (isset($quantity)) ?$quantity :1;
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
   * TODO move this to CartControllerExtension
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
   * Must get options for nextAttributeID, but these options should be filtered so 
   * that only the options for the variations that match attributeID and optionID
   * are returned.
   */
  public function options(SS_HTTPRequest $request) {

    $data = array();
    $product = $this->data();
    $options = new DataObjectSet();
    $variations = $product->Variations();
    $filteredVariations = new DataObjectSet();
    
    $attributeOptions = $request->postVar('Options');
    $nextAttributeID = $request->postVar('NextAttributeID');
    
    //Filter variations to match attribute ID and option ID
    //Variations need to have the same option for each attribute ID in POST data to be considered
    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      $variationOptions = array();
      //if ($attributeOptions && is_array($attributeOptions)) 
      foreach ($attributeOptions as $attributeID => $optionID) {
        
        //Get option for attribute ID, if this variation has options for every attribute in the array then add it to filtered
        $attributeOption = $variation->getAttributeOption($attributeID);
        if ($attributeOption && $attributeOption->ID == $optionID) $variationOptions[$attributeID] = $optionID;
      }
      
      if ($variationOptions == $attributeOptions && $variation->isEnabled()) {
        $filteredVariations->push($variation);
      }
    }
    
    //Find options in filtered variations that match next attribute ID
    //All variations must have options for all attributes so this is belt and braces really
    if ($filteredVariations && $filteredVariations->exists()) foreach ($filteredVariations as $variation) {
      $attributeOption = $variation->getAttributeOption($nextAttributeID);
      if ($attributeOption) $options->push($attributeOption);
    }
    
    if ($options && $options->exists()) {

      $map = $options->map();
      //This resets the array counter to 0 which ruins the attribute IDs
      //array_unshift($map, 'Please Select'); 
      $data['options'] = $map;
      
      $data['count'] = count($map);
      $data['nextAttributeID'] = $nextAttributeID;
    }

    return json_encode($data);
  }
  
  /**
   * TODO return the total here as well
   * TODO format with a + or - prefix
   * 
   * @param unknown_type $request
   */
  function variationprice(SS_HTTPRequest $request) {

    $data = array();
    $product = $this->data();
    $variations = $product->Variations();
    
    $attributeOptions = $request->postVar('Options');
    
    //Filter variations to match attribute ID and option ID
    $variationOptions = array();
    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      $options = $variation->Options();
      if ($options) foreach ($options as $option) {
        $variationOptions[$variation->ID][$option->AttributeID] = $option->ID;
      }
    }
    
    $variation = null;
    foreach ($variationOptions as $variationID => $options) {
      
      if ($options == $attributeOptions) {
        $variation = $variations->find('ID', $variationID);
        break;
      }
    }
    
    if ($variation) {

      if ($variation->Amount->getAmount() == 0) {
        $data['priceDifference'] = 0;
      }
      else if ($variation->Amount->getAmount() > 0) {
        $data['priceDifference'] = '(+' . $variation->Amount->Nice() . ')';
      }
      else {
        $data['priceDifference'] = '(' . $variation->Amount->Nice() . ')';
      }
    }
    
    return json_encode($data);
  }
  
  function show(SS_HTTPRequest $request) {
    
    $product = $this->data();

    if ($product && $product->exists()) {
      $data = array(
      	'Product' => $product,
        //'Content' => $this->Content, 
       	//'Form' => $this->Form 
      );
 
      $ssv = new SSViewer("Page"); 
      $ssv->setTemplateFile("Layout", "Product_show"); 
      return $this->Customise($data)->renderWith($ssv); 
    }
    else {
      return $this->httpError(404, 'Sorry that product could not be found');
    }
  }
}