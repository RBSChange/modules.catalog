<?php
/**
 * @package modules.catalog.lib
 */
interface catalog_ComparableProperty
{
	/**
	 * @param mixed[] $parameters
	 */
	function __construct($parameters);	
	
	/**
	 * @return string
	 */
	function getLabel();
	
	/**
	 * @return string
	 */
	function getLabelAsHtml();
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return mixed
	 */
	function getValue($product);
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return mixed
	 */
	function getValueAsHtml($product);
}

/**
 * @package modules.catalog.lib
 */
abstract class catalog_BaseComparableProperty implements catalog_ComparableProperty
{
	/**
	 * @return string
	 */
	public function getLabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getLabel());
	}
}

/**
 * @package modules.catalog.lib
 */
class catalog_GetterComparableProperty extends catalog_BaseComparableProperty
{
	/**
	 * @var string
	 */
	protected $getter; 
	
	/**
	 * @var string
	 */
	protected $labelkey; 
	
	/**
	 * @param string[] $parameters [getter, label]
	 */
	public function __construct($parameters)
	{
		list($this->getter,	$this->labelKey) = $parameters;
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO($this->labelKey, array('ucf'));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return mixed
	 */
	public function getValue($product)
	{
		return $product->{$this->getter}();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return mixed
	 */
	public function getValueAsHtml($product)
	{
		if (f_util_ClassUtils::methodExists($product, $this->getter.'AsHtml'))
		{
			return $product->{$this->getter.'AsHtml'}();
		}
		else 
		{
			return $product->{$this->getter}();
		}
	}
}

/**
 * @package modules.catalog.lib
 */
class catalog_PersistentComparableProperty extends catalog_GetterComparableProperty
{
	/**
	 * @var BeanPropertyInfo
	 */
	protected $prop;
	
	/**
	 * @param string[] $parameters [propertyName]
	 */
	public function __construct($parameters)
	{
		list($propertyName) = $parameters;
		$this->prop = f_persistentdocument_PersistentDocumentModel::getInstance('catalog', 'product')->getBeanPropertyInfo($propertyName);
		$this->getter = $this->prop->getGetterName();
		$this->labelKey = LocaleService::getInstance()->cleanOldKey($this->prop->getLabelKey());
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @return mixed
	 */
	public function getValueAsHtml($product)
	{
		switch ($this->prop->getType())
		{
			case BeanPropertyType::BOOLEAN:
				$key = 'f.boolean.' . ($product->{$this->getter}() ? 'yes' : 'no');
				return LocaleService::getInstance()->transFO($key, array('ucf', 'html'));
				
			case BeanPropertyType::STRING:
			case BeanPropertyType::LONGSTRING:
			case BeanPropertyType::XHTMLFRAGMENT:
				return $product->{$this->getter.'AsHtml'}();
				
			case BeanPropertyType::DOCUMENT:
				if ($this->prop->getMaxOccurs() != 1)
				{
					return 'invalid max occurs: ' . $this->prop->getMaxOccurs();
				}
				$doc = $product->{$this->getter}();
				if ($doc === null || !$doc->isPublished()) { return null; }
				if ($doc->getPersistentModel()->hasUrl())
				{
					return '<a class="link" href="' . LinkHelper::getDocumentUrl($doc) . '">' . $doc->getLabelAsHtml() . '</a>';
				}
				else
				{
					return $doc->getLabelAsHtml();
				}
				
			case BeanPropertyType::DATE:
				$date = $doc = $product->{$this->getter}();
				if ($date === null) { return null; }
				return date_Formatter::toDefaultDate($date);
				
			case BeanPropertyType::DATETIME:
				$date = $doc = $product->{$this->getter}();
				if ($date === null) { return null; }
				return date_Formatter::toDefaultDatetime($date);
				
			default:
				return $product->{$this->getter}();
		}
	}
}

/**
 * @package modules.catalog.lib
 */
class catalog_AvailabilityComparableProperty extends catalog_BaseComparableProperty
{
	/**
	 * @param string[] $parameters
	 */
	public function __construct($parameters)
	{
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO('m.catalog.frontoffice.availability', array('ucf'));
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValue($product)
	{
		return f_util_HtmlUtils::htmlToText($this->getValueAsHtml($product));
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValueAsHtml($product)
	{
		if (!$product->getPriceForCurrentShopAndCustomer())
		{
			return LocaleService::getInstance()->transFO('m.catalog.fo.add-to-cart-unavaible', array('ucf', 'html'));
		}
		return $product->getAvailability();
	}
}

/**
 * @package modules.catalog.lib
 */
class catalog_ReviewComparableProperty extends catalog_BaseComparableProperty
{
	/**
	 * @var boolean
	 */
	protected $withRating;
	
	/**
	 * @param string[] $parameters [withRating]
	 */
	public function __construct($parameters)
	{
		list($withRating) = $parameters;
		$this->withRating = $withRating == 'true';
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO('m.catalog.fo.reviews', array('ucf'));
	}
		
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValue($product)
	{
		return f_util_HtmlUtils::htmlToText($this->getValueAsHtml($product));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValueAsHtml($product)
	{
		$ls = LocaleService::getInstance();
		$count = comment_CommentService::getInstance()->getPublishedCountByTargetId($product->getId());
		if ($count)
		{
			$value = '<a class="link" href="' . LinkHelper::getDocumentUrl($product) . '#comments-' . $product->getId() . '">';
			$value .= $ls->transFO('m.catalog.fo.reviews-count', array('ucf', 'html'), array('count' => $count));
			$value .= '</a>';
			if ($this->withRating)
			{
				$value .= '<br />';
				$value .= $ls->transFO('m.catalog.frontoffice.rating-average', array('ucf', 'lab', 'html')) . ' ';
				$rating = $product->getFormattedRatingAverage();
				$value .= ($rating != '0.0') ? $rating : $ls->transFO('m.catalog.fo.no-rating', array('ucf', 'html'));
			}
		}
		else
		{
			$value = $ls->transFO('m.catalog.fo.no-review-yet', array('ucf', 'html'));
		}
		return $value;
	}
}

/**
 * @package modules.catalog.lib
 */
class catalog_ShortDescriptionComparableProperty extends catalog_BaseComparableProperty
{
	/**
	 * @var integer
	 */
	protected $maxCount;
	
	/**
	 * @param string[] $parameters [withRating]
	 */
	public function __construct($parameters)
	{
		list($this->maxCount) = $parameters;
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO('m.catalog.document.product.description', array('ucf'));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValue($product)
	{
		return f_util_HtmlUtils::htmlToText($product->getShortDescription($this->maxCount));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValueAsHtml($product)
	{
		$html = '<div class="product-description ctoggle"><div class="short">';
		$html .= $product->getShortDescription($this->maxCount);
		$html .= '</div><div class="full">';
		$html .= $product->getDescriptionAsHtml();
		$html .= '</div></div>';
		return $html;
	}
}

/**
 * @package modules.catalog.lib
 */
class catalog_PriceComparableProperty extends catalog_BaseComparableProperty
{
	/**
	 * @var catalog_persistentdocument_price[]
	 */
	protected static $pricesByProduct = array();
	
	/**
	 * @var string ht|ttc
	 */
	protected $mode;
	
	/**
	 * @param string[] $parameters [withRating]
	 */
	public function __construct($parameters)
	{
		list($this->mode) = $parameters;
	}
	
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO('m.catalog.frontoffice.price'.$this->mode, array('ucf'));
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValue($product)
	{
		$suffix = ($this->mode == 'ht') ? 'WithoutTax' : 'WithTax';
		return $price->{'getFormattedValue'.$suffix}();
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return string
	 */
	public function getValueAsHtml($product)
	{
		$suffix = ($this->mode == 'ht') ? 'WithoutTax' : 'WithTax';
		$price = $this->getPrice($product);
		if ($price === null)
		{
			return '-';
		}
		
		$html = '<span class="price">' . $price->{'getFormattedValue'.$suffix}();
		$html .= ' <span class="tax-mode">' . LocaleService::getInstance()->transFO('m.catalog.frontoffice.' . $this->mode, array('uc', 'html')) . '</span>';
		if ($price->isDiscount())
		{
			$html .= ' <del>' . $price->{'getFormattedOldValue'.$suffix}() . '</del>';
		}
		$html .= '</span>';
		return $html;
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 * @return catalog_persistentdocument_price
	 */
	protected function getPrice($product)
	{
		$id = $product->getId();
		if (!isset(self::$pricesByProduct[$id]))
		{
			self::$pricesByProduct[$id] = $product->getPriceForCurrentShopAndCustomer();
		}
		return self::$pricesByProduct[$id];
	}
}