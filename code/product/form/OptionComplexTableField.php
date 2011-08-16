<?php
class OptionComplexTableField extends ComplexTableField {
  
  protected $attributeID;

	function setAttributeID($id) {
		$this->attributeID = $id;
	}
	
  function getFieldsFor($childData) {
		
		$detailFields = parent::getFieldsFor($childData);
		
		$detailFields->removeByName('AttributeID');
		$detailFields->push(new HiddenField('AttributeID', '', $this->attributeID));
		
		return $detailFields;
	}
	
}