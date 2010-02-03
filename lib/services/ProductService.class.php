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
			$query = catalog_CompiledproductService::getInstance()
				->createQuery()
				->add(Restrictions::eq('product.id', $product->getId()))
				->add(Restrictions::eq('websiteId', $website->getId()))
				->addOrder(Order::asc('shelfIndex'))
				->setFirstResult(0)
				->setMaxResults(1);
			$criteria = $query->createPropertyCriteria('shelfId', 'modules_catalog/shelf');
			$criteria->add(Restrictions::published());
			$compiledProduct = f_util_ArrayUtils::firstElement($query->find());
			if ($compiledProduct !== null)
			{
				return DocumentHelper::getDocumentInstance($compiledProduct->getShelfId());
			}
		}
		return null;
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
	 * @return array
	 */
	public function getResume($document, $forModuleName)
	{
		$data = parent::getResume($document, $forModuleName);
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
			
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
				$this->currentShopForResume = $shop;
				$urlData[] = array(
					'label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.Url-for-website;', array('website' => $shop->getWebsite()->getLabel())), 
					'href' => str_replace('&amp;', '&', LinkHelper::getDocumentUrl($document, $lang, array(), false)),
					'class' => $shop->isPublished() ? 'link' : ''
				);
			}
			$this->currentShopForResume = null;
			
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
		// Update shelves counters.
		// This must be done here, because recursive post count is
		// refreshed by a query so, the update must be done at this time.
		if ($document->isPropertyModified('shelf') && $document->isPublished())
		{
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
	 * @param catalog_persistentdocument_product $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		// Generate compiled products.
		catalog_CompiledproductService::getInstance()->generateForProduct($document->getProductToCompile(), true);
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
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// Generate compiled products.
		if ($document->isPropertyModified('shelf') || $document->getAndResetHasNewTranslation())
		{
			catalog_CompiledproductService::getInstance()->generateForProduct($document->getProductToCompile());
		}
		else if ($document->isPropertyModified('label') || $document->isPropertyModified('brand') || $document->isPropertyModified('stockQuantity') || $document->isPropertyModified('stockLevel'))
		{
			catalog_CompiledproductService::getInstance()->compileProductInfos($document->getProductToCompile());
		}
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
	 * @param catalog_persistentdocument_product $document
	 * @return void
	 */
	protected function preDeleteLocalized($document)
	{
		// Delete compiled products for current lang.
		$this->deleteRelatedCompiledProducts($document, RequestContext::getInstance()->getLang());
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param String $document $lang
	 */
	protected function deleteRelatedCompiledProducts($document, $lang = null)
	{
		catalog_CompiledproductService::getInstance()->deleteForProduct($document->getProductToCompile()->getId(), $lang);
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
		
		// Handle compilation.
		$this->refreshCompilationOnPublicationStatusChanged($document, $oldPublicationStatus, $params);
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function refreshCompilationOnPublicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		// Refresh compiled products publication.
		$productToCompile = $document->getProductToCompile();
		if ($productToCompile !== null)
		{
			catalog_CompiledproductService::getInstance()->refreshPublicationForProduct($productToCompile->getId());
		}
		else 
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . " no product to refresh publication for, bailing out gracefuly!");
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
}