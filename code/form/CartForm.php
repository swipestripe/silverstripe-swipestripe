<?php
/**
 * Form to display the {@link Order} contents on the {@link CartPage}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class CartForm extends Form {
  
  /**
   * The current {@link Order} (cart).
   * 
   * @var Order
   */
  public $currentOrder;
  
  /**
   * Construct the form, set the current order and the template to be used for rendering.
   * 
   * @param Controller $controller
   * @param String $name
   * @param FieldList $fields
   * @param FieldList $actions
   * @param Validator $validator
   * @param Order $currentOrder
   */
  function __construct($controller, $name, FieldList $fields, FieldList $actions, $validator = null, Order $currentOrder = null) {
    
		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setTemplate('CartForm');
		$this->currentOrder = $currentOrder;
  }

  /**
   * Set quantity to 0 for items to be removed in order to avoid issues with Item->validate() when the item
   * options are no longer valid.
   */
  public function httpSubmission($request) {
		$vars = $request->requestVars();
		if(isset($funcName)) {
			Form::set_current_action($funcName);
		}

		//Set quantity to zero for items we are removing
		if (isset($vars['action_removeItem'])) {
			$removeID = $vars['action_removeItem'];
			$vars['Quantity'][$removeID] = 0;
		}

		// Populate the form
		$this->loadDataFrom($vars, true);
	
		// Protection against CSRF attacks
		$token = $this->getSecurityToken();
		if(!$token->checkRequest($request)) {
			$this->httpError(400, "Sorry, your session has timed out.");
		}
		
		// Determine the action button clicked
		$funcName = null;
		foreach($vars as $paramName => $paramVal) {
			if(substr($paramName,0,7) == 'action_') {
				// Break off querystring arguments included in the action
				if(strpos($paramName,'?') !== false) {
					list($paramName, $paramVars) = explode('?', $paramName, 2);
					$newRequestParams = array();
					parse_str($paramVars, $newRequestParams);
					$vars = array_merge((array)$vars, (array)$newRequestParams);
				}
				
				// Cleanup action_, _x and _y from image fields
				$funcName = preg_replace(array('/^action_/','/_x$|_y$/'),'',$paramName);
				break;
			}
		}
		
		// If the action wasnt' set, choose the default on the form.
		if(!isset($funcName) && $defaultAction = $this->defaultAction()){
			$funcName = $defaultAction->actionName();
		}
			
		if(isset($funcName)) {
			$this->setButtonClicked($funcName);
		}
		
		// Permission checks (first on controller, then falling back to form)
		if(
			// Ensure that the action is actually a button or method on the form,
			// and not just a method on the controller.
			$this->controller->hasMethod($funcName)
			&& !$this->controller->checkAccessAction($funcName)
			// If a button exists, allow it on the controller
			&& !$this->actions->fieldByName('action_' . $funcName)
		) {
			return $this->httpError(
				403, 
				sprintf('Action "%s" not allowed on controller (Class: %s)', $funcName, get_class($this->controller))
			);
		} elseif(
			$this->hasMethod($funcName)
			&& !$this->checkAccessAction($funcName)
			// No checks for button existence or $allowed_actions is performed -
			// all form methods are callable (e.g. the legacy "callfieldmethod()")
		) {
			return $this->httpError(
				403, 
				sprintf('Action "%s" not allowed on form (Name: "%s")', $funcName, $this->name)
			);
		}
		// TODO : Once we switch to a stricter policy regarding allowed_actions (meaning actions must be set
		// explicitly in allowed_actions in order to run)
		// Uncomment the following for checking security against running actions on form fields
		/* else {
			// Try to find a field that has the action, and allows it
			$fieldsHaveMethod = false;
			foreach ($this->Fields() as $field){
				if ($field->hasMethod($funcName) && $field->checkAccessAction($funcName)) {
					$fieldsHaveMethod = true;
				}
			}
			if (!$fieldsHaveMethod) {
				return $this->httpError(
					403, 
					sprintf('Action "%s" not allowed on any fields of form (Name: "%s")', $funcName, $this->Name())
				);
			}
		}*/
		
		// Validate the form
		if(!$this->validate()) {
			if(Director::is_ajax()) {
				// Special case for legacy Validator.js implementation (assumes eval'ed javascript collected through
				// FormResponse)
				$acceptType = $request->getHeader('Accept');
				if(strpos($acceptType, 'application/json') !== FALSE) {
					// Send validation errors back as JSON with a flag at the start
					$response = new SS_HTTPResponse(Convert::array2json($this->validator->getErrors()));
					$response->addHeader('Content-Type', 'application/json');
				} else {
					$this->setupFormErrors();
					// Send the newly rendered form tag as HTML
					$response = new SS_HTTPResponse($this->forTemplate());
					$response->addHeader('Content-Type', 'text/html');
				}
				
				return $response;
			} else {
				if($this->getRedirectToFormOnValidationError()) {
					if($pageURL = $request->getHeader('Referer')) {
						if(Director::is_site_url($pageURL)) {
							// Remove existing pragmas
							$pageURL = preg_replace('/(#.*)/', '', $pageURL);
							return $this->controller->redirect($pageURL . '#' . $this->FormName());
						}
					}
				}
				return $this->controller->redirectBack();
			}
		}
		
		// First, try a handler method on the controller (has been checked for allowed_actions above already)
		if($this->controller->hasMethod($funcName)) {
			return $this->controller->$funcName($vars, $this, $request);
		// Otherwise, try a handler method on the form object.
		} elseif($this->hasMethod($funcName)) {
			return $this->$funcName($vars, $this, $request);
		} elseif($field = $this->checkFieldsForAction($this->Fields(), $funcName)) {
			return $field->$funcName($vars, $this, $request);
		}
		
		return $this->httpError(404);
	}
  
  /*
   * Retrieve the current {@link Order} which is the cart.
   * 
   * @return Order The current order (cart)
   */
  function Cart() {
    return $this->currentOrder;
  }

}