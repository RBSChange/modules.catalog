<?php
/**
 * @package modules.catalog.lib.services
 */
class catalog_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var catalog_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return catalog_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		if ($document instanceof catalog_persistentdocument_price)
		{
			return $document->getProduct();
		}
		else if ($document instanceof catalog_persistentdocument_productdeclination)
		{
			return $document->getRelatedDeclinedProduct();
		}
		else if ($document instanceof catalog_persistentdocument_product)
		{
			return $document->getShelf(0);
		}
		return null;
	}
	
	/**
	 * @return Boolean
	 */
	public function areCommentsEnabled()
	{
		$ms = ModuleService::getInstance();
		return $ms->moduleExists('comment') && $ms->getPreferenceValue('catalog', 'enableComments');
	}
	
	/**
	 * @return Boolean
	 */
	public function isCartEnabled()
	{
		return ModuleService::getInstance()->moduleExists('order');
	}
	
	/**
	 * @return Boolean
	 */
	public function areCustomersEnabled()
	{
		return ModuleService::getInstance()->moduleExists('customer');
	}
	
	/**
	 * @return Boolean
	 */
	public function isComplementarySynchroEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'synchronizeComplementary');
	}
	
	/**
	 * @return Boolean
	 */
	public function isSimilarSynchroEnabled()
	{
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'synchronizeSimilar');
	}
	
	/**
	 * @return String[]
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function getProductModelsThatMayAppearInCarts()
	{
		$model = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'product');
		$models = array();
		foreach ($model->getChildrenNames() as $model)
		{
			$modelInstance = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($model);
			if ($modelInstance->getDocumentService()->mayAppearInCarts())
			{
				$models[] = $model;
			}
		}
		return $models;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function addConsultedProduct($product)
	{
		return $this->getSessionLists()->addConsultedProductId($product->getId());
	}
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getConsultedProducts()
	{
		return $this->getSessionLists()->getConsultedProducts();
	}	
	
	/**
	 * @return integer[]
	 */
	public function getConsultedProductIds()
	{
		return $this->getSessionLists()->getConsultedProductIds();
	}	
		
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function addFavoriteProduct($product)
	{
		if ($this->getSessionLists()->addFavoriteProductId($product->getId()))
		{
			$productList = catalog_ProductlistService::getInstance()->getCurrentProductList();
			if ($productList)
			{
				$productList->addProduct($product);
				$productList->save();
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function removeFavoriteProduct($product)
	{
		if ($this->getSessionLists()->removeFavoriteProductId($product->getId()))
		{
			$productList = catalog_ProductlistService::getInstance()->getCurrentProductList();
			if ($productList)
			{
				$productList->removeProduct($product);
				$productList->save();
			}			
			return true;
		}
		return false;
	}
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getFavoriteProducts()
	{
		return $this->getSessionLists()->getFavoriteProducts();
	}		
	
	/**
	 *
	 * @param catalog_persistentdocument_productlist $productList
	 */
	public function mergeFavoriteProductWithList($productList)
	{
		if ($productList)
		{
			$currentProducts = $this->getSessionLists()->getFavoriteProducts();
			foreach ($currentProducts as $product) 
			{
				$productList->addProduct($product);
			}
			foreach ($productList->getProductArray() as $product) 
			{
				if ($product->isPublished())
				{
					$this->addFavoriteProduct($product);
				}
				else
				{
					$productList->removeProduct($product);
				}
			}
			$productList->save();
		}
	}

	/**
	 * @var catalog_SessionLists
	 */
	private $sessionLists;
	
	/**
	 * @return catalog_SessionLists
	 */
	private function getSessionLists()
	{
		if ($this->sessionLists === null)
		{
			$sessionUser = Controller::getInstance()->getContext()->getUser();
			if ($sessionUser->hasAttribute('SessionLists', 'catalog'))
			{
				$this->sessionLists = $sessionUser->getAttribute('SessionLists', 'catalog');
			}
			if (!$this->sessionLists instanceof catalog_SessionLists) 
			{
				$this->saveSessionLists(new catalog_SessionLists());
			}
			if (Framework::isDebugEnabled())
			{
				Framework::debug(var_export($this->sessionLists, true));
			}
		}
		return $this->sessionLists;
	}

	/**
	 * @param catalog_SessionLists $sessionLists
	 */
	private function saveSessionLists($sessionLists)
	{
		$sessionUser = Controller::getInstance()->getContext()->getUser();
		if ($sessionLists instanceof catalog_SessionLists)
		{
			$sessionUser->setAttribute('SessionLists', $sessionLists, 'catalog');
			$this->sessionLists = $sessionLists; 
		}
		else
		{
			$sessionUser->removeAttribute('SessionLists', 'catalog');
			$this->sessionLists = null;
		}
	}
}

class catalog_SessionLists
{
	private $consultedProductIds;
	
	private $favoriteProductIds;
	
	private $comparedProductIds;
	
	/**
	 * @return integer[]
	 */
	public function getConsultedProductIds()
	{
		if (!is_array($this->consultedProductIds))
		{
			$this->consultedProductIds = array();
		}
		return $this->consultedProductIds;
	}
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getConsultedProducts()
	{
		return $this->convertToProductArray($this->getConsultedProductIds());
		
	}
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function addConsultedProduct($product)
	{
		$this->addConsultedProductId($product->getId());
	}	
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function addConsultedProductId($id)
	{
		$ids = $this->getConsultedProductIds();
		if (!in_array($id, $ids))
		{
			$this->consultedProductIds = array_slice(array_merge(array($id), $ids),0,10);
			return true;
		}
		return false;
	}
	
	
	/**
	 * @return integer[]
	 */
	public function getFavoriteProductIds()
	{
		if (!is_array($this->favoriteProductIds))
		{
			$this->favoriteProductIds = array();
		}
		return $this->favoriteProductIds;
	}
	
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	public function getFavoriteProducts()
	{
		return $this->convertToProductArray($this->getFavoriteProductIds());
		
	}
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function addFavoriteProduct($product)
	{
		$this->addFavoriteProductId($product->getId());
	}	
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function addFavoriteProductId($id)
	{
		$ids = $this->getFavoriteProductIds();
		if (!in_array($id, $ids))
		{
			$this->favoriteProductIds = array_merge(array($id), $ids);
			return true;
		}
		return false;
	}	
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function removeFavoriteProductId($id)
	{
		$ids = $this->getFavoriteProductIds();
		if (in_array($id, $ids))
		{
			$data = array();
			foreach ($ids as $oid) 
			{
				if ($oid == $id) {continue;}
				$data[] = $oid;
			}
			$this->favoriteProductIds = $data;
			return true;
		}
		return false;
	}
	
	/**
	 * @param integer[] $ids
	 * @return catalog_persistentdocument_product[]
	 */
	private function convertToProductArray($ids)
	{
		if (count($ids) > 0)
		{
			$result = array();
			$ds = f_persistentdocument_DocumentService::getInstance();
			foreach ($ids as $id) 
			{
				try 
				{
					$product = $ds->getDocumentInstance($id, 'modules_catalog/product');
					if ($product->isPublished())
					{
						$result[] = $product;
					}
				}
				catch (Exception $e)
				{
					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . ' ' . $e->getMessage());
					}
				}
			}
			return $result;
		}
		return array();		
	}
}