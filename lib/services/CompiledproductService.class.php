<?php
/**
 * catalog_CompiledproductService
 * @package modules.catalog
 */
class catalog_CompiledproductService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_CompiledproductService
	 */
	private static $instance;

	/**
	 * @return catalog_CompiledproductService
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
	 * @return catalog_persistentdocument_compiledproduct
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/compiledproduct');
	}

	/**
	 * Create a query based on 'modules_catalog/compiledproduct' model.
	 * Return document that are instance of modules_catalog/compiledproduct,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/compiledproduct');
	}
	
	/**
	 * Create a query based on 'modules_catalog/compiledproduct' model.
	 * Only documents that are strictly instance of modules_catalog/compiledproduct
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/compiledproduct', false);
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param Integer $parentNodeId
	 */
	protected function preSave($document, $parentNodeId)
	{
		if (ModuleService::getInstance()->moduleExists('twitterconnect'))
		{
			$product = $document->getProduct();
			if ($product->hasMeta(twitterconnect_TweetService::META_TWEET_ON_PUBLISH_FOR_WEBSITE))
			{
				$meta = twitterconnect_TweetService::META_TWEET_ON_PUBLISH;
				if ($document->getPrimary() && !$document->hasMeta($meta))
				{
					$document->setMeta($meta, $product->getId());
				}
				else if (!$document->getPrimary() && $document->hasMeta($meta))
				{
					$document->setMeta($meta, null);
				}
			}
		}
	}
		
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param Integer $parentNodeId
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		if (!$document->getPrimary()) {return;}
		
		if ($document->isPropertyModified('isAvailable') && $document->getIsAvailable())
		{
			catalog_AlertService::getInstance()->setPendingForAvailability($document);
		}
		if ($document->isPropertyModified('price') && $document->getPrice() < $document->getPriceOldValue())
		{
			catalog_AlertService::getInstance()->setPendingForPrice($document);
		}
	}

	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param String $lang
	 * @param Array $parameters
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		if (!is_array($parameters)) {$parameters = array();}
		$parameters['catalogParam']['shopId'] = $document->getShopId();
		$parameters['catalogParam']['topicId'] = $document->getTopicId();
		return LinkHelper::getDocumentUrl($document->getProduct(), $document->getLang(), $parameters);
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
	public function getSolrsearchResultItemTemplate($document, $bockName)
	{
		$product = $document->getProduct();
		return $product->getDocumentService()->getSolrsearchResultItemTemplate($product, $bockName);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function deleteForProduct($product)
	{
		if (!($product instanceof catalog_persistentdocument_product)) 
		{
			return;
		}	
		$this->createQuery()->add(Restrictions::eq('product', $product))->delete();
	}
	
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function deleteForShop($shop)
	{	
		if ($shop instanceof catalog_persistentdocument_shop) 
		{
			$this->createQuery()->add(Restrictions::eq('shopId', $shop->getId()))->delete();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 */
	public function deleteForShelf($shelf)
	{	
		if ($shelf instanceof catalog_persistentdocument_shelf) 
		{
			$this->createQuery()->add(Restrictions::eq('shelfId', $shelf->getId()))->delete();
		}
	}
		
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function generateForProduct($product)
	{
		if (!($product instanceof catalog_persistentdocument_product)) 
		{
			return;
		}
		try 
		{
			$this->tm->beginTransaction();
			$container = array();
			
			foreach ($product->getShelfArray() as $shelf) 
			{
				foreach ($shelf->getTopicArray() as $topic) 
				{
					$shop = catalog_ShopService::getInstance()->getByTopic($topic);
					$container[$shop->getId()][$shelf->getId()] = $topic;
				}
			}
			
			$CPIds = array();	
			$rqc = RequestContext::getInstance();
			foreach ($product->getI18nInfo()->getLangs() as $lang)
			{
				try 
				{
					$rqc->beginI18nWork($lang);	
					foreach ($container as $shopId => $selfInfos) 
					{
						$shop = catalog_persistentdocument_shop::getInstanceById($shopId);
						$primaryCompiled = null;
						foreach ($selfInfos as $shelfId => $topic)
						{
							if ($topic->isLangAvailable($lang))
							{
								$shelf = catalog_persistentdocument_shelf::getInstanceById($shelfId);
								$cp = $this->generate($product, $topic, $shelf, $shop, $lang, $primaryCompiled);
								$CPIds[] = $cp->getId();
							}
						}
					}
					$rqc->endI18nWork();
				}
				catch (Exception $rce)
				{
					$rqc->endI18nWork($rce);	
				}
			}			
			$query = $this->createQuery()->add(Restrictions::eq('product', $product));			
			if (count($CPIds))
			{
				$query->add(Restrictions::notin('id', $CPIds));
			}
			$query->delete();
			
			//Save product meta for compilation
			$product->saveMeta();
			
			catalog_ProductService::getInstance()->setCompiled($product->getId());
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param website_persistentdocument_topic $topic
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_shop $shop
	 * @param string $lang
	 * @param catalog_persistentdocument_compiledproduct $primaryCompiled
	 * @return catalog_persistentdocument_compiledproduct
	 */
	protected function generate($product, $topic, $shelf, $shop, $lang, &$primaryCompiled)
	{		
		$compiledProduct = $this->createQuery()->add(Restrictions::eq('product', $product))
			->add(Restrictions::eq('topicId', $topic->getId()))
			->add(Restrictions::eq('lang', $lang))
			->findUnique();
			
		if ($compiledProduct === null)
		{
			$compiledProduct = $this->getNewDocumentInstance();
			$compiledProduct->setLang($lang);
			$compiledProduct->setProduct($product);
			$compiledProduct->setTopicId($topic->getId());
			$compiledProduct->setPosition($product->getId());
		}

		$website = $shop->getWebsite();
		website_WebsiteModuleService::getInstance()->setCurrentWebsite($website);
		catalog_ShopService::getInstance()->setCurrentShop($shop);
		
		$compiledProduct->setShopId($shop->getId());
		$compiledProduct->setWebsiteId($website->getId());			
		$compiledProduct->setShelfId($shelf->getId());	
		$compiledProduct->setShelfIndex($product->getIndexofShelf($shelf));
		$compiledProduct->setTopshelfId(catalog_ShelfService::getInstance()->getTopShelfByShelf($shelf)->getId());
		
		$publicationCode = 0;
		
		// Product synchro.
		$compiledProduct->setLabel($product->getLabel());
		$price = $product->getPrice($shop, null);
		if ($price === null)
		{
			$compiledProduct->setPrice(null);
			$compiledProduct->setDiscountLevel(null);
			$compiledProduct->setIsDiscount(null);
			$publicationCode = 1;
		}
		else
		{	
			$compiledProduct->setPrice($price->getValueWithTax());
			$isDiscount = $price->isDiscount();
			$compiledProduct->setIsDiscount($isDiscount);
			if ($isDiscount)
			{
				$newPrice = $price->getValueWithTax();
				$oldPrice = $price->getOldValueWithTax();
				$discountLevel = round((($oldPrice-$newPrice) / $oldPrice) * 100);
				$compiledProduct->setDiscountLevel($discountLevel);
			}
			else
			{
				$compiledProduct->setDiscountLevel(null);
			}
		}
		
		$compiledProduct->setIsAvailable($product->isAvailable($shop));
		if ($publicationCode === 0)
		{
			if (!$product->isPublished())
			{
				$publicationCode = 2;
			}
			else if (!$shop->isPublished())
			{
				$publicationCode = 3;
			}
			else if  (!$shelf->isPublished())
			{
				$publicationCode = 4;
			}
			else if  (!$shop->getDisplayOutOfStock() && !$compiledProduct->getIsAvailable())
			{
				$publicationCode = 5;
			}
		}
		
		$compiledProduct->setPublicationCode($publicationCode);
		if ($publicationCode == 0)
		{
			if (!$compiledProduct->isPublished())
			{
				$rows = $this->createQuery()->add(Restrictions::eq('product', $product))
					->add(Restrictions::eq('shopId', $shop->getId()))
					->add(Restrictions::eq('lang', $lang))
					->add(Restrictions::isNotNull('firstpublicationdate'))
					->setProjection(Projections::property('firstpublicationdate'))
					->findColumn('firstpublicationdate');
				$now = date_Calendar::getInstance()->toString();
				$date = (count($rows) > 0) ? $rows[0] : $now;
				$compiledProduct->setFirstpublicationdate($date);		
				$compiledProduct->setLastpublicationdate($now);
			}
		}
		
		if ($primaryCompiled === null && $publicationCode == 0)
		{
			$primaryCompiled = $compiledProduct;
			$compiledProduct->setPrimary(true);
		}
		else
		{
			$compiledProduct->setPrimary(false);
		}

		if (catalog_ModuleService::getInstance()->areCommentsEnabled($shop))
		{
			$ratingAverage = $product->getDocumentService()->getRatingAverage($product, $website->getId());
			$compiledProduct->setRatingAverage($ratingAverage);
			$product->setRatingMetaForWebsiteid($ratingAverage, $website->getId());
		}		
		
		$brand = $product->getBrand();
		if ($brand !== null && $brand->isPublished())
		{
			$compiledProduct->setBrandId($brand->getId());
			$compiledProduct->setBrandLabel($brand->getLabel());
		}
		else
		{
			$compiledProduct->setBrandId(null);
			$compiledProduct->setBrandLabel(null);
		}

		$product->getDocumentService()->updateCompiledProduct($product, $compiledProduct);
		if ($compiledProduct->isNew() || $compiledProduct->isModified())
		{
			$this->save($compiledProduct);
		}
		else
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' ' . $compiledProduct->getId() . ' not modified');
			}
		}
		
		return $compiledProduct;
	}
	 
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			switch ($document->getPublicationCode()) 
			{
				case 0:
					return true;
				case 1:
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.no-price;');
					return false;	
				case 2:
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.product-not-published;');
					return false;	
				case 3:	
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.shop-not-published;');
					return false;
				case 4:	
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.shelf-not-published;');
					return false;		
				case 5:
					$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.product-not-displayable;');
					return false;				
			}
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param String $oldPublicationStatus
	 * @param Array<Mixed> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->isPublished() || 'PUBLICATED' === $oldPublicationStatus)
		{
			if (!isset($params['cause']) || $params['cause'] != 'delete')
			{
				website_SystemtopicService::getInstance()->publishIfPossible($document->getTopicId());
			}
		}
	}
		
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		return $document->getWebsiteId();
	}	
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function getOrderInfosByShelfAndShop($shelf, $shop)
	{
		$query = $this->createQuery()->addOrder(Order::asc('position'));
		$query->add(Restrictions::eq('shelfId', $shelf->getId()))->add(Restrictions::eq('shopId', $shop->getId()));
		$infos = array();
		foreach ($query->find() as $cp)
		{
			$product = $cp->getProduct();
			$infos[] = array(
				'id' => $product->getId(),
				'label' => $product->getTreeNodeLabel(),
				'icon' => MediaHelper::getIcon($product->getPersistentModel()->getIcon(), MediaHelper::SMALL)
			);
		}
		return $infos;
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param catalog_persistentdocument_shop $shop
	 */
	public function setPositionsByShelfAndShop($shelf, $shop, $order)
	{
		$tm = $this->getTransactionManager();
		try 
		{
			$tm->beginTransaction();
			
			$query = $this->createQuery()->add(Restrictions::eq('shelfId', $shelf->getId()))->add(Restrictions::eq('shopId', $shop->getId()));
			foreach ($query->find() as $doc)
			{
				$doc->setPosition($order[$doc->getProduct()->getId()]);
				$doc->save();
			}
			
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 * @param indexer_IndexedDocument $indexDocument
	 * @return boolean
	 */
	public function indexFacets($compiledProduct, $indexDocument)
	{
		return catalog_ProductFacetIndexer::getInstance()->populateIndexDocument($indexDocument, $compiledProduct);
	}
	
	/**
	 * @see twitterconnect_PlannerService::sendTweetsPlannedOnPublishByRelatedDocument()
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @return catalog_persistentdocument_product
	 */
	public function getRelatedForTweets($document)
	{
		return $document->getPrimary() ? $document->getProduct() : null;
	}

	/**
	 * DEPRECATED function
	 */
	
	/**
	 * @deprecated 
	 */
	public function disableCompilation()
	{
	}
	
	/**
	 * @deprecated 
	 */
	public function enableCompilation()
	{
	}
}