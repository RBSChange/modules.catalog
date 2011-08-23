<?php
/**
 * catalog_ListAxesService
 * @package modules.catalog.lib.services
 */
class catalog_ListAxesService extends BaseService
{
	/**
	 * @var catalog_ListAxesService
	 */
	private static $instance;

	/**
	 * @return catalog_ListAxesService
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
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		$axes = $this->getAllDeclinationAxes();	
		foreach ($axes as $axe) 
		{
			$items[] = new list_Item($axe->getUILabel(), $axe->getName());
		}
		return $items;
	}

	/**
	 * @var Array
	 */
	private $parameters = array();
	
	/**
	 * @see list_persistentdocument_dynamiclist::getListService()
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * @see list_persistentdocument_dynamiclist::getItemByValue()
	 * @param string $value;
	 * @return list_Item
	 */
//	public function getItemByValue($value)
//	{
//	}

	/**
	 * @return catalog_ProductAxe[]
	 */
	protected function getAllDeclinationAxes()
	{
		$axes = array();
		$model = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'productdeclination');
		foreach ($model->getEditablePropertiesInfos() as $propertyInfo) 
		{
			$axe = $this->buildAxesByProperty($propertyInfo);
			if ($axe !== null)
			{
				$axes[] = $axe;
			}
		}
		
		$attrFolder = catalog_AttributefolderService::getInstance()->getAttributeFolder();
		if ($attrFolder !== null)
		{
			foreach ($attrFolder->getAttributes() as $def) 
			{
				$axes[] = new catalog_ProductAttributeAxe($def['code']);
			}
		}
		return $axes;
	}
	
	/**
	 * @param PropertyInfo $propertyInfo
	 * @return catalog_ProductPropertyAxe
	 */
	private function buildAxesByProperty($propertyInfo)
	{
		$name = $propertyInfo->getName();
		switch ($name)
		{
			case 'authorid':
			case 'lang':
			case 'modelversion':	
			case 'documentversion':
			case 'articleId':
			case 'declinedproduct':
			case 'indexInDeclinedproduct':
			case 'axe1':	
			case 'axe2':	
			case 'axe3':
				return null;		
		}
		
		if ($propertyInfo->getType() === f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING)
		{
			$constraints = $propertyInfo->getConstraints();
			if (empty($constraints) || $propertyInfo->isLocalized())
			{
				return null;
			}
			$matches = null;
			if (!preg_match('/maxSize:(\d+)/', $constraints, $matches))
			{
				return null;
			}
			else if (intval($matches[1] > 25))
			{
				return null;
			}
			return new catalog_ProductPropertyAxe($name);
		}
		else if ($propertyInfo->isDocument() && !$propertyInfo->isArray())
		{
			return new catalog_ProductPropertyAxe($name);
		}
		else if ($propertyInfo->getType() === f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER)
		{
			return new catalog_ProductPropertyAxe($name === 'id' ? 'label' : $name);
		}
		else
		{
			return null;
		}
	}
}

class catalog_ProductAxe
{	
	/**
	 * @var catalog_ProductAxe[]
	 */
	private static $namedInstances = array();
	
	/**
	 * @param string $name
	 * @param integer $axeNumber
	 * @return catalog_ProductAxe;
	 */
	public static function getInstanceByName($name, $axeNumber = 1)
	{
		if (!array_key_exists($name, self::$namedInstances))
		{
			list($type, $subName) = explode('::', $name);
			switch ($type) 
			{
				case 'property':
					self::$namedInstances[$name] = new catalog_ProductPropertyAxe($subName);
					break;
				case 'attribute':
					self::$namedInstances[$name] = new catalog_ProductAttributeAxe($subName);
					break;
				default:
					self::$namedInstances[$name] = null;
					break;
			}			
		}
		$axe = self::$namedInstances[$name];
		if ($axe instanceof catalog_ProductAxe)
		{
			$axe->setNumber($axeNumber);
		}
		return $axe;
	}
	
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $labelKey;
	
	/**
	 * @var integer
	 */	
	private $number;
		
	/**
	 * @return the $name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return the $label
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO($this->labelKey, array('ucf', 'html'));
	}
	
	/**
	 * @return the $label
	 */
	public function getUILabel()
	{
		return LocaleService::getInstance()->transBO($this->labelKey, array('ucf', 'html'));
	}

	/**
	 * @return the $number
	 */
	public function getNumber()
	{
		return $this->number;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param string $label
	 */
	public function setLabelKey($key)
	{
		$this->labelKey = $key;
	}

	/**
	 * @param integer $number
	 */
	public function setNumber($number)
	{
		$this->number = $number;
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	protected function getAxeValue($productDeclination)
	{
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 */
	public function populateAxeValue($productDeclination)
	{
		if ($this->number > 0)
		{
			$setter = 'setAxe' . $this->number;
			$productDeclination->{$setter}($this->getAxeValue($productDeclination));
		}
	}
}

class catalog_ProductPropertyAxe extends catalog_ProductAxe
{

	private $getter;
	
	/**
	 * @param PropertyInfo $propertyInfo
	 */
	public function __construct($propertyName)
	{
		$this->setName('property::' . $propertyName);
		if ($propertyName === 'label')
		{
			$this->getter = 'getId';	
		}
		else
		{
			$this->getter = 'get' . ucfirst($propertyName);
		}
		$this->setLabelKey('m.catalog.document.productdeclination.'. strtolower($propertyName));
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	protected function getAxeValue($productDeclination)
	{
		
		return $productDeclination->{$this->getter}();
	}	
}

class catalog_ProductAttributeAxe extends catalog_ProductAxe
{
	private $attributeName;
	
	/**
	 * @return the $label
	 */
	public function getUILabel()
	{
		$attrFolder = catalog_AttributefolderService::getInstance()->getAttributeFolder();
		if ($attrFolder !== null)
		{
			foreach ($attrFolder->getAttributes() as $def) 
			{
				if ($def['code'] === $this->attributeName)
				{
					return $def['label'];
				}
			}
		}		
		return parent::getUILabel();
	}
	
	/**
	 * @param string $attributeName
	 */
	public function __construct($attributeName)
	{
		$this->setName('attribute::' . $attributeName);
		$this->attributeName = $attributeName;
		$this->setLabelKey('m.catalog.document.product.attr-'. strtolower($attributeName));
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	protected function getAxeValue($productDeclination)
	{
		$attrs = $productDeclination->getAttributes();
		if (isset($attrs[$this->attributeName]))
		{
			return $attrs[$this->attributeName];
		}
		return null;
	}	
}

