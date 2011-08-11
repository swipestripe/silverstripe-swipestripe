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
    return new FieldSet(
      new TextareaField('Caption'),
      new FileIFrameField('Image')
    );
  }
}