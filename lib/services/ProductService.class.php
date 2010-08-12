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
	public function getSolrsearchResultItemTemplate($document, $bockName)
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
			$compiledProduct = $this->getPrimaryCompiledProductForWebsite($product, $website);
			if ($compiledProduct !== null)
			{
				return DocumentHelper::getDocumentInstance($compiledProduct->getShelfId(), 'modules_catalog/shelf');
			}
		}
		return null;
	}
	
	/**
	 * @param unknown_type $product
	 * @param unknown_type $shop
	 */
	public function getPrimaryCompiledProductForWebsite($product, $website)
	{
		return catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product', $product))
			->add(Restrictions::eq('indexed', true))
			->add(Restrictions::eq('websiteId', $website->getId()))
			->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
			->findUnique();
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
		// get visual from the product.
		$visual = $product->getVisual();

		// ... or from shop.
		if ($visual === null && $shop !== null)
		{
			$visual = $shop->getDefaultDetailVisual();
		}

		// ... or from module preferences.
		if ($visual === null)
		{
			$visual = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultDetailVisual');
		}
		return $visual;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function getListVisual($product, $shop)
	{
		// get visual from the product.
		$visual = $product->getVisual();

		// ... or from shop
		if ($visual === null && $shop !== null)
		{
			$visual = $shop->getDefaultListVisual();
		}

		// ... or from module preferences
		if ($visual === null)
		{
			$visual = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultListVisual');
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
	 * @param catalog_persistentdocument_product $product
	 * @param array $properties
	 * @return void
	 */
	public function updateProductFromCartProperties($product, $properties)
	{
		return;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param array $properties
	 * @return void
	 */
	public function updateOrderLineProperties($product, &$properties)
	{
		return;
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
		unset($allowedSections['urlrewriting']);
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
			
			$query = catalog_CompiledproductService::getInstance()->createQuery()
				->add(Restrictions::eq('product.id', $document->getId()))
				->add(Restrictions::eq('indexed', true))
				->setProjection(Projections::property('shopId'), Projections::property('publicationstatus'));
			foreach ($query->find() as $row)
			{
				$shop = DocumentHelper::getDocumentInstance($row['shopId'], 'modules_catalog/shop');
				$urlData[] = array(
					'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.Url-for-website;', array('website' => $shop->getWebsite()->getLabel())), 
					'href' => str_replace('&amp;', '&', $this->generateUrlForShop($document, $shop, $lang, array(), false)),
					'class' => ($shop->isPublished() && $row['publicationstatus'] == 'PUBLICATED') ? 'link' : ''
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
	 * @param catalog_persistentdocument_product $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
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
			f_SimpleCache::clearCacheById($product->getId());
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
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_price $price
	 */
	public function replicatePrice($product, $price)
	{
		// Nothing to do by default.
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceByTargetIds($product, $shop, $targetIds, $quantity = 1)
	{
		return catalog_PriceService::getInstance()->getPriceByTargetIds($product, $shop, $targetIds, $quantity);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPricesByTargetIds($product, $shop, $targetIds)
	{
		return catalog_PriceService::getInstance()->getPricesByTargetIds($product, $shop, $targetIds);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return array
	 */
	public function getPublicationInShopsInfos($product)
	{
		if (!$product->getCompiled())
		{
			catalog_CompiledproductService::getInstance()->generateForProduct($product);
		}
		
		$query = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product.id', $product->getId()))
			->add(Restrictions::eq('indexed', true))
			->addOrder(Order::asc('shopId'));
		
		$compiledByShopId = array();
		foreach ($query->find() as $compiledProduct)
		{
			$shopId = $compiledProduct->getShopId();
			if (!isset($compiledByShopId[$shopId]))
			{
				$compiledByShopId[$shopId] = array();
			}
			$compiledByShopId[$shopId][] = $compiledProduct;
		}
		
		$result = array();		
		foreach ($compiledByShopId as $shopId => $compiledProducts)
		{
			$shopInfos = array();
			$shop = DocumentHelper::getDocumentInstance($shopId, 'modules_catalog/shop');
			$shopInfos['shopLabel'] = f_Locale::translateUI('&modules.catalog.bo.general.Shop-in-website;', array('shop' => $shop->getLabel(), 'website' => $shop->getWebsite()->getLabel()));		
			
			$shopInfos['products'] = array();
			foreach ($compiledProducts as $compiledProduct)
			{
				$lang = $product->getLang();
				$publication = f_Locale::translateUI(DocumentHelper::getPublicationstatusLocaleKey($compiledProduct));
				if ($compiledProduct->getPublicationStatus() === 'ACTIVE' && $compiledProduct->hasMeta('ActPubStatInf'.$lang))
				{
					$publication .= ' (' . f_Locale::translateUI($compiledProduct->getMeta('ActPubStatInf'.$lang)) . ')';
				}
				
				$shopInfos['products'][] = array(
					'lang' => $lang,
					'price' => ($compiledProduct->getPrice() !== null) ? $shop->formatPrice($compiledProduct->getPrice()) : '',
					'shelfLabel' => $compiledProduct->getShelf()->getLabel(),
					'plublication' => $publication
				);
			}
			
			$result[] = $shopInfos;
		}
		
		return $result;
	}
	
	/**
	 * This method is called before compiled product properties update.
	 * @see catalog_CompiledproductService::generate()
	 * 
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function updateCompiledMetas($product, $shop)
	{
		// Nothing to do by default.
	}
	
	// Tweets handling.
	
	/**
	 * @param catalog_persistentdocument_product $document or null
	 * @param integer $websiteId
	 * @return array
	 */
	public function getReplacementsForTweet($document, $websiteId)
	{
		$shop = catalog_ShopService::getInstance()->getPublishedByWebsiteId($websiteId);
		if ($shop === null)
		{
			return array();
		}
		
		$label = array(
			'name' => 'label',
			'label' => f_Locale::translateUI('&modules.catalog.document.product.Label;'),
			'maxLength' => 80
		);
		$shortUrl = array(
			'name' => 'shortUrl', 
			'label' => f_Locale::translateUI('&modules.twitterconnect.bo.general.Short-url;'),
			'maxLength' => 30
		);		
		if ($document !== null)
		{
			$label['value'] = f_util_StringUtils::shortenString($document->getLabel(), 80);
			$shortUrl['value'] = website_ShortenUrlService::getInstance()->shortenUrl(LinkHelper::getDocumentUrl($document));
		}	
		$replacements = array($label, $shortUrl);
		
		if ($document !== null)
		{
			$price = $document->getPrice($shop, null);
			if ($price !== null)
			{
				$replacements[] = array(
					'name' => 'price',
					'value' => $shop->formatPrice($price->getValueWithTax()),
					'label' => f_Locale::translateUI('&modules.catalog.bo.general.Price;'),
					'maxLength' => 10
				);
			}			
			$brand = $document->getBrand();
			if ($brand !== null)
			{
				$replacements[] = array(
					'name' => 'brand',
					'value' => f_util_StringUtils::shortenString($brand->getLabel()),
					'label' => f_Locale::translateUI('&modules.catalog.document.product.Brand;'),
					'maxLength' => 40
				);
			}
		}
		else 
		{
			$replacements[] = array(
				'name' => 'price',
				'label' => f_Locale::translateUI('&modules.catalog.bo.general.Price;'),
				'maxLength' => 10
			);
			$replacements[] = array(
				'name' => 'brand',
				'label' => f_Locale::translateUI('&modules.catalog.document.product.Brand;'),
				'maxLength' => 40
			);
		}
		
		return $replacements;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return website_persistentdocument_website[]
	 */
	public function getWebsitesForTweets($product)
	{
		$websites = array();
		$query = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product.id', $product->getId()))
			->add(Restrictions::eq('indexed', true))
			->setProjection(Projections::property('shopId'), Projections::property('publicationstatus'));
		foreach ($query->find() as $row)
		{
			$shop = DocumentHelper::getDocumentInstance($row['shopId'], 'modules_catalog/shop');
			if ($shop->isPublished())
			{
				$websites[] = $shop->getWebsite();
			}
		}
		return $websites;
	}
	
	/**
	 * @see twitterconnect_TweetService::setTweetOnPublishMeta()
	 * @param catalog_persistentdocument_product $document
	 * @param integer $websiteId
	 */
	public function setTweetOnPublishMeta($document, $websiteId)
	{
		$document->addMetaValue('modules.twitterconnect.tweetOnPublishForWebsite', $websiteId);
		$document->saveMeta();
		$query = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product.id', $document->getId()))
			->add(Restrictions::eq('websiteId', $websiteId))
			->add(Restrictions::eq('indexed', true));
		foreach ($query->find() as $compiledProduct)
		{
			$compiledProduct->setMeta('modules.twitterconnect.tweetOnPublish', $document->getId());
			$compiledProduct->saveMeta();
		}
	}
	
	/**
	 * @see twitterconnect_TweetService::removeTweetOnPublishMeta()
	 * @param catalog_persistentdocument_product $document
	 * @param integer $websiteId
	 */
	public function removeTweetOnPublishMeta($document, $websiteId)
	{
		$document->removeMetaValue('modules.twitterconnect.tweetOnPublishForWebsite', $websiteId);
		$document->saveMeta();
		$query = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product.id', $document->getId()))
			->add(Restrictions::eq('websiteId', $websiteId))
			->add(Restrictions::eq('indexed', true));
		foreach ($query->find() as $compiledProduct)
		{
			$compiledProduct->setMeta('modules.twitterconnect.tweetOnPublish', null);
			$compiledProduct->saveMeta();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getContainersForTweets($document)
	{
		$containers = array();
		if (ModuleService::getInstance()->moduleExists('marketing'))
		{
			$containers = array_merge($containers, marketing_EditableanimService::getInstance()->getByContainedProduct($document));
		}
		return $containers;
	}
	
	/**
	 * @return integer[]
	 */
	public function getAlreadyTweetedPublishedProductIds()
	{
		$query = twitterconnect_TweetService::getInstance()->createQuery();
		$query->createPropertyCriteria('relatedId', 'modules_catalog/product')->add(Restrictions::published());
		$query->setProjection(Projections::property('relatedId'));
		return $query->findColumn('relatedId');
	}
}