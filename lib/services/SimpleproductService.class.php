<?php
/**
 * catalog_SimpleproductService
 * @package catalog
 */
class catalog_SimpleproductService extends catalog_ProductService
{
	/**
	 * @var catalog_SimpleproductService
	 */
	private static $instance;
	
	/**
	 * @return catalog_SimpleproductService
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
	 * @return catalog_persistentdocument_simpleproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/simpleproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/simpleproduct' model.
	 * Return document that are instance of modules_catalog/simpleproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/simpleproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/simpleproduct' model.
	 * Only documents that are strictly instance of modules_catalog/simpleproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/simpleproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_simpleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		
		// Initialize label for url.
		$label = $document->getLabelForUrl();
		if (is_null($label) || strlen($label) == 0)
		{
			$document->setLabelForUrl($document->getLabel());
		}
	}
	
	
	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
	{
	}
		
	/**
	 * @return boolean
	 * @see catalog_ModuleService::getProductModelsThatMayAppearInCarts()
	 */
	public function mayAppearInCarts()
	{
		return true;
	}
	
	/**
	 * @return array
	 */
	public function getPropertyNamesForMarkergas()
	{
		return array('catalog/simpleproduct' => array(
			'label' => 'label',
			'brand' => 'brand/label',
			'codeReference' => 'codeReference'
		));
	}
}