<?php
/**
 * catalog_CompiledproductService
 * @package modules.catalog
 */
class catalog_CompiledproductService extends f_persistentdocument_DocumentService
{
	const MODE_PRODUCT_INFO = 'product';
	const MODE_BRAND_INFO = 'brand';
	const MODE_RATING_INFO = 'rating';
	const MODE_ALL_INFO = 'all';
	
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
	 * @param String $lang
	 * @param Array $parameters
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		return LinkHelper::getDocumentUrl($document->getProduct(), $lang, $parameters);
	}
	
	/**
	 * @var Boolean
	 */
	private $disableCompilation = false;
	
	/**
	 * @return Boolean
	 */
	public function disableCompilation()
	{
		$this->disableCompilation = true;
	}
	
	/**
	 * @return Boolean
	 */
	public function enableCompilation()
	{
		$this->disableCompilation = false;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param Boolean $isNew
	 */
	public function generateForProduct($product, $isNew = false)
	{
		if ($product === null)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . " no product received, bailing out gracefuly!");
			}
			return;
		}
		if (!$this->disableCompilation)
		{
			$query = website_SystemtopicService::getInstance()->createQuery();
			$query->createCriteria('shelf')->add(Restrictions::eq('product.id', $product->getId()));
			$topics = $query->find();
			
			$rqc = RequestContext::getInstance();
			foreach ($rqc->getSupportedLanguages() as $lang)
			{
				$rqc->beginI18nWork($lang);
		
				$topicIds = array();
				if ($product->isLangAvailable($lang))
				{				
					foreach ($topics as $topic)
					{
						$topicIds[] = $topic->getId();
						$this->generate($product, $topic);
					}
				}
				
				if (!$isNew)
				{
					$query = $this->createQuery()->add(Restrictions::eq('product.id', $product->getId()))
						->add(Restrictions::eq('lang', $lang));
					if (count($topicIds) > 0)
					{
						$query->add(Restrictions::notin('topicId', $topicIds));
					}
					$query->delete();
				}
				
				$rqc->endI18nWork();
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param Boolean $isNew
	 */
	public function generateForShop($shop, $isNew = false)
	{
		if (!$this->disableCompilation)
		{
			$cs = catalog_ProductService::getInstance();
			$query = website_SystemtopicService::getInstance()->createQuery();
			$query->add(Restrictions::descendentOf($shop->getTopic()->getId()));
			
			$rqc = RequestContext::getInstance();
			foreach ($rqc->getSupportedLanguages() as $lang)
			{
				$rqc->beginI18nWork($lang);
				$topicIds = array();			
				foreach ($query->find() as $topic)
				{
					$topicIds[] = $topic->getId();
					$query = $cs->createQuery();
					$query->createCriteria('shelf')->add(Restrictions::eq('topic.id', $topic->getId()));
					foreach ($query->find() as $product)
					{
						if ($product->isLangAvailable($lang))
						{
							$this->generate($product, $topic);
						}
					}
				}
				if (!$isNew)
				{
					$query = $this->createQuery()->add(Restrictions::eq('shopId', $shop->getId()))
						->add(Restrictions::eq('lang', $lang));
					if (count($topicIds) > 0)
					{
						$query->add(Restrictions::notin('topicId', $topicIds));
					}
					$query->delete();
				}
				
				$rqc->endI18nWork();
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param Boolean $isNew
	 */
	public function generateForShelf($shelf, $isNew = false)
	{
		if (!$this->disableCompilation)
		{
			$cs = catalog_ProductService::getInstance();
			$query = catalog_ShelfService::getInstance()->createQuery()->add(Restrictions::orExp(
				Restrictions::eq('id', $shelf->getId()), 
				Restrictions::descendentOf($shelf->getId())
			));
			foreach ($query->find() as $shelf)
			{
				$rqc = RequestContext::getInstance();
				foreach ($rqc->getSupportedLanguages() as $lang)
				{
					$rqc->beginI18nWork($lang);
					
					$topicIds = array();
					foreach ($shelf->getTopicArray() as $topic)
					{
						$topicIds[] = $topic->getId();
						$query = $cs->createQuery();
						$query->createCriteria('shelf')->add(Restrictions::eq('topic.id', $topic->getId()));
						foreach ($query->find() as $product)
						{
							if ($product->isLangAvailable($lang))
							{
								$this->generate($product, $topic);
							}
						}
					}
					
					if (!$isNew)
					{
						$query = $this->createQuery()->add(Restrictions::eq('shelfId', $shelf->getId()))
							->add(Restrictions::eq('lang', $lang));
						if (count($topicIds) > 0)
						{
							$query->add(Restrictions::notin('topicId', $topicIds));
						}
						$query->delete();
					}
					
					$rqc->endI18nWork();
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function compileAllInfos($product)
	{
		$this->compileInfosForEachAvailableLang($product, self::MODE_ALL_INFO);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function compileProductInfos($product)
	{
		$this->compileInfosForEachAvailableLang($product, self::MODE_PRODUCT_INFO);
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 */
	public function compileRatingInfos($product)
	{
		$this->compileInfosForEachAvailableLang($product, self::MODE_RATING_INFO);
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param String $mode
	 */
	private function compileInfosForEachAvailableLang($product, $mode)
	{
		if (!$this->disableCompilation)
		{
			$rqc = RequestContext::getInstance();
			foreach ($rqc->getSupportedLanguages() as $lang)
			{
				if ($product->isLangAvailable($lang))
				{
					$rqc->beginI18nWork($lang);
				
					$query = $this->createQuery()->add(Restrictions::eq('product.id', $product->getId()))
						->add(Restrictions::eq('lang', $lang));
					foreach ($query->find() as $compiledProduct)
					{
						$this->compile($compiledProduct, $mode);				
					}
					
					$rqc->endI18nWork();
				}
			}
		}
	}
	
	/**
	 * @param Integer $brandId
	 */
	public function compileBrandInfos($brandId)
	{
		if (!$this->disableCompilation)
		{
			$rqc = RequestContext::getInstance();
			foreach ($rqc->getSupportedLanguages() as $lang)
			{
				$rqc->beginI18nWork($lang);
				
				$query = $this->createQuery()->add(Restrictions::eq('brand.id', $brandId))
					->add(Restrictions::eq('lang', $lang));
				foreach ($query->find() as $compiledProduct)
				{
					$this->compile($compiledProduct, self::MODE_BRAND_INFO);				
				}
				
				$rqc->endI18nWork();
			}
		}
	}
		
	/**
	 * @param Integer $productId
	 */
	public function refreshPublicationForProduct($productId)
	{
		if (!$this->disableCompilation)
		{
			$query = $this->createQuery()->add(Restrictions::eq('product.id', $productId))
				->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
				->setProjection(Projections::property('id'));
			$rows = $query->find();
			foreach ($rows as $row)
			{
				$this->publishIfPossible($row['id']);				
			}
		}
	}
	
	/**
	 * @param Integer $shopId
	 */
	public function refreshPublicationForShop($shopId)
	{
		if (!$this->disableCompilation)
		{
			$query = $this->createQuery()->add(Restrictions::eq('shopId', $shopId))
				->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
				->setProjection(Projections::property('id'));
			foreach ($query->find() as $row)
			{
				$this->publishIfPossible($row['id']);				
			}
		}
	}
	
	/**
	 * @param Integer $shelfId
	 */
	public function refreshPublicationForShelf($shelfId)
	{
		if (!$this->disableCompilation)
		{
			$query = $this->createQuery()->add(Restrictions::eq('shelfId', $shelfId))
				->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
				->setProjection(Projections::property('id'));
			foreach ($query->find() as $row)
			{
				$this->publishIfPossible($row['id']);				
			}
		}
	}
	
	/**
	 * @param Integer $productId
	 * @param String $lang
	 */
	public function deleteForProduct($productId, $lang = null)
	{
		$query = $this->createQuery()->add(Restrictions::eq('product.id', $productId));
		if ($lang != null)
		{
			$query->add(Restrictions::eq('lang', $lang));
		}
		$query->delete();
	}
	
	/**
	 * @param Integer $shopId
	 * @param String $lang
	 */
	public function deleteForShop($shopId, $lang = null)
	{
		$query = $this->createQuery()->add(Restrictions::eq('shopId', $shopId));
		if ($lang != null)
		{
			$query->add(Restrictions::eq('lang', $lang));
		}
		$query->delete();
	}
	
	/**
	 * @param Integer $topicId
	 * @param String $lang
	 */
	public function deleteForTopic($topicId, $lang = null)
	{
		$query = $this->createQuery()->add(Restrictions::eq('topicId', $topicId));
		if ($lang != null)
		{
			$query->add(Restrictions::eq('lang', $lang));
		}
		$query->delete();
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 */	
	protected function compile($document, $mode)
	{
		switch ($mode)
		{
			case self::MODE_PRODUCT_INFO :
				$this->refreshProductInfo($document);
				break;
				
			case self::MODE_RATING_INFO :
				$this->refreshRatingInfo($document);
				break;
				
			case self::MODE_BRAND_INFO :
				$this->refreshBrandInfo($document);
				break;
			
			case self::MODE_ALL_INFO :
			default :
				$this->refreshProductInfo($document);
				$this->refreshRatingInfo($document);
				$this->refreshBrandInfo($document);
				break;
		}
		
		if ($document->isNew() || $document->isModified())
		{
			$document->save();
		}
		else
		{
			$result = $this->publishIfPossible($document->getId());
		}
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
			$document->setIsDiscount(null);
		}
		else
		{
			$document->setPrice($price->getValueWithTax());
			$document->setIsDiscount($price->isDiscount());
		}
		$document->setIsAvailable($product->isAvailable($shop));
		
		// Shelf index info.
		$document->setShelfIndex($product->getIndexofShelf($document->getShelf()));
		
		// Brand synchro.
		$brand = $product->getBrand();
		if ($brand !== null && $brand->getId() != $document->getBrandId())
		{
			$document->setBrandId($brand->getId());
			$this->refreshBrandInfo($document);
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
			$document->setRatingAverage($product->getDocumentService()->getRatingAverage($product, $websiteId));
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
	 * @param catalog_persistentdocument_product $product
	 * @param website_persistentdocument_topic $topic
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
			$shelf = catalog_ShelfService::getInstance()->getByTopic($topic);
			$compiledProduct->setShelfId($shelf->getId());			
			$compiledProduct->setShelfIndex($product->getIndexofShelf($shelf));
			// TODO.
			$compiledProduct->setPosition(0);
		}
		$this->compile($compiledProduct, self::MODE_ALL_INFO);
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$product = $document->getProduct();
		$shop = $document->getShop();
		$shelf = $document->getShelf();
		//Framework::debug(__METHOD__ . ' hasPrice = ' . ($document->getPrice() !== null) . ', productPublished = ' . $product->isPublished() . ', shopPublished = ' . $shop->isPublished() . ', shelfLangAvailable = ' . $shelf->isContextLangAvailable() . ', productCanBeDisplayed = ' . $product->canBeDisplayed($shop) . ', parentPublishable = ' . parent::isPublishable($document));
		return ($document->getPrice() !== null && $product->isPublished() && $shop->isPublished() && $shelf->isContextLangAvailable() && $product->canBeDisplayed($shop) && parent::isPublishable($document));
	}
	
	/**
	 * @param catalog_persistentdocument_compiledproduct $document
	 * @param String $oldPublicationStatus
	 * @param Array<Mixed> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->isPublished() || "PUBLICATED" == $oldPublicationStatus)
		{
			website_SystemtopicService::getInstance()->publishIfPossible($document->getTopicId());
		}
	}
}