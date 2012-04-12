<?php
/**
 * Field for picking categories for a {@link Product}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 * @version 1.0
 */
class CategoriesField extends TreeMultiselectField {
  
  /**
   * Set dummy callback so that filterMarking() is triggered.
   * 
   * @param String $name
   * @param String $title
   * @param String $sourceObject
   * @param String $keyField
   * @param String $labelField
   */
  function __construct($name, $title, $sourceObject = "Group", $keyField = "ID", $labelField = "Title") {
		parent::__construct($name, $title, $sourceObject, $keyField, $labelField);
		$this->value = 'unchanged';
		$this->filterCallback = 'dummyTrigger';
	}
  
	/**
	 * Overriding to display only {@link ProductCategory}s in the dropdown.
	 * 
	 * @see TreeDropdownField::filterMarking()
	 */
  function filterMarking($node) {

    if ($node->ClassName === 'ProductCategory') return true;
    
    //If node has children that are categories then include it in the tree
    if ($node->ClassName != 'ProductCategory') {
      
      $children = $node->AllChildren();
      if ($children && $children->exists()) {
        foreach ($children as $child) {
          if ($child->ClassName === 'ProductCategory') {
            return true;
          }
          else {
            return false;
          }
        }
      }
      else {
        return false;
      }
    }
    
    //Ignore the callback
    //if ($this->filterCallback && !call_user_func($this->filterCallback, $node)) return false;
    
		if ($this->sourceObject == "Folder" && $node->ClassName != 'Folder') return false;
		if ($this->search != "") {
			return isset($this->searchIds[$node->ID]) && $this->searchIds[$node->ID] ? true : false;
		}
		
		return true;
  }
}