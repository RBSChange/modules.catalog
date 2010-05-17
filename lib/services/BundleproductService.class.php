<?php
/**
 * catalog_BundleproductService
 * @package modules.catalog
 */
class catalog_BundleproductService extends catalog_ProductService
{
	/**
	 * @var catalog_BundleproductService
	 */
	private static $instance;

	/**
	 * @return catalog_BundleproductService
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
	 * @return catalog_persistentdocument_bundleproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/bundleproduct');
	}

	/**
	 * Create a query based on 'modules_catalog/bundleproduct' model.
	 * Return document that are instance of modules_catalog/bundleproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/bundleproduct' model.
	 * Only documents that are strictly instance of modules_catalog/bundleproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/bundleproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $bundleProduct
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_bundleditem or null
	 */
	protected function getBundledItemByProduct($bundleProduct, $product)
	{
		foreach ($bundleProduct->getBundleditemArray() as $bundledItem) 
		{	
			if (DocumentHelper::isEquals($product, $bundledItem->getProduct()))
			{
				return $bundledItem;
			}
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		parent::preInsert($document, $parentNodeId);
		$newProducts = $document->getNewBundledItemProductsDocument();
		$bis = catalog_BundleditemService::getInstance();
		foreach ($newProducts as $product) 
		{
			$bundledItem = $this->getBundledItemByProduct($document, $product);
			if ($bundledItem === null)
			{
				$bundledItem = $bis->getNewDocumentInstance();
				$bundledItem->setLabel($product->getVoLabel());
				$bundledItem->setQuantity(intval($document->getNewBundledItemQtt()));
				$bundledItem->setProduct($product);
				$bis->save($bundledItem);
				$document->addBundleditem($bundledItem);
			}
		}
		$document->resetNewBundledItemProducts();
		
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId = null)
	{	
		parent::preUpdate($document, $parentNodeId);
		$newProducts = $document->getNewBundledItemProductsDocument();
		$bis = catalog_BundleditemService::getInstance();
		foreach ($newProducts as $product) 
		{
			Framework::info(__METHOD__ . ' ' . $product->__toString());
			$bundledItem = $this->getBundledItemByProduct($document, $product);
			if ($bundledItem === null)
			{
				$bundledItem = $bis->getNewDocumentInstance();
				$bundledItem->setLabel($product->getVoLabel());
				$bundledItem->setQuantity(intval($document->getNewBundledItemQtt()));
				$bundledItem->setProduct($product);
				$bis->save($bundledItem);
				Framework::info(__METHOD__ . ' item ' . $bundledItem->__toString());
				$document->addBundleditem($bundledItem);
			}
		}
		$document->resetNewBundledItemProducts();	
	}	
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId = null)
	{
		parent::postUpdate($document, $parentNodeId);
		$bundledItemsToDelete = $document->getBundledItemsToDelete();
		foreach ($bundledItemsToDelete as $bundledItem) 
		{
			$bundledItem->delete();
		}
	}
	

	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			foreach ($this->getBundledProductArray($document) as $product) 
			{
				if (!$product->isPublished())
				{
					$statusInfo = f_Locale::translateUI('&modules.catalog.bo.general.Product-not-published;', array('label' => $product->getVoLabel()));
					$this->setActivePublicationStatusInfo($document, $statusInfo);
					return false;
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getBundledProductArray($document)
	{
		$result = array();
		if ($document->getBundleditemCount())
		{
			foreach ($document->getBundleditemArray() as $bundledItem) 
			{
				$result[] = $bundledItem->getProduct();
			}	
		}
		return $result; 
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_product $product
	 * @return array
	 */
	public function getByBundledProduct($shop, $product)
	{
		$query = $this->createStrictQuery()
		->add(Restrictions::published())
		->add(Restrictions::eq('bundleditem.product', $product));
		
		$query->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('shopId', $shop->getId()));
		
		return $query->find();
	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId = null)
//	{
//		parent::preSave($document, $parentNodeId);
//
//	}
	
	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId = null)
//	{
//		parent::postInsert($document, $parentNodeId);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId = null)
//	{
//		parent::postSave($document, $parentNodeId);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//		parent::preDelete($document);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//		parent::preDeleteLocalized($document);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//		parent::postDelete($document);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//		parent::postDeleteLocalized($document);
//	}




	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//		parent::onCorrectionActivated($document, $args);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//		parent::tagAdded($document, $tag);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//		parent::tagRemoved($document, $tag);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param catalog_persistentdocument_bundleproduct $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedTo($fromDocument, $toDocument, $tag);
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//		parent::onMoveToStart($document, $destId);
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//		parent::onDocumentMoved($document, $destId);
//	}

	/**
	 * this method is call before save the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param f_persistentdocument_PersistentDocument $newDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * @param catalog_persistentdocument_bundleproduct $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}
}