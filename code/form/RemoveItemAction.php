<?php
/**
 * {@link FormAction} to remove an item from the {@link CartPage}. 
 * The button is changed to an (x) image in the CSS. 
 * 
 * @see CartQuantityField::RemoveItemAction()
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class RemoveItemAction extends FormAction {

  /**
   * Create the form action with a useful description.
   * 
   * @param String $action
   * @param String $title
   * @param Form $form
   * @param String $extraData
   * @param String $extraClass
   */
  function __construct($action, $title = "", $form = null, $extraData = null, $extraClass = '') {

    $this->description = "Remove item #$title";
		parent::__construct($action, $title, $form, $extraData, $extraClass);
	}
	
	/**
	 * Set the ID attribute for the HTML to something unique.
	 * 
	 * (non-PHPdoc)
	 * @see FormField::id()
	 */
  function id() { 
		$name = ereg_replace('(^-)|(-$)','',ereg_replace('[^A-Za-z0-9_-]+','-',$this->name)) . '_' . $this->title;
		if($this->form) return $this->form->FormName() . '_' . $name;
		else return $name;
	}
}