<?php
/**
 * catalog_ProductLists
 * @package modules.catalog
 */
interface catalog_ProductList
{
	const FAVORITE = 'favorite';
	const CONSULTED = 'consulted';

	/**
	 * @param string $listName
	 * @param integer $maxCount
	 * @return catalog_ProductList
	 */
	static function getListInstance($listName, $maxCount);
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	function addProductId($id);
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	function removeProductId($id);
	
	/**
	 * @return integer[]
	 */
	function getProductIds();
	
	/**
	 * Save the list.
	 */
	function saveList();
}

/**
 * This implementation uses session to store the list.
 * 
 * catalog_SessionProductList
 * @package modules.catalog
 */
class catalog_SessionProductList implements catalog_ProductList
{
	/**
	 * @var catalog_SessionLists[]
	 */
	private static $instances = array();
	
	/**
	 * @param string $listName
	 * @param integer $maxCount
	 * @return catalog_SessionProductLists
	 */
	public static function getListInstance($listName, $maxCount)
	{
		if (!isset(self::$instances[$listName]))
		{
			self::$instances[$listName] = new catalog_SessionProductList($listName, $maxCount);
			if (Framework::isDebugEnabled())
			{
				Framework::debug(var_export(self::$instances[$listName], true));
			}
		}
		return self::$instances[$listName];
	}
	
	/**
	 * @var integer[]
	 */
	private $productIds = array();
	
	/**
	 * @var string
	 */
	private $key;
	
	/**
	 * @var integer
	 */
	private $maxCount;
	
	/**
	 * @param string $listName
	 * @param integer $maxCount
	 */
	private function __construct($listName, $maxCount)
	{
		$key = 'ProductList'.ucfirst($listName);
		$this->key = $key;
		
		$sessionUser = Controller::getInstance()->getContext()->getUser();
		if ($sessionUser->hasAttribute($key, 'catalog'))
		{
			$this->productIds = $sessionUser->getAttribute($key, 'catalog');
		}
	}
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function addProductId($id)
	{
		$ids = $this->getProductIds();
		if (!in_array($id, $ids))
		{
			$this->productIds = array_merge(array($id), $ids);
			if ($this->maxCount !== null)
			{
				array_slice($this->productIds, 0, $this->maxCount);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function removeProductId($id)
	{
		$ids = $this->getProductIds();
		if (in_array($id, $ids))
		{
			$data = array();
			foreach ($ids as $oid) 
			{
				if ($oid == $id)
				{
					continue;
				}
				$data[] = $oid;
			}
			$this->productIds = $data;
			return true;
		}
		return false;
	}
	
	/**
	 * @return integer[]
	 */
	public function getProductIds()
	{
		if (!is_array($this->productIds))
		{
			$this->productIds = array();
		}
		return $this->productIds;
	}
	
	/**
	 * Save the lsit in session.
	 */
	public function saveList()
	{
		$sessionUser = Controller::getInstance()->getContext()->getUser();
		if (count($this->productIds) > 0)
		{
			$sessionUser->setAttribute($this->key, $this->productIds, 'catalog');
		}
		else
		{
			$sessionUser->removeAttribute($this->key, 'catalog');
		}
	}
}

/**
 * This implementation uses persistent cookies to store the list.
 * 
 * catalog_CookieProductList
 * @package modules.catalog
 */
class catalog_CookieProductList implements catalog_ProductList
{
	/**
	 * @var catalog_SessionLists[]
	 */
	private static $instances = array();
	
	/**
	 * @param string $listName
	 * @param integer $maxCount
	 * @return catalog_CookieProductList
	 */
	public static function getListInstance($listName, $maxCount)
	{
		if (!isset(self::$instances[$listName]))
		{
			self::$instances[$listName] = new catalog_CookieProductList($listName, $maxCount);
			if (Framework::isDebugEnabled())
			{
				Framework::debug(var_export(self::$instances[$listName], true));
			}
		}
		return self::$instances[$listName];
	}
	
	/**
	 * @var integer[]
	 */
	private $productIds = array();
	
	/**
	 * @var string
	 */
	private $key;
	
	/**
	 * @var integer
	 */
	private $maxCount;
	
	/**
	 * @param string $listName
	 * @param integer $maxCount
	 */
	private function __construct($listName, $maxCount)
	{
		$key = 'ProductList'.ucfirst($listName);
		$this->key = $key;
		
		if (isset($_COOKIE[$key]))
		{
			$this->productIds = explode(',', $_COOKIE[$key]);
		}
	}
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function addProductId($id)
	{
		$ids = $this->getProductIds();
		if (!in_array($id, $ids))
		{
			$this->productIds = array_merge(array($id), $ids);
			if ($this->maxCount !== null)
			{
				array_slice($this->productIds, 0, $this->maxCount);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function removeProductId($id)
	{
		$ids = $this->getProductIds();
		if (in_array($id, $ids))
		{
			$data = array();
			foreach ($ids as $oid) 
			{
				if ($oid == $id)
				{
					continue;
				}
				$data[] = $oid;
			}
			$this->productIds = $data;
			return true;
		}
		return false;
	}
	
	/**
	 * @return integer[]
	 */
	public function getProductIds()
	{
		if (!is_array($this->productIds))
		{
			$this->productIds = array();
		}
		return $this->productIds;
	}
	
	/**
	 * Save the lsit in session.
	 */
	public function saveList()
	{
		setcookie($this->key, implode(',', $this->productIds), time() + 365*24*3600, '/');
	}
}

/**
 * This implementation does not store the list and just set logs.
 * 
 * catalog_NullProductList
 * @package modules.catalog
 */
class catalog_NullProductList implements catalog_ProductList
{
	/**
	 * @var catalog_NullProductList
	 */
	private static $instance;
	
	/**
	 * @param string $listName
	 * @param integer $maxCount
	 * @return catalog_NullProductList
	 */
	public static function getListInstance($listName, $maxCount)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . '(' . $listName . ', ' . $maxCount . ')');
		}
		if (!isset(self::$instance))
		{
			self::$instance = new catalog_NullProductList($listName);
		}
		return self::$instance;
	}
	
	/**
	 * @var string
	 */
	private $listName;
	
	/**
	 * @var string
	 */
	private $maxCount;
	
	/**
	 * @param string $listName
	 */
	private function __construct($listName, $maxCount)
	{
		$this->listName = $listName;
		$this->maxCount = $maxCount;
	}
	
	/**
	 * @param integer $id
	 * @param integer $maxCount
	 * @return boolean
	 */
	public function addProductId($id)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . '(' . $id . ') on list ' . $this->listName);
		}
		return false;
	}
	
	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function removeProductId($id)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . '(' . $id . ') on list ' . $this->listName);
		}
		return false;
	}
	
	/**
	 * @return integer[]
	 */
	public function getProductIds()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . '() on list ' . $this->listName);
		}
	}
	
	/**
	 * Save the list.
	 */
	public function saveList()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . '() on list ' . $this->listName);
		}
	}
}