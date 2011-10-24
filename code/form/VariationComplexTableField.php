<?php
class VariationComplexTableField extends ComplexTableField {
  
  function handleItem($request) {
		return new VariationComplexTableField_ItemRequest($this, $request->param('ID'));
	}
  
	/**
	 * Use the URL-Parameter "action_saveComplexTableField"
	 * to provide a clue to the main controller if the main form has to be rendered,
	 * even if there is no action relevant for the main controller (to provide the instance of ComplexTableField
	 * which in turn saves the record.
	 *
	 * This is for adding new item records. {@link ComplexTableField_ItemRequest::saveComplexTableField()}
	 *
	 * @see Form::ReferencedField
	 */
	function saveComplexTableField($data, $form, $params) {
		$className = $this->sourceClass();
		$childData = new $className();
		$form->saveInto($childData);

		try {
			$childData->write();
			
			//Loop through options and save those for this variation
			$parentRecord = $childData;
			$relationName = 'Options';
			$componentSet = $parentRecord->getManyManyComponents($relationName);
			if($componentSet) {
			  
			  foreach ($componentSet as $component) {
			    $componentSet->remove($component);
			  }
			  
  			if (isset($data['Options']) && is_array($data['Options'])) {
  			  foreach ($data['Options'] as $attributeID => $optionID) {
  			     
  			    $option = DataObject::get_by_id('Option', $optionID);
  			    $componentSet->add($option);
  			  }
  			}
			} 
			
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}

		// Save the many many relationship if it's available
		if(isset($data['ctf']['manyManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['manyManyRelation'];
			$componentSet = $parentRecord ? $parentRecord->getManyManyComponents($relationName) : null;
			if($componentSet) $componentSet->add($childData);
		}
		
		if(isset($data['ctf']['hasManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['hasManyRelation'];
			
			$componentSet = $parentRecord ? $parentRecord->getComponents($relationName) : null;
			if($componentSet) $componentSet->add($childData);
		}
		
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		
		$closeLink = sprintf(
			'<small><a href="%s" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			$referrer,
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		
		$editLink = Controller::join_links($this->Link(), 'item/' . $childData->ID . '/edit');
		
		$message = sprintf(
			_t('ComplexTableField.SUCCESSADD', 'Added %s %s %s'),
			$childData->singular_name(),
			'<a href="' . $editLink . '">' . $childData->Title . '</a>',
			$closeLink
		);
		
		$form->sessionMessage($message, 'good');

		Director::redirectBack();
	}
	
}

class VariationComplexTableField_ItemRequest extends ComplexTableField_ItemRequest {
  
	/**
	 * Use the URL-Parameter "action_saveComplexTableField"
	 * to provide a clue to the main controller if the main form has to be rendered,
	 * even if there is no action relevant for the main controller (to provide the instance of ComplexTableField
	 * which in turn saves the record.
	 *
	 * This is for editing existing item records. {@link ComplexTableField::saveComplexTableField()}
	 *
	 * @see Form::ReferencedField
	 */
	function saveComplexTableField($data, $form, $request) {
		$dataObject = $this->dataObj();

		try {
			$form->saveInto($dataObject);
			$dataObject->write();
			
		  //Loop through options and save those for this variation
			$parentRecord = $dataObject;
			$relationName = 'Options';
			$componentSet = $parentRecord->getManyManyComponents($relationName);
			if($componentSet) {
			  
			  foreach ($componentSet as $component) {
			    $componentSet->remove($component);
			  }
			  
  			if (isset($data['Options']) && is_array($data['Options'])) {
  			  foreach ($data['Options'] as $attributeID => $optionID) {
  			     
  			    $option = DataObject::get_by_id('Option', $optionID);
  			    $componentSet->add($option);
  			  }
  			}
			}
			
		} catch (ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}
		
		// Save the many many relationship if it's available
		if(isset($data['ctf']['manyManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['manyManyRelation'];
			$componentSet = $parentRecord->getManyManyComponents($relationName);
			$componentSet->add($dataObject);
		}
		
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		
		$closeLink = sprintf(
			'<small><a href="%s" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			$referrer,
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		$message = sprintf(
			_t('ComplexTableField.SUCCESSEDIT', 'Saved %s %s %s'),
			$dataObject->singular_name(),
			'<a href="' . $this->Link() . '">"' . htmlspecialchars($dataObject->Title, ENT_QUOTES) . '"</a>',
			$closeLink
		);
		
		$form->sessionMessage($message, 'good');

		Director::redirectBack();
	}
}
