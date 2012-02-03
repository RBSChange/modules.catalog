<?php

/**
 * @TODO: renamed to catalog_CompositeProduct in 4.0
 */
interface catalog_BundleProduct
{
	/**
	 * catalog_BundledProduct[]
	 */
	function getBundledProducts();
}

/**
 * @TODO: renamed to catalog_CompositeProductItem in 4.0
 */
interface catalog_BundledProduct
{
	/**
	 * @return catalog_persistentdocument_product
	 */	
	function getProduct();
	
	/**
	 * @return integer
	 */
	function getQuantity();
}

class catalog_BundledProductImpl implements catalog_BundledProduct
{
	/**
	 * @var catalog_persistentdocument_product
	 */
	private $product;
	
	/**
	 * @var integer
	 */	
	private $quantity;
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @param integer $quantity
	 */
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
	 * @return integer
	 */
	function getQuantity()
	{
		return $this->quantity;
	}
}
