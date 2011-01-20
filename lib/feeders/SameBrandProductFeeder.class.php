<?php
/**
 * @package modules.catalog.lib.feeders
 */
class catalog_SameBrandProductFeeder extends catalog_ProductFeeder
{
	/**
	 * @var catalog_SameBrandProductFeeder
	 */
	private static $instance;

	/**
	 * @return catalog_SameBrandProductFeeder
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			$finalClassName = Injection::getFinalClassName(get_class());
			self::$instance = new $finalClassName();
		}
		return self::$instance;
	}

	/**
	 * @param array<String, mixed> $parameters
	 * @return array<array>
	 */
	public function getProductArray($parameters)
	{
		$product = DocumentHelper::getDocumentInstance($parameters['productId']);			
		$brand = $product->getBrand();
		if ($brand === null)
		{
			return array();
		}
		$query = catalog_CompiledproductService::getInstance()->createQuery();
		if (count($parameters['excludedId']) > 0)
		{
			$query->add(Restrictions::notin('product.id', $parameters['excludedId']));
		}
		$query->add(Restrictions::eq('primary', true));
		$query->add(Restrictions::eq('brandId', $brand->getId()));
		$query->setProjection(Projections::property('product', 'product'));
		$products = $query->findColumn('product');
		
		foreach (array_slice($products, 0, $parameters['maxResults']) as $product)
		{
			$result[] = array($product, '1');
		}
		return $result;
	}
}