<?php
/**
 * catalog_ProductService
 * @package modules.catalog
 */
class catalog_ProductService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var catalog_ProductService
	 */
	private static $instance = null;

	/**
	 * @return catalog_ProductService
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
	 * @return catalog_persistentdocument_product
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/product');
	}

	/**
	 * Create a query based on 'modules_catalog/product' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/product');
	}

	/**
	 * @param catalog_persistentdocument_product $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
	public function getSolrserachResultItemTemplate($document, $bockName)
	{
		return array('module' => 'catalog', 'template' => 'Catalog-Inc-ProductResultDetail');
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getPrimaryShelf($product, $website = null)
	{
		if ($website === null)
		{
			return $product->getShelf(0);
		}
		else 
		{
			
			$compiledProduct = catalog_CompiledproductService::getInstance()->createQuery()
				->add(Restrictions::eq('product', $product))
				->add(Restrictions::eq('indexed', true))
				->add(Restrictions::eq('websiteId', $website->getId()))
				->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
				->findUnique();
				
			if ($compiledProduct !== null)
			{
				return DocumentHelper::getDocumentInstance($compiledProduct->getShelfId(), 'modules_catalog/shelf');
			}
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @return string
	 */
	public function getPathForShop($product, $shop)
	{
		$primaryShelf = $this->getPrimaryShelf($product, $shop->getWebsite());
		if ($primaryShelf === null)
		{
			return '';
		}
		
		$path = array();
		foreach ($primaryShelf->getDocumentService()->getAncestorsOf($primaryShelf) as $ancestor)
		{
			if ($ancestor instanceof catalog_persistentdocument_shelf)
			{
				$path[] = $ancestor->getLabel();
			}
		}
		$path[] = $primaryShelf->getLabel();
		return implode(' > ', $path);
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function getDefaultVisual($product, $shop)
	{
		$visual = null;

		// get visual from the product
		if ($visual === null)
		{
			$visual = $product->getVisual();
		}

		// ... or from shop
		if ($visual === null && $shop !== null)
		{
			$visual = $shop->getDefaultDetailVisual();
		}

		// ... or from module preferences
		if ($visual === null)
		{
			$visual = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultDetailVisual');
		}
		return $visual;
	}

	/**
	 * @param integer $articleId
	 * @return array<catalog_persistentdocument_product>
	 */
	public function getByArticleId($articleId)
	{
		return $this->createQuery('modules_catalog/product')
			->add(Restrictions::eq('articleId', $articleId))
			->find();
	}

	/**
	 * @param catalog_persistentdocument_product $article
	 * @return array<catalog_persistentdocument_product>
	 */
	public function getByArticle($article)
	{
		return $this->getByArticleId($article->getId());
	}

	/**
	 * @param Integer $brandId
	 * @return f_persistentdocument_criteria_Query
	 */
	public function getByBrandId($brandId)
	{
		return $this->createQuery()
			->add(Restrictions::eq('brand.id', $brandId))
			->find();
	}

	/**
	 * @param Integer $brandId
	 * @return array<catalog_persistentdocument_product>
	 */
	public function getByBrand($brand)
	{
 		return $this->getByBrandId($brand->getId());
	}

	/**
	 * @param catalog_persistentdocument_product $document
	 * @param Integer $websiteId
	 */
	public function getRatingAverage($document, $websiteId = null)
	{
		if (catalog_ModuleService::getInstance()->areCommentsEnabled())
		{
			return comment_CommentService::getInstance()->getRatingAverageByTargetId($document->getId(), $websiteId);
		}
		return null;
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
		// TODO: is there a more simple implementation?
		$methodName = 'getPublished'.ucfirst($type).'Array';
		if (!f_util_ClassUtils::methodExists($document, $methodName))
		{
			throw new Exception('Bad method name: '.$methodName);
		}
		$products = $document->{$methodName}();

		if (count($products))
		{
			$query = catalog_ProductService::getInstance()->createQuery()
				->add(Restrictions::in('id', DocumentHelper::getIdArrayFromDocumentArray($products)));
			$query->createCriteria('compiledproduct')
				->add(Restrictions::eq('shopId', $shop->getId()))
				->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
				->add(Restrictions::published());
			$displayableProducts = $query->find();
		}
		else 
		{
			return array();
		}
		
		if ($sortBy == 'random')
		{
			shuffle($displayableProducts);
		}
		else if ($sortBy == 'fieldorder')
		{
			$displayableIds = DocumentHelper::getIdArrayFromDocumentArray($displayableProducts);
			$displayableProducts = array();
			foreach ($products as $product)
			{
				if (in_array($product->getId(), $displayableIds))
				{
					$displayableProducts[] = $product;
				}
			}
		}
		return $displayableProducts;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param Double $quantity
	 * @param Array<String, Mixed> $properties
	 * @return catalog_persistentdocument_product
	 * @see order_CartService::addProduct()
	 */
	public function getProductToAddToCart($product, $shop, $quantity, $properties)
	{
		return $product;
	}
	
	/**
	 * @var catalog_persistentdocument_shop
	 */
	private $currentShopForResume = null;
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		if ($this->currentShopForResume !== null)
		{
			return $this->currentShopForResume->getWebsite()->getId();
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
		
		$data['properties']['compiled'] = f_Locale::translateUI('&framework.boolean.' . 
			($document->getCompiled() ? 'True' : 'False') . ';');
		
		$data['properties']['alertCount'] = catalog_AlertService::getInstance()->getPublishedCountByProduct($document);
			
		try 
		{
			$rc->beginI18nWork($lang);
			
			$urlData = array();
			
			$shops = array();
			foreach ($this->getContainingShopsIds($document) as $shopId)
			{
				$shop = DocumentHelper::getDocumentInstance($shopId);
				$websiteId = $shop->getWebsite()->getId();
				if ($shop->isPublished() || !isset($shops[$websiteId]))
				{
					$shops[$websiteId] = $shop;
				}				
			}			
			foreach ($shops as $shop)
			{
				$urlData[] = array(
					'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.Url-for-website;', array('website' => $shop->getWebsite()->getLabel())), 
					'href' => str_replace('&amp;', '&', $this->generateUrlForShop($document, $shop, $lang, array(), false)),
					'class' => $shop->isPublished() ? 'link' : ''
				);
			}
			
			$data['urlrewriting'] = $urlData;
									
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		
		return $data;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param string $lang
	 * @param array $parameters
	 * @param Boolean $useCache
	 * @return string
	 */
	public function generateUrlForShop($product, $shop, $lang = null, $parameters = array(), $useCache = true)
	{
		$this->currentShopForResume = $shop;
		$url = LinkHelper::getDocumentUrl($product, $lang, $parameters, $useCache);
		$this->currentShopForResume = null;
		return $url;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return Integer
	 */
	private function getContainingShopsIds($product)
	{
		return catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product.id', $product->getId()))
			->setProjection(Projections::groupProperty('shopId'))
			->findColumn('shopId');
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId = null)
	{
		if ($document->isPropertyModified('shelf') && $document->isPublished())
		{
			$this->onShelfPropertyModified($document);
		}
		
		// Handle similar and complementary products synchronization.
		$cms = catalog_ModuleService::getInstance();
		if ($document->isPropertyModified('complementary') && $cms->isComplementarySynchroEnabled())
		{
			$this->synchronizeField($document, 'complementary');
		}
		if ($document->isPropertyModified('similar') && $cms->isSimilarSynchroEnabled())
		{
			$this->synchronizeField($document, 'similar');
		}
		
		// Generate compiled products.
		$this->updateCompiledProperty($document, false);
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 */
	protected function onShelfPropertyModified($document)
	{
		// Update shelves counters.
		// This must be done here, because recursive post count is
		// refreshed by a query so, the update must be done at this time.

		$oldIds = $document->getShelfOldValueIds();
		$currentShelves = $document->getShelfArray();
		$ss = catalog_ShelfService::getInstance();
		
		// Increment product count for added shelves.
		$currentIds = array();
		foreach ($currentShelves as $shelf)
		{
			if (!in_array($shelf->getId(), $oldIds))
			{
				$ss->incrementPublishedDocumentCount($shelf);
			}
			$currentIds[] = $shelf->getId();
		}
		
		// Decrement product count for removed shelves.
		foreach ($oldIds as $shelfId)
		{
			if (!in_array($shelfId, $currentIds))
			{
				$shelf = DocumentHelper::getDocumentInstance($shelfId);
				$ss->decrementPublishedDocumentCount($shelf);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param String $fieldName
	 */
	protected function synchronizeField($document, $fieldName)
	{
		$ucfirstFieldName = ucfirst($fieldName);
		$oldIds = $document->{'get'.$ucfirstFieldName.'OldValueIds'}();
		$currentProducts = $document->{'get'.$ucfirstFieldName.'Array'}();
		
		// Update added products.
		$productsToSave = array();
		$currentIds = array();
		foreach ($currentProducts as $product)
		{
			if (!in_array($product->getId(), $oldIds))
			{
				if ($product->{'getIndexof'.$ucfirstFieldName}($document) == -1)
				{
					$product->{'add'.$ucfirstFieldName}($document);
					$product->save();
				}
			}
			$currentIds[] = $product->getId();
		}
		
		// Update removed products.
		foreach ($oldIds as $productId)
		{
			if (!in_array($productId, $currentIds))
			{
				$product = DocumentHelper::getDocumentInstance($productId);
				if ($product->{'getIndexof'.$ucfirstFieldName}($document) != -1)
				{
					$product->{'remove'.$ucfirstFieldName}($document);
					$product->save();
				}
			}
		}
		
		return $productsToSave;
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
	 * @param catalog_persistentdocument_product $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		$document->persistHasNewTranslation();
	}

	/**
	 * @param catalog_persistentdocument_product $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		// Delete compiled products.
		$this->deleteRelatedCompiledProducts($document);
		catalog_PriceService::getInstance()->deleteForProductId($document->getId());
	}
	
	
	/**
	 * @see f_persistentdocument_DocumentService::postDeleteLocalized()
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected function postDeleteLocalized($document)
	{
		$this->updateCompiledProperty($document, false);
	}

	/**
	 * @param catalog_persistentdocument_product $document
	 */
	protected function deleteRelatedCompiledProducts($document)
	{
		catalog_CompiledproductService::getInstance()->deleteForProduct($document);
	}
		
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function fullDelete($product)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' START for product: ' . $product->__toString());
		}
		try 
		{
			$this->tm->beginTransaction();
			$langs = array_reverse($product->getI18nInfo()->getLangs());
			$rc = RequestContext::getInstance();
			foreach ($langs as $lang) 
			{
				try 
				{
					$rc->beginI18nWork($lang);
					$product->delete();
					$rc->endI18nWork();
				}
				catch (Exception $rce)
				{
					$rc->endI18nWork($rce);
				}
			}
			$this->tm->commit();
		}
		catch (Exception $tme)
		{
			$this->tm->rollBack($tme);
			throw $tme;
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' END for product: ' . $product->__toString());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		$this->refreshShelfPublishedDocumentCount($document, $oldPublicationStatus);	
		
		// Handle compilation.
		if (!isset($params['cause']) || $params["cause"] != "delete")
		{
			if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
			{	
				$this->updateCompiledProperty($document, false);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param String $oldPublicationStatus
	 */
	protected function refreshShelfPublishedDocumentCount($document, $oldPublicationStatus)
	{		
		// Status transit from ACTIVE to PUBLICATED.
		if ($document->isPublished())
		{
			$ss = catalog_ShelfService::getInstance();
			foreach ($document->getShelfArray() as $shelf)
			{
				$ss->incrementPublishedDocumentCount($shelf);
			}
		}
		// Status transit from PUBLICATED to ACTIVE.
		else if ($oldPublicationStatus == 'PUBLICATED')
		{
			$ss = catalog_ShelfService::getInstance();
			foreach ($document->getShelfArray() as $shelf)
			{
				$ss->decrementPublishedDocumentCount($shelf);
			}
		}		
	}
	
	
	/**
	 * @return Boolean
	 */
	public function hasIdsForSitemap()
	{
		return false;
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @return website_persistentdocument_page
	 */
	public function getDisplayPage($document)
	{
		$model = $document->getPersistentModel();
		if ($model->hasURL() && $document->isPublished())
		{
			$shelf = $this->getPrimaryShelf($document, website_WebsiteModuleService::getInstance()->getCurrentWebsite());
			if ($shelf !== null)
			{
				$topic = $shelf->getDocumentService()->getRelatedTopicByShop($shelf, catalog_ShopService::getInstance()->getCurrentShop());
				$page = website_PageService::getInstance()->createQuery()->add(Restrictions::childOf($topic->getId()))->add(Restrictions::published())->add(Restrictions::hasTag('functional_catalog_product-detail'))->findUnique();
				return $page;
			}
		}
		return null;
	}
	
	/**
	 * @return Boolean
	 * @see catalog_ModuleService::getProductModelsThatMayAppearInCarts()
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function mayAppearInCarts()
	{
		return false;
	}
	
	/**
	 * @return Boolean
	 * @see markergas_lib_cMarkergasEditorTagReplacer::preRun()
	 */
	public function getPropertyNamesForMarkergas()
	{
		return array();
	}
	
	/**
	 * @return integer[]
	 */
	public final function getProductIdsToCompile()
	{
		return $this->createQuery()
			->add(Restrictions::eq('compiled', false))
			->setProjection(Projections::property('id', 'id'))
			->findColumn('id');
	}
	
	/**
	 * @return integer
	 */
	public final function getCountProductIdsToCompile()
	{
		$data = $this->createQuery()
			->add(Restrictions::eq('compiled', false))
			->setProjection(Projections::rowCount('count'))
			->findColumn('count');
		return $data[0];
	}	

	/**
	 * @return integer[]
	 */
	public final function getAllProductIdsToCompile()
	{
		return $this->createQuery()->setProjection(Projections::property('id', 'id'))
			->findColumn('id');
	}	
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return integer[]
	 */
	protected final function getCompiledProductIdsForShelf($shelf)
	{
		$shelfIds = catalog_ShelfService::getInstance()->createQuery()
			->add(Restrictions::descendentOf($shelf->getId()))
			->setProjection(Projections::groupProperty('id', 'id'))
			->findColumn('id');
		$shelfIds[] = $shelf->getId();
		
		$productIds = $this->createQuery()->add(Restrictions::eq('compiled', true))
			->add(Restrictions::in('shelf.id', $shelfIds))
			->setProjection(Projections::groupProperty('id', 'id'))
			->findColumn('id');
					
		return $productIds;
	}
	

	/**
	 * @param integer[] $productIds
	 */
	public function setNeedCompile($productIds)
	{
		if (f_util_ArrayUtils::isNotEmpty($productIds))
		{
			try 
			{
				$this->tm->beginTransaction();
				foreach ($productIds as $productId) 
				{
					$product = $this->getDocumentInstance($productId, 'modules_catalog/product');
					$product->getDocumentService()->updateCompiledProperty($product, false);
				}
				$this->tm->commit();
			} 
			catch (Exception $e)
			{
				$this->tm->rollBack($e);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function setNeedCompileForShop($shop)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__);
		}
		$productIds = array();		
		foreach ($shop->getTopShelfArray() as $topShelf) 
		{
			$productIds = array_merge($productIds, $this->getCompiledProductIdsForShelf($topShelf));
		}
		
		$compiledProdIds = $this->createQuery()->add(Restrictions::eq('compiled', true))
			->add(Restrictions::eq('compiledproduct.shopId', $shop->getId()))		
			->setProjection(Projections::groupProperty('id', 'id'))
			->findColumn('id');
					
		$productIds = array_unique(array_merge($productIds, $compiledProdIds));
		
		$this->setNeedCompile($productIds);
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 */
	public function setNeedCompileForShelf($shelf)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__);
		}
		$ids = $this->getCompiledProductIdsForShelf($shelf);
		$this->setNeedCompile($ids);
	}

	/**
	 * @param brand_persistentdocument_brand $brand
	 */
	public function setNeedCompileForBrand($brand)
	{
		$ids = $this->createQuery()->add(Restrictions::eq('compiled', true))
			->add(Restrictions::eq('brand', $brand))		
			->setProjection(Projections::groupProperty('id', 'id'))
			->findColumn('id');		
		$this->setNeedCompile($ids);
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 */
	public function setNeedCompileForPrice($price)
	{
		$product = $price->getProduct();
		if ($product instanceof catalog_persistentdocument_product) 
		{
			$this->updateCompiledProperty($product, false);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param boolean $compiled
	 */
	protected function updateCompiledProperty($document, $compiled)
	{
		$product = $document->getProductToCompile();
		if ($product !== null && $product->getCompiled() != $compiled)
		{
			if ($product->isModified())
			{
				Framework::warn(__METHOD__ . $product->__toString() . ", $compiled : Is not possible on modified product");
				return;		
			}
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' ' . $product->__toString() . ($compiled ? ' is compiled':' to recompile'));
			}
			$product->setCompiled($compiled);
			$this->pp->updateDocument($product);
		}
	}
	
	/**
	 * @param integer $productId
	 */
	public function setCompiled($productId)
	{
		$product = $this->getDocumentInstance($productId, 'modules_catalog/product');
		$product->getDocumentService()->updateCompiledProperty($product, true);
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 */
	public function replicatePrice($price)
	{
		// Nothing to do bu default.
	}
}