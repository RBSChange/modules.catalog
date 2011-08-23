<?php
/**
 * catalog_NoshelfproductfolderService
 * @package modules.catalog
 */
class catalog_NoshelfproductfolderService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_NoshelfproductfolderService
	 */
	private static $instance;

	/**
	 * @return catalog_NoshelfproductfolderService
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
	 * @return catalog_persistentdocument_noshelfproductfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/noshelfproductfolder');
	}

	/**
	 * Create a query based on 'modules_catalog/noshelfproductfolder' model.
	 * Return document that are instance of modules_catalog/noshelfproductfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/noshelfproductfolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/noshelfproductfolder' model.
	 * Only documents that are strictly instance of modules_catalog/noshelfproductfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/noshelfproductfolder', false);
	}
	
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		$totalCount = f_util_ArrayUtils::firstElement(catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::in("model", $subModelNames))
			->add(Restrictions::isEmpty("shelf"))
			->setProjection(Projections::rowCount("c"))
			->findColumn("c"));
		$totalCount += f_util_ArrayUtils::firstElement(catalog_DeclinedproductService::getInstance()->createQuery()
			->add(Restrictions::isEmpty("shelf"))
			->setProjection(Projections::rowCount("c"))
			->findColumn("c"));

		$productsInfo = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::in("model", $subModelNames))
			->add(Restrictions::isEmpty("shelf"))
			->setProjection(Projections::property("id", "id"), Projections::property("label", "l"))
			->find();
		$declinedProductsInfo = catalog_DeclinedproductService::getInstance()->createQuery()
			->setProjection(Projections::property("id", "id"), Projections::property("label", "l"))
			->add(Restrictions::isEmpty("shelf"))
			->find();
			
		$productsInfo = array_merge($productsInfo, $declinedProductsInfo);
		uasort($productsInfo, array($this, "orderInfosbyLabel"));
		
		$products = array();
		foreach (array_slice($productsInfo, $startIndex, $pageSize) as $productInfo)
		{
			$products[] = DocumentHelper::getDocumentInstance($productInfo["id"]);
		}
		
		return $products;
	}
	
	/**
	 * @param array $a
	 * @param array $b
	 * @return integer
	 */
	private function orderInfosByLabel($a, $b)
	{
		return ($a["l"] < $b["l"]) ? -1 : 1;
	}
}