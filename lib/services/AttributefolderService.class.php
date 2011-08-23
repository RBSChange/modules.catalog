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
			self::$instance = new self();
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
	 * Cache for attributefolder
	 * @var catalog_persistentdocument_attributefolder
	 */
	private $attributeFolder;
	
	private $attributeInfos;
	
	/**
	 * @return catalog_persistentdocument_attributefolder
	 */
	public function getAttributeFolder()
	{
		if ($this->attributeFolder === null)
		{
			// There should be only one shopfolder.
			$this->attributeFolder = $this->createQuery()->findUnique();	
		}
		return $this->attributeFolder;
	}
	
	protected function postSave($document, $parentNodeId)
	{
		$this->resetAttributeFolderCache();
	}
	
	protected function postDelete($document)
	{
		$this->resetAttributeFolderCache();
	}
	
	protected function resetAttributeFolderCache()
	{
		$this->attributeFolder = null;
		$this->attributeInfos = null;
	}
	
	/**
	 * @param String $code
	 * @return array
	 * @throws Exception if attribute does not exist
	 */
	public function getAttributeInfo($code)
	{
		if ($this->attributeInfos === null) 
		{
			$attrs = $this->getAttributeFolder()->getAttributes();
			$this->attributeInfos = array();
			if (f_util_ArrayUtils::isNotEmpty($attrs))
			{
				foreach ($attrs as $attrInfo) 
				{
					$this->attributeInfos[$attrInfo['code']] = $attrInfo;
				}
			}
		}
		if (isset($this->attributeInfos[$code]))
		{
			return $this->attributeInfos[$code];
		}
		
		throw new Exception("Attribute $code does not exist");
	}
	
	/**
	 * @return array
	 */
	public function getAttributesForProduct($product)
	{
		$attrFolder = $this->getAttributeFolder();
		return $attrFolder ? $attrFolder->getAttributes() : array();
	}
}