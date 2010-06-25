<?php
/**
 * catalog_persistentdocument_compiledproduct
 * @package modules.catalog
 */
class catalog_persistentdocument_compiledproduct extends catalog_persistentdocument_compiledproductbase implements indexer_IndexableDocument
{
	/**
	 * Get the indexable document
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		if ($this->getIndexed())
		{
			$product = $this->getProduct();
			$indexDocument = $product->getIndexedDocumentForShop($this->getShop());
			if ($indexDocument !== null)
			{
				$indexDocument->setId($this->getId());
				$indexDocument->setDocumentModel("modules_catalog/compiledproduct");
				
				// So listing is possible
				$indexDocument->setVolatileIntegerField('topicId', $this->getTopicId());
				
				// Facet/sort fields
				$indexDocument->setVolatileIntegerField('primaryShelfId', $this->getShelfId());
				$brandId = $this->getBrandId();
				if ($brandId !== null)
				{
					$indexDocument->setVolatileIntegerField('brandId', $brandId);
				}
				$indexDocument->setVolatileFloatField('price', $this->getPrice());
				
				// discount
				$isDiscount = $this->getIsDiscount();
				$indexDocument->setVolatileIntegerField('isDiscount', intval($isDiscount));
				if ($isDiscount)
				{
					$indexDocument->setVolatileIntegerField('discountLevel', $this->getDiscountLevel());
				}
				else
				{
					$indexDocument->setVolatileIntegerField('discountLevel', 0);
				}
				
				// availability
				$indexDocument->setVolatileIntegerField('isAvailable', intval($this->getIsAvailable()));
				
				$indexDocument->setVolatileIntegerField('position', $this->getPosition());
				
				// Rating
				$rating = $this->getRatingAverage();
				if ($rating !== null) 
				{
					$indexDocument->setVolatileFloatField('ratingAverage', $rating);	
				}
				else
				{
					$indexDocument->setVolatileFloatField('ratingAverage', -1);
				}
				
				// Extended attributes
				$attributes = $product->getAttributes();
				if (f_util_ArrayUtils::isNotEmpty($attributes))
				{
					$attrService = catalog_AttributefolderService::getInstance();
					foreach ($attributes as $attrName => $attrValue)
					{
						$attrInfo = $attrService->getAttributeInfo($attrName);
						switch ($attrInfo['type'])
						{
							case 'text':
								$indexDocument->setVolatileStringField($attrName, $attrValue);
								break;
							case 'numeric':
								$indexDocument->setVolatileFloatField($attrName, $attrValue);
								break;
						}
					}
				}
				
				// For extended attributes filters
				$indexDocument->setIntegerField('productId', $this->getProduct()->getId());
				
				return $indexDocument;
			}
		}
		return null;
	}
	
	private static $i = 1;
	
	/**
	 * @return catalog_persistentdocument_shop
	 */
	public function getShop()
	{
		return DocumentHelper::getDocumentInstance($this->getShopId());
	}
	
	/**
	 * @return catalog_persistentdocument_shelf
	 */
	public function getShelf()
	{
		return DocumentHelper::getDocumentInstance($this->getShelfId());
	}
	
	/**
	 * @return website_persistentdocument_website
	 */
	public function getWebsite()
	{
		return DocumentHelper::getDocumentInstance($this->getWebsiteId());
	}
	
	/**
	 * @return website_persistentdocument_topic
	 */
	public function getTopic()
	{
		return DocumentHelper::getDocumentInstance($this->getTopicId());
	}
	
	/**
	 * @return brand_persistentdocument_brand
	 */
	public function getBrand()
	{
		$brandId = $this->getBrandId();
		if ($brandId !== null)
		{
			try
			{
				return DocumentHelper::getDocumentInstance($brandId);
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}
		}
		return null;
	}
}