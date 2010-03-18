<?php
/**
 * catalog_AttributefolderService
 * @package catalog
 */
class catalog_AttributefolderService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_AttributefolderService
	 */
	private static $instance;

	/**
	 * @return catalog_AttributefolderService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return catalog_persistentdocument_attributefolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/attributefolder');
	}

	/**
	 * Create a query based on 'modules_catalog/attributefolder' model.
	 * Return document that are instance of modules_catalog/attributefolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/attributefolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/attributefolder' model.
	 * Only documents that are strictly instance of modules_catalog/attributefolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/attributefolder', false);
	}
	
	/**
	 * @return catalog_persistentdocument_attributefolder
	 */
	public function getAttributeFolder()
	{
		// There should be only one shopfolder.
		return $this->createQuery()->findUnique();
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getAttributesForProduct($product)
	{
		$attrFolder = $this->getAttributeFolder();
		return$attrFolder ? $attrFolder->getAttributes() : array();
	}
}