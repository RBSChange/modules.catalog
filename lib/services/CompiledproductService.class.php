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
		$product = $document->getProduct();
		if (ModuleService::getInstance()->moduleExists('twitterconnect'))
		{
			if ($product->hasMeta(twitterconnect_TweetService::META_TWEET_ON_PUBLISH_FOR_WEBSITE))
			{
				$meta = twitterconnect_TweetService::META_TWEET_ON_PUBLISH;
				if ($document->getIndexed() && !$document->hasMeta($meta))
				{
					$document->setMeta($meta, $product->getId());
					$document->saveMeta();
				}
				else if (!$document->getIndexed() && $document->hasMeta($meta))
				{
					$document->setMeta($meta, null);
					$document->saveMeta();
				}
			}
		}
	}
		
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param Integer $parentNodeId
	 */
	protected function postSave($document, $parentNodeId)
	{
		$alertService = catalog_AlertService::getInstance();
		if ($document->isPropertyModified('isAvailable') && $document->getIsAvailable())
		{
			$query = $alertService->createQuery()->add(Restrictions::published())->add(Restrictions::eq('pending', false))
				->add(Restrictions::eq('alertType', catalog_AlertService::TYPE_AVAILABILITY));
			foreach ($query->find() as $alert)
			{
				$alert->setPending(true);
				$alert->save();
			}
		}
		if ($document->isPropertyModified('price') && $document->getPrice() < $document->getPriceOldValue())
		{
			$query = $alertService->createQuery()->add(Restrictions::published())->add(Restrictions::eq('pending', false))
				->add(Restrictions::eq('alertType', catalog_AlertService::TYPE_PRICE))
				->add(Restrictions::gt('priceReference', $document->getPrice()));
			foreach ($query->find() as $alert)
			{
				$alert->setPending(true);
				$alert->save();
			}
		}
	}

	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param String $lang
	 * @param Array $parameters
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		return LinkHelper::getDocumentUrl($document->getProduct(), $lang, $parameters);
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
		if (!($product instanceof catalog_persistentdocument_product) || !$product->isCompilable()) 
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
		if (!($product instanceof catalog_persistentdocument_product) || !$product->isCompilable()) 
		{
			return;
		}
		
		try 
		{
			$this->tm->beginTransaction();

			$query = website_SystemtopicService::getInstance()->createQuery();
			$query->createCriteria('shelf')->add(Restrictions::eq('product.id', $product->getId()));
			$topics = $query->find();
			
			$CPIds = array();
			$rqc = RequestContext::getInstance();
			foreach ($rqc->getSupportedLanguages() as $lang)
			{
				try 
				{
					$rqc->beginI18nWork($lang);	
					$indexedCP = array();
					if ($product->isLangAvailable($lang))
					{
						foreach ($topics as $topic)
						{
							if ($topic->isLangAvailable($lang))
							{				
								$cp = $this->generate($product, $topic);
								$CPIds[] = $cp->getId();
								
								//Prepare Indexed document
								$key = $cp->getWebsiteId();
								if (!isset($indexedCP[$key]))
								{
									$indexedCP[$key] = array($cp);
								}
								else
								{
									$indexedCP[$key][] = $cp;
								}
							}
						}
					}
		
					$this->fixIndexedCompiledProduct($indexedCP);
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
			catalog_ProductService::getInstance()->setCompiled($product->getId());
			
			foreach ($CPIds as $cpId)
			{
				$cproduct = DocumentHelper::getDocumentInstance($cpId);
				if ($cproduct->isPublished() && $cproduct->getIndexed())
				{
					try
					{
						$rqc->beginI18nWork($cproduct->getLang());
						indexer_IndexService::getInstance()->update($cproduct);
						$rqc->endI18nWork();
					}
					catch (Exception $e)
					{
						$rqc->endI18nWork($e);
					}
				}
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	private function fixIndexedCompiledProduct($indexedCP)
	{		
		foreach ($indexedCP as $cps) 
		{
			$oldIndexed = null;
			$newIndexed = null;
			$newIndexedUnpublished = null;
			foreach ($cps as $cp) 
			{
				if ($cp->getIndexed()) {$oldIndexed = $cp;}
				if ($cp->isPublished())
				{
					if ($newIndexed === null || ($newIndexed->getShelfIndex() > $cp->getShelfIndex()))
					{
						$newIndexed = $cp;		
					}
				}
				else if ($newIndexed === null)
				{
					if ($newIndexedUnpublished === null || ($newIndexedUnpublished->getShelfIndex() > $cp->getShelfIndex()))
					{
						$newIndexedUnpublished = $cp;		
					}
				}
			}
			
			if ($newIndexed === null)
			{
				$newIndexed = $newIndexedUnpublished;
			}
			
			if ($oldIndexed !== $newIndexed)
			{
				if ($oldIndexed !== null)
				{
					$oldIndexed->setIndexed(false);
					$this->save($oldIndexed);
				}
				if ($newIndexed !== null)
				{
					$newIndexed->setIndexed(true);
					$this->save($newIndexed);
				}
			}
		}
	}
	
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param website_persistentdocument_topic $topic
	 * @return catalog_persistentdocument_compiledproduct
	 */
	private function generate($product, $topic)
	{
		$lang = RequestContext::getInstance()->getLang();
		
		$compiledProduct = $this->createQuery()->add(Restrictions::eq('product.id', $product->getId()))
			->add(Restrictions::eq('topicId', $topic->getId()))
			->add(Restrictions::eq('lang', $lang))->findUnique();
			
		if ($compiledProduct === null)
		{
			$compiledProduct = $this->getNewDocumentInstance();
			$compiledProduct->setLang($lang);
			$compiledProduct->setProduct($product);
			$compiledProduct->setTopicId($topic->getId());
			$shop = catalog_ShopService::getInstance()->getByTopic($topic);
			$compiledProduct->setShopId($shop->getId());
			$compiledProduct->setWebsiteId($shop->getWebsite()->getId());			
			$shelfService = catalog_ShelfService::getInstance();
			$shelf = $shelfService->getByTopic($topic);
			$compiledProduct->setShelfId($shelf->getId());	
			$indexOfShelf = $product->getIndexofShelf($shelf);
			$compiledProduct->setShelfIndex($indexOfShelf);
			// TODO.
			$compiledProduct->setPosition($product->getId());
		}
		$this->compile($compiledProduct);
		
		return $compiledProduct;
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */	
	protected function compile($document)
	{
		$this->refreshProductInfo($document);
		$this->refreshRatingInfo($document);
		$this->refreshBrandInfo($document);

		if ($document->isNew() || $document->isModified())
		{
			$this->save($document);
		}
		else
		{
			$oldPublicationStatus = $document->getPublicationstatus();
			$this->publishDocumentIfPossible($document, array('cause' => 'compilation'));
			if ($oldPublicationStatus === 'PUBLICATED' && $document->isPublished())
			{
				// Reindex when product is updated bu compiled product not
				indexer_IndexService::getInstance()->update($document);
			}
		}
		
		if (!$document->getIndexed())
		{
			$this->reindexProduct($document);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $compiledproduct
	 */
	private function reindexProduct($compiledproduct)
	{
		$indexedProduct = $this->createQuery()->add(Restrictions::eq('product', $compiledproduct->getProduct()))
								->add(Restrictions::published())
								->add(Restrictions::eq('indexed', true))
								->add(Restrictions::eq('shopId', $compiledproduct->getShopId()))->findUnique();	
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */
	private function refreshProductInfo($document)
	{
		$product = $document->getProduct();
		$shop = $document->getShop();
		
		// Product synchro.
		$document->setLabel($product->getLabel());
		$price = $product->getPrice($shop, null);
		if ($price === null)
		{
			$document->setPrice(null);
			$document->setDiscountLevel(null);
			$document->setIsDiscount(null);
		}
		else
		{	
			$document->setPrice($price->getValueWithTax());
			$isDiscount = $price->isDiscount();
			$document->setIsDiscount($isDiscount);
			if ($isDiscount)
			{
				$newPrice = $price->getValueWithTax();
				$oldPrice = $price->getOldValueWithTax();
				$discountLevel = round((($oldPrice-$newPrice) / $oldPrice) * 100);
				$document->setDiscountLevel($discountLevel);
			}
			else
			{
				$document->setDiscountLevel(null);
			}
		}
		$document->setIsAvailable($product->isAvailable($shop));
		
		$shelf = $document->getShelf();
		
		// Shelf index info.
		$document->setShelfIndex($product->getIndexofShelf($shelf));
		
		// Top shelf ID
		$document->setTopshelfId(catalog_ShelfService::getInstance()->getTopShelfByShelf($shelf)->getId());
			
		// Brand synchro.
		$brand = $product->getBrand();
		if ($brand !== null)
		{
			if ($brand->getId() != $document->getBrandId())
			{
				$document->setBrandId($brand->getId());
				$this->refreshBrandInfo($document);
			}
		}
		else if ($document->getBrandId() !== null)
		{
			$document->setBrandId(null);
			$this->refreshBrandInfo($document);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */
	private function refreshRatingInfo($document)
	{
		if (catalog_ModuleService::getInstance()->areCommentsEnabled())
		{
			$product = $document->getProduct();
			$websiteId = $document->getWebsiteId();
			$ratingAverage = $product->getDocumentService()->getRatingAverage($product, $websiteId);
			$document->setRatingAverage($ratingAverage);
			$product->setRatingMetaForWebsiteid($ratingAverage, $websiteId);
			$product->saveMeta();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */
	private function refreshBrandInfo($document)
	{
		$brand = $document->getBrand();
		if ($brand !== null)
		{
			if ($brand->isContextLangAvailable())
			{
				$document->setBrandLabel($brand->getLabel());
			}
			else
			{
				$document->setBrandLabel($brand->getVoLabel());
			}
		}
		else
		{
			$document->setBrandLabel(null);
		}
	}
		
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		if ($document->getPrice() === null)
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.no-price;');
			return false;
		}
		
		$product = $document->getProduct();
		if (!$product->isPublished())
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.product-not-published;');
			return false;
		}
		
		$shop = $document->getShop();
		if (!$shop->isPublished())
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.shop-not-published;');
			return false;
		}
		
		$shelf = $document->getShelf();
		if (!$shelf->isPublished())
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.shop-not-published;');
			return false;
		}
		
		if (!$product->canBeDisplayed($shop))
		{
			$this->setActivePublicationStatusInfo($document, '&modules.catalog.document.compiledproduct.publication.product-not-displayable;');
			return false;
		}
		
		return parent::isPublishable($document);
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param String $oldPublicationStatus
	 * @param Array<Mixed> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if (!isset($params['cause']) || $params["cause"] != "delete")
		{
			if ($document->isPublished() || "PUBLICATED" == $oldPublicationStatus)
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
	 * @see twitterconnect_PlannerService::sendTweetsPlannedOnPublishByRelatedDocument()
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @return catalog_persistentdocument_product
	 */
	public function getRelatedForTweets($document)
	{
		return $document->getIndexed() ? $document->getProduct() : null;
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