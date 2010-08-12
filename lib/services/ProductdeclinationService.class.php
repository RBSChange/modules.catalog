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
	 * @see catalog_ProductService::getPrimaryShelf()
	 *
	 * @param catalog_persistentdocument_productdeclination $product
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getPrimaryShelf($product, $website)
	{
		$declinedProduct = $product->getRelatedDeclinedProduct();
		return $declinedProduct->getDocumentService()->getPrimaryShelf($declinedProduct, $website);
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
	protected function postInsert($document, $parentNodeId)
	{
		if ($parentNodeId !== null)
		{
			$declinedProduct = DocumentHelper::getDocumentInstance($parentNodeId);
			$declinedProduct->addDeclination($document);
			$declinedProduct->save();
			
			// Price replication.
			if ($declinedProduct->getSynchronizePrices())
			{
				$prices = catalog_PriceService::getInstance()->createQuery()->add(Restrictions::eq('productId', $declinedProduct->getId()))->find();
				$this->replicatePricesOnDeclinationIds($prices, array($document->getId()));
			}
		}
		
		parent::postInsert($document, $parentNodeId);
	}
	
	/**
	 * @param catalog_persistentdocument_price[] $prices
	 * @param integer[] $declinationIds
	 */
	public function replicatePricesOnDeclinationIds($prices, $declinationIds)
	{
		try 
		{
			$this->tm->beginTransaction();
			foreach ($prices as $price) 
			{
				if (!$price->hasMeta(catalog_PriceService::META_IS_REPLICATED))
				{
					$price->setMeta(catalog_PriceService::META_IS_REPLICATED, '1');
					$price->saveMeta();
				}
				
				foreach ($declinationIds as $declinationId)
				{
					$this->replicatePriceOnDeclinationId($price, $declinationId);
				}
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 * @param integer $productId
	 * @return catalog_persistentdocument_lockedprice
	 */
	protected function replicatePriceOnDeclinationId($price, $productId)
	{
		$newPrice = catalog_LockedpriceService::getInstance()->getNewDocumentInstance();
		$price->copyPropertiesTo($newPrice, true);
		$newPrice->setMeta("f_tags", array());
		$newPrice->setAuthor(null);
		$newPrice->setAuthorid(null);
		$newPrice->setCreationdate(null);
		$newPrice->setModificationdate(null);
		$newPrice->setDocumentversion(0);
		$newPrice->setProductId($productId);
		$newPrice->setLockedFor($price->getId());
		$newPrice->save();	
		return $newPrice;
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
		
		// Handle stock alerts
		catalog_StockService::getInstance()->handleStockAlert($document);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 */
	protected function onShelfPropertyModified($document)
	{
		//productdeclination do not update shelfCount
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 */
	protected function deleteRelatedCompiledProducts($document)
	{
		$this->updateCompiledProperty($document, false);
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
	
	/**
	 * @param catalog_persistentdocument_productdeclination $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function getPrimaryCompiledProductForWebsite($product, $website)
	{
		$declinedProduct = $product->getRelatedDeclinedProduct();
		return $declinedProduct->getDocumentService()->getPrimaryCompiledProductForWebsite($declinedProduct, $website);
	}
}
