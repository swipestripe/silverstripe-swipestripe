<?php
/**
 * Represents a Product, which is a type of a {@link Page}. Products are managed in a seperate
 * admin area {@link ShopAdmin}. A product can have {@link Variation}s, in fact if a Product
 * has attributes (e.g Size, Color) then it must have Variations. Products are Versioned so that
 * when a Product is added to an Order, then subsequently changed, the Order can get the correct
 * details about the Product.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage product
 * @version 1.0
 */
class Product extends Page {
  
  /**
   * Flag for denoting if this is the first time this Product is being written.
   * 
   * @var Boolean
   */
  protected $firstWrite = false;
  
  /**
   * Currency allowed to be used for products
   * Code match Payment::$site_currency
   * Only one currency site wide allowed
   * 
   * TODO Set currency in a central location
   * 
   * @var Array Currency code indexes currency name
   */
  public static $allowed_currency = array(
    'NZD' => 'New Zealand Dollar'
  );

  /**
   * DB fields for Product.
   * 
   * @var Array
   */
  public static $db = array(
    'Amount' => 'Money',
    'Stock' => 'Int'
  );

  /**
   * Has many relations for Product.
   * 
   * @var Array
   */
  public static $has_many = array(
    'Images' => 'ProductImage',
    'Options' => 'Option',
    'Variations' => 'Variation'
  );
  
  /**
   * Many many relations for Product
   * 
   * @var Array
   */
  public static $many_many = array(
    'Attributes' => 'Attribute'
  );
  
  /**
   * Belongs many many relations for Product
   * 
   * @var Array
   */
  static $belongs_many_many = array(    
    'ProductCategories' => 'ProductCategory'
  );
  
  /**
   * Defaults for Product
   * 
   * @var Array
   */
  public static $defaults = array(
    'ParentID' => -1
  );
  
  /**
   * Summary fields for displaying Products in the CMS
   * 
   * @var Array
   */
  public static $summary_fields = array(
    'FirstImage' => 'Image',
    'SummaryOfPrice' => 'Price',
	  'Title' => 'Name',
    'Status' => 'Status',
    'SummaryOfCategories' => 'Categories'
	);
	
	/**
   * Searchable fields for searching for Products in the CMS
   * 
   * @var Array
   */
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
	
	/**
   * Casting for searchable fields
   * 
   * @see Product::$searchable_fields
   * @var Array
   */
	public static $casting = array(
		'Category' => 'Varchar'
	);
	
	/**
	 * Filter for order admin area search.
	 * 
	 * @see DataObject::scaffoldSearchFields()
	 * @return FieldSet
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
	 * Set firstWrite flag if this is the first time this Product is written.
	 * If this product is a child of a ProductCategory, make sure that ProductCategory 
	 * is in the ProductCategories for this Product.
	 * 
	 * @see SiteTree::onBeforeWrite()
	 * @see Product::onAfterWrite()
	 */
  function onBeforeWrite() {
    parent::onBeforeWrite();
    if (!$this->ID) $this->firstWrite = true;
    
    //If the ParentID is set to a ProductCategory, select that category for this Product
    $parent = $this->getParent();
    if ($parent && $parent instanceof ProductCategory) {
      $productCategories = $this->ProductCategories();
      if (!in_array($parent->ID, array_keys($productCategories->map()))) {
        $productCategories->add($parent);
      }
    }
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
	 * Set the currency for all products. Must match site curency.
	 * TODO set currency for entire site in central location
	 * 
	 * @param Array $currency
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
    
	/**
	 * Set some CMS fields for managing Product images, Variations, Options, Attributes etc.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldSet
	 */
	public function getCMSFields() {
    $fields = parent::getCMSFields();
    
    //Gallery
    $manager = new ComplexTableField(
      $this,
      'Images',
      'ProductImage',
      array(
        'SummaryOfImage' => 'Thumbnail',
        'Caption' => 'Caption'
      ),
      'getCMSFields_forPopup'
    );
    $manager->setPopupSize(650, 400);
    $fields->addFieldToTab("Root.Content.Gallery", new HeaderField(
    	'GalleryHeading', 
    	'Add images for this product, the first image will be used as a thumbnail',
      3
    ));
    $fields->addFieldToTab("Root.Content.Gallery", $manager);
    
    
    //Product fields
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(self::$allowed_currency);	
		$fields->addFieldToTab('Root.Content.Main', $amountField, 'Content');
		
		$fields->addFieldToTab('Root.Content.Main', new StockField('Stock'), 'Content');
		
		//Product categories
    $manager = new BelongsManyManyComplexTableField(
      $this,
      'ProductCategories',
      'ProductCategory',
      array(),
      'getCMSFields_forPopup'
    );
    $manager->setPermissions(array());
    $fields->addFieldToTab("Root.Content.Categories", new HeaderField(
    	'CategoriesHeading', 
    	'Select categories you would like this product to appear in',
      3
    ));
    $fields->addFieldToTab("Root.Content.Categories", $manager);
		
		//Attributes selection
		$anyAttribute = DataObject::get_one('Attribute');
		if ($anyAttribute && $anyAttribute->exists()) {
  		$tablefield = new ManyManyComplexTableField(
        $this,
        'Attributes',
        'Attribute',
        array(
        	'Title' => 'Title',
        	'Description' => 'Description'
        ),
        'getCMSFields'
      );
      $tablefield->setPermissions(array());
      $fields->addFieldToTab("Root.Content.Attributes", new HeaderField(
      	'AttributeHeading', 
      	'Select attributes for this product',
        3
      ));
      $attributeHelp = <<<EOS
<p class="ProductHelp">
Once attributes are selected don't forget to save. 
Always make sure there are options for each attribute and variations which are enabled and have 
an option selected for each attribute.
</p>
EOS;
      $fields->addFieldToTab("Root.Content.Attributes", new LiteralField('AttributeHelp', $attributeHelp));
      
      $attributeAlert = <<<EOS
<p id="AttributeAlert" class="message good">
Please 'Save' after you have finished changing attributes and check that product variations are correct.
</p>
EOS;
      $fields->addFieldToTab("Root.Content.Attributes", new LiteralField('AttributeAlert', $attributeAlert));
      
      $fields->addFieldToTab("Root.Content.Attributes", $tablefield);
		}

    //Options selection
    $attributes = $this->Attributes();
    if ($attributes && $attributes->exists()) {
      
      //Remove the stock level field if there are variations, each variation has a stock field
      $fields->removeByName('Stock');
      
      $variationFieldList = array();
      
      $fields->addFieldToTab("Root.Content", new TabSet('Options'));
      $fields->addFieldToTab("Root.Content", new Tab('Variations'));
      
      foreach ($attributes as $attribute) {

        $variationFieldList['AttributeValue_'.$attribute->ID] = $attribute->Title;

        //TODO refactor, this is a really dumb place to be writing default options probably
        
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

        $attributeTabName = str_replace(' ', '', $attribute->Title);
        $fields->addFieldToTab("Root.Content.Options", new Tab($attributeTabName));
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
        $fields->addFieldToTab("Root.Content.Options.".$attributeTabName, $manager);
      }

      $variationFieldList['SummaryOfStock'] = 'Stock';
      $variationFieldList['SummaryOfPrice'] = 'Price Difference';
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
    
    return $fields;
	}

  /**
   * Hack to set Amount field in the array of database fields for this Product.
   * Helps to ensure a new version is created when Amount (type of {@link Money}) is changed
   * on a Product.
   * 
   * @see DataObject::inheritedDatabaseFields()
   * @return Array
   */
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
   * Duplicate product images, useful when duplicating a product. 
   * 
   * @see Product::onAfterWrite()
   * @param DataObjectSet $images
   */
  protected function duplicateProductImages(DataObjectSet $images) {
    
    foreach ($images as $productImage) {
      $newImage = $productImage->duplicate(false);
      $newImage->ProductID = $this->ID;
      $newImage->write();
    }
  }
  
  /**
   * Get the first Image of all Images attached to this Product.
   * 
   * @return Image
   */
  public function FirstImage() {
    $images = $this->Images();
    $images->sort('SortOrder', 'ASC');
    return $images->First();
  }
	
	/**
	 * Summary of product categories for convenience, categories are comma seperated.
	 * 
	 * @return String
	 */
	function SummaryOfCategories() {
	  $summary = array();
	  $categories = $this->ProductCategories();
	  
	  if ($categories) foreach ($categories as $productCategory) {
	    $summary[] = $productCategory->Title;
	  } 
	  
	  return implode(', ', $summary);
	}
	
	/**
	 * Get the URL for this Product, products that are not part of the SiteTree are 
	 * displayed by the {@link Product_Controller}.
	 * 
	 * @see SiteTree::Link()
	 * @see Product_Controller::show()
	 * @return String
	 */
	function Link($action = null) {
	  
	  if ($this->ParentID > -1) {
	    //return Controller::join_links(Director::baseURL() . 'product/', $this->URLSegment .'/');
	    return parent::Link($action);
	  }
	  return Controller::join_links(Director::baseURL() . 'product/', $this->RelativeLink($action));
	}
	
	/**
   * A product is required to be added to a cart with a variation if it has attributes.
   * A product with attributes needs to have some enabled {@link Variation}s
   * 
   * @return Boolean
   */
  public function requiresVariation() {
    $attributes = $this->Attributes();
    return $attributes && $attributes->exists();
  }
  
  /**
   * Get options for an Attribute of this Product.
   * 
   * @param Int $attributeID
   * @return DataObjectSet
   */
  public function getOptionsForAttribute($attributeID) {
    
    $options = new DataObjectSet();
    $variations = $this->Variations();
    
    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      if ($variation->isEnabled()) {
        $option = $variation->getOptionForAttribute($attributeID);
        if ($option) $options->push($option); 
      }
    }
    return $options;
  }
  
	/**
   * Validate the Product before it is saved in {@link ShopAdmin}.
   * 
   * @see DataObject::validate()
   * @return ValidationResult
   */
  public function validate() {
    
    $result = new ValidationResult(); 

    //If this is being published, check that enabled variations exist if they are required
    $request = Controller::curr()->getRequest();
    $publishing = ($request && $request->getVar('action_publish')) ? true : false;
    
    if ($publishing && $this->requiresVariation()) {
      
      $variations = $this->Variations();
      
      if (!in_array('Enabled', $variations->map('ID', 'Status'))) {
  		  $result->error(
  	      'Cannot publish product when no variations are enabled. Please enable some product variations and try again.',
  	      'VariationsDisabledError'
  	    );
  		}
    }
    return $result;
	}
	
	/**
	 * Set custom validator for validating EditForm in {@link ShopAdmin}. Not currently used.
	 * 
	 * TODO could use this custom validator to check variations perhaps
	 * 
	 * @return ProductAdminValidator
	 */
	function getCMSValidator() {
	  return new ProductAdminValidator();
	}
	
	/**
	 * Summary of price for convenience
	 * 
	 * @return String Amount formatted with Nice()
	 */
	function SummaryOfPrice() {
	  return $this->Amount->Nice();
	}
	
  public function replenishStockWith($quantity) {
	  //get the latest version of this product and increase the stock by quantity
	}

}

/**
 * Displays a product, add to cart form, gets options and variation price for a {@link Product} 
 * via AJAX.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage product
 * @version 1.0
 */
class Product_Controller extends Page_Controller {
  
  /**
   * Allowed actions for this controller
   * 
   * @var Array
   */
  public static $allowed_actions = array (
  	'add',
    'options',
    'AddToCartForm',
    'variationprice',
    'index'
  );

  /**
   * URL handlers to redirect URLs of the type /product/[Product URL Segment]
   * to the correct actions. As well as directing norman nested URLs to the same
   * actions. This is so that Products without a ParentID (not part of the site tree) 
   * can be accessed from a nicely formatted generic URL.
   * 
   * @see Product::Link()
   * @var Array
   */
  public static $url_handlers = array( 
    '' => 'index',
  	'AddToCartForm' => 'AddToCartForm',
    'add' => 'add',
  	'options' => 'options',
    'variationprice' => 'variationprice',
  	
    '$ID!/AddToCartForm' => 'AddToCartForm',
    '$ID!/add' => 'add',
    '$ID/options' => 'options',
    '$ID/variationprice' => 'variationprice',
  	'$ID!' => 'index',
  );
  
  /**
   * Include some CSS and set the dataRecord to the current Product that is being viewed.
   * 
   * @see Page_Controller::init()
   */
  function init() {
    parent::init();
    
    Requirements::css('shop/css/Shop.css');
    
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
          
          $this->customise(array(
            'Product' => $this->data()
          ));
        }
      }
    }
  }
  
  /**
   * Display a {@link Product}.
   * 
   * @param SS_HTTPRequest $request
   */
  function index(SS_HTTPRequest $request) {

    $product = $this->data();

    if ($product && $product->exists()) {
      $data = array(
      	'Product' => $product,
        'Content' => $this->Content, 
       	'Form' => $this->AddToCartForm() 
      );
      return $this->Customise($data)->renderWith(array('Product','Page'));
      
      /*
      $ssv = new SSViewer("Page"); 
      $ssv->setTemplateFile("Layout", "Product_show"); 
      return $this->Customise($data)->renderWith($ssv); 
      */
    }
    else {
      return $this->httpError(404, 'Sorry that product could not be found');
    }
  }
  
	/**
   * Add to cart form for adding Products, to show on the Product page.
   * 
   * @param Int $quantity
   * @param String $redirectURL A URL to redirect to after the product is added, useful to redirect to cart page
   */
  function AddToCartForm($quantity = null, $redirectURL = null) {
    
    $product = $this->data();
    
    $fields = new FieldSet(
      new HiddenField('ProductClass', 'ProductClass', $product->ClassName),
      new HiddenField('ProductID', 'ProductID', $product->ID),
      //new HiddenField('ProductVariationID', 'ProductVariationID', 0),
      new HiddenField('Redirect', 'Redirect', $redirectURL)
    );

    //Get the options for this product
    $optionGroupField = new OptionGroupField('OptionGroup', $product);
    $fields->push($optionGroupField);
    
    $fields->push(new QuantityField('Quantity', 'Quantity', $quantity));
    
    $actions = new FieldSet(
      new FormAction('add', 'Add To Cart')
    );
    $validator = new AddToCartFormValidator(
    	'ProductClass', 
    	'ProductID',
      'Quantity'
    );
    $validator->setJavascriptValidationHandler('none'); 
    
    $controller = Controller::curr();
    $form = new AddToCartForm($controller, 'AddToCartForm', $fields, $actions, $validator);
    $form->disableSecurityToken();
    
    //Change the action to accommodate product pages not in the site tree (ParentID = -1)
	  //$form->setFormAction('/product/'.$this->URLSegment.'/add');
    
    return $form;
	}
  
	/**
	 * Add an item to the current cart ({@link Order}) for a given {@link Product}.
	 * 
	 * @param Array $data
	 * @param Form $form
	 */
  function add(Array $data, Form $form) {
    CartControllerExtension::get_current_order()->addItem($this->getProduct(), $this->getQuantity(), $this->getProductOptions());
    
    $form->sessionMessage(
			'The product was added to your cart.',
			'good'
		);
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
   * Get options for a product and return for use in the form
   * Must get options for nextAttributeID, but these options should be filtered so 
   * that only the options for the variations that match attributeID and optionID
   * are returned.
   * 
   * In other words, do not just return options for a product, return options for product
   * variations.
   * 
   * Usually called via AJAX.
   * 
   * @param SS_HTTPRequest $request
   * @return String JSON encoded string for use to update options in select fields on Product page
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
        $attributeOption = $variation->getOptionForAttribute($attributeID);
        if ($attributeOption && $attributeOption->ID == $optionID) $variationOptions[$attributeID] = $optionID;
      }
      
      if ($variationOptions == $attributeOptions && $variation->isEnabled()) {
        $filteredVariations->push($variation);
      }
    }
    
    //Find options in filtered variations that match next attribute ID
    //All variations must have options for all attributes so this is belt and braces really
    if ($filteredVariations && $filteredVariations->exists()) foreach ($filteredVariations as $variation) {
      $attributeOption = $variation->getOptionForAttribute($nextAttributeID);
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
   * Calculate the {@link Variation} price difference based on current request. 
   * Current seleted options are passed in POST vars, if a matching Variation can 
   * be found, the price difference of that Variation is returned for display on the Product 
   * page.
   * 
   * TODO return the total here as well
   * 
   * @param SS_HTTPRequest $request
   * @return String JSON encoded string of price difference
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
    
    $data['totalPrice'] = $product->Amount->Nice();
    
    if ($variation) {

      if ($variation->Amount->getAmount() == 0) {
        $data['priceDifference'] = 0;
      }
      else if ($variation->Amount->getAmount() > 0) {
        $data['priceDifference'] = '(+' . $variation->Amount->Nice() . ')';
        $newTotal = new Money();
        $newTotal->setCurrency($product->Amount->getCurrency());
        $newTotal->setAmount($product->Amount->getAmount() + $variation->Amount->getAmount());
        $data['totalPrice'] = $newTotal->Nice();
      }
      else { //Variations have been changed so only positive values, so this is unnecessary
        //$data['priceDifference'] = '(' . $variation->Amount->Nice() . ')';
      }
    }

    return json_encode($data);
  }
}