<?php

class OrderAdmin extends ModelAdmin{

	static $url_segment = 'orders';

	static $menu_title = 'Orders';

	static $menu_priority = 6;

	public static $managed_models = array('Order');
	
	static $default_model   = 'Order';

	public static $collection_controller_class = 'OrderAdmin_CollectionController';

	public static $record_controller_class = 'OrderAdmin_RecordController';

	/**
	 * Load css and javacript for the order admin area
	 * 
	 * @see ModelAdmin::init()
	 */
	function init() {
		parent::init();
		Requirements::css('simplecart/css/OrderReport.css');
		Requirements::javascript('simplecart/javascript/OrderAdmin.js');
	}
	
	/**
	 * Get a list of orders to display by default
	 * Only show orders with payments i.e: not the cart orders
	 * This is basically redundant because OrderAdmin.js submits 
	 * search form on load.
	 * This additionally has problems stripping out the order save and
	 * back buttons when clicking to view an order.
	 */
  function getEditForm(){ 
    $searchCriteria = new SS_HTTPRequest('GET', '/', array('TotalPaid' => '1'));
    return $this->bindModelController('Order')->ResultsForm($searchCriteria); 
  }

}

class OrderAdmin_CollectionController extends ModelAdmin_CollectionController {

  /**
   * No form for creating Orders
   * 
   * @see ModelAdmin_CollectionController::CreateForm()
   */
	public function CreateForm() {
	  return false;
	}
	
	/**
	 * No form for importing Orders
	 * 
	 * @see ModelAdmin_CollectionController::ImportForm()
	 */
	function ImportForm() {
	  return false;
	}

}

class OrderAdmin_RecordController extends ModelAdmin_RecordController {
  
  
  
	/**
	 * Save a record and perform any other actions such as 
	 * sending an email to customer.
	 *
	 * @see ModelAdmin_RecordController::doSave()
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 * @return mixed
	 */
	function doSave($data, $form, $request) {
	  
	  //Reset the download count for a particular item
	  if (isset($data['DownloadCountItem'])) foreach($data['DownloadCountItem'] as $itemID => $newDownloadCount) {
	    $item = DataObject::get_by_id('Item', $itemID);
	    $item->DownloadCount = $newDownloadCount;
	    $item->write();
	  }

	  $form->saveInto($this->currentRecord);
		
		try {
			$this->currentRecord->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
		}
		
		// Behaviour switched on ajax.
		if(Director::is_ajax()) {
			return $this->edit($request);
		} else {
			Director::redirectBack();
		}
	}	
  
}
