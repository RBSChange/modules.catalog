<?php
/**
 * catalog_DeclinedproductService
 * @package catalog
 */
class catalog_DeclinedproductService extends catalog_ProductService
{
	/**
	 * @var catalog_DeclinedproductService
	 */
	private static $instance;

	/**
	 * @return catalog_DeclinedproductService
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
	 * @return catalog_persistentdocument_declinedproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/declinedproduct');
	}

	/**
	 * Create a query based on 'modules_catalog/declinedproduct' model.
	 * Return document that are instance of modules_catalog/declinedproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/declinedproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/declinedproduct' model.
	 * Only documents that are strictly instance of modules_catalog/declinedproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/declinedproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getDefaultDeclination($document, $shop)
	{
		$id = $this->findDefaultDeclinationId($document, $shop);
		if ($id !== null)
		{
			return DocumentHelper::getDocumentInstance($id, 'modules_catalog/productdeclination');
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param catalog_persistentdocument_shop $shop
	 * @return integer or null
	 */
	private function findDefaultDeclinationId($document, $shop)
	{
		$query =  catalog_PriceService::getInstance()->createQuery()
					->add(Restrictions::published())
					->add(Restrictions::eq('shopId', $shop->getId()))
					->setProjection(Projections::property('productId'));
					
		$declinationQuery = $query->createPropertyCriteria('productId', 'modules_catalog/productdeclination')
			->add(Restrictions::published())
			->add(Restrictions::eq('declinedproduct', $document));
			
		if (!$shop->getDisplayOutOfStock())
		{
			$declinationQuery->add(Restrictions::ne('stockLevel', catalog_StockService::LEVEL_UNAVAILABLE));
			$query->addOrder(Order::asc('valuewithtax'));
			$query->setMaxResults(1);
			$rows = $query->find();
			if (count($rows)) 
			{
				return $rows[0]['productId'];
			}	
		}
		else
		{
			$declinationQuery->setProjection(Projections::property('stockLevel'));
			$query->addOrder(Order::asc('valuewithtax'));
			$unavailable = null;
			foreach ($query->find() as $row) 
			{
				if ($row['stockLevel'] != catalog_StockService::LEVEL_UNAVAILABLE)
				{
					return $row['productId'];
				}
				else if ($unavailable === null)
				{
					$unavailable =$row['productId'];
				}
			}
			return $unavailable;
		}
		return null;	
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getDisplayableDeclinations($document, $shop)
	{
		$result = array();
		foreach ($document->getDeclinationArray() as $declination)
		{
			if (!$declination->isPublished())
			{
				continue;
			}
			else if ($shop->getDisplayOutOfStock() && $declination->getStockLevel == catalog_StockService::LEVEL_UNAVAILABLE)
			{
				continue;
			}
			else if ($declination->getPrice($shop, null) !== null)
			{
				$result[] = $declination;
			}
		}
		return $result;
	}
	
	/**
	 * @param Integer $productId
	 * @param String $sortOnField
	 * @param String $sortDirection
	 */
	public function getSortedDeclinationsById($productId, $sortOnField = 'document_label', $sortDirection = 'asc')
	{
		$query = catalog_ProductdeclinationService::getInstance()->createQuery()
			->add(Restrictions::eq('declinedproduct.id', $productId));
		if ($sortDirection == 'desc')
		{
			$query->addOrder(Order::idesc($sortOnField));
		}
		else
		{
			$query->addOrder(Order::iasc($sortOnField));
		}
		return $query->find();
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param Double $quantity
	 * @param Array<String, Mixed> $properties
	 * @return catalog_persistentdocument_product
	 * @see order_CartService::addProduct()
	 */
	public function getProductToAddToCart($product, $shop, $quantity, $properties)
	{
		return $this->getDefaultDeclination($product, $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		parent::preSave($document, $parentNodeId);

		// Synchronize shelf property with declinations.
		if ($document->isPropertyModified('shelf'))
		{
			foreach ($document->getDeclinationArray() as $declination)
			{
				$declination->setShelfArray($document->getShelfArray());
			}
		}
		
		// Label for url equals label by default.
		$label = $document->getLabelForUrl();
		if (is_null($label) || strlen($label) == 0)
		{
			$document->setLabelForUrl($document->getLabel());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		parent::preDelete($document);	
		$declinations = $document->getDeclinationArray();
		$document->removeAllDeclination();
		$document->setDeclinationsToDelete($declinations);
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::postDelete()
	 *
	 * @param catalog_persistentdocument_declinedproduct $document
	 */
	protected function postDelete($document)
	{
		$declinations = $document->getDeclinationsToDelete();
		if (f_util_ArrayUtils::isNotEmpty($declinations))
		{
			foreach ($declinations as $declination)
			{
				if ($declination instanceof catalog_persistentdocument_productdeclination) 
				{
					$declination->getDocumentService()->fullDelete($declination);
				}
			}			
		}
	}

	/**
	 * @return Boolean
	 */
	public function hasIdsForSitemap()
	{
		return true;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Integer $maxUrl
	 * @return array
	 */
	public function getIdsForSitemap($website, $maxUrl)
	{
		$shop = catalog_ShopService::getInstance()->getPublishedByWebsite($website);
		if ($shop === null)
		{
			return array();
		}
		return $this->createQuery()->add(Restrictions::eq('compiledproduct.shopId', $shop->getId()))->setMaxResults($maxUrl)->setProjection(Projections::property('id', 'id'))->findColumn('id');
	}
		
	/**
	 * @param catalog_persistentdocument_declinedproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		return $document->getPublishedDeclinationCount() > 0 && parent::isPublishable($document);
	}
}