<?php

class ProductImageUploadField extends UploadField {

	protected $ufConfig = array(
		/**
		 * @var boolean
		 */
		'autoUpload' => true,
		/**
		 * php validation of allowedMaxFileNumber only works when a db relation is available, set to null to allow
		 * unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
		 * @var int
		 */
		'allowedMaxFileNumber' => null,
		/**
		 * @var int
		 */
		'previewMaxWidth' => 80,
		/**
		 * @var int
		 */
		'previewMaxHeight' => 60,
		/**
		 * javascript template used to display uploading files
		 * @see javascript/UploadField_uploadtemplate.js
		 * @var string
		 */
		'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
		/**
		 * javascript template used to display already uploaded files
		 * @see javascript/UploadField_downloadtemplate.js
		 * @var string
		 */
		'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
		/**
		 * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
		 * @example 'getCMSFields'
		 * @var FieldList|string
		 */
		'fileEditFields' => null,
		/**
		 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
		 * @example 'getCMSActions'
		 * @var FieldList|string
		 */
		'fileEditActions' => null,
		/**
		 * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
		 * @example 'getCMSValidator'
		 * @var string
		 */
		'fileEditValidator' => null
	);

	public function Field($properties = array()) {
		$record = $this->getRecord();
		$name = $this->getName();

		// if there is a has_one relation with that name on the record and 
		// allowedMaxFileNumber has not been set, it's wanted to be 1
		if(
			$record && $record->exists()
			&& $record->has_one($name) && !$this->getConfig('allowedMaxFileNumber')
		) {
			$this->setConfig('allowedMaxFileNumber', 1);
		}

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/i18n.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js');

		Requirements::combine_files('uploadfield.js', array(
			THIRDPARTY_DIR . '/javascript-templates/tmpl.js',
			THIRDPARTY_DIR . '/javascript-loadimage/load-image.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/cors/jquery.xdr-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload-ui.js',
			FRAMEWORK_DIR . '/javascript/UploadField_uploadtemplate.js',
			FRAMEWORK_DIR . '/javascript/UploadField_downloadtemplate.js',
			FRAMEWORK_DIR . '/javascript/UploadField.js',
		));

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('swipestripe/javascript/ProductImageUploadField.js');

		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css'); // TODO hmmm, remove it?
		Requirements::css(FRAMEWORK_DIR . '/css/UploadField.css');
		Requirements::css('swipestripe/css/ProductImageUploadField.css');

		$config = array(
			'url' => $this->Link('upload'),
			'urlSelectDialog' => $this->Link('select'),
			'urlAttach' => $this->Link('attach'),
			'urlSort' => $this->Link('sort'),
			'acceptFileTypes' => '.+$',
			'maxNumberOfFiles' => $this->getConfig('allowedMaxFileNumber')
		);
		if (count($this->getValidator()->getAllowedExtensions())) {
			$allowedExtensions = $this->getValidator()->getAllowedExtensions();
			$config['acceptFileTypes'] = '(\.|\/)(' . implode('|', $allowedExtensions) . ')$';
			$config['errorMessages']['acceptFileTypes'] = _t(
				'File.INVALIDEXTENSIONSHORT', 
				'Extension is not allowed'
			);
		}
		if ($this->getValidator()->getAllowedMaxFileSize()) {
			$config['maxFileSize'] = $this->getValidator()->getAllowedMaxFileSize();
			$config['errorMessages']['maxFileSize'] = _t(
				'File.TOOLARGESHORT', 
				'Filesize exceeds {size}',
				array('size' => File::format_size($config['maxFileSize']))
			);
		}
		if ($config['maxNumberOfFiles'] > 1) {
			$config['errorMessages']['maxNumberOfFiles'] = _t(
				'UploadField.MAXNUMBEROFFILESSHORT', 
				'Can only upload {count} files',
				array('count' => $config['maxNumberOfFiles'])
			);
		}
		$configOverwrite = array();
		if (is_numeric($config['maxNumberOfFiles']) && $this->getItems()->count()) {
			$configOverwrite['maxNumberOfFiles'] = $config['maxNumberOfFiles'] - $this->getItems()->count();
		}
		
		$config = array_merge($config, $this->ufConfig, $configOverwrite);

		return $this->customise(array(
			'configString' => str_replace('"', "'", Convert::raw2json($config)),
			'config' => new ArrayData($config),
			'multiple' => $config['maxNumberOfFiles'] !== 1,
			'displayInput' => (!isset($configOverwrite['maxNumberOfFiles']) || $configOverwrite['maxNumberOfFiles'])
		))->renderWith('ProductImageUploadField');
	}

	public function getItemHandler($itemID) {
		return ProductImageUploadField_ItemHandler::create($this, $itemID);
	}

	public function sort($request) {

		$fileIDs = $request->postVar('ids');

		if ($fileIDs && is_array($fileIDs)) foreach ($fileIDs as $order => $fileID) {
			$newOrder = $order + 1;

			//Get the file, set the sort order
			$file = Product_Image::get()
				->where("\"File\".\"ID\" = '{$fileID}'")
				->first();

			$file->SortOrder = $newOrder;
			$file->write();
		}
	}

}

class ProductImageUploadField_ItemHandler extends UploadField_ItemHandler {

	public function getItem() {
		return DataObject::get_by_id('Product_Image', $this->itemID);
	}

	public function edit(SS_HTTPRequest $request) {
		// Check form field state
		if($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

		// Check item permissions
		$item = $this->getItem();
		if(!$item) return $this->httpError(404);
		if(!$item->canEdit()) return $this->httpError(403);

		// Only allow actions on files in the managed relation (if one exists)
		$items = $this->parent->getItems();
		if($this->parent->managesRelation() && !$items->byID($item->ID)) return $this->httpError(403);

		Requirements::css(FRAMEWORK_DIR . '/css/UploadField.css');

		return $this->customise(array(
			'Form' => $this->EditForm()
		))->renderWith($this->parent->getTemplateFileEdit());
	}

	public function EditForm() {

		$file = $this->getItem();

		//Fields
		if (is_a($this->parent->getConfig('fileEditFields'), 'FieldList')) {
			$fields = $this->parent->getConfig('fileEditFields');
		} 
		elseif ($file->hasMethod($this->parent->getConfig('fileEditFields'))) {
			$fields = $file->{$this->parent->getConfig('fileEditFields')}();
		} 
		else {
			$fields = $file->getCMSFields();
			// Only display main tab, to avoid overly complex interface
			if($fields->hasTabSet() && $mainTab = $fields->findOrMakeTab('Root.Main')) $fields = $mainTab->Fields();
		}

		//Actions
		if (is_a($this->parent->getConfig('fileEditActions'), 'FieldList')) {
			$actions = $this->parent->getConfig('fileEditActions');
		} 
		elseif ($file->hasMethod($this->parent->getConfig('fileEditActions'))) {
			$actions = $file->{$this->parent->getConfig('fileEditActions')}();
		} 
		else {
			$actions = new FieldList($saveAction = new FormAction('doEdit', _t('UploadField.DOEDIT', 'Save')));
			$saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
		}

		//Validator
		if (is_a($this->parent->getConfig('fileEditValidator'), 'Validator')) {
			$validator = $this->parent->getConfig('fileEditValidator');
		} 
		elseif ($file->hasMethod($this->parent->getConfig('fileEditValidator'))) {
			$validator = $file->{$this->parent->getConfig('fileEditValidator')}();
		} 
		else {
			$validator = null;
		}

		$form = new Form(
			$this,
			__FUNCTION__, 
			$fields,
			$actions,
			$validator
		);
		$form->loadDataFrom($file);
		$form->addExtraClass('small');
		
		Requirements::css('swipestripe/css/ProductImageUploadField.css');
		return $form;
	}
}
