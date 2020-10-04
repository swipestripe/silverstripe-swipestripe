<?php

namespace SwipeStripe\Core\Form;

use SilverStripe\Forms\HiddenField;
use SwipeStripe\Core\code\Form\ModificationField_Dropdown;
use SilverStripe\Forms\DropdownField;

/**
 * For displaying a {@link Modifier} on the {@link CheckoutPage} which will inject details
 * into {@link Order} {@link Modifications}.
 *
 * The hidden field stores the {@link Modifier} ID.
 */
class ModificationField_Hidden extends HiddenField
{
    
    /**
     * Template for rendering
     *
     * @var String
     */
    protected $template = ModificationField_Hidden::class;

    /**
     * To hold the modifier (link FlatFeeShipping) class that will set the value for the
     * order {@link Modification}.
     *
     * @var Object
     */
    protected $modifier;
    
    /**
     * Creates a new optionset field for order modifers with the naming convention
     * Modifiers[ClassName] where ClassName is name of modifier class.
     *
     * @param name The field name, needs to be the class name of the class that is going to be the modifier
     * @param title The field title
     * @param source An map of the dropdown items
     * @param value The current value
     * @param form The parent form
     */
    public function __construct($modifier, $title = null, $value = "", $maxLength = null, $form = null)
    {
        $name = "Modifiers[" . get_class($modifier) . "]";
        $this->modifier = $modifier;

        parent::__construct($name, $title, $value, $maxLength, $form);
    }
    
    /**
     * Render field with the appropriate template.
     *
     * @see FormField::FieldHolder()
     * @return String
     */
    public function FieldHolder($properties = array())
    {
        return $this->renderWith($this->template);
    }
    
    /**
     * Validation is not currently done on this field at this point.
     *
     * @see FormField::validate()
     */
    public function validate($validator)
    {
        return true;
    }
    
    /**
     * Get the modifier e.g: FlatFeeShipping
     *
     * @return Object Mixed object
     */
    public function getModifier()
    {
        return $this->modifier;
    }
    
    /**
     * A description to show alongside the hidden field on the {@link CheckoutForm}.
     * For instance, this might be a calculated value.
     *
     * @return String Description of the modifier e.g: a calculated value of tax
     */
    public function Description()
    {
        return;
    }
    
    /**
     * Does not modify {@link Order} sub total by default.
     *
     * @return Boolean False
     */
    public function modifiesSubTotal()
    {
        return false;
    }
}

/**
 * For displaying a set of modifiers on the {@link CheckoutPage} which will inject their details
 * into {@link Order} {@link Modifications}.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class ModificationField_Dropdown extends DropdownField
{
    
    /**
     * Template for rendering
     *
     * @var String
     */
    protected $template = ModificationField_Dropdown::class;

    /**
     * To hold the modifier (link FlatFeeShipping) class that will set the value for the
     * order {@link Modification}.
     *
     * @var Object
     */
    protected $modifier;
    
    /**
     * Creates a new optionset field for order modifers with the naming convention
     * Modifiers[ClassName] where ClassName is name of modifier class.
     *
     * @param name The field name, needs to be the class name of the class that is going to be the modifier
     * @param title The field title
     * @param source An map of the dropdown items
     * @param value The current value
     * @param form The parent form
     */
    public function __construct($modifier, $title = "", $source = array(), $value = "", $form = null)
    {
        $className = get_class($modifier);
        $name = "Modifiers[$className]";
        $this->modifier = $modifier;

        parent::__construct($name, $title, $source, $value, $form);
    }
    
    /**
     * Render field with the appropriate template.
     *
     * @see FormField::FieldHolder()
     * @return String
     */
    public function FieldHolder($properties = array())
    {
        return $this->renderWith($this->template);
    }
    
    /**
     * Validation is not currently done on this field at this point.
     *
     * @see FormField::validate()
     */
    public function validate($validator)
    {
        return true;
    }
    
    /**
     * Get the modifier e.g: FlatFeeShipping
     *
     * @return Object Mixed object
     */
    public function getModifier()
    {
        return $this->modifier;
    }
    
    /**
     * Does not modify {@link Order} sub total by default.
     *
     * @return Boolean False
     */
    public function modifiesSubTotal()
    {
        return false;
    }
}
