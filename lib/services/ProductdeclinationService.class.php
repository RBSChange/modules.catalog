<?php
/**
 * catalog_ProductdeclinationService
 * @package catalog
 */
class catalog_ProductdeclinationService extends catalog_ProductService
{
	/**
	 * @var catalog_ProductdeclinationService
	 */
	private static $instance;

	/**
	 * @return catalog_ProductdeclinationService
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
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/productdeclination');
	}

	/**
	 * Create a query based on 'modules_catalog/productdeclination' model.
	 * Return document that are instance of modules_catalog/productdeclination,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/productdeclination');
	}
	
	/**
	 * Create a query based on 'modules_catalog/productdeclination' model.
	 * Only documents that are strictly instance of modules_catalog/productdeclination
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/productdeclination', false);
	}
		
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param catalog_persistentdocument_shop $shop
	 * @param String $type from list 'modules_catalog/crosssellingtypes'
	 * @param String $sortBy from list 'modules_catalog/crosssellingsortby'
	 * @return catalog_persistentdocument_product[]
	 */
	public function getDisplayableCrossSelling($document, $shop, $type = 'complementary', $sortBy = 'fieldorder')
	{
		return $document->getDocumentService()->getDisplayableCrossSelling($document, $shop, $type, $sortBy);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		parent::preInsert($document, $parentNodeId);
		
		$document->setInsertInTree(false);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		if ($parentNodeId !== null)
		{
			$declinedProduct = DocumentHelper::getDocumentInstance($parentNodeId);
			$declinedProduct->addDeclination($document);
			$declinedProduct->save();
		}
		
		parent::postInsert($document, $parentNodeId);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);
		
		// Handle stocks.
		$stockServie = catalog_StockService::getInstance();
		$stockServie->setLevelFromQuantity($document);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		if ($document->isStatusModified('en'))
		{
			$declinedProduct = $document->getRelatedDeclinedProduct();
			$declinedProduct->getDocumentService()->publishIfPossible($declinedProduct->getId());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId = null)
	{
		parent::postSave($document, $parentNodeId);
		
		// Handle stock alerts.
		$stockServie = catalog_StockService::getInstance();
		$stockServie->handleStockAlert($document);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param String $document $lang
	 */
	protected function deleteRelatedCompiledProducts($document, $lang = null)
	{
		$declinedProduct = $document->getProductToCompile();
		if ($declinedProduct !== null)
		{
			catalog_CompiledproductService::getInstance()->compileAllInfos($declinedProduct);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		$declinedProduct = $document->getRelatedDeclinedProduct();
		if ($declinedProduct !== null)
		{
			$declinedProduct->getDocumentService()->publishIfPossible($declinedProduct->getId());
		}
		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function refreshCompilationOnPublicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		// Refresh compiled products publication.
		$productToCompile = $document->getProductToCompile();
		if ($productToCompile !== null)
		{
			catalog_CompiledproductService::getInstance()->compileProductInfos($productToCompile);
		}
	}
	
	/**
	 * @return Boolean
	 * @see catalog_ModuleService::getProductModelsThatMayAppearInCarts()
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function mayAppearInCarts()
	{
		return true;
	}
	
	/**
	 * @return Boolean
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function getPropertyNamesForMarkergas()
	{
		return array(
			'catalog/productdeclination' => array(
				'label' => 'label',
				'codeReference' => 'codeReference'
			),
			'catalog/declinedproduct' => array(
				'label' => 'relatedDeclinedProduct/label',
				'brand' => 'relatedDeclinedProduct/brand/label',
				'codeReference' => 'relatedDeclinedProduct/codeReference'
			)
		);
	}
}