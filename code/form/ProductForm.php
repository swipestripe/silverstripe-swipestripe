<?php
/**
 * Form for adding items to the cart from a {@link Product} page.
 */
class ProductForm extends Form {

	protected $product;
	protected $quantity;
	protected $redirectURL;
	
	private static $allowed_actions = array(
		'add'
	);

	public function __construct($controller, $name, $quantity = null, $redirectURL = null) {

		parent::__construct($controller, $name, FieldList::create(), FieldList::create(), null);

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('swipestripe/javascript/ProductForm.js');

		$this->product = $controller->data();
		$this->quantity = $quantity;
		$this->redirectURL = $redirectURL;

		$this->fields = $this->createFields();
		$this->actions = $this->createActions();
		$this->validator = $this->createValidator();

		$this->setupFormErrors();

		$this->addExtraClass('product-form');


		//Add a map of all variations and prices to the page for updating the price
		$map = array();
		$variations = $this->product->Variations();
		$productPrice = $this->product->Price();

		if ($variations && $variations->exists()) foreach ($variations as $variation) {

			if ($variation->isEnabled()) {
				$variationPrice = $variation->Price();
				
				$amount = Price::create();
				$amount->setAmount($productPrice->getAmount() + $variationPrice->getAmount());
				$amount->setCurrency($productPrice->getCurrency());
				$amount->setSymbol($productPrice->getSymbol());

				$map[] = array(
					'price' => $amount->Nice(),
					'options' => $variation->Options()->column('ID'),
					'free' => _t('Product.FREE', 'Free'),
				);
			}
		}

		$this->setAttribute('data-map', json_encode($map));
	}

	/**
	 * Set up current form errors in session to
	 * the current form if appropriate.
	 */
	public function setupFormErrors() {

		//Only run when fields exist
		if ($this->fields->exists()) {
			parent::setupFormErrors();
		}
	}

	public function createFields() {

		$product = $this->product;

		$fields = FieldList::create(
			HiddenField::create('ProductClass', 'ProductClass', $product->ClassName),
			HiddenField::create('ProductID', 'ProductID', $product->ID),
			HiddenField::create('Redirect', 'Redirect', $this->redirectURL)
		);

		$attributes = $this->product->Attributes();
		$prev = null;

		if ($attributes && $attributes->exists()) foreach ($attributes as $attribute) {

			$field = $attribute->getOptionField($prev);
			$fields->push($field);

			$prev = $attribute;
		}

		$fields->push(ProductForm_QuantityField::create('Quantity', _t('ProductForm.QUANTITY', 'Quantity'), $this->quantity));

		$this->extend('updateFields', $fields);
		$fields->setForm($this);
		return $fields;
	}

	public function createActions() {
		$actions = new FieldList(
			new FormAction('add', _t('ProductForm.ADD_TO_CART', 'Add To Cart'))
		);

		$this->extend('updateActions', $actions);
		$actions->setForm($this);
		return $actions;
	}

	public function createValidator() {

		$validator = new ProductForm_Validator(
			'ProductClass', 
			'ProductID',
			'Quantity'
		);

		$this->extend('updateValidator', $validator);
		$validator->setForm($this);
		return $validator;
	}
	
	/**
	 * Overloaded so that form error messages are displayed.
	 * 
	 * @see OrderFormValidator::php()
	 * @see Form::validate()
	 */
	public function validate(){

		if($this->validator){
			$errors = $this->validator->validate();

			if($errors){
				if(Director::is_ajax()) { // && $this->validator->getJavascriptValidationHandler() == 'prototype') {

					FormResponse::status_message(_t('Form.VALIDATIONFAILED', 'Validation failed'), 'bad');
					foreach($errors as $error) {
						FormResponse::add(sprintf(
							"validationError('%s', '%s', '%s');\n",
							Convert::raw2js($error['fieldName']),
							Convert::raw2js($error['message']),
							Convert::raw2js($error['messageType'])
						));
					}
				} else {
					$data = $this->getData();

					$formError = array();
					if ($formMessageType = $this->MessageType()) {
						$formError['message'] = $this->Message();
						$formError['messageType'] = $formMessageType;
					}

					// Load errors into session and post back
					Session::set("FormInfo.{$this->FormName()}", array(
						'errors' => $errors,
						'data' => $data,
						'formError' => $formError
					));

				}
				return false;
			}
		}
		return true;
	}

	/**
	 * Add an item to the current cart ({@link Order}) for a given {@link Product}.
	 * 
	 * @param Array $data
	 * @param Form $form
	 */
	public function add(Array $data, Form $form) {

		Cart::get_current_order(true)
			->addItem(
				$this->getProduct(), 
				$this->getVariation(), 
				$this->getQuantity(), 
				$this->getOptions()
		);
		
		//Show feedback if redirecting back to the Product page
		if (!$this->getRequest()->requestVar('Redirect')) {
			$cartPage = DataObject::get_one('CartPage');
			$message = _t('ProductForm.PRODUCT_ADDED', 'The product was added to your cart.');
			if ($cartPage->exists()) {
				$message = _t(
					'ProductForm.PRODUCT_ADDED_LINK', 
					'The product was added to {openanchor}your cart{closeanchor}.',
					array(
						'openanchor' => "<a href=\"{$cartPage->Link()}\">",
						'closeanchor' => "</a>"
					)
				);
			}
			$form->sessionMessage(
				DBField::create_field("HTMLText", $message),
				'good',
				false
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
		$product = $this->product;
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
		if ($redirectURL && Director::is_site_url($redirectURL)) {
			$this->controller->redirect(Director::absoluteURL(Director::baseURL() . $redirectURL));
		} 
		else {
			$this->controller->redirectBack();
		}
	}

}

/**
 * Validator for {@link AddToCartForm} which validates that the product {@link Variation} is 
 * correct for the {@link Product} being added to the cart.
 */
class ProductForm_Validator extends RequiredFields {

	/**
	 * Check that current product variation is valid
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	public function php($data) {

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//Check that variation exists if necessary
		$form = $this->form;
		$request = $this->form->getRequest();

		//Get product variations from options sent
		//TODO refactor this
		
		$productVariations = new ArrayList();

		$options = $request->postVar('Options');
		$product = DataObject::get_by_id($data['ProductClass'], $data['ProductID']);
		$variations = ($product) ? $product->Variations() : new ArrayList();

		if ($variations && $variations->exists()) foreach ($variations as $variation) {
			
			$variationOptions = $variation->Options()->map('AttributeID', 'ID')->toArray();
			if ($options == $variationOptions && $variation->isEnabled()) {
				$productVariations->push($variation);
			}
		}
		
		if ((!$productVariations || !$productVariations->exists()) && $product && $product->requiresVariation()) {
			$this->form->sessionMessage(
				_t('ProductForm.VARIATIONS_REQUIRED', 'This product requires options before it can be added to the cart.'),
				'bad'
			);
			
			//Have to set an error for Form::validate()
			$this->errors[] = true;
			$valid = false;
			return $valid;
		}

		//Validate that base currency is set for this cart
		$config = ShopConfig::current_shop_config();
		if (!$config->BaseCurrency) {
			$this->form->sessionMessage(
				_t('ProductForm.BASE_CURRENCY_NOT_SET', 'The currency is not set.'),
				'bad'
			);
			
			//Have to set an error for Form::validate()
			$this->errors[] = true;
			$valid = false;
		}

		return $valid;
	}
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form The current form
	 */
	public function getForm() {
		return $this->form;
	}
}

/**
 * Represent each {@link Item} in the {@link Order} on the {@link Product} {@link AddToCartForm}.
 */
class ProductForm_QuantityField extends NumericField {

	public function Type() {
		return 'quantity';	
	}
	
	/**
	 * Validate the quantity is above 0.
	 * 
	 * @see FormField::validate()
	 * @return Boolean
	 */
	public function validate($validator) {

		$valid = true;
		$quantity = $this->Value();
		
		if ($quantity == null || !is_numeric($quantity)) {
			$errorMessage = _t('ProductForm.ITEM_QUANTITY_INCORRECT', 'The quantity must be a number');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
			$valid = false;
		}
		else if ($quantity <= 0) {
			$errorMessage = _t('ProductForm.ITEM_QUANTITY_LESS_ONE', 'The quantity must be at least 1');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
			$valid = false;
		}
		else if ($quantity > 2147483647) {
			$errorMessage = _t('ProductForm.ITEM_QUANTITY_INCORRECT', 'The quantity must be less than 2,147,483,647');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
			$valid = false;
		}


		return $valid;
	}
	
}
