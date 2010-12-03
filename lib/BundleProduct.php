<?php
interface catalog_BundleProduct
{
	/**
	 * catalog_BundledProduct[]
	 */
	function getBundledProducts();
}

interface catalog_BundledProduct
{
	/**
	 * @return catalog_persistentdocument_product
	 */
	
	function getProduct();
	/**
	 * @return int
	 */
	function getQuantity();
}

class catalog_BundledProductImpl implements catalog_BundledProduct
{
	private $product;
	private $quantity;
	
	public function __construct($product, $quantity)
	{
		$this->product = $product;
		$this->quantity = $quantity;
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	function getProduct()
	{
		return $this->product;
	}
	
	/**
	 * @return int
	 */
	function getQuantity()
	{
		return $this->quantity;
	}
}