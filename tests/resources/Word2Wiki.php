<?php
class modules_catalog_tests_Word2WikiTest extends catalog_tests_AbstractBaseUnitTest
{

	public function prepareTest()
	{
	}

	public function testWord2Wiki()
	{
		$path = FileResolver::getInstance()->setPackageName('modules_catalog')->getPath('tests/unit/wiki');
		$base = 'aide_catalogue';
		$htmlFile = $base.'.html';
		$imgTag = '<IMG SRC="'.$base.'_html_';
		$imgTagLength = strlen($imgTag);

		$counter = 1;
		$offset = 0;
		$html = file_get_contents($path.DIRECTORY_SEPARATOR.$htmlFile);
		while ($offset = strpos($html, $imgTag, $offset))
		{
			$offset += $imgTagLength;
			$dotPos = strpos($html, '.', $offset);
			$imgId  = substr($html, $offset, $dotPos-$offset);
			$imgExt = substr($html, $dotPos, strpos($html, '"', $dotPos)-$dotPos);
			copy(
				$path.DIRECTORY_SEPARATOR.$base.'_html_'.$imgId.$imgExt,
				$path.DIRECTORY_SEPARATOR.'modules_catalog_help_'.sprintf("%03d", $counter).$imgExt
				);
			$mapping[$counter] = 'modules_catalog_help_'.sprintf("%03d", $counter).$imgExt;
			$counter++;
		}


		$content = f_util_FileUtils::read($path.DIRECTORY_SEPARATOR.$base.'.txt');
		$content = str_replace('’', "'", $content);
		$text = '[[Image:]]';
		$len = strlen($text);
		$counter = 0;
		while ($offset = strpos($content, $text, $offset))
		{
			$repl = '[[Image:'.$mapping[++$counter].']]';
			$content = substr_replace($content, $repl, $offset, $len);
		}
		f_util_FileUtils::write($path.DIRECTORY_SEPARATOR.$base.'.mediawiki.txt', $content, f_util_FileUtils::OVERRIDE);
	}
}
