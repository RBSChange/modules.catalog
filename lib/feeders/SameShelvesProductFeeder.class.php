<?php
/**
 * @package modules.catalog.lib.feeders
 */
class catalog_SameShelvesProductFeeder extends catalog_ProductFeeder
{
	/**
	 * @var catalog_SameShelvesProductFeeder
	 */
	private static $instance;

	/**
	 * @return catalog_SameShelvesProductFeeder
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @param array<String, mixed> $parameters
	 * @return array<array>
	 */
	public function getProductArray($parameters)
	{
		$query = catalog_CompiledproductService::getInstance()->createQuery();
		if (count($parameters['excludedId']) > 0)
		{
			$query->add(Restrictions::notin('product.id', $parameters['excludedId']));
		}
		$query->createCriteria('product')->createCriteria('shelf')->add(Restrictions::eq('product.id', $parameters['productId']));
		
		$productInstances = array();
		$productCounts = array();
		foreach ($query->find() as $compiledProduct)
		{
			$product = $compiledProduct->getProduct();
			$productInstances[$product->getId()] = $product;
			
			$productId = $product->getId();
			if (isset($productCounts[$productId]))
			{
				$productCounts[$productId] += 1;
			}
			else
			{
				$productCounts[$productId] = 1;
			}
		}
		
		arsort($productCounts);
		$result = array();
		foreach (array_slice($productCounts, 0, $parameters['maxResults'], true) as $id => $count)
		{
			$result[] = array($productInstances[$id], $count);
		}
		return $result;
	}
}