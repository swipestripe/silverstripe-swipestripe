<?php
class ProductImage extends DataObject
{
  static $db = array (
    'Caption' => 'Text'
  );

  static $has_one = array (
    'Image' => 'Image',
    'Product' => 'Product'
  );

  public function getCMSFields_forPopup() {
    
    $fields = new FieldSet();
    $fields->push(new TextareaField('Caption'));
    
    if (class_exists('ImageUploadField')) $fields->push(new ImageUploadField('Image'));
    else $fields->push(new FileIFrameField('Image'));
    
    return $fields;
  }
  
  function ThumbnailSummary() {
    if ($Image = $this->Image()) return $Image->CMSThumbnail();
    else return '(No Image)';
  }
  
  function fortemplate() {
    if ($Image = $this->Image()) return $Image->CroppedImage(40,40)->forTemplate();
    else return '(No Image)';
  }
}