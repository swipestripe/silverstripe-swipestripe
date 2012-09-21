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
    'Amount' => 'Money'
    //'Amount' => 'Decimal(19,4)',
    //'Currency' => 'Varchar(3)'
  );
  
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
  
 //  /**
 //   * Summary fields for displaying Products in the CMS
 //   * 
 //   * @var Array
 //   */
 //  public static $summary_fields = array(
 //    'FirstImage' => 'Image',
 //    'SummaryOfPrice' => 'Price',
	//   'Title' => 'Name',
 //    'Status' => 'Status',
 //    'SummaryOfCategories' => 'Categories'
	// );
	
	// /**
 //   * Searchable fields for searching for Products in the CMS
 //   * 
 //   * @var Array
 //   */
	// public static $searchable_fields = array(
	//   'Title' => array(
	// 		'field' => 'TextField',
	// 		'filter' => 'PartialMatchFilter',
	// 		'title' => 'Name'
	// 	),
	// 	'Status' => array(
	// 		'filter' => 'PublishedStatusSearchFilter',
	// 		'title' => 'Status'
	// 	),
	// 	'Category' => array(
 //  		'filter' => 'ProductCategorySearchFilter',
 //  	)
	// );
	
	// /**
 //   * Casting for searchable fields
 //   * 
 //   * @see Product::$searchable_fields
 //   * @var Array
 //   */
	// public static $casting = array(
 //    'Category' => 'Varchar',
	// );
	
	// /**
	//  * Filter for order admin area search.
	//  * 
	//  * @see DataObject::scaffoldSearchFields()
	//  * @return FieldList
	//  */
 //  function scaffoldSearchFields(){
	// 	$fieldSet = parent::scaffoldSearchFields();

	// 	$statusField = new DropdownField('Status', 'Status', array(
	// 	  1 => "published", 
	// 	  2 => "not published"
	// 	));
	// 	$statusField->setHasEmptyDefault(true);
	// 	$fieldSet->push($statusField);
		
	// 	if($categories = DataObject::get('ProductCategory')) {
	// 	  $categories->sort('MenuTitle');
	// 	  $categoryOptions = $categories->map("ID", "MenuTitle")->toArray();
	// 	  //$fieldSet->push(new CheckboxSetField('Category', 'Category', $categoryOptions));
		  
	// 	  $dropDown = new DropdownField('Category', 'Category', $categoryOptions);
	// 	  $dropDown->setHasEmptyDefault(true);
	// 	  $fieldSet->push($dropDown);
	// 	}

	// 	return $fieldSet;
	// }
	
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
    
    //If the ParentID is set to a ProductCategory, select that category for this Product
    $parent = $this->getParent();
    if ($parent && $parent instanceof ProductCategory) {
      $productCategories = $this->ProductCategories();
      if (!in_array($parent->ID, array_keys($productCategories->map()->toArray()))) {
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
      
      //TODO Make sure there is a StockLevel for this product by default
      
      //Copy product images across when duplicating product
      $original = DataObject::get_by_id($this->class, $this->original['ID']);
      if ($original) {
        $images = $original->Images();
        $this->duplicateProductImages($images);
      }
    }

    // TODO: Check the variations are valid again
    //If the variation does not have a complete set of valid options, then disable it
    // $variations = DataObject::get('Variation', "Variation.ProductID = " . $this->ID . " AND Variation.Status = 'Enabled'");

    // if ($variations) foreach ($variations as $variation) {
    //   if (!$variation->hasValidOptions()) {
    //     $variation->Status = 'Disabled';
    //     $variation->write();
    //   }
    // }
    
    $curr = Controller::curr();
    $request = $curr->getRequest();
    if ($request) {
      $categoryOrdering = $request->requestVar('CategoryOrder');
      if ($categoryOrdering && is_array($categoryOrdering)) foreach ($categoryOrdering as $categoryID => $categoryOrder) {

        $productID = $this->ID;
        $query = <<<EOS
UPDATE "ProductCategory_Products" 
SET  "ProductOrder" =  '$categoryOrder' 
WHERE  "ProductCategory_Products"."ProductCategoryID" = $categoryID 
AND "ProductCategory_Products"."ProductID" = $productID 
EOS;
        DB::query($query);
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
	  
    //if (count($currency) && array_key_exists(Payment::site_currency(), $currency)) {

    if (count($currency) && array_key_exists('NZD', $currency)) {
	    self::$allowed_currency = $currency;
	  }
	  else {

	    //user_error("Cannot set allowed currency. Currency must match: ".Payment::site_currency(), E_USER_WARNING);

      user_error("Cannot set allowed currency. Currency must match: ".'NZD', E_USER_WARNING);
	    //TODO return meaningful error to browser in case error not shown
	    return;
	  }
	}
    
	/**
	 * Set some CMS fields for managing Product images, Variations, Options, Attributes etc.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	public function getCMSFields() {
    $fields = parent::getCMSFields();

    //Product fields
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(self::$allowed_currency);	
		$fields->addFieldToTab('Root.Main', $amountField, 'Content');

    $categoriesField = new CategoriesField('ProductCategories', 'Categories', 'ProductCategory');
    $fields->addFieldToTab('Root.Main', $categoriesField, 'Content');
		
		//Stock level field
		$level = $this->StockLevel()->Level;
		//$fields->addFieldToTab('Root.Main', new StockField('Stock', null, $level, $this), 'Content');
    $fields->addFieldToTab('Root.Main', new Textfield('Stock', null, $level), 'Content');

    //Replace URL Segment field
    $urlsegment = new SiteTreeURLSegmentField("URLSegment", 'URLSegment');
    $baseLink = Controller::join_links(Director::absoluteBaseURL(), 'product/');
    $url = (strlen($baseLink) > 36) ? "..." .substr($baseLink, -32) : $baseLink;
    $urlsegment->setURLPrefix($url);
    $fields->replaceField('URLSegment', $urlsegment);

    //Gallery
    $config = GridFieldConfig_RelationEditor::create(10)
      ->addComponent(new GridFieldSortableRows('SortOrder'));
    $fields->addFieldToTab('Root.Gallery', new GridField('Images', 'Images', $this->Images(), $config));

    //Product attributes
    $listField = new GridField(
      'Attributes',
      'Attributes',
      $this->Attributes(),
      GridFieldConfig_HasManyRelationEditor::create()
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

    //Ability to edit fields added to CMS here
    $this->extend('updateProductCMSFields', $fields);
    
    if (file_exists(BASE_PATH . '/swipestripe') && ShopConfig::get_license_key() == null) {
      $fields->addFieldToTab('Root.Main', new LiteralField("SwipeStripeLicenseWarning", 
        '<p class="message warning">
           Warning: You have SwipeStripe installed without a license key. 
           Please <a href="http://swipestripe.com" target="_blank">purchase a license key here</a> before this site goes live.
        </p>'
      ), 'Title');
    }

    return $fields;
    
    //Product ordering
    /*
    $categories = $this->ProductCategories();
    if ($categories && $categories->exists()) {
    
      $fields->addFieldToTab("Root.Content", new Tab('Order'));
      
      $fields->addFieldToTab("Root.Order", new HeaderField(
        	'OrderHeading', 
        	'Set the order of this product in each of it\'s categories',
          3
        ));
        
        $orderHelp = <<<EOS
<p class="ProductHelp">
Products with higher order numbers in each category will appear further at the front of 
that category.
</p>
EOS;
        $fields->addFieldToTab("Root.Order", new LiteralField('OrderHelp', $orderHelp));
      
      foreach ($categories as $category) {

        $categoryTitle = $category->Title;
        $categoryID = $category->ID;
        $productID = $this->ID;
        $sql = <<<EOS
SELECT "ProductOrder" 
FROM  "ProductCategory_Products" 
WHERE "ProductCategoryID" = $categoryID 
AND "ProductID" = $productID 
EOS;
        $query = DB::query($sql);
        $order = $query->value();
        
        $val = ($order) ? $order : 0; 
        $fields->addFieldToTab('Root.Order', new TextField("CategoryOrder[$categoryID]", "Order in $categoryTitle Category", $val));
      } 
    }
    
    return $fields;
    */
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
	 * Removing generic entries for "AmountAmount", "AmountCurrency" because they are ambiguous when two dataobjects have those columns
	 * @see Money::addToQuery()
	 * 
	 * Build a {@link SQLQuery} object to perform the given query.
	 *
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string|array $sort A sort expression to be inserted into the ORDER BY clause. If omitted, self::$default_sort will be used.
	 * @param string|array $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $join A single join clause. This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param boolean $restictClasses Restrict results to only objects of either this class of a subclass of this class
	 * @param string $having A filter to be inserted into the HAVING clause.
	 *
	 * @return SQLQuery Query built.
	 */
	public function buildSQL($filter = "", $sort = "", $limit = "", $join = "", $restrictClasses = true, $having = "") {
	  
	  $query = parent::buildSQL($filter, $sort, $limit, $join, $restrictClasses, $having);

	  if (isset($query->select[0]) 
	      && isset($query->select[1])
	      && isset($query->select[2])
	      && isset($query->select[3])) {
	    unset($query->select[0]);
  	  unset($query->select[1]);
  	  $query->select[0] = $query->select[2];
  	  $query->select[1] = $query->select[3];
  	  unset($query->select[2]);
  	  unset($query->select[3]);
	  }

	  return $query;
	}
  
  /**
   * Duplicate product images, useful when duplicating a product. 
   * 
   * @see Product::onAfterWrite()
   * @param ArrayList $images
   */
  protected function duplicateProductImages(ArrayList $images) {
    
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
    //$images->sort('SortOrder', 'ASC');
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
	 * Get parent type for Product, extra parent type of exempt where the product is not
	 * part of the site tree (instead associated to product categories).
	 * 
	 * @see SiteTree::getParentType()
	 * @return String Returns root, exempt or subpage
	 */
  function getParentType() {
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
	      if ($variation->InStock()) {
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
	function getUnprocessedQuantity() {
	  
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
  	'$ID!/SearchForm' => 'SearchForm',
    '$ID!/results' => 'results',
    '$ID!' => 'index',
  );
  
  /**
   * Include some CSS and set the dataRecord to the current Product that is being viewed.
   * 
   * @see Page_Controller::init()
   */
  function init() {
    parent::init();
    
    Requirements::css('swipestripe/css/Shop.css');
    
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
    
    $this->extend('onInit');
  }
  
  /**
   * Display a {@link Product}.
   * 
   * @param SS_HTTPRequest $request
   */
  function index(SS_HTTPRequest $request) {
    
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
  function AddToCartForm($quantity = null, $redirectURL = null) {
    
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

    //TODO handle js validation
    //$validator->setJavascriptValidationHandler('none'); 
    
    // TODO: Put stock checking back in
    //Disable add to cart function when product out of stock
    // if (!$product->InStock()) {
    //   $fields = new FieldList(new LiteralField('ProductNotInStock', '<p class="message">Sorry this product is currently out of stock. Please check back soon.</p>'));
    //   $actions = new FieldList();
    // }
    
    $controller = Controller::curr();
    $form = new AddToCartForm($controller, 'AddToCartForm', $fields, $actions, $validator);
    $form->disableSecurityToken();

    return $form;
	}
  
	/**
	 * Add an item to the current cart ({@link Order}) for a given {@link Product}.
	 * 
	 * @param Array $data
	 * @param Form $form
	 */
  function add(Array $data, Form $form) {

    Cart::get_current_order(true)->addItem($this->getProduct(), $this->getQuantity(), $this->getProductOptions());
    
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
  
  /**
   * Get product variations based on current request, check that options in request
   * correspond to a variation
   * 
   * @see SS_HTTPRequest
   * @return ArrayList 
   */
  private function getProductOptions() {
    
    $productVariations = new ArrayList();
    $request = $this->getRequest();
    $options = $request->requestVar('Options');
    $product = $this->data();
    $variations = $product->Variations();

    if ($variations && $variations->exists()) foreach ($variations as $variation) {

      $variationOptions = $variation->Options()->map('AttributeID', 'ID')->toArray();
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
    return (isset($quantity)) ? $quantity : 1;
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
      //if ($attributeOptions && is_array($attributeOptions)) {
        foreach ($attributeOptions as $attributeID => $optionID) {
          
          //Get option for attribute ID, if this variation has options for every attribute in the array then add it to filtered
          $attributeOption = $variation->getOptionForAttribute($attributeID);
          if ($attributeOption && $attributeOption->ID == $optionID) $variationOptions[$attributeID] = $optionID;
        }
      //}
      
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

/**
 * A image for {@link Product}s.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 */
class Product_Image extends DataObject {

  public static $singular_name = 'Image';
  public static $plural_name = 'Images';
  
  /**
   * DB fields
   * 
   * @var Array
   */
  static $db = array (
    'Caption' => 'Text',
    'SortOrder' => 'Int'
  );

  /**
   * Has one relations
   * 
   * @var Array
   */
  static $has_one = array (
    'Image' => 'Image',
    'Product' => 'Product'
  );

  static $summary_fields = array(
    'SortOrder' => 'SortOrder',
    'SummaryOfImage' => 'Image',
    'Caption' => 'Caption'
  );

  public static $default_sort = 'SortOrder';

  public function getCMSFields() {
    $fields = parent::getCMSFields();

    if (!$this->ID) {
      $fields->removeByName('Image');
    }
    $fields->removeByName('SortOrder');
    $fields->removeByName('ProductID');

    return $fields;
  }
  
  /**
   * Helper method to return a thumbnail image for displaying in CTF fields in CMS.
   * 
   * @return Image|String If no image can be found returns '(No Image)'
   */
  function SummaryOfImage() {
    if ($Image = $this->Image()) return $Image->CMSThumbnail();
    else return '(No Image)';
  }
}

