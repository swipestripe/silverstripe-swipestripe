<?php
/**
 * Patched ManyManyComplexTableField 
 * @see http://open.silverstripe.org/attachment/ticket/6737/mmctf.diff
 * 
 * Special ComplexTableField for editing a many_many relation.
 * 
 * This field  allows you to show a **many-to-many** relation with a group of 
 * DataObjects as a (readonly) tabular list (similiar to {@link ComplexTableField}). 
 * Its most useful when you want to manage the relationship itself 
 * thanks to the check boxes present on each line of the table.
 * 
 * See {@link ComplexTableField} for more documentation on the base-class.
 * See {@link HasManyComplexTableField} for more documentation on the relation table base-class.
 * 
 * Note: This class relies on the fact that both sides of the relation have database tables. 
 * If you are only creating a class as a logical extension (that is, it doesn't have any database fields), 
 * then you will need to create a dummy static $db array because SilverStripe won't create a database 
 * table unless needed.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * $tablefield = new ManyManyComplexTableField(
 *     $this,
 *     'MyFruits',
 *     'Fruit',
 *     array(
 * 	'Name' => 'Name',
 * 	'Color' => 'Color'
 *     ),
 *     'getCMSFields_forPopup'
 * );
 * </code>
 * 
 * @package forms
 * @subpackage fields-relational
 */
class BelongsManyManyComplexTableField extends HasManyComplexTableField {
	
	private $manyManyParentClass, $manyManyTable;
	
	public $itemClass = 'BelongsManyManyComplexTableField_Item';
		
	function __construct($controller, $name, $sourceClass, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {

		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);
		
		$classes = array_reverse(ClassInfo::ancestry($this->controllerClass()));
		foreach($classes as $class) {
			$singleton = singleton($class);
			$manyManyRelations = $singleton->uninherited('many_many', true);
			if(isset($manyManyRelations) && array_key_exists($this->name, $manyManyRelations)) {
				$this->manyManyParentClass = $class;
				$this->manyManyTable = $class . '_' . $this->name;
				break;
			}
			$belongsManyManyRelations = $singleton->uninherited( 'belongs_many_many', true );
			 if( isset( $belongsManyManyRelations ) && array_key_exists( $this->name, $belongsManyManyRelations ) ) {
				$singleton = singleton($belongsManyManyRelations[$this->name]);
				$manyManyRelations = $singleton->uninherited('many_many', true);
				$this->manyManyParentClass = $class;
				$relation = array_flip($manyManyRelations);
				$this->manyManyTable = $belongsManyManyRelations[$this->name] . '_' . $relation[$class];
				break;
			}
		}
		$tableClasses = ClassInfo::dataClassesFor($this->sourceClass);
		$source = array_shift($tableClasses);
		$sourceField = $this->sourceClass;
		if($this->manyManyParentClass == $sourceField)
			$sourceField = 'Child';
		$parentID = $this->controller->ID;
		
		$this->sourceJoin .= " LEFT JOIN \"{$this->manyManyTable}\" ON (\"$source\".\"ID\" = \"{$this->manyManyTable}\".\"{$sourceField}ID\" AND \"{$this->manyManyTable}\".\"{$this->manyManyParentClass}ID\" = '$parentID')";
		
		$this->joinField = 'Checked';
	}
		
	function getQuery() {
		$query = parent::getQuery();//var_dump($query);die;
		$query->select[] = "CASE WHEN \"{$this->manyManyTable}\".\"{$this->manyManyParentClass}ID\" IS NULL THEN '0' ELSE '1' END AS \"Checked\"";
		$query->groupby[] = "\"{$this->manyManyTable}\".\"{$this->manyManyParentClass}ID\""; // necessary for Postgres

		return $query;
	}
		
	function getParentIdName($parentClass, $childClass) {
		return $this->getParentIdNameRelation($parentClass, $childClass, 'many_many');
	}
}

/**
 * One record in a {@link BelongsManyManyComplexTableField}.
 * @package forms
 * @subpackage fields-relational
 */
class BelongsManyManyComplexTableField_Item extends ComplexTableField_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->Name() . '[]';
		
		if($this->parent->IsReadOnly)
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if($this->item->{$this->parent->joinField})
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}
