<?php
/**
 * catalog_ListVisibledeclinationsbyproductService
 * @package modules.catalog.lib.services
 */
class catalog_ListVisibledeclinationsbyproductService extends BaseService implements list_ListItemsService
{
	/**
	 * @var catalog_ListVisibledeclinationsbyproductService
	 */
	private static $instance;

	/**
	 * @return catalog_ListVisibledeclinationsbyproductService
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
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		foreach ($this->getVisibleDeclinations() as $declination)
		{
			$items[] = new list_Item(
				$declination->getNavigationLabel(),
				$declination->getId()
			);
		}
		return $items;
	}

	/**
	 * @return string
	 */
	public final function getDefaultId()
	{
		return 'none';
	}
	
	/**
	 * @var array<integer, catalog_persistentdocument_productdeclination[]>
	 */
	protected $visibleDeclinationsByDclinedId = array();
	
	/**
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	protected function getVisibleDeclinations()
	{
		$request = Controller::getInstance()->getContext()->getRequest();
		$declinedId = intval($request->getParameter('productId', 0));
		Framework::fatal(__METHOD__ . ' ' . $declinedId);
		if (!isset($this->visibleDeclinationsByDclinedId[$declinedId]))
		{
			$declined = DocumentHelper::getDocumentInstanceIfExists($declinedId);
			$visibleDeclinations = array();
			if ($declined instanceof catalog_persistentdocument_declinedproduct)
			{
				foreach ($declined->getDeclinationArray() as $declination)
				{
					$key = $declined->getId() . '|';
					switch ($declined->getShowAxeInList())
					{
						case 1: $key .= $declination->getAxe1() . '||'; break;
						case 2: $key .= $declination->getAxe1() . '|' . $declination->getAxe2() . '|'; break;
						case 3: $key .= $declination->getAxe1() . '|' . $declination->getAxe2() . '|' . $declination->getAxe3(); break;
						default: $key .= '||'; break;
					}
				
					if (!isset($visibleDeclinations[$key]))
					{
						$visibleDeclinations[$key] = $declination;
					}
				}
			}
			$this->visibleDeclinationsByDclinedId[$declinedId] = array_values($visibleDeclinations);
		}
		return $this->visibleDeclinationsByDclinedId[$declinedId];
	}
}