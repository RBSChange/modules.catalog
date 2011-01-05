<?php
/**
 * @package modules.catalog
 */
interface catalog_ProductFacetIndexerStrategy
{
	/**
	 * @return indexer_Facet[]
	 */
	public function getFacets();
	
	/**
	 * @param indexer_IndexedDocument $indexDocument
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 * @return boolean
	 */
	public function populateIndexDocument($indexDocument, $compiledProduct);
	
	/**
	 * @param indexer_FacetResult[] $facetResults
	 */
	public function setLabels($facetResults);
	
	/**
	 * @param string $fieldName
	 * @param string $value
	 * @return string
	 */
	public function getLabelValue($fieldName, $value);
	
}

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
		Framework::info(__METHOD__ . ' ' . $className);
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
	 * @return indexer_Facet[]
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
	 * @param indexer_IndexedDocument $indexDocument
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 * @return boolean
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
	 * @param indexer_FacetResult[] $facetResults
	 */
	public function setLabels($facetResults)
	{
		if ($this->strategy !== null)
		{
			$this->strategy->setLabels($facetResults);
		}
	}
	
	/**
	 * @param string $fieldName
	 * @param string $value
	 * @return string
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
	 * @return void
	 */
	public static final function clearInstance()
	{
		self::$instance = null;
	}
}
