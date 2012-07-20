<?php
/**
 * @deprecated
 */
interface catalog_ProductFacetIndexerStrategy extends catalog_FacetIndexerStrategy
{
	/**
	 * @deprecated
	 */
	public function getFacets();
	
	/**
	 * @deprecated
	 */
	public function setLabels($facetResults);
	
	/**
	 * @deprecated
	 */
	public function getLabelValue($fieldName, $value);
	
}

/**
 * @deprecated
 */
class catalog_ProductFacetIndexer
{
	/**
	 * @var catalog_ProductFacetIndexer
	 */
	private static $instance;
	
	/**
	 * @var catalog_ProductFacetIndexerStrategy
	 */
	private $strategy = null;


	private function __construct()
	{
		$className = Framework::getConfiguration('modules/catalog/productFacetIndexerStrategyClass', false);
		if ($className !== false)
		{
			$this->strategy = new $className;
			if (!($this->strategy instanceof catalog_ProductFacetIndexerStrategy))
			{
				$this->strategy = null;
			}
		} 
	}
	
	/**
	 * @return catalog_ProductFacetIndexer
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @deprecated
	 */
	public function getFacets()
	{
		if ($this->strategy !== null)
		{
			return $this->strategy->getFacets();
		}
		return array();
	}
	
	/**
	 * @deprecated
	 */
	public function populateIndexDocument($indexDocument, $compiledProduct)
	{
		if ($this->strategy !== null)
		{
			return $this->strategy->populateIndexDocument($indexDocument, $compiledProduct);
		}
		return true;
	}
	
	/**
	 * @deprecated
	 */
	public function setLabels($facetResults)
	{
		if ($this->strategy !== null)
		{
			$this->strategy->setLabels($facetResults);
		}
	}
	
	/**
	 * @deprecated
	 */
	public function getLabelValue($fieldName, $value)
	{
		if ($this->strategy !== null)
		{
			return $this->strategy->getLabelValue($fieldName, $value);
		}
		return $value;
	}
	
	/**
	 * @deprecated
	 */
	public static final function clearInstance()
	{
		self::$instance = null;
	}
}