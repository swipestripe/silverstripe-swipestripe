<?php
/**
 * Extend {@link FieldSet} with utility to findOrMakeTabSet so that modules can share
 * the same tab in {@link SiteConfig}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class FieldSetExtension extends Extension {
  
  /**
   * Utility function to make a tab set, so that other modules can share a tabset
   * in the SiteConfig.
   * 
   * @see FieldSet::findOrMakeTab()
   * @param string $tabName The tab to return, in the form "Tab.Subtab.Subsubtab".
	 *   Caution: Does not recursively create TabSet instances, you need to make sure everything
	 *   up until the last tab in the chain exists.
	 * @param string $title Natural language title of the tab. If {@link $tabName} is passed in dot notation,
	 *   the title parameter will only apply to the innermost referenced tab.
	 *   The title is only changed if the tab doesn't exist already.
	 * @return Tab The found or newly created Tab instance
   */
  public function findOrMakeTabSet($tabName, $title = null) {
		$parts = explode('.',$tabName);
		
		// We could have made this recursive, but I've chosen to keep all the logic code within FieldSet rather than add it to TabSet and Tab too.
		$currentPointer = $this->owner;
		foreach($parts as $k => $part) {
			$parentPointer = $currentPointer;
			$currentPointer = $currentPointer->fieldByName($part);
			// Create any missing tabs
			if(!$currentPointer) {
				if(is_a($parentPointer, 'TabSet')) {
					// use $title on the innermost tab only
					if($title && $k == count($parts)-1) {
						$currentPointer = new TabSet($part, $title);
					} else {
						$currentPointer = new TabSet($part);
					}
					$parentPointer->push($currentPointer);
				} else {
					$withName = ($parentPointer->hasMethod('Name')) ? " named '{$parentPointer->Name()}'" : null;
					user_error("FieldSet::addFieldToTab() Tried to add a tab to object '{$parentPointer->class}'{$withName} - '$part' didn't exist.", E_USER_ERROR);
				}
			}
		}
		
		return $currentPointer;
	}
}

