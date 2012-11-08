<?php
/**
 * catalog_patch_0380
 * @package modules.catalog
 */
class catalog_patch_0380 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		if (ModuleService::getInstance()->isInstalled('mysqlstock'))
		{
			// Detect collisions.
			$query = 'SELECT TRIM(`code_sku`) AS `sku`, count(*) AS `quantity` FROM `m_mysqlstock_mod_product` GROUP BY TRIM(`code_sku`) HAVING count(*) > 1';
			$statement = $this->executeSQLSelect($query);
			$statement->execute();
			$rows = $statement->fetchAll();
			if (count($rows))
			{
				$this->logError(count($rows) . ' SKU codes will cause collisions after trim:');
				foreach ($rows as $row)
				{
					$this->logError('  `' . $row['sku'] . '`: ' . $row['quantity'] . ' collisions');
				}
				$this->logError('Fix them before executing this patch.');
				die;
			}
			
			// Apply trim on SKU codes in mysqlstock.
			$this->executeSQLQuery('UPDATE `m_mysqlstock_mod_product` SET `code_sku` = TRIM(`code_sku`);');
		}
		
		// Apply trim on SKU codes in products.
		$batchPath = 'modules/catalog/patch/0380/batchTrimSKU.php';
		$chunkSize = 50;
		$id = 0;
		$errors = array();
		while ($id >= 0)
		{
			$result = f_util_System::execScript($batchPath, array($id, $chunkSize));
			$lines = array_reverse(explode(PHP_EOL, $result));
			foreach ($lines as $line)
			{
				if (preg_match('/^id:([0-9-]+)$/', $line, $matches))
				{
					$id = $matches[1];
					break;
				}
				else
				{
					$errors[] = $line;
				}
			}
		}
		if (count($errors))
		{
			throw new Exception(implode(PHP_EOL, array_reverse($errors)));
		}
	}
}