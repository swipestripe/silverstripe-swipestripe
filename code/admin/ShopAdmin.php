<?php
/**
 * The shop admin area which provides access to orders, products and attributes.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 * @version 1.0
 */
class ShopAdmin extends ModelAdmin {

  /**
   * Class used for administering the collections of products, orders etc.
   * 
   * @var String
   */
	public static $collection_controller_class = "ShopAdmin_CollectionController";

	/**
	 * Class used for administering a record
	 * 
	 * @var String
	 */
	public static $record_controller_class = "ShopAdmin_RecordController";
	
	/**
	 * No idea why this exists. 
	 * TODO remove this variable
	 * 
	 * @var String
	 */
	public static $parent_page_type = "SiteTree";
	
	/**
	 * The data objects managed by this admin.
	 * 
	 * @var Array
	 */
	public static $managed_models = array( 
	  'Order',
    'Product',
    'Attribute',
	  'Customer'
  );
  
  /**
   * URL segment for this admin area.
   * 
   * @var String
   */
  static $url_segment = 'shop';
  
  /**
   * Menu title for this admin area.
   * 
   * @var String
   */
  static $menu_title = 'Shop';
  
  /**
   * Default model to be managed when the admin is loaded.
   * 
   * @var String
   */
  static $default_model   = 'Order'; 
  
  /**
   * Do not show an import form
   * 
   * @var Boolean
   */
  public $showImportForm = false;
  
  /**
   * Set a high priority in the CMS top navigation
   * 
   * @var Int
   */
  static $menu_priority = 7;
  
  /**
   * Class for displaying collection results.
   * 
   * @var String
   */
  protected $resultsTableClassName = 'TableListField';
  
  /**
   * Number of records to display at one time.
   * 
   * @var Int
   */
  public static $page_length = 20;
	
  /**
   * Load some css and javascript for the admin area, remove some functionality from tinymce.
   * 
   * @see ModelAdmin::init()
   */
	public function init() {
	    parent::init();
	    
	    //For managing Orders
	    //Requirements::css('swipestripe/css/Shop.css');
    	Requirements::css('swipestripe/css/ShopAdmin.css');
    	
    	Requirements::css('sapphire/thirdparty/jquery-ui-themes/base/jquery.ui.all.css');
    	Requirements::css('sapphire/thirdparty/jquery-ui-themes/base/jquery.ui.datepicker.css');
    	Requirements::css('swipestripe/css/libs/ui.daterangepicker.css');
    	
    	Requirements::javascript('sapphire/thirdparty/jquery-ui/jquery.ui.core.js');
    	Requirements::javascript('sapphire/thirdparty/jquery-ui/jquery.ui.datepicker.js');
    	Requirements::javascript('swipestripe/javascript/libs/daterangepicker.jquery.js');
    	
    	Requirements::javascript('swipestripe/javascript/ShopAdmin.js');
	    
	    // Remove all the junk that will break ModelAdmin
	    $config = HtmlEditorConfig::get_active();
	    $buttons = array('undo','redo','separator','cut','copy','paste','pastetext','pasteword','spellchecker','separator','sslink','unlink','anchor','separator','advcode','search','replace','selectall','visualaid','separator');
	    $config->setButtonsForLine(2,$buttons);
	}

	/**
	 * Return 'dropdown' or 'tabs' for template file.
	 * 
	 * @see ModelAdmin::SearchClassSelector()
	 * @return String Either 'dropdown' or 'tabs'
	 */
	public function SearchClassSelector() {
		return "tabs";
	}
	
	/**
	 * Perform a search for the default list view?
	 * TODO remove this, not sure it is required
	 * 
	 * @deprecated
	 */
	public function ListView() {
		return $this->search(array(), $this->SearchForm());
	}
	
	/**
	 * Retrieve a parent page for a particular record.
	 * 
	 * @see ShopAdmin_CollectionController::getSearchQuery()
	 * @return DataObject|SiteTree|Boolean If the parent page cannot be found returns false
	 */
	public function getParentPage() {
		if($parent = $this->stat('parent')) {
			if(is_numeric($parent)) {
				return DataObject::get_by_id($this->modelClass(), $parent);
			}
			elseif(is_string($parent)) {
				return SiteTree::get_by_link($parent);
			}			
		}
		return false;	
	}

}

/**
 * Managing collections of the model class, includes overloaded methods for
 * adding and getting a search query.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 * @version 1.0
 */
class ShopAdmin_CollectionController extends ModelAdmin_CollectionController {

  /**
   * Form for creating a new model record.
   * 
   * @see ModelAdmin_CollectionController::add()
   * @return String Rendered form
   */
	function add($request) {
		$class = $this->modelClass;
		$record = new $class();
		$record->write();
		$class = $this->parentController->getRecordControllerClass($this->getModelClass());
		$response = new $class($this, $request, $record->ID);
		return $response->edit($request);
	}
	
	/**
	 * Gets the search query generated on the SearchContext from
	 * {@link DataObject::getDefaultSearchContext()},
	 * and the current GET parameters on the request.
	 * 
	 * @see ModelAdmin_CollectionController::getSearchQuery()
	 * @return SQLQuery
	 */
	function getSearchQuery($searchCriteria) {
		$query = parent::getSearchQuery($searchCriteria);
		if(!is_subclass_of($this->getModelClass(),"SiteTree")) {
			return $query;
		}
		$query->orderby("\"SiteTree\".\"LastEdited\" DESC");
		if($page = $this->parentController->getParentPage()) {
			$query->where[] = "ParentID = $page->ID";					
		}
		return $query;
	}

}

/**
 * Handles operations on a single model record.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 * @version 1.0
 */
class ShopAdmin_RecordController extends ModelAdmin_RecordController {

  /**
   * Create the form for editing a model.
   * 
   * @see ModelAdmin_RecordController::EditForm()
   * @return Form
   */
	public function EditForm() {
		$form = parent::EditForm();

		if ($this->currentRecord instanceof Product || is_subclass_of($this->currentRecord->class, 'Product')) {
		  
		  //Add another option to ParentType field for when products are not part of the site tree
		  $fields = $form->Fields();
		  $parentTypeField = $fields->fieldByName('Root.Behaviour.ParentType');

		  if ($parentTypeField && $parentTypeField->exists() && $parentTypeField instanceof OptionsetField) {
		    $source = $parentTypeField->getSource();
		    $source['exempt'] = 'Not part of the site tree';
		    $parentTypeField->setSource($source);
		    $parentTypeField->setValue($this->currentRecord->getParentType());
		  }
		}
		
		if(is_subclass_of($this->currentRecord->class, "SiteTree")) {

			$form->setActions($this->currentRecord->getCMSActions());
			
			$live_link = Controller::join_links($this->currentRecord->Link(),'?stage=Live');
			$stage_link = Controller::join_links($this->currentRecord->Link(),'?stage=Stage');
			
			$form->Fields()->insertFirst(
			  new LiteralField('view','<div class="publishpreviews clr">'._t('ShopAdmin.VIEW','View Page').': <a target="_blank" href="'.$live_link.'">'._t('ShopAdmin.VIEWLIVE','Live Site').'</a> <a target="_blank" href="'.$stage_link.'">'._t('ShopAdmin.VIEWDRAFT','Draft Site').'</a></div></div>')
			);
			$form->Fields()->insertFirst(
			  new LiteralField('back','<div class="modelpagenav clr"><button id="list_view">&laquo; '._t('ShopAdmin.BACKTOLIST','Back to list view').'</button>')
			);	
		}	
		else {
		  $form->Fields()->insertFirst(new LiteralField('back','<div class="modelpagenav clr"><button id="list_view">&laquo; '._t('ShopAdmin.BACKTOLIST','Back to list view').'</button></div>'));		
		}	
		
		return $form;	
	}
		
	/**
	 * Handle publishing a record, will set errors on form if {@link Product} validation fails.
	 * 
	 * @see Product::validate()
	 * @param Array $data
	 * @param Form $form
	 * @param SS_HttpRequest $request
	 */
	public function publish($data, $form, $request) {

		if ($this->currentRecord && !$this->currentRecord->canPublish()) {
			return Security::permissionFailure($this);
		}

		try {
  		$form->saveInto($this->currentRecord);		
  		$this->currentRecord->doPublish();
  		
  		$response = new SS_HTTPResponse(
  			Convert::array2json(array(
  				'html' => $this->EditForm()->forAjaxTemplate(),
  			  //'html' => $this->edit($request),
  				'message' => _t('ModelAdmin.PUBLISHED','Published')
  			)),				
  			200
  		);
		}
		catch (ValidationException $e) {

		  $form->setMessage($e->getResult()->message(), 'bad');
		  $response = new SS_HTTPResponse(
  			Convert::array2json(array(
  				'html' => $form->forAjaxTemplate()
  			)),				
  			200
  		);
		}
		
		if (Director::is_ajax()) {
			return $response;
		} 
		else {
			Director::redirectBack();
		}
	}
	
	/**
	 * Handle unpublishing a record
	 * 
	 * @param Array $data
	 * @param Form $form
	 * @param SS_HttpRequest $request
	 */
	public function unpublish($data, $form, $request) {
	  
		if ($this->currentRecord && !$this->currentRecord->canDeleteFromLive()) return Security::permissionFailure($this);
		
		$this->currentRecord->doUnpublish();
		
		if(Director::is_ajax()) {
			return new SS_HTTPResponse(
				Convert::array2json(array(
					'html' => $this->EditForm()->forAjaxTemplate(),
					'message' => _t('ModelAdmin.UNPUBLISHED','Unpublished')
				)),				
				200
			);
		} else {
			Director::redirectBack();
		}

	}
	
	/**
	 * Handle a rollback for a record.
	 * 
	 * @param Int $id
	 * @param Int|String $version Either the string 'Live' or a version number
	 */
	protected function performRollback($id, $version) {
		$record = DataObject::get_by_id($this->currentRecord->class, $id);
		if($record && !$record->canEdit()) 
			return Security::permissionFailure($this);

		$record->doRollbackTo($version);
		return $record;
	}
	
	/**
	 * Handle a rollback for a record.
	 * 
	 * @param Array $data
	 * @param Form $form
	 * @param SS_HttpRequest $request
	 */
	public function rollback($data, $form, $request) {
	  
		$record = $this->performRollback($this->currentRecord->ID, "Live");
		
		if(Director::is_ajax()) {
			return new SS_HTTPResponse(
				Convert::array2json(array(
					'html' => $this->EditForm()->forAjaxTemplate(),
					'message' => _t('ModelAdmin.ROLLEDBACK','Rolled back version')
				)),				
				200
			);
		} else {
			Director::redirectBack();
		}
	}
	
	/**
	 * Handle deleting a record.
	 * 
	 * @param Array $data
	 * @param Form $form
	 * @param SS_HttpRequest $request
	 */
	public function delete($data, $form, $request) {
		$record = $this->currentRecord;
		if($record && !$record->canDelete()) return Security::permissionFailure();
		
		// save ID and delete record
		$recordID = $record->ID;
		$record->delete();
		
		if(Director::is_ajax()) {
			$body = "";
			return new SS_HTTPResponse(
				Convert::array2json(array(
					'html' => $this->EditForm()->forAjaxTemplate(),
					'message' => _t('ModelAdmin.DELETED','Deleted')
				)),				
				200
			);
		} else {
			Director::redirectBack();
		}
	}
	
	/**
	 * Handle saving a record.
	 * 
	 * @param Array $data
	 * @param Form $form
	 * @param SS_HttpRequest $request
	 */
	public function save($data, $form, $request) {

		if($this->currentRecord && !$this->currentRecord->canEdit()) {
			return Security::permissionFailure($this);
		}	

		$form->saveInto($this->currentRecord);		
		$this->currentRecord->write();

		if(Director::is_ajax()) {
			return new SS_HTTPResponse(
				Convert::array2json(array(
					'html' => $this->EditForm()->forAjaxTemplate(),
					'message' => _t('ModelAdmin.SAVED','Saved')
				)),				
				200
			);
		} else {
			Director::redirectBack();
		}
	}
	
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
	  
	  //Set the status of payments for Orders
	  if (isset($data['Payments'])) {
	  
  	  foreach ($data['Payments'] as $paymentID => $newPaymentStatus) {
  	    $payment = DataObject::get_by_id('Payment', $paymentID);
  	    $payment->Status = $newPaymentStatus;
  	    $payment->write();
  	  }
  	  
  	  $this->currentRecord->updatePaymentStatus();
  
  	  //TODO move this to some place else
  	  //Reset the download count for a particular item
  	  /*
  	  if (isset($data['DownloadCountItem'])) foreach($data['DownloadCountItem'] as $itemID => $newDownloadCount) {
  	    $item = DataObject::get_by_id('Item', $itemID);
  	    $item->DownloadCount = $newDownloadCount;
  	    $item->write();
  	  }
  	  */
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
	
	/**
	 * Handle deleting a record from the live site, unpublishing.
	 * 
	 * @param Array $data
	 * @param Form $form
	 * @param SS_HttpRequest $request
	 */
	public function deletefromlive($data, $form, $request) {
		Versioned::reading_stage('Live');
		$record = $this->currentRecord;
		if($record && !($record->canDelete() && $record->canDeleteFromLive())) 
			return Security::permissionFailure($this);

		
		$descRemoved = '';
		$descendantsRemoved = 0;
		
		// before deleting the records, get the descendants of this tree
		if($record) {
			$descendantIDs = $record->getDescendantIDList();

			// then delete them from the live site too
			$descendantsRemoved = 0;
			foreach( $descendantIDs as $descID )
				if( $descendant = DataObject::get_by_id('SiteTree', $descID) ) {
					$descendant->doDeleteFromLive();
					$descendantsRemoved++;
				}

			// delete the record
			$record->doDeleteFromLive();
		}

		Versioned::reading_stage('Stage');

		if(Director::is_ajax()) {
			$body = $this->parentController->ListView()->getBody();
			return new SS_HTTPResponse(
				Convert::array2json(array(
					'html' => $body,
					'message' => _t('ModelAdmin.DELETEDFROMLIVE','Deleted')
				)),				
				200
			);
		} else {
			Director::redirectBack();
		}
	}
}