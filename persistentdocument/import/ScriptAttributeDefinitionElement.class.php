<?php
class catalog_ScriptAttributeDefinitionElement extends import_ScriptBaseElement
{
	/**
	 * @return array
	 */
	public function getAttributeInfos()
	{
		$attrs = $this->getComputedAttributes();

		if (!isset($attrs['code']))
		{
			throw new Exception('The attribute "code" is required on attribute.');
		}
		if (!isset($attrs['type']))
		{
			throw new Exception('The attribute "type" is required on attribute.');
		}
		$attributeInfos = array('code' => $attrs['code'], 'type' => $attrs['type']);
		foreach ($attrs as $name => $value)
		{
			if (f_util_StringUtils::beginsWith($name, '__'))
			{
				$attributeInfos['parameters'][substr($name, 2)] = $value;
			}
			elseif ($name == 'list')
			{
				$attributeInfos['listid'] = $this->getComputedAttribute('list')->getId();
				unset($this->attributes['list-refid']);
			}
			elseif ($name == 'label')
			{
				$attributeInfos['label'] = $value;
			}
			elseif ($name != 'code' && $name != 'type' && $name != 'list-refid' && $name != 'label')
			{
				echo __METHOD__, ' Unknown attribue ', $name, PHP_EOL;
			}
		}
		return $attributeInfos;
	}
}