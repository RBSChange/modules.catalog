<?php
/**
 * @package modules.catalog
 * @method catalog_ListAxesService getInstance()
 */
class catalog_ListAxesService extends change_BaseService implements list_ListItemsService
{
	/**
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
				/* @var $def catalog_AttributeDefinition */
				$axes[] = new catalog_ProductAttributeAxe($def);
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

abstract class catalog_ProductAxe
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
			
			if (strpos($type, '/') > 0)
			{
				list($module, $type) = explode('/', $type);
			}
			else
			{
				$module = 'catalog';
			}
			$class = $module . '_Product' . ucfirst($type) . 'Axe';
			if (class_exists($class))
			{
				self::$namedInstances[$name] = new $class($subName);
			}
			else
			{
				self::$namedInstances[$name] = null;
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
		return LocaleService::getInstance()->trans($this->labelKey, array('ucf', 'html'));
	}
	
	/**
	 * @return the $label
	 */
	public function getUILabel()
	{
		return LocaleService::getInstance()->trans($this->labelKey, array('ucf', 'html'));
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
	abstract protected function getAxeValue($productDeclination);
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	public function getAxeValueLabel($productDeclination)
	{
		return $this->getAxeValue($productDeclination);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 */
	public function populateAxeValue($productDeclination)
	{
		if ($this->number > 0)
		{
			$setter = 'setAxe' . $this->number;
			$value = $this->getAxeValue($productDeclination);
			if ($value instanceof f_persistentdocument_PersistentDocument)
			{
				$productDeclination->{$setter}(strval($value->getId()));
			}
			elseif ($value !== null)
			{
				$productDeclination->{$setter}(strval($value));
			}
			else
			{
				$productDeclination->{$setter}(null);
			}
		}
	}
}

class catalog_ProductPropertyAxe extends catalog_ProductAxe
{
	private $propertyName;
	private $getter;
	private $labelGetter;
	
	/**
	 * @param PropertyInfo $propertyInfo
	 */
	public function __construct($propertyName)
	{
		$this->setName('property::' . $propertyName);
		$this->propertyName = $propertyName;
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
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	public function getAxeValueLabel($productDeclination)
	{
		if ($this->labelGetter === null)
		{
			if ($this->propertyName === 'label')
			{
				$this->labelGetter = array('getLabel');
			}
			else
			{
				$getter = 'get'.ucfirst($this->propertyName).'Label';
				if (f_util_ClassUtils::methodExists($productDeclination, $getter))
				{
					$this->labelGetter = array($getter);
				}
				else
				{
					$propertyInfo = $productDeclination->getPersistentModel()->getEditableProperty($this->propertyName);
					if ($propertyInfo->isDocument())
					{
						$this->labelGetter = array('get'.ucfirst($this->propertyName), 'getNavigationLabel');
					}
				}
			}
			if ($this->labelGetter === null)
			{
				$this->labelGetter = 'auto';
			}
		}
		
		if (is_array($this->labelGetter))
		{
			$value = $productDeclination;
			foreach ($this->labelGetter as $getter)
			{
				if ($value === null)
				{
					return null;
				}
				$value = $value->{$getter}();
			}
			return $value;
		}
		return $this->getAxeValue($productDeclination);
	}
}

class catalog_ProductAttributeAxe extends catalog_ProductAxe
{
	/**
	 * @var catalog_AttributeDefinition
	 */
	private $attr;
	
	/**
	 * @param catalog_AttributeDefinition|string $attribute
	 */
	public function __construct($attribute)
	{
		if ($attribute instanceof catalog_AttributeDefinition)
		{
			$this->attr = $attribute;
		}
		elseif (is_string($attribute))
		{
			$this->attr = catalog_AttributefolderService::getInstance()->getAttributeInfo($attribute);
		}
		else
		{
			throw new Exception("Invalide [attribute] parameter type:" . var_export($attribute, true));
		}
		
		$this->setName('attribute::' . $this->attr->getCode());
		$this->setLabelKey($this->attr->getI18nLabelKey());
	}
	
	/**
	 * @return the $label
	 */
	public function getUILabel()
	{
		if ($this->attr !== null)
		{
			return $this->attr->getLabel();
		}		
		return parent::getUILabel();
	}
	

	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	protected function getAxeValue($productDeclination)
	{
		$attrs = $productDeclination->getAttributes();
		if (isset($attrs[$this->attr->getCode()]))
		{
			return $attrs[$this->attr->getCode()];
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $productDeclination
	 * @return string
	 */
	public function getAxeValueLabel($productDeclination)
	{
		$value = $this->getAxeValue($productDeclination);
		if ($value)
		{
			$list = $this->attr->getList();
			if ($list)
			{
				$item = $list->getItemByValue($value);
				if ($item)
				{
					return $item->getLabel();
				}
			}
		}
		return $value;
	}
}

