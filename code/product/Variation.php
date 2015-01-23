<?php
/**
 * Represents a Variation for a Product. A variation needs to have a valid Option set for each
 * Attribute that the product has e.g Size:Medium, Color:Red, Material:Cotton. Variations are Versioned
 * so that when they are added to an Order and then changed, the Order can still access the correct
 * information.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 */
class Variation extends DataObject implements PermissionProvider {

	/**
	 * DB fields for a Variation
	 * 
	 * @var Array
	 */
	private static $db = array(
		'Price' => 'Decimal(19,8)',
		'Currency' => 'Varchar(3)',
		'Status' => "Enum('Enabled,Disabled','Enabled')",
		'SortOrder' => 'Int'
	);

	public function Amount() {

		$amount = Price::create();
		$amount->setCurrency($this->Currency);
		$amount->setAmount($this->Price);
		$amount->setSymbol(ShopConfig::current_shop_config()->BaseCurrencySymbol);

		//Transform amount for applying discounts etc.
		$this->extend('updateAmount', $amount);

		return $amount;
	}

	public function Price() {
		
		$amount = $this->Amount();

		//Transform price here for display in different currencies etc.
		$this->extend('updatePrice', $amount);

		return $amount;
	}

	/**
	 * Has one relation for a Variation
	 * 
	 * @var Array
	 */
	private static $has_one = array(
		'Product' => 'Product'
	);
	
	/**
	 * Many many relation for a Variation
	 * 
	 * @var Array
	 */
	private static $many_many = array(
		'Options' => 'Option'
	);
	
	/**
	 * Summary fields for displaying Variations in the CMS
	 * 
	 * @see Product::getCMSFields()
	 * @var Array
	 */
	private static $summary_fields = array(
		'SummaryOfPrice' => 'Added Price',
		'Status' => 'Status',
	);
	
	/**
	 * Versioning for a Variation, so that Orders can access the version 
	 * that was purchased and correct information can be retrieved.
	 * 
	 * @var Array
	 */
	private static $extensions = array(
		"Versioned('Live')",
	);

	private static $defaults = array(
		'Status' => 'Enabled'
	);

	private static $default_sort = 'SortOrder';

	public function providePermissions() {
		return array(
			'EDIT_VARIATIONS' => 'Edit Variations',
		);
	}

	public function canEdit($member = null) {
		return Permission::check('EDIT_VARIATIONS');
	}

	public function canView($member = null) {
		return true;
	}

	public function canDelete($member = null) {
		return Permission::check('EDIT_VARIATIONS');
	}

	public function canCreate($member = null) {
		return Permission::check('EDIT_VARIATIONS');
	}
	
	/**
	 * Overloaded magic method so that attribute values can be retrieved for display 
	 * in CTFs etc.
	 * 
	 * @see ViewableData::__get()
	 * @see Product::getCMSFields()
	 */
	public function __get($property) {

		if (strpos($property, 'AttributeValue_') === 0) {
			return $this->SummaryOfOptionValueForAttribute(str_replace('AttributeValue_', '', $property));
		}
		else {
			return parent::__get($property);
		}
	}
	
	/**
	 * Get a Variation option for an attribute
	 * 
	 * @param Int $attributeID
	 * @return Option
	 */
	public function getOptionForAttribute($attributeID) {
		$options = $this->Options();
		if ($options && $options->exists()) foreach ($options as $option) {
			
			if ($option->AttributeID == $attributeID) {
				return $option;
			}
		} 
		return null;
	}
	
	/**
	 * Add fields for editing a Variation in the CMS popup.
	 * 
	 * @return FieldList
	 */
	public function getCMSFields() {

		$fields = new FieldList(
			$rootTab = new TabSet('Root',
				$tabMain = new Tab('Variation')
			)
		);

		$product = $this->Product();
		$attributes = $product->Attributes();
		if ($attributes && $attributes->exists()) foreach ($attributes as $attribute) {

			$options = $attribute->Options();
			$currentOptionID = ($currentOption = $this->Options()->find('AttributeID', $attribute->ID)) ? $currentOption->ID : null;

			$optionField = new OptionField($attribute->ID, $attribute->Title, $options, $currentOptionID);
			$optionField->setHasEmptyDefault(false);
			$fields->addFieldToTab('Root.Variation', $optionField);
		}

		$fields->addFieldToTab('Root.Variation', PriceField::create('Price', 'Price')
			->setRightTitle('Amount that this variation will increase the base product price by')
		);

		$fields->addFieldToTab('Root.Variation', DropdownField::create(
			'Status', 
			'Status', 
			$this->dbObject('Status')->enumValues()
		)->setRightTitle('You can disable a variation to prevent it being sold'));

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}
	
	/**
	 * Get a summary of the Options, helper method for displaying Options nicely
	 * 
	 * TODO allow attributes to be sorted
	 * 
	 * @return String
	 */
	public function SummaryOfOptions() {
		$options = $this->Options();
		$options->sort('AttributeID');
		
		$temp = array();
		$summary = '';
		if ($options && $options->exists()) foreach ($options as $option) {
			$temp[] = '<strong>' . $option->Attribute()->Title . ':</strong> ' . $option->Title;
		} 
		$summary = implode('<br /> ', $temp);
		return $summary;
	}
	
	/**
	 * Get attribute option value, helper method
	 * 
	 * @see Variation::__get()
	 * @param Int $attributeID
	 * @return String
	 */
	public function SummaryOfOptionValueForAttribute($attributeID) {

		$options = $this->Options();
		if ($options && $options->exists()) foreach ($options as $option) {
			if ($option->AttributeID == $attributeID) {
				return $option->Title;
			}
		} 
		return null;
	}
	
	/**
	 * Summarize the Product price, returns Amount formatted with Nice()
	 * 
	 * @return String
	 */
	public function SummaryOfPrice() {
		return $this->Amount()->Nice();
	}
	
	/**
	 * Validate that this variation is suitable for adding to the cart.
	 * 
	 * @return ValidationResult
	 */
	public function validateForCart() {
		
		$result = new ValidationResult(); 
		
		// if (!$this->hasValidOptions()) {
		//   $result->error(
		//     'This product does not have valid options set',
		//     'VariationValidOptionsError'
		//   );
		// }
		
		if (!$this->isEnabled()) {
			$result->error(
				'These product options are not available sorry, please choose again',
				'VariationValidOptionsError'
			);
		}
		
		if ($this->isDeleted()) {
			$result->error(
				'These product options have been deleted sorry, please choose again',
				'VariationDeltedError'
			);
		}
		
		return $result;
	}
	
	/**
	 * Convenience method to check that this Variation has valid options.
	 * 
	 * @return Boolean
	 */
	public function hasValidOptions() {

		//Get the options for the product
		//Get the attributes for the product
		//Each variation should have a valid option for each attribute
		//Each variation should have only attributes that match the product

		$productAttributeOptions = array();
		$attributes = $this->Product()->Attributes();

		if ($attributes && $attributes->exists()) foreach ($attributes as $attribute) {
			$options = $attribute->Options();
			$productAttributeOptions[$attribute->ID] = $options->map()->toArray();
		}

		$variationAttributeOptions = array();
		$variationOptions = $this->Options();
		
		if (!$variationOptions || !$variationOptions->exists()) return false;
		foreach ($variationOptions as $option) {
			$variationAttributeOptions[$option->AttributeID] = $option->ID;
		}

		//If attributes are not equal between product and variation, variation is invalid
		if (array_diff_key($productAttributeOptions, $variationAttributeOptions)
		 || array_diff_key($variationAttributeOptions, $productAttributeOptions)) {
			return false;
		}
		
		foreach ($productAttributeOptions as $attributeID => $validOptionIDs) {
			if (!array_key_exists($variationAttributeOptions[$attributeID], $validOptionIDs)) {
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Convenience method to check that this Variation is not a duplicate.
	 * 
	 * @see Varaition::validate()
	 * @return Boolean
	 */
	public function isDuplicate() {

		//Hacky way to get new option IDs from $this->record because $this->Options() returns existing options
		//not the new ones passed in POST data    
		$attributeIDs = $this->Product()->Attributes()->map()->toArray();
		$variationAttributeOptions = array();
		if ($attributeIDs) foreach ($attributeIDs as $attributeID => $title) {
			
			$attributeOptionID = (isset($this->record['Options[' . $attributeID .']'])) ? $this->record['Options[' . $attributeID .']'] : null;
			if ($attributeOptionID) {
				$variationAttributeOptions[$attributeID] = $attributeOptionID;
			}
		}

		if ($variationAttributeOptions) {

			$product = $this->Product();
			$variations = DataObject::get('Variation', "\"Variation\".\"ProductID\" = " . $product->ID . " AND \"Variation\".\"ID\" != " . $this->ID);
			
			if ($variations) foreach ($variations as $variation) {
	
				$tempAttrOptions = array();
				if ($variation->Options()) foreach ($variation->Options() as $option) {
					$tempAttrOptions[$option->AttributeID] = $option->ID;
				} 

				if ($tempAttrOptions == $variationAttributeOptions) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * If current variation is enabled, checks lastest version of variation because status is saved
	 * in versions. So a variation can be saved as enabled, the version can be added to cart, then
	 * the variation is disabled but the previous version stays enabled.
	 * 
	 * @return Boolean
	 */
	public function isEnabled() {

		$latestVersion = Versioned::get_latest_version('Variation', $this->ID);
		$enabled = $latestVersion->Status == 'Enabled';
		$this->extend('isEnabled', $enabled);
		return $enabled;
	}
	
	/**
	 * Check if the variation has been deleted, need to check the actual variation and not just this version.
	 * 
	 * @return Boolean
	 */
	public function isDeleted() {
		
		$latest = DataObject::get_by_id('Variation', $this->ID);
		return (!$latest || !$latest->exists());
	}
	
	/**
	 * Check if {@link Variation} amount is a negative value
	 * 
	 * @return Boolean
	 */
	public function isNegativeAmount() {
		return $this->Amount()->getAmount() < 0;
	}

	/**
	 * Validate the Variation before it is saved. 
	 * 
	 * @see DataObject::validate()
	 * @return ValidationResult
	 */
	public function validate() {
		
		$result = new ValidationResult(); 

		if ($this->isDuplicate()) {
			$result->error(
				'Duplicate variation for this product',
				'VariationDuplicateError'
			);
		}

		if ($this->isNegativeAmount()) {
			$result->error(
				'Variation price difference is a negative amount',
				'VariationNegativeAmountError'
			);
		}
		return $result;
	}
	
	/**
	 * Unpublish {@link Product}s if after the Variations have been saved there are no enabled Variations.
	 * 
	 * TODO check that this works when changing attributes
	 * 
	 * @see DataObject::onAfterWrite()
	 */
	protected function onAfterWrite() {
		parent::onAfterWrite();

		//Save the variation options (pretty hacky TODO this with HasManyList)
		$request = Controller::curr()->getRequest();
		if ($request) {

			$options = $request->requestVar('Options');
			if (isset($options)) {

				$existingOptions = Variation_Options::get()->where("\"VariationID\" = '{$this->ID}'");
				foreach ($existingOptions as $existingOption) {
					$existingOption->delete();
				}
				foreach ($options as $optionID) {
					$join = new Variation_Options();
					$join->VariationID = $this->ID;
					$join->OptionID = $optionID;
					$join->write();
				}
			}
		}

		//Unpublish product if variations do not exist
		// $product = $this->Product();
		// $variations = $product->Variations();

		//TODO this might not be a good idea to unpublish the product
		// if (!in_array('Enabled', $variations->map('ID', 'Status')->toArray())) {
		//   $product->doUnpublish(); 
		// }
	}
	
	/**
	 * Update stock level associated with this Variation.
	 * 
	 * (non-PHPdoc)
	 * @see DataObject::onBeforeWrite()
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();

		//Save in base currency
		$shopConfig = ShopConfig::current_shop_config();
		$this->Currency = $shopConfig->BaseCurrency;
	}
}

class Variation_Options extends DataObject {

	private static $has_one = array(
		'Variation' => 'Variation',
		'Option' => 'Option'
	);
}
