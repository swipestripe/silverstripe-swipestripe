<?php

class OrderAdmin extends ModelAdmin{

	static $url_segment = 'orders';

	static $menu_title = 'Orders';

	static $menu_priority = 6;

	public static $managed_models = array('Order');
	
	static $default_model   = 'Order';

	public static $collection_controller_class = 'OrderAdmin_CollectionController';

	public static $record_controller_class = 'OrderAdmin_RecordController';

	function init() {
		parent::init();
		Requirements::css('simplecart/css/OrderReport.css');
	}
	
  function getEditForm(){ 
    return $this->bindModelController('Order')->ResultsForm(array()); 
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
	
  /*
	function search($request, $form) {
		// Get the results form to be rendered
		$query = $this->getSearchQuery(array_merge($form->getData(), $request));
		$resultMap = new SQLMap($query, $keyField = "ID", $titleField = "Title");
		$items = $resultMap->getItems();
		$array = array();
		if($items && $items->count()) {
			foreach($items as $item) {
				$array[] = $item->ID;
			}
		}
		Session::set("OrderAdminLatestSearch",serialize($array));
		return parent::search($request, $form);
	}
	*/
}

class OrderAdmin_RecordController extends ModelAdmin_RecordController {
}
