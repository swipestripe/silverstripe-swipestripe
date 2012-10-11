<?php
class GridFieldDetailForm_HasManyItemRequest extends GridFieldDetailForm_ItemRequest {

	/**
	 * Builds an item edit form.  The arguments to getCMSFields() are the popupController and
	 * popupFormName, however this is an experimental API and may change.
	 * 
	 * @todo In the future, we will probably need to come up with a tigher object representing a partially
	 * complete controller with gaps for extra functionality.  This, for example, would be a better way
	 * of letting Security/login put its log-in form inside a UI specified elsewhere.
	 * 
	 * @return Form 
	 */
	function ItemEditForm() {
		if (empty($this->record)) {
			$controller = Controller::curr();
			$noActionURL = $controller->removeAction($_REQUEST['url']);
			$controller->getResponse()->removeHeader('Location');   //clear the existing redirect
			return $controller->redirect($noActionURL, 302);
		}

		$actions = new FieldList();
		if($this->record->ID !== 0) {
			$actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Save', 'Save'))
				->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept'));
			$actions->push(FormAction::create('doDelete', _t('GridFieldDetailForm.Delete', 'Delete'))
				->addExtraClass('ss-ui-action-destructive'));
		}else{ // adding new record
			//Change the Save label to 'Create'
			$actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Create', 'Create'))
				->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'add'));
				
			// Add a Cancel link which is a button-like link and link back to one level up.
			$curmbs = $this->Breadcrumbs();
			if($curmbs && $curmbs->count()>=2){
				$one_level_up = $curmbs->offsetGet($curmbs->count()-2);
				$text = "
				<a class=\"crumb ss-ui-button ss-ui-action-destructive cms-panel-link ui-corner-all\" href=\"".$one_level_up->Link."\">
					Cancel
				</a>";
				$actions->push(new LiteralField('cancelbutton', $text));
			}
		}
		
		$fk = $this->gridField->getList()->foreignKey;
		$this->record->$fk = $this->gridField->getList()->foreignID;

		$form = new Form(
			$this,
			'ItemEditForm',
			$this->record->getCMSFields(),
			$actions,
			$this->component->getValidator()
		);
		$form->loadDataFrom($this->record);

		// TODO Coupling with CMS
		$toplevelController = $this->getToplevelController();
		if($toplevelController && $toplevelController instanceof LeftAndMain) {
			// Always show with base template (full width, no other panels), 
			// regardless of overloaded CMS controller templates.
			// TODO Allow customization, e.g. to display an edit form alongside a search form from the CMS controller
			$form->setTemplate('LeftAndMain_EditForm');
			$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
			$form->setAttribute('data-pjax-fragment', 'CurrentForm Content');
			if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

			if($toplevelController->hasMethod('Backlink')) {
				$form->Backlink = $toplevelController->Backlink();
			} elseif($this->popupController->hasMethod('Breadcrumbs')) {
				$parents = $this->popupController->Breadcrumbs(false)->items;
				$form->Backlink = array_pop($parents)->Link;
			} else {
				$form->Backlink = $toplevelController->Link();
			}
		}

		$cb = $this->component->getItemEditFormCallback();
		if($cb) $cb($form, $this);

		return $form;
	}
}

/**
 * Detail form to save the record when first created before editing, allowing images 
 * to be attached to the gallery item immediately.
 */
class GridFieldDetailForm_Gallery extends GridFieldDetailForm {

  public function handleItem($gridField, $request) {
    $controller = $gridField->getForm()->Controller();

    if(is_numeric($request->param('ID'))) {
      $record = $gridField->getList()->byId($request->param("ID"));
    } 
    else if (is_numeric($request->latestParam('ID'))) {
      $record = $gridField->getList()->byId($request->latestParam("ID"));
    }
    else {
      $record = Object::create($gridField->getModelClass()); 
      $record->write();
      $gridField->setList($gridField->getList()->add($record));
    }

    $class = $this->getItemRequestClass();

    $handler = Object::create($class, $gridField, $this, $record, $controller, $this->name);
    $handler->setTemplate($this->template);

    return $handler->handleRequest($request, DataModel::inst());
  }
}

/**
 * Simply to manage the return URL from the doDelete() action, unnecessary once this pull request is accepted:
 * https://github.com/silverstripe/sapphire/pull/852
 * http://open.silverstripe.org/ticket/7927
 */
class GridFieldDetailForm_GalleryItemRequest extends GridFieldDetailForm_ItemRequest {

  public function ItemEditForm() {

    if (empty($this->record)) {
      $controller = Controller::curr();
      $noActionURL = $controller->removeAction($_REQUEST['url']);
      $controller->getResponse()->removeHeader('Location');   //clear the existing redirect
      return $controller->redirect($noActionURL, 302);
    }

    $actions = new FieldList();
    if($this->record->ID !== 0) {
      $actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Save', 'Save'))
        ->setUseButtonTag(true)
        ->addExtraClass('ss-ui-action-constructive')
        ->setAttribute('data-icon', 'accept'));

      $actions->push(FormAction::create('doDelete', _t('GridFieldDetailForm.Delete', 'Delete'))
        ->addExtraClass('ss-ui-action-destructive'));

    }
    else { // adding new record
      //Change the Save label to 'Create'
      $actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Create', 'Create'))
        ->setUseButtonTag(true)
        ->addExtraClass('ss-ui-action-constructive')
        ->setAttribute('data-icon', 'add'));
        
      // Add a Cancel link which is a button-like link and link back to one level up.
      $crumbs = $this->Breadcrumbs();

      if ($crumbs && $crumbs->count()>=2) {
        $one_level_up = $crumbs->offsetGet($crumbs->count()-2);
        $text = sprintf(
          "<a class=\"%s\" href=\"%s\">%s</a>",
          "crumb ss-ui-button ss-ui-action-destructive cms-panel-link ui-corner-all", // CSS classes
          $one_level_up->Link, // url
          _t('GridFieldDetailForm.CancelBtn', 'Cancel') // label
        );
        $actions->push(new LiteralField('cancelbutton', $text));
      }
    }

    $form = new Form(
      $this,
      'ItemEditForm',
      $this->record->getCMSFields(),
      $actions,
      $this->component->getValidator()
    );
    if($this->record->ID !== 0) {
      $form->loadDataFrom($this->record);
    }

    // TODO Coupling with CMS
    $toplevelController = $this->getToplevelController();
    if($toplevelController && $toplevelController instanceof LeftAndMain) {
      // Always show with base template (full width, no other panels), 
      // regardless of overloaded CMS controller templates.
      // TODO Allow customization, e.g. to display an edit form alongside a search form from the CMS controller
      $form->setTemplate('LeftAndMain_EditForm');
      $form->addExtraClass('cms-content cms-edit-form center');
      $form->setAttribute('data-pjax-fragment', 'CurrentForm Content');
      if($form->Fields()->hasTabset()) {
        $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
        $form->addExtraClass('ss-tabset cms-tabset');
      }

      $form->Backlink = $this->getBackLink();
    }

    $cb = $this->component->getItemEditFormCallback();
    if($cb) $cb($form, $this);

    return $form;
  }

  public function doDelete($data, $form) {

    try {

      $toDelete = $this->record;
      if (!$toDelete->canDelete()) {
        throw new ValidationException(
          _t('GridFieldDetailForm.DeletePermissionsFailure',"No delete permissions"),0);
      }
      $toDelete->delete();

    } catch(ValidationException $e) {
      $form->sessionMessage($e->getResult()->message(), 'bad');
      return Controller::curr()->redirectBack();
    }

    $toplevelController = $this->getToplevelController();
    if($toplevelController && $toplevelController instanceof LeftAndMain) {
      $backForm = $toplevelController->getEditForm();

      $message = sprintf(
        _t('GridFieldDetailForm.Deleted', 'Deleted %s %s'),
        $this->record->singular_name(),
        ''
      );

      $backForm->sessionMessage($message, 'good');
    }

    //when an item is deleted, redirect to the revelant admin section without the action parameter
    $controller = Controller::curr();
    $controller->getRequest()->addHeader('X-Pjax', 'Content'); // Force a content refresh
    return $controller->redirect($this->getBacklink(), 302); //redirect back to admin section
  }

  protected function getBackLink(){
    // TODO Coupling with CMS
    $backlink = '';
    $toplevelController = $this->getToplevelController();
    if($toplevelController && $toplevelController instanceof LeftAndMain) {
      if($toplevelController->hasMethod('Backlink')) {
        $backlink = $toplevelController->Backlink();
      } elseif($this->popupController->hasMethod('Breadcrumbs')) {
        $parents = $this->popupController->Breadcrumbs(false)->items;
        $backlink = array_pop($parents)->Link;
      } else {
        $backlink = $toplevelController->Link();
      }
    }
    return $backlink;
  }
}