<?php
/**
 * Common functions for SwipeStripe testing.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 */
class SWS_Test extends FunctionalTest {

	protected static $fixture_file = 'swipestripe/tests/SWS.yml';
	protected static $disable_themes = true;
	protected static $use_draft_site = false;

	public function setUp() {
		parent::setUp();
		
		//Do not display deprecated errors etc.
		ini_set('display_errors', 1);
		ini_set("log_errors", 1);
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
	}
	
	/**
	 * Log current member out by clearing session
	 */
	public function logOut() {
		$this->session()->clear('loggedInAs');
	}
	
	/**
	 * Helper to get data from a form.
	 * 
	 * @param String $formID
	 * @return Array
	 */
	public function getFormData($formID) {
		$page = $this->mainSession->lastPage();
		$data = array();
		
		if ($page) {
			$form = $page->getFormById($formID);
			if (!$form) user_error("Function getFormData() failed to find the form {$formID}", E_USER_ERROR);
	
			foreach ($form->_widgets as $widget) {
	
				$fieldName = $widget->getName();
				$fieldValue = $widget->getValue();
				
				$data[$fieldName] = $fieldValue;
			}
		}
		else user_error("Function getFormData() called when there is no form loaded.  Visit the page with the form first", E_USER_ERROR);
		
		return $data;
	}

	/**
	 * Helper to get data from a form in a nested array instead of just flat. Useful for post() calls.
	 * 
	 * @param String $formID
	 * @return Array
	 */
	public function getFormDataNested($formID) {
		$page = $this->mainSession->lastPage();
		$data = array();
		
		if ($page) {
			$form = $page->getFormById($formID);
			if (!$form) user_error("Function getFormData() failed to find the form {$formID}", E_USER_ERROR);

			foreach ($form->_widgets as $widget) {
	
				$fieldName = $widget->getName();
				$fieldValue = $widget->getValue();

				$fieldName = preg_replace('/([^\[]*)(.*)/i', '[$1]$2', $fieldName);
				$fieldName = str_replace('[]', '', $fieldName);

				@eval('$data' . $fieldName . ' = "' . $fieldValue . '";');
			}
		}
		else user_error("Function getFormData() called when there is no form loaded.  Visit the page with the form first", E_USER_ERROR);
		
		return $data;
	}
	
	/**
	 * Search a nested array for key => value pair
	 * 
	 * @param Array $arr
	 * @param String $id
	 * @param String $val
	 * @return Array Parent array containing key=>value pair
	 */
	public function searchNestedArray($arr, $id, $val) {
		
		$arrIt = new RecursiveIteratorIterator(new RecursiveArrayIterator($arr));
		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray[$id] === $val) {
					$outputArray[] = iterator_to_array($subArray);
			}
		}
		return $outputArray;
	}

	public function testSWS() {
		
	}
}