<?php
/**
 * Represents a Product, which is a type of a {@link Page}. Products are managed in a seperate
 * admin area {@link ShopAdmin}. A product can have {@link Variation}s, in fact if a Product
 * has attributes (e.g Size, Color) then it must have Variations. Products are Versioned so that
 * when a Product is added to an Order, then subsequently changed, the Order can get the correct
 * details about the Product.
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
	private static $db = array(
		'Price' => 'Decimal(19,8)',
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

		$amount = Price::create();
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
	private static $has_many = array(
		'Attributes' => 'Attribute',
		'Options' => 'Option',
		'Variations' => 'Variation'
	);
	
	/**
	 * Defaults for Product
	 * 
	 * @var Array
	 */
	private static $defaults = array(
		'ParentID' => -1
	);
	
	/**
	 * Summary fields for displaying Products in the CMS
	 * 
	 * @var Array
	 */
	private static $summary_fields = array(
		'Amount.Nice' => 'Price',
		'Title' => 'Title'
	);

	private static $searchable_fields = array(
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
	 * Set some CMS fields for managing Products
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

		if ($warning = ShopConfig::base_currency_warning()) {
			$fields->addFieldToTab('Root.Main', new LiteralField('BaseCurrencyWarning', 
				'<p class="message warning">'.$warning.'</p>'
			), 'Title');
		}
		
		return $fields;
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
		
		$this->extend('updaterequiresVariation', $attributes);
		
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
		$options = $options->sort('SortOrder');
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
	private static $allowed_actions = array(
		'ProductForm'
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
	 * Legacy function allowing access to product data via $Product variable in templates
	 */
	public function Product() {
		return $this->data();
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


