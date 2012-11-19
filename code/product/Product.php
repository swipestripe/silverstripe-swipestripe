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
 * @package swipestripe
 * @subpackage product
 */
class Product extends Page {
  
  /**
   * Flag for denoting if this is the first time this Product is being written.
   * 
   * @var Boolean
   */
  protected $firstWrite = false;

  /**
   * DB fields for Product.
   * 
   * @var Array
   */
  public static $db = array(
    'Price' => 'Decimal(19,4)',
    'Currency' => 'Varchar(3)'
  );

  /**
   * Actual price in base currency, can decorate to apply discounts etc.
   * 
   * @return Price
   */
  public function Amount() {

    // TODO: Multi currency
  	$shopConfig = ShopConfig::current_shop_config();

    $amount = new Price();
    $amount->setAmount($this->Price);
    $amount->setCurrency($shopConfig->BaseCurrency);
    $amount->setSymbol($shopConfig->BaseCurrencySymbol);

    //Transform amount for applying discounts etc.
    $this->extend('updateAmount', $amount);

    return $amount;
  }

  /**
   * Display price, can decorate for multiple currency etc.
   * 
   * @return Price
   */
  public function Price() {
    
    $amount = $this->Amount();

    //Transform price here for display in different currencies etc.
    $this->extend('updatePrice', $amount);

    return $amount;
  }
  
  /**
   * Has one relations for Product
   * 
   * @var Array
   */
  public static $has_one = array(
    'StockLevel' => 'StockLevel'
  );

  /**
   * Has many relations for Product.
   * 
   * @var Array
   */
  public static $has_many = array(
    'Images' => 'Product_Image',
    'Attributes' => 'Attribute',
    'Options' => 'Option',
    'Variations' => 'Variation'
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
    'SummaryOfImage' => 'Image',
    'SummaryOfPrice' => 'Price',
	  'Title' => 'Title'
	);

  public static $searchable_fields = array(
    'Title' => array(
      'field' => 'TextField',
      'filter' => 'PartialMatchFilter',
      'title' => 'Name'
    )
  );

	/**
	 * Set firstWrite flag if this is the first time this Product is written.
	 * 
	 * @see SiteTree::onBeforeWrite()
	 * @see Product::onAfterWrite()
	 */
  public function onBeforeWrite() {
    parent::onBeforeWrite();
    if (!$this->ID) $this->firstWrite = true;

    //Save in base currency
    $shopConfig = ShopConfig::current_shop_config();
    $this->Currency = $shopConfig->BaseCurrency;
    
    //If a stock level is set then update StockLevel
    $request = Controller::curr()->getRequest();
    if ($request) {
      $newLevel = $request->requestVar('Stock');
      if (isset($newLevel)) {
        $stockLevel = $this->StockLevel();
        $stockLevel->Level = $newLevel;
        $stockLevel->write();
        $this->StockLevelID = $stockLevel->ID;
      }
    }
  }
  
	/**
   * Copy the original product options or generate the default product 
   * options
   * 
   * @see SiteTree::onAfterWrite()
   */
  public function onAfterWrite() {
    parent::onAfterWrite();

    if ($this->firstWrite) {
      
      //TODO Make sure there is a StockLevel for this product by default
      
      //Copy product images across when duplicating product
      $original = DataObject::get_by_id($this->class, $this->original['ID']);
      if ($original) {
        foreach ($original->Images() as $productImage) {
          $newImage = $productImage->duplicate(false);
          $newImage->ProductID = $this->ID;
          $newImage->write();
        }
      }
    }
  }
	
	/**
	 * Unpublish products if they get deleted, such as in product admin area
	 * 
	 * @see SiteTree::onAfterDelete()
	 */
  public function onAfterDelete() {
    parent::onAfterDelete();
  
    if ($this->isPublished()) {
      $this->doUnpublish();
    }
  }
    
	/**
	 * Set some CMS fields for managing Product images, Variations, Options, Attributes etc.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	public function getCMSFields() {
    
    $shopConfig = ShopConfig::current_shop_config();
    $fields = parent::getCMSFields();

    //Product fields
    $fields->addFieldToTab('Root.Main', new PriceField('Price'), 'Content');

		//Stock level field
    if ($shopConfig->StockCheck) {
      $level = $this->StockLevel()->Level;
      //$fields->addFieldToTab('Root.Main', new StockField('Stock', null, $level, $this), 'Content');
      $fields->addFieldToTab('Root.Main', new Hiddenfield('Stock', null, -1), 'Content');
    }
		else {
      $fields->addFieldToTab('Root.Main', new Hiddenfield('Stock', null, -1), 'Content');
    }

    //Replace URL Segment field
    if ($this->ParentID == -1) {
    	$urlsegment = new SiteTreeURLSegmentField("URLSegment", 'URLSegment');
	    $baseLink = Controller::join_links(Director::absoluteBaseURL(), 'product/');
	    $url = (strlen($baseLink) > 36) ? "..." .substr($baseLink, -32) : $baseLink;
	    $urlsegment->setURLPrefix($url);
	    $fields->replaceField('URLSegment', $urlsegment);
    }

    if ($this->isInDB()) {

    	//Gallery
  		$fields->addFieldToTab('Root.Gallery', ProductImageUploadField::create('Images', ''));

	    //Product attributes
	    $listField = new GridField(
	      'Attributes',
	      'Attributes',
	      $this->Attributes(),
	      GridFieldConfig_BasicSortable::create()
	    );
	    $fields->addFieldToTab('Root.Attributes', $listField);

	    //Product variations
	    $attributes = $this->Attributes();
	    if ($attributes && $attributes->exists()) {
	      
	      //Remove the stock level field if there are variations, each variation has a stock field
	      $fields->removeByName('Stock');
	      
	      $variationFieldList = array();
	      foreach ($attributes as $attribute) {
	        $variationFieldList['AttributeValue_'.$attribute->ID] = $attribute->Title;
	      }
	      $variationFieldList = array_merge($variationFieldList, singleton('Variation')->summaryFields());

	      $config = GridFieldConfig_HasManyRelationEditor::create();
	      $dataColumns = $config->getComponentByType('GridFieldDataColumns');
	      $dataColumns->setDisplayFields($variationFieldList);

	      $listField = new GridField(
	        'Variations',
	        'Variations',
	        $this->Variations(),
	        $config
	      );
	      $fields->addFieldToTab('Root.Variations', $listField);
	    }
    }

    //Ability to edit fields added to CMS here
    $this->extend('updateProductCMSFields', $fields);

    if ($warning = ShopConfig::licence_key_warning()) {
      $fields->addFieldToTab('Root.Main', new LiteralField('SwipeStripeLicenseWarning', 
        '<p class="message warning">'.$warning.'</p>'
      ), 'Title');
    }

    if ($warning = ShopConfig::base_currency_warning()) {
      $fields->addFieldToTab('Root.Main', new LiteralField('BaseCurrencyWarning', 
	      '<p class="message warning">'.$warning.'</p>'
	    ), 'Title');
    }
    
    return $fields;
	}

	public function getSettingsFields() {
		$fields = parent::getSettingsFields();

		$parentTypeField = $fields->dataFieldByName('ParentType');
	  if ($parentTypeField && $parentTypeField->exists() && $parentTypeField instanceof OptionsetField) {
	    // $source = $parentTypeField->getSource();
	    // $source['exempt'] = _t('ShopAdmin.NOT_PART_OF_SITE_TREE',"Not part of the site tree");
	    $source = array(
				"root" => _t("SiteTree.PARENTTYPE_ROOT", "Top-level page"),
				"exempt" => _t('ShopAdmin.NOT_PART_OF_SITE_TREE',"Not part of the site tree"),
				"subpage" => _t("SiteTree.PARENTTYPE_SUBPAGE", "Sub-page underneath a parent page"),
			);
	    $parentTypeField->setSource($source);
	    $parentTypeField->setValue($this->getParentType());
	  }

	  Requirements::customScript("
	  	(function($) {
				$(document).ready(function() {
					$('#Form_EditForm_ParentType_exempt').live('click', function(e){
						e.stopPropagation();
						$('#Form_EditForm_ParentID').val(-1);
						$('#ParentID').css('display', 'none');
					});
	  			if ($('#Form_EditForm_ParentID').val() == -1) {
	  				$('#Form_EditForm_ParentType_exempt').click();
	  			}
				});
			})(jQuery);
	  ");

		return $fields;
	}

  /**
   * Set custom validator for validating EditForm in {@link ShopAdmin}. Not currently used.
   * 
   * TODO could use this custom validator to check variations perhaps
   * 
   * @return ProductAdminValidator
   */
  public function getCMSValidator() {
    return new ProductAdminValidator();
  }
  
  /**
   * Get the first Image of all Images attached to this Product.
   * 
   * @return Image
   */
  public function FirstImage() {
    return $this->Images()->First();
  }

  public function SummaryOfImage() {
  	if ($image = $this->FirstImage()) return $image->CMSThumbnail();
    else return '(No Image)';
  }
	
	/**
	 * Get the URL for this Product, products that are not part of the SiteTree are 
	 * displayed by the {@link Product_Controller}.
	 * 
	 * @see SiteTree::Link()
	 * @see Product_Controller::show()
	 * @return String
	 */
  public function Link($action = null) {
	  
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
   * @return ArrayList
   */
  public function getOptionsForAttribute($attributeID) {

    $options = new ArrayList();
    $variations = $this->Variations();

    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      if ($variation->isEnabled() && $variation->InStock()) {
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
      
      if (!in_array('Enabled', $variations->map('ID', 'Status')->toArray())) {
  		  $result->error(
  	      'Cannot publish product when no variations are enabled. Please enable some product variations and try again.',
  	      'VariationsDisabledError'
  	    );
  		}
    }
    return $result;
	}
	
	/**
	 * Summary of price for convenience
	 * 
	 * @return String Amount formatted with Nice()
	 */
  public function SummaryOfPrice() {
	  return $this->Amount()->Nice();
	}

	/**
	 * Get parent type for Product, extra parent type of exempt where the product is not
	 * part of the site tree (instead associated to product categories).
	 * 
	 * @see SiteTree::getParentType()
	 * @return String Returns root, exempt or subpage
	 */
  public function getParentType() {
    $parentType = null;
    if ($this->ParentID == 0) {
      $parentType = 'root';
    }
    else if ($this->ParentID == -1) {
      $parentType = 'exempt';
    }
    else {
      $parentType = 'subpage';
    }
    return $parentType;
	}

  /**
   * Update the stock level for this {@link Product}. A negative quantity is passed 
   * when product is added to a cart, a positive quantity when product is removed from a 
   * cart.
   * 
   * @param Int $quantity
   * @return Void
   */
  public function updateStockBy($quantity) {
    $stockLevel = $this->StockLevel();

    //Do not change stock level if it is already set to unlimited (-1)
    if ($stockLevel->Level != -1) {
      $stockLevel->Level += $quantity;
      if ($stockLevel->Level < 0) $stockLevel->Level = 0;
      $stockLevel->write();
    }
  }
	
	/**
	 * Product is in stock if stock level for product is != 0 or if ANY of its product
	 * variations is in stock.
	 * 
	 * @return Boolean 
	 */
	public function InStock() {
	  //if has variations, check if any variations in stock
	  //else check if this is in stock
	  $inStock = false;
	  if ($this->requiresVariation()) {

	    //Check variations for stock levels
	    $variations = $this->Variations();
	    if ($variations && $variations->exists()) foreach ($variations as $variation) {
	      //If there is a single variation in stock, then this product is in stock
	      if ($variation->InStock() && $variation->isEnabled()) {
	        $inStock = true;
	        continue;
	      } 
	    }
	    else {
	      $inStock = false;
	    }
	  }
	  else {
	    $stockLevel = $this->StockLevel();
	    if ($stockLevel && $stockLevel->exists() && $stockLevel->Level != 0) {
	      $inStock = true;
	    }
	  }
	  return $inStock;
	}
	
	/**
	 * Get the quantity of this product that is currently in shopping carts
	 * or unprocessed orders
	 * 
	 * @return Array Number in carts and number in orders
	 */
  public function getUnprocessedQuantity() {
	  
	  //Get items with this objectID/objectClass (nevermind the version)
	  //where the order status is either cart, pending or processing
	  $objectID = $this->ID;
	  $objectClass = $this->class;
	  $totalQuantity = array(
	    'InCarts' => 0,
	    'InOrders' => 0
	  );

	  //TODO refactor using COUNT(Item.Quantity)
    /*
	  $items = DataObject::get(
	  	'Item', 
	    "\"Item\".\"ObjectID\" = $objectID AND \"Item\".\"ObjectClass\" = '$objectClass' AND \"Order\".\"Status\" IN ('Cart','Pending','Processing')",
	    '',
	    "INNER JOIN \"Order\" ON \"Order\".\"ID\" = \"Item\".\"OrderID\""
	  );
    */

    $items = Item::get()
      ->where("\"Item\".\"ObjectID\" = $objectID AND \"Item\".\"ObjectClass\" = '$objectClass' AND \"Order\".\"Status\" IN ('Cart','Pending','Processing')")
      ->innerJoin('Order', "\"Order\".\"ID\" = \"Item\".\"OrderID\"");
	  
	  if ($items && $items->exists()) foreach ($items as $item) {
	    if ($item->Order()->Status == 'Cart') $totalQuantity['InCarts'] += $item->Quantity;
	    else $totalQuantity['InOrders'] += $item->Quantity;
	  }
	  return $totalQuantity;
	}
}

/**
 * Displays a product, add to cart form, gets options and variation price for a {@link Product} 
 * via AJAX.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
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
    'index',
    'SearchForm',
    'results',
  );

  /**
   * URL handlers to redirect URLs of the type /product/[Product URL Segment]
   * to the correct actions. As well as directing normal nested URLs to the same
   * actions. This is so that Products without a ParentID (not part of the site tree) 
   * can be accessed from a nicely formatted generic URL.
   * 
   * @see Product::Link()
   * @var Array
   */
  public static $url_handlers = array( 
  	'$ID!/$Action/$OtherID' => 'handleAction',
    '$ID!' => 'index',
  );
  
  /**
   * Include some CSS and set the dataRecord to the current Product that is being viewed.
   * 
   * @see Page_Controller::init()
   */
  public function init() {
    parent::init();
    
    Requirements::css('swipestripe/css/Shop.css');
    
    //Get current product page for products that are not part of the site tree
    //and do not have a ParentID set, they are accessed via this controller using
    //Director rules
    if ($this->dataRecord->ID == -1) {
      
      $params = $this->getURLParams();
      
      if ($urlSegment = Convert::raw2sql($params['ID'])) {

        $product = Product::get()
        	->where("\"URLSegment\" = '$urlSegment'")
        	->limit(1)
        	->first();
        
        if ($product && $product->exists()) {
          $this->dataRecord = $product; 
          $this->failover = $this->dataRecord;
          
          $this->customise(array(
            'Product' => $this->data()
          ));
        }
      }
    }
    
    $this->extend('onInit');
  }
  
  /**
   * Display a {@link Product}.
   * 
   * @param SS_HTTPRequest $request
   */
  public function index(SS_HTTPRequest $request) {

    //Update stock levels before displaying product
    Order::delete_abandoned();

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
  public function AddToCartForm($quantity = null, $redirectURL = null) {
    
    $product = $this->data();

    $fields = new FieldList(
      new HiddenField('ProductClass', 'ProductClass', $product->ClassName),
      new HiddenField('ProductID', 'ProductID', $product->ID),
      new HiddenField('Redirect', 'Redirect', $redirectURL),
      new OptionGroupField('OptionGroup', $product),
      new QuantityField('Quantity', 'Quantity', $quantity)
    );
    
    $actions = new FieldList(
      new FormAction('add', 'Add To Cart')
    );

    $validator = new AddToCartFormValidator(
    	'ProductClass', 
    	'ProductID',
      'Quantity'
    );

    //Disable add to cart function when product out of stock
    if (!$product->InStock()) {
      $fields = new FieldList(new LiteralField('ProductNotInStock', '<p class="message">Sorry this product is currently out of stock. Please check back soon.</p>'));
      $actions = new FieldList();
    }
    
    $controller = Controller::curr();
    $form = new AddToCartForm($controller, 'AddToCartForm', $fields, $actions, $validator);
    $form->disableSecurityToken();

    $this->extend('updateAddToCartForm', $form);

    return $form;
	}
  
	/**
	 * Add an item to the current cart ({@link Order}) for a given {@link Product}.
	 * 
	 * @param Array $data
	 * @param Form $form
	 */
  public function add(Array $data, Form $form) {

    Cart::get_current_order(true)->addItem($this->getProduct(), $this->getVariation(), $this->getQuantity(), $this->getOptions());
    
    //Show feedback if redirecting back to the Product page
    if (!$this->getRequest()->requestVar('Redirect')) {
      $cartPage = DataObject::get_one('CartPage');
      $message = ($cartPage) 
        ? 'The product was added to <a href="' . $cartPage->Link() . '">your cart</a>.'
        : "The product was added to your cart.";
      $form->sessionMessage(
  			$message,
  			'good'
  		);
    }
    $this->goToNextPage();
  }
  
	/**
   * Find a product based on current request - maybe shoul dbe deprecated?
   * 
   * @see SS_HTTPRequest
   * @return DataObject 
   */
  private function getProduct() {
    $request = $this->getRequest();
    return DataObject::get_by_id($request->requestVar('ProductClass'), $request->requestVar('ProductID'));
  }

  private function getVariation() {

    $productVariation = new Variation();
    $request = $this->getRequest();
    $options = $request->requestVar('Options');
    $product = $this->data();
    $variations = $product->Variations();

    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      $variationOptions = $variation->Options()->map('AttributeID', 'ID')->toArray();
      if ($options == $variationOptions && $variation->isEnabled()) {
        $productVariation = $variation;
      }
    }

    return $productVariation;
  }

  /**
   * Find the quantity based on current request
   * 
   * @return Int
   */
  private function getQuantity() {
    $quantity = $this->getRequest()->requestVar('Quantity');
    return (isset($quantity)) ? $quantity : 1;
  }

  private function getOptions() {

    $options = new ArrayList();
    $this->extend('updateOptions', $options);
    return $options;
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
    else $this->redirectBack();
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
    $options = new ArrayList();
    $variations = $product->Variations();
    $filteredVariations = new ArrayList();
    
    $attributeOptions = $request->postVar('Options');
    $nextAttributeID = $request->postVar('NextAttributeID');
    
    //Filter variations to match attribute ID and option ID
    //Variations need to have the same option for each attribute ID in POST data to be considered
    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      $variationOptions = array();
      if ($attributeOptions && is_array($attributeOptions)) {
        foreach ($attributeOptions as $attributeID => $optionID) {
          
          //Get option for attribute ID, if this variation has options for every attribute in the array then add it to filtered
          $attributeOption = $variation->getOptionForAttribute($attributeID);
          if ($attributeOption && $attributeOption->ID == $optionID) $variationOptions[$attributeID] = $optionID;
        }
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
  public function variationprice(SS_HTTPRequest $request) {
    
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
    
    $productPrice = $product->Price();
    
    if ($variation) {

      if ($variation->Amount()->getAmount() == 0) {
        $data['priceDifference'] = 0;
      }
      else if ($variation->Amount()->getAmount() > 0) {
        $data['priceDifference'] = '(+' . $variation->Amount()->Nice() . ')';

        // TODO: Multi currency

        $variationPrice = $variation->Price();
        $productPrice->setAmount($productPrice->getAmount() + $variationPrice->getAmount());
      }
    }

    $data['totalPrice'] = $productPrice->Nice();
    return json_encode($data);
  }
}

/**
 * A image for {@link Product}s.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 */

class Product_Image extends Image {

	public static $singular_name = 'Image';
  public static $plural_name = 'Images';

	static $db = array (
    'Caption' => 'Text',
    'SortOrder' => 'Int'
  );

	static $has_one = array (
    'Product' => 'Product'
  );

  public static $default_sort = 'SortOrder';

  public function getCMSFields() {

  	$fields = parent::getCMSFields();

  	$fileAttributes = $fields->fieldByName('Root.Main.FilePreview')->fieldByName('FilePreviewData');
  	$fileAttributes->push(TextareaField::create('Caption', 'Caption:')->setRows(4));

  	//$fields->addFieldToTab('Root.Main', HiddenField::create('SortOrder'));
  	$fields->removeFieldsFromTab('Root.Main', array(
  		'Title',
  		'Name',
  		'OwnerID',
  		'ParentID',
  		'Created',
  		'LastEdited',
  		'BackLinkCount',
  		'Di'
  	));
  	return $fields;
  }
}


