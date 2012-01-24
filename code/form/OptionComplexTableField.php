<?php
/**
 * For displaying a set of {@link Option}s for a {@link Product} in the CMS.
 * Sets the {@link Attribute} ID correctly.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 * @version 1.0
 */
class OptionComplexTableField extends ComplexTableField {
  
  /**
   * {@link Attribute} ID for this options
   * 
   * @var Int
   */
  protected $attributeID;

  /**
   * Set the {@link Attribute} ID for these options
   * 
   * @param Int $id
   */
	function setAttributeID($id) {
		$this->attributeID = $id;
	}
	
	/**
	 * Ensure the fields on the add and edit forms for each option have the correct
	 * {@link Attribute} ID set.
	 * 
	 * @see ComplexTableField::getFieldsFor()
	 * @return FieldSet
	 */
  function getFieldsFor($childData) {
		
		$detailFields = parent::getFieldsFor($childData);
		
		$detailFields->removeByName('AttributeID');
		$detailFields->push(new HiddenField('AttributeID', '', $this->attributeID));
		
		return $detailFields;
	}
	
}