<?php
class ShopAdmin extends ModelAdmin {

	public static $collection_controller_class = "ShopAdmin_CollectionController";

	public static $record_controller_class = "ShopAdmin_RecordController";
	
	public static $parent_page_type = "SiteTree";
	
	public static $managed_models = array( 
	  'Order',
    'Product',
    'Attribute',
    //'SiteConfig'
  );
  
  static $url_segment = 'shop';
  
  static $menu_title = 'Shop';
  
  static $default_model   = 'Order'; 
  
  public $showImportForm = false;
  
  static $menu_priority = 7;
  
  protected $resultsTableClassName = 'TableListField';
  
  public static $page_length = 20;
	
	public function init() {
	    parent::init();
	    
	    //For managing Orders
	    Requirements::css('shop/css/Shop.css');
    	Requirements::css('shop/css/ShopAdmin.css');
    	
    	Requirements::css('sapphire/thirdparty/jquery-ui-themes/base/jquery.ui.all.css');
    	Requirements::css('sapphire/thirdparty/jquery-ui-themes/base/jquery.ui.datepicker.css');
    	Requirements::css('shop/css/libs/ui.daterangepicker.css');
    	
    	Requirements::javascript('sapphire/thirdparty/jquery-ui/jquery.ui.core.js');
    	Requirements::javascript('sapphire/thirdparty/jquery-ui/jquery.ui.datepicker.js');
    	Requirements::javascript('shop/javascript/libs/daterangepicker.jquery.js');
    	
    	Requirements::javascript('shop/javascript/ShopAdmin.js');
	    
	    // Remove all the junk that will break ModelAdmin
	    $config = HtmlEditorConfig::get_active();
	    $buttons = array('undo','redo','separator','cut','copy','paste','pastetext','pasteword','spellchecker','separator','sslink','unlink','anchor','separator','advcode','search','replace','selectall','visualaid','separator');
	    $config->setButtonsForLine(2,$buttons);
	    
	}

	/**
	 * Return 'dropdown' or 'tabs' for template file.
	 * 
	 * @see ModelAdmin::SearchClassSelector()
	 */
	public function SearchClassSelector() {
		return "tabs";
	}
	
	public function ListView() {
		return $this->search(array(), $this->SearchForm());
	}
	
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

class ShopAdmin_CollectionController extends ModelAdmin_CollectionController {

	function add($request) {
		$class = $this->modelClass;
		$record = new $class();
		$record->write();
		$class = $this->parentController->getRecordControllerClass($this->getModelClass());
		$response = new $class($this, $request, $record->ID);
		return $response->edit($request);
	}
	
	function getSearchQuery($searchCriteria) {
		$query = parent::getSearchQuery($searchCriteria);
		if(!is_subclass_of($this->getModelClass(),"SiteTree")) {
			return $query;
		}
		$query->orderby("`SiteTree`.LastEdited DESC");
		if($page = $this->parentController->getParentPage()) {
			$query->where[] = "ParentID = $page->ID";					
		}
		return $query;
	}

}

class ShopAdmin_RecordController extends ModelAdmin_RecordController {

	public function EditForm() {
		$form = parent::EditForm();

		if ($this->currentRecord instanceof Product || is_subclass_of($this->currentRecord->class, 'Product')) {
		  
		  //Allow products to be added to the site tree, or remain disconnected from it
		  $product = $this->currentRecord;
			$pages = DataObject::get('SiteTree', 'ParentID > -1 AND SiteTree.ID != ' . $product->ID);
			$pageMapPrefix = array(
			  -1 => 'Not part of site tree navigation',
			  0 => 'Root'
			);
			$pageMap = $pages->map('ID', 'MenuTitle');
			$pagesMap = $pageMapPrefix + $pageMap;

			$treeField = new DropdownField('ParentID', 'Parent page', $pagesMap, $product->ParentID);
			$form->Fields()->addFieldToTab('Root.Behaviour', $treeField);
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
		
	public function publish($data, $form, $request) {
		if($this->currentRecord && !$this->currentRecord->canPublish()) 
			return Security::permissionFailure($this);

		$form->saveInto($this->currentRecord);		
		$this->currentRecord->doPublish();

		if(Director::is_ajax()) {
			return new SS_HTTPResponse(
				Convert::array2json(array(
					'html' => $this->EditForm()->forAjaxTemplate(),
					'message' => _t('ModelAdmin.PUBLISHED','Published')
				)),				
				200
			);
		} else {
			Director::redirectBack();
		}
	}
	
	public function unpublish($data, $form, $request) {
		if($this->currentRecord && !$this->currentRecord->canDeleteFromLive()) 
			return Security::permissionFailure($this);
		
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
	
	protected function performRollback($id, $version) {
		$record = DataObject::get_by_id($this->currentRecord->class, $id);
		if($record && !$record->canEdit()) 
			return Security::permissionFailure($this);
		
		$record->doRollbackTo($version);
		return $record;
	}
	
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
	
		
	public function delete($data, $form, $request) {
		$record = $this->currentRecord;
		if($record && !$record->canDelete())
			return Security::permissionFailure();
		
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
  	  if (isset($data['DownloadCountItem'])) foreach($data['DownloadCountItem'] as $itemID => $newDownloadCount) {
  	    $item = DataObject::get_by_id('Item', $itemID);
  	    $item->DownloadCount = $newDownloadCount;
  	    $item->write();
  	  }
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