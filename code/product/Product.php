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
    'FirstImage.CMSThumbnail' => 'Image',
    'Amount.Nice' => 'Price',
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
  
  /**
   * Get the first Image of all Images attached to this Product.
   * 
   * @return Image
   */
  public function FirstImage() {
    return $this->Images()->First();
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
      
      if (!in_array('Enabled', $variations->map('ID', 'Status')->toArray())) {
  		  $result->error(
  	      'Cannot publish product when no variations are enabled. Please enable some product variations and try again.',
  	      'VariationsDisabledError'
  	    );
  		}
    }
    return $result;
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
  	'index',
  	'ProductForm'
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
    //Order::delete_abandoned();

    $product = $this->data();

    if ($product && $product->exists()) {
      $data = array(
      	'Product' => $product,
        'Content' => $this->Content, 
       	'Form' => $this->ProductForm() 
      );
      return $this->Customise($data)->renderWith(array('Product','Page'));
    }
    else {
      return $this->httpError(404, 'Sorry that product could not be found');
    }
  }

  public function ProductForm($quantity = null, $redirectURL = null) {

  	return ProductForm::create(
  		$this,
  		'ProductForm',
  		$quantity,
  		$redirectURL
  	)->disableSecurityToken();
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


