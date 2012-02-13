<?php

class RemoveItemAction extends FormAction {

  function __construct($action, $title = "", $form = null, $extraData = null, $extraClass = '') {

    $this->description = "Remove item #$title";
		parent::__construct($action, $title, $form, $extraData, $extraClass);
	}
	
  function id() { 
		$name = ereg_replace('(^-)|(-$)','',ereg_replace('[^A-Za-z0-9_-]+','-',$this->name)) . '_' . $this->title;
		if($this->form) return $this->form->FormName() . '_' . $name;
		else return $name;
	}
}