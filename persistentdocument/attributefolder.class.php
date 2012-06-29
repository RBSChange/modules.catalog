<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_attributefolder
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_attributefolder extends catalog_persistentdocument_attributefolderbase 
{
	/**
	 * cached version of serializedattributes property
	 * @var array
	 */
	private $unserializedAttributes;
	
	/**
	 * @return catalog_AttributeDefinition[]
	 */
	public final function getAttributes()
	{
		if ($this->unserializedAttributes === null)
		{
			$this->unserializedAttributes = array();
			
			// set cache
			$val = $this->getSerializedattributes();
			if (f_util_StringUtils::isNotEmpty($val))
			{
				foreach (unserialize($val) as $array)
				{
					$attr = new catalog_AttributeDefinition();
					$attr->initFromArray($array);
					$this->unserializedAttributes[$attr->getCode()] = $attr;
				}
			}
		}	
		
		return $this->unserializedAttributes;
	}
	
	/**
	 * @param string $code
	 * @return catalog_AttributeDefinition
	 */
	public final function getAttributeByCode($code)
	{
		$attrs = $this->getAttributes();
		return (isset($attrs[$code])) ? $attrs[$code] : null;
	}
	
	/**
	 * @param catalog_AttributeDefinition[] $attributes
	 */
	public final function setAttributes($attributes)
	{
		// reset cache
		$this->unserializedAttributes = null;	
		if (f_util_ArrayUtils::isEmpty($attributes))
		{
			$this->setSerializedattributes(null);
		}
		else
		{
			$data = array();
			foreach ($attributes as $value)
			{
				/* @var $value catalog_AttributeDefinition */
				if ($value instanceof catalog_AttributeDefinition)
				{
					$data[$value->getCode()] = $value->toArray();
				}
			}
			$this->setSerializedattributes(serialize(array_values($data)));
		}
	}

	
	/**
	 * Return JSON String
	 * @return string
	 */
	public function getAttributesJSON()
	{
		$attributes = $this->getAttributes();
		if (count($attributes))
		{
			$data = array();
			foreach ($attributes as $attr)
			{
				/* @var $attr catalog_AttributeDefinition */
				$data[] = $attr->toBoArray();
			}
			return JsonService::getInstance()->encode($data);
		}
		return null;
	}
	
	/**
	 * @param string $json
	 */
	public function setAttributesJSON($json)
	{
		if (f_util_StringUtils::isEmpty($json))
		{
			$this->setSerializedattributes(null);
		}
		else
		{
			$data = JsonService::getInstance()->decode($json);
			$attributes = array();
			foreach ($data as $array)
			{
				$attr = new catalog_AttributeDefinition();
				$attr->initFromArray($array);
				$attributes[$attr->getCode()] = $attr;
				if (!$attr->hasI18nLabel() || (f_util_StringUtils::isNotEmpty($array['label']) && $attr->getLabel() != $array['label']))
				{
					$this->getDocumentService()->setDefaultI18nLabel($attr->getI18nLabelKey(), $array['label']);
				}
			}
			$this->setAttributes($attributes);
		}
	}
}



class catalog_AttributeDefinition 
{
	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string
	 */
	private $label = false;
	
	/**
	 * @var string text|numeric
	 */
	private $type = 'text';

	/**
	 * @var integer
	 */
	private $listid = null;
	
	
	/**
	 * @var string
	 */
	private $listlabel = false;
	
	
	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->label === false)
		{
			$this->label = LocaleService::getInstance()->trans($this->getI18nLabelKey(), array('ucf'));
		}
		return $this->label;
	}
	
	/**
	 * @return string
	 */
	public function getI18nLabelKey()
	{
		return'm.catalog.attributes.label-' . strtolower($this->getCode());
	}
	
	/**
	 * @return boolean
	 */
	public function hasI18nLabel()
	{
		return $this->getI18nLabelKey() != $this->getLabel();
	}	
	
	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return number
	 */
	public function getListid()
	{
		return $this->listid;
	}

	/**
	 * @param string $code
	 */
	protected function setCode($code)
	{
		$this->code = $code;
	}

	/**
	 * @param string $type
	 */
	protected  function setType($type)
	{
		if ($type === 'numeric')
		{
			$this->type = 'numeric';
		}
		else
		{
			$this->type = 'text';
		}
	}
	
	/**
	 * @return number
	 */
	protected function setListid($listid)
	{
		$list = DocumentHelper::getDocumentInstanceIfExists($listid);
		if ($list instanceof list_persistentdocument_list)
		{
			$this->listid = $list->getId();
			$this->listlabel = $list->getTreeNodeLabel();
			return;
		}
	
		$this->listid = null;
		$this->listlabel = '';
	}
	
	/**
	 * @return list_persistentdocument_list | null
	 */
	public function getList()
	{
		$list = DocumentHelper::getDocumentInstanceIfExists($this->listid);
		if ($list instanceof list_persistentdocument_list)
		{

			return $list;
		}
		return null;
	}

	public function initFromArray($array)
	{
		$this->setCode($array['code']);
		
		if (isset($array['listid']) && intval($array['listid']))
		{
			$this->setListid($array['listid']);
		}
		
		if (isset($array['type']))
		{
			$this->setType($array['type']);
		}
	}
		
	public function toArray()
	{
		$result = array('code' => $this->getCode(), 'type' => $this->getType(), 'listid' => $this->getListid());
		return $result;
	}
	
	public function toBoArray()
	{
		$result = $this->toArray();
		$result['label'] = $this->getLabel();
		$list = $this->getList();
		if ($list) 
		{
			$result['listlabel'] = $list->getTreeNodeLabel();
			$result['listcode'] = $list->getListid();
		}
		else
		{
			$result['listlabel'] = '';
			$result['listcode'] = '';
		}
		$result['labelkey'] = $this->getI18nLabelKey();
		return $result;
	}
}