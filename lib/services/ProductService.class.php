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
	 * @param catalog_persistentdocument_shop $shop
	 * @return string
	 */
	public function getPathForShop($product, $shop)
	{
		$primaryShelf = $this->getShopPrimaryShelf($product, $shop);
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
	 * @return media_persistentdocument_media
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
	 * @param string $codeReference
	 * @return array<catalog_persistentdocument_product>
	 */
	public function getByCodeReference($codeReference)
	{
		return $this->createQuery('modules_catalog/product')
			->add(Restrictions::eq('codeReference', $codeReference))
			->find();
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
	public function getProductToAddToCart($product, $shop, $quantity, &$properties)
	{
		if (intval($product->getShippingModeId()) > 0)
		{
			$properties['shippingModeId'] = $product->getShippingModeId();
		}
		else if (isset($properties['shippingModeId']))
		{
			unset($properties['shippingModeId']);
		}
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
	 * @param catalog_persistentdocument_kit $product
	 * @param array $cartLineProperties
	 * @param order_persistentdocument_orderline $orderLine
	 * @param order_persistentdocument_order $order
	 */
	public function updateOrderLineProperties($product, &$cartLineProperties, $orderLine, $order)
	{
		$shop = $order->getShop();
		$cp = $product->getDocumentService()->getPrimaryCompiledProductForShop($product, $shop);
		if ($cp !== null)
		{
			$cartLineProperties['shelf'] = catalog_persistentdocument_shelf::getInstanceById($cp->getTopshelfId())->getLabel();
			$cartLineProperties['subshelf'] = $cp->getShelf()->getLabel();
		}
	}	
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param array $properties
	 * @return void
	 */
	public function updateProductFromRequestParameters($product, $parameters)
	{
		return;
	}	
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @return integer[] | null
	 */
	public function getWebsiteIds($document)
	{
		$ids = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product', $document))
			->setProjection(Projections::groupProperty('websiteId', 'id'))
			->findColumn('id');
		return $ids;
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
		$ls = LocaleService::getInstance();
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
		
		$data['properties']['compiled'] = $ls->transBO('f.boolean.' . ($document->getCompiled() ? 'true' : 'false'), array('ucf'));
		$data['properties']['alertCount'] = catalog_AlertService::getInstance()->getPublishedCountByProduct($document);		
		try 
		{
			$rc->beginI18nWork($lang);	
			$urlData = array();
			
			$query = catalog_CompiledproductService::getInstance()->createQuery()
				->add(Restrictions::eq('lang', $lang))
				->add(Restrictions::eq('product.id', $document->getId()))
				->add(Restrictions::eq('primary', true));
			foreach ($query->find() as $compiledProduct)
			{
				if ($compiledProduct instanceof catalog_persistentdocument_compiledproduct) 
				{
					$shop = $compiledProduct->getShop();
					$website = $compiledProduct->getWebsite();
					$href = website_UrlRewritingService::getInstance()->getDocumentLinkForWebsite($compiledProduct, $website, $lang)->setArgSeparator('&')->getUrl();
					$urlData[] = array(
						'label' => $ls->transBO('m.catalog.bo.doceditor.url-for-website', array('ucf'), array('website' => $website->getLabel())), 
						'href' => $href, 'class' => ($shop->isPublished() && $compiledProduct->isPublished()) ? 'link' : ''
					);
				}
				
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
	 * Used in productexporter to specify the products urls.
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
	public function generateUrlForExporter($product, $shop, $lang = null, $parameters = array())
	{
		if (!$shop->getIsDefault())
		{
			$parameters['catalogParam']['shopId'] = $shop->getId();
		}
		return website_UrlRewritingService::getInstance()->getDocumentLinkForWebsite($product, $shop->getWebsite(), $lang, $parameters)->getUrl();
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		if ($document->isPropertyModified('stockQuantity') && !$document->isPropertyModified('stockLevel'))
		{
			$this->updateStockLevel($document);
		}
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
		
		$this->synchronizeFields($document);
		
		// Generate compiled products.
		$this->updateCompiledProperty($document, false);
		
		//Send low stock alert notification
		catalog_StockService::getInstance()->handleStockAlert($document);
	}
		
	/**
	 * @param catalog_persistentdocument_product $document
	 */
	protected function onShelfPropertyModified($document)
	{
		$oldIds = $document->getShelfOldValueIds();
		$currentShelves = $document->getShelfArray();
		$ss = catalog_ShelfService::getInstance();
		
		$currentIds = array();
		foreach ($currentShelves as $shelf)
		{
			if (!in_array($shelf->getId(), $oldIds))
			{
				$ss->productPublished($shelf, $document);
			}
			$currentIds[] = $shelf->getId();
		}
		
		foreach ($oldIds as $shelfId)
		{
			if (!in_array($shelfId, $currentIds))
			{
				$shelf = DocumentHelper::getDocumentInstance($shelfId);
				$ss->productUnpublished($shelf, $document);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 */
	protected function synchronizeFields($document)
	{
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
		$currentIds = array();
		foreach ($currentProducts as $product)
		{
			if (!in_array($product->getId(), $oldIds))
			{
				$product->getDocumentService()->addProductToTargetField($product, $fieldName, $document);
			}
			$currentIds[] = $product->getId();
		}
		
		// Update removed products.
		foreach ($oldIds as $productId)
		{
			if (!in_array($productId, $currentIds))
			{
				$product = DocumentHelper::getDocumentInstance($productId);
				$product->getDocumentService()->removeProductFromTargetField($product, $fieldName, $document);
			}
		}
	}
	
	/**
	 * 
	 * @param catalog_persistentdocument_product $targetProduct
	 * @param string $fieldName
	 * @param catalog_persistentdocument_product $product
	 */
	public function addProductToTargetField($targetProduct, $fieldName, $product)
	{
		if ($targetProduct === $product) {return;}
		$ucfirstFieldName = ucfirst($fieldName);
		if ($targetProduct->{'getIndexof'.$ucfirstFieldName}($product) == -1)
		{
			$targetProduct->{'add'.$ucfirstFieldName}($product);
			$this->save($targetProduct);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $targetProduct
	 * @param string $fieldName
	 * @param catalog_persistentdocument_product $product
	 */
	public function removeProductFromTargetField($targetProduct, $fieldName, $product)
	{
		$ucfirstFieldName = ucfirst($fieldName);
		if ($targetProduct->{'getIndexof'.$ucfirstFieldName}($product) != -1)
		{
			$targetProduct->{'remove'.$ucfirstFieldName}($product);
			$this->save($targetProduct);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
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
		if ($document->getCodeSKU() === null)
		{
			$document->setCodeSKU(strval($document->getId()));
		}
		$this->updateRelatedProductsMetas($document, 'complementary');
		$this->updateRelatedProductsMetas($document, 'similar');
		$this->updateRelatedProductsMetas($document, 'upsell');
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
		$this->refreshShelfPublicationStatus($document, $oldPublicationStatus);			
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
	protected function refreshShelfPublicationStatus($document, $oldPublicationStatus)
	{		
		// Status transit from ACTIVE to PUBLICATED.
		if ($document->isPublished())
		{
			$ss = catalog_ShelfService::getInstance();
			foreach ($document->getShelfArray() as $shelf)
			{
				$ss->productPublished($shelf, $document);
			}
		}
		// Status transit from PUBLICATED to ACTIVE.
		else if ($oldPublicationStatus == 'PUBLICATED')
		{
			$ss = catalog_ShelfService::getInstance();
			foreach ($document->getShelfArray() as $shelf)
			{
				$ss->productUnpublished($shelf, $document);
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
		if ($document->isPublished())
		{
			$shopService = catalog_ShopService::getInstance();
			$request = HttpController::getInstance()->getContext()->getRequest();	
			$topicId = intval($request->getModuleParameter('catalog', 'topicId'));
			if ($topicId > 0)
			{
				$cp = catalog_CompiledproductService::getInstance()->getByProductInContext($document, $topicId, RequestContext::getInstance()->getLang());
				if ($cp)
				{
					$shopService->setCurrentShop($cp->getShop());
					return website_PageService::getInstance()->createQuery()->add(Restrictions::published())
						->add(Restrictions::childOf($topicId))
						->add(Restrictions::hasTag('functional_catalog_product-detail'))
						->findUnique();
				}
			}
			
			$shop = $shopService->getShopFromRequest('shopId');
			if ($shop === null)
			{
				$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
				$shop = $shopService->getDefaultByWebsite($website);
				if ($shop === null)
				{
					return null;
				}
			}

			$shopService->setCurrentShop($shop);
			$shelf = $this->getShopPrimaryShelf($document, $shop, true);
			if ($shelf !== null)
			{
				$topic = $shelf->getDocumentService()->getRelatedTopicByShop($shelf, $shop);
				if ($topic !== null)
				{
					return website_PageService::getInstance()->createQuery()->add(Restrictions::published())
						->add(Restrictions::childOf($topic->getId()))
						->add(Restrictions::hasTag('functional_catalog_product-detail'))
						->findUnique();
				}
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
		return $this->createQuery()->setProjection(Projections::property('id', 'id'))->findColumn('id');
	}

	/**
	 * @return integer[]
	 */
	public final function getAllProductIdsToFeedRelated()
	{
		return $this->createQuery()->setProjection(Projections::property('id', 'id'))->findColumn('id');
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
		$product = $document;
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
			f_DataCacheService::getInstance()->clearCacheByDocId($product->getId());
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
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 */
	public function updateCompiledProduct($product, $compiledProduct)
	{
		//TODO update compiledproduct properties if needed
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
			->add(Restrictions::eq('showInList', true))
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
				$lang = $compiledProduct->getLang();
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
	 * This method is called after à price has added on document
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_price $price
	 */
	public function priceAdded($product, $price)
	{
		// Nothing to do by default.
	}
	
	/**
	 * This method is called before à price has added on document
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_price $price
	 */
	public function priceRemoved($product, $price)
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
		$shop = catalog_ShopService::getInstance()->getDefaultByWebsiteId($websiteId);
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
			->add(Restrictions::eq('primary', true))
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
			->add(Restrictions::eq('primary', true));
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
			->add(Restrictions::eq('primary', true));
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
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param integer $complementaryMaxCount
	 * @param integer $similarMaxCount
	 * @param integer $upsellMaxCount
	 */
	public function feedRelatedProducts($document)
	{
		$this->feedField($document, 'complementary');
		$this->feedField($document, 'similar');
		$this->feedField($document, 'upsell');
		$document->save();
	}
	
	/**
	 * @param string $propertyName
	 * @return string
	 */
	private function getAutoAddedMetaKey($propertyName)
	{
		return 'auto-added.' . strtolower($propertyName);
	}
	
	/**
	 * @param string $propertyName
	 * @return string
	 */
	private function getManuallyExcludedMetaKey($propertyName)
	{
		return 'manually-excluded.' . strtolower($propertyName);
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param string $propertyName
	 */
	private function feedField($document, $propertyName)
	{
		$ms = ModuleService::getInstance();
		$feeder = $ms->getPreferenceValue('catalog', 'suggest' . ucfirst($propertyName) . 'FeederClass');
		$maxCount = $ms->getPreferenceValue('catalog', 'autoFeed' . ucfirst($propertyName) . 'MaxCount');
		if ($feeder && $feeder != 'none' && $maxCount > 0)
		{
			$metaAutoKey = $this->getAutoAddedMetaKey($propertyName);
			$metaExcludedKey = $this->getManuallyExcludedMetaKey($propertyName);
			$metaIds = $document->getMetaMultiple($metaAutoKey);
			$toRemove = $metaIds;
			$excludedIds = $document->getMetaMultiple($metaExcludedKey);
			$excludedIds[] = $document->getId();
			
			$parameters = array('productId' => $document->getId(), 'excludedId' => $excludedIds, 'maxResults' => $maxCount);
			$products = f_util_ClassUtils::callMethod($feeder, 'getInstance')->getProductArray($parameters);
			foreach ($products as $row)
			{
				$product = $row[0];
				$productId = $product->getId();
				if (!in_array($productId, $metaIds) && !in_array($productId, $excludedIds))
				{
					$document->{'add' . ucfirst($propertyName)}($product);
					$metaIds[] = $productId;
				}
				else 
				{
					unset($toRemove[array_search($productId, $toRemove)]);
				}
			}
			
			foreach ($toRemove as $id)
			{
				$product = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
				$document->{'remove' . ucfirst($propertyName)}($product);
				unset($metaIds[array_search($productId, $metaIds)]);
			}
			
			$value = array_values($metaIds);
			$document->setMetaMultiple($metaAutoKey, f_util_ArrayUtils::isEmpty($value) ? null : $value);
		}
	}	
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param string $propertyName
	 */
	private function updateRelatedProductsMetas($document, $propertyName)
	{
		if (!$document->isPropertyModified($propertyName))
		{
			return;
		}
		
		$currentIds = DocumentHelper::getIdArrayFromDocumentArray($document->{'get' . ucfirst($propertyName) . 'Array'}());
		
		$metaAutoKey = $this->getAutoAddedMetaKey($propertyName);
		$idsInMeta = $document->getMetaMultiple($metaAutoKey);
		$value = array_intersect($idsInMeta, $currentIds);
		$document->setMetaMultiple($metaAutoKey, f_util_ArrayUtils::isEmpty($value) ? null : $value);
		
		$metaExcludedKey = $this->getManuallyExcludedMetaKey($propertyName);
		$excludedIds = $document->getMetaMultiple($metaExcludedKey);
		$value = array_merge(array_diff($idsInMeta, $currentIds), array_diff($excludedIds, $currentIds));
		$document->setMetaMultiple($metaExcludedKey, f_util_ArrayUtils::isEmpty($value) ? null : $value);
	}
	
    /**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function getShelfIdsByShop($product, $shop, $lang)
	{
		return catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product.id', $product->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::eq('lang', $lang))
			->add(Restrictions::published())
			->setProjection(Projections::property('shelfId', 'shelfId'))->findColumn('shelfId');
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getBoPrimaryShelf($product)
	{
		return $product->getShelf(0);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getShopPrimaryShelf($product, $shop, $publlishedOnly = false)
	{
		$compiledProduct = $this->getPrimaryCompiledProductForShop($product, $shop);
		if ($compiledProduct !== null && (!$publlishedOnly || $compiledProduct->isPublished() || $compiledProduct->getPublicationCode() == 5))
		{
			return DocumentHelper::getDocumentInstance($compiledProduct->getShelfId(), 'modules_catalog/shelf');
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_compiledproduct
	 */
	public function getPrimaryCompiledProductForShop($product, $shop)
	{
		return catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product', $product))
			->add(Restrictions::eq('primary', true))
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
			->findUnique();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param double $quantity
	 * @return double | null 
	 */
	public function addStockQuantity($product, $quantity)
	{
		$oldQuantity = $product->getStockQuantity();
		if ($oldQuantity !== null)
		{
			$newQuantity = $oldQuantity + $quantity;		
			$product->setStockQuantity($newQuantity);
			if ($quantity < 0)
			{
				$theshold = $product->getStockAlertThreshold();
				if ($theshold === null)
				{
					$theshold = intval(ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertThreshold'));
				}
				$mustSend = ($oldQuantity > $theshold && $newQuantity <= $theshold);
				if ($mustSend)
				{
					$product->setMustSendStockAlert($mustSend);
					$product->setModificationdate(null);
				}	
			}
			
			$this->updateStockLevel($product);
			if ($product->isModified())
			{
				$product->save();
			}
			return $newQuantity;
		}
		return $oldQuantity;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	protected function updateStockLevel($product)
	{
		$quantity = $product->getStockQuantity();
		if ($quantity === null || $quantity >= 1)
		{
			$product->setStockLevel(catalog_StockService::LEVEL_AVAILABLE);
		}
		else
		{
			$product->setStockLevel(catalog_StockService::LEVEL_UNAVAILABLE);
		}				
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return double | null 
	 */
	public function getCurrentStockQuantity($product)
	{
		return $product->getStockQuantity();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string [catalog_StockService::LEVEL_AVAILABLE] 
	 */
	public function getCurrentStockLevel($product)
	{
		$lvl = $product->getStockLevel();
		return $lvl === NULL ? catalog_StockService::LEVEL_AVAILABLE : $lvl;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return boolean
	 */
	public function mustSendStockAlert($product)
	{
		return false;
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param string $actionType
	 * @param array $formProperties
	 */
	public function addFormProperties($product, $propertiesNames, &$formProperties)
	{
		$preferences = ModuleService::getInstance()->getPreferencesDocument('catalog');
		if (in_array('complementary', $propertiesNames))
		{
			$formProperties['suggestComplementaryFeederClass'] = $preferences->getSuggestComplementaryFeederClass(); 		
			$formProperties['suggestSimilarFeederClass'] = $preferences->getSuggestSimilarFeederClass(); 		
			$formProperties['suggestUpsellFeederClass'] = $preferences->getSuggestUpsellFeederClass(); 		
		}
		if (in_array('codeSKU', $propertiesNames))
		{
			if ($product->getCodeSKU() === null)
			{
				$formProperties['codeSKU'] = $product->getId();
			} 		
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param string $moduleName
	 * @param string $treeType
	 * @param unknown_type $nodeAttributes
	 */
	public function addTreeAttributes($product, $moduleName, $treeType, &$nodeAttributes)
	{
		$detailVisual = $product->getDefaultVisual();
		if ($detailVisual)
		{
			$nodeAttributes['hasPreviewImage'] = true;
			if ($treeType == 'wlist')
			{
				$nodeAttributes['thumbnailsrc'] = MediaHelper::getPublicFormatedUrl($detailVisual, "modules.uixul.backoffice/thumbnaillistitem");			
			}
		}
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use getBoPrimaryShelf or getShopPrimaryShelf instead
	 */
	public function getPrimaryShelf($product, $website = null, $publlishedOnly = false)
	{
		if ($website === null)
		{
			return $this->getBoPrimaryShelf($product);
		}
		else 
		{
			$shop = catalog_ShopService::getInstance()->getDefaultByWebsite($website);
			return $this->getShopPrimaryShelf($product, $shop, $publlishedOnly);
		}
	}

	/**
	 * @deprecated (will be removed in 4.0) use getPrimaryCompiledProductForShop instead
	 */
	public function getPrimaryCompiledProductForWebsite($product, $website)
	{
		$shop = catalog_ShopService::getInstance()->getDefaultByWebsite($website);
		return $this->getPrimaryCompiledProductForShop($product, $shop);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPrimaryCompiledProductForShop instead
	 */
	public function generateUrlForShop($product, $shop, $lang = null, $parameters = array(), $useCache = true)
	{
		if (!$shop->getIsDefault())
		{
			$parameters['catalogParam']['shopId'] = $shop->getId();
		}
		return website_UrlRewritingService::getInstance()->getDocumentLinkForWebsite($product, $shop->getWebsite(), $lang, $parameters)->getUrl();
	}
}