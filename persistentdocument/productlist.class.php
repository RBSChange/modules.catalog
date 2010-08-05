<?php
/**
 * catalog_persistentdocument_productlist
 * @package catalog
 */
class catalog_persistentdocument_productlist extends catalog_persistentdocument_productlistbase implements catalog_ProductList
{
	/**
	 * @var integer
	 */
	private $maxCount;
	
	/**
	 * @param string $listName
	 * @return catalog_persistentdocument_productlist
	 */
	public static function getListInstance($listName, $maxCount)
	{
		$list = catalog_ProductlistService::getInstance()->getCurrentProductList($listName, $maxCount);
		if ($list === null)
		{
			return null;
		}
		$list->maxCount = $maxCount;
		return $list;
	}
		
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function addProductId($id)
	{
		$product = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
		if ($this->getIndexofProduct($product) != -1)
		{
			return false;
		}
		
		$this->addProduct($product);
		if ($this->maxCount !== null)
		{
			$this->setProductArray(array_slice($this->getProductArray(), 0 - $this->maxCount));
		}
		return true;
	}
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function removeProductId($id)
	{
		$product = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
		if ($this->getIndexofProduct($product) == -1)
		{
			return false;
		}
		$this->removeProduct($product);
		return true;
	}
	
	/**
	 * @return integer[]
	 */
	public function getProductIds()
	{
		return DocumentHelper::getIdArrayFromDocumentArray($this->getProductArray());
	}
	
	/**
	 * Save the list.
	 */
	public function saveList()
	{
		$this->save();
	}
}