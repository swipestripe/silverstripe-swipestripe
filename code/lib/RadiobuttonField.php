<?php
/**
 * Single radio button field.
 * @package forms
 * @subpackage fields-basic
 */
class RadiobuttonField extends CheckboxField {
  
  protected $postfixID;
  
	/**
	 * Create a new field.
	 * @param name The internal field name, passed to forms.
	 * @param title The field label.
	 * @param value The value of the field.
	 * @param form Reference to the container form
	 * @param maxLength The Maximum length of the attribute
	 */
	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null, $postfixID) {

		$this->postfixID = $postfixID;
		parent::__construct($name, $title, $value, $form, $rightTitle);
	}
	
	function Field() {
		$attributes = array(
			'type' => 'radio',
			'class' => 'radio' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => 1,
			'checked' => $this->value ? 'checked' : '',
			'tabindex' => $this->getTabIndex()
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';
		
		return $this->createTag('input', $attributes);
	}
	
	/**
	 * Returns the HTML ID of the field - used in the template by label tags.
	 * The ID is generated as FormName_FieldName.  All Field functions should ensure
	 * that this ID is included in the field.
	 */
	function id() { 
		$name = ereg_replace('(^-)|(-$)','',ereg_replace('[^A-Za-z0-9_-]+','-',$this->name));
		if($this->form) return $this->form->FormName() . '_' . $name . '_' . $this->postfixID;
		else return $name;
	}


	/**
	 * Returns a readonly version of this field
	 */
	 
	function performReadonlyTransformation() {
		$field = new RadiobuttonField_Readonly($this->name, $this->title, $this->value ? _t('RadiobuttonField.YES', 'Yes') : _t('RadiobuttonField.NO', 'No'));
		$field->setForm($this->form);
		return $field;	
	}
}

/**
 * Readonly version of a Radiobutton field - "Yes" or "No".
 * @package forms
 * @subpackage fields-basic
 */
class RadiobuttonField_Readonly extends ReadonlyField {
	function performReadonlyTransformation() {
		return clone $this;
	}
	
	function setValue($val) {
		$this->value = (int)($val) ? _t('RadiobuttonField.YES', 'Yes') : _t('RadiobuttonField.NO', 'No');
	}
}

/**
 * Single checkbox field, disabled
 * @package forms
 * @subpackage fields-basic
 */
class RadiobuttonField_Disabled extends RadiobuttonField {
	
	protected $disabled = true;
	
	/**
	 * Returns a single checkbox field - used by templates.
	 */
	function Field() {
		$attributes = array(
			'type' => 'radio',
			'class' => 'radio' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'tabindex' => $this->getTabIndex(),
			'checked' => ($this->value) ? 'checked' : false,
			'disabled' => 'disabled' 
		);
		
		return $this->createTag('input', $attributes);
	}
}
?>