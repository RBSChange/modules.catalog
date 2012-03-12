<?php
/**
 * catalog_BillingareafolderService
 * @package modules.catalog
 */
class catalog_BillingareafolderService extends generic_FolderService
{
	/**
	 * @var catalog_BillingareafolderService
	 */
	private static $instance;

	/**
	 * @return catalog_BillingareafolderService
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
	 * @return catalog_persistentdocument_billingareafolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/billingareafolder');
	}

	/**
	 * Create a query based on 'modules_catalog/billingareafolder' model.
	 * Return document that are instance of modules_catalog/billingareafolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/billingareafolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/billingareafolder' model.
	 * Only documents that are strictly instance of modules_catalog/billingareafolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/billingareafolder', false);
	}
	
	/**
	 * @return catalog_persistentdocument_billingareafolder
	 */
	public function getDefault()
	{
		$folders = $this->createQuery()->find();
		if (count($folders))
		{
			return $folders[0];
		}
		
		$folder = $this->getNewDocumentInstance();
		$this->save($folder);
		TreeService::getInstance()->newFirstChild(ModuleService::getInstance()->getRootFolderId('catalog') , $folder->getId());
		return $folder;
	}
	
	/**
	 * @param catalog_persistentdocument_billingareafolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		if (f_util_StringUtils::isEmpty($document->getLabel()))
		{
			$document->setLabel('billingareafolder');
		}
	}		
}