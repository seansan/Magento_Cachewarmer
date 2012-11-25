<?php
/**
 * SNH Extension
 *
 * NOTICE OF LICENSE
 *
 *
 * @category   SNH
 * @package    SNH_Cachewarm
 * @copyright  Copyright (c) 2011-2012 SNH
 * @license    http://opensource.com/license
 */

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

//define('MAGENTO_ROOT', getcwd());
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
umask (0);
Mage::app();


// require_once '../app/code/core/Mage/Sitemap/Model/Sitemap.php';
umask(0);

class Cachewarmer_Model_Sitemap extends Mage_Sitemap_Model_Sitemap {

    public function generateSitemaps()
    {
        $errors = array();

        $collection = Mage::getModel('sitemap/sitemap')->getCollection();
        /* @var $collection Mage_Sitemap_Model_Mysql4_Sitemap_Collection */
        foreach ($collection as $sitemap) {
            /* @var $sitemap Mage_Sitemap_Model_Sitemap */
            try {
                $sitemap->generateXml();
				return true;
            }
            catch (Exception $e) {
                $errors[] = $e->getMessage();
				return false;
            }
        }
	}


	public function warmCache($mute, $all)
	{
	// this->generateSitemaps();
	if (empty($all)) $all = false;
	if (empty($mute)) $mute = false;

	$collection = Mage::getModel('sitemap/sitemap')->getCollection();

	foreach ($collection as $sitemap) {
		// Not working always executing 1
		// if (!$all & Mage::app()->getStore()->getId() != $sitemap->{store_id}) continue;
		$io = new Varien_Io_File();
		$fn = $io->getCleanPath(Mage::getBaseDir() . '/' . $sitemap->getSitemapPath()) . $sitemap->getSitemapFilename();

		$tf = "REMOVE_CURL_TMP.txt"; $fp = fopen($tf, "w");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		// TODO: Create an verbose option to redirect output to browser
		// Every page under the other with H2 TAG with counter, page name and timestamp
		curl_setopt($ch, CURLOPT_FILE, $fp);

		$start=time();
		if (!$mute) echo "\n<br/>Cachewarmer started @ " . date("H:i:s", time()) . "\n<br/>";

		if (fopen($fn,"r")) {
			$xml = simplexml_load_file($fn);
			for($i=0,$size=count($xml);$i<$size;$i++)
			{
				if (!$mute) echo $i.": ".date("H:i:s", time()).": ".$xml->url[$i]->loc."\n<br/>";
				curl_setopt($ch, CURLOPT_URL, $xml->url[$i]->loc);
				curl_exec($ch);
				if (!$mute) flush();
			}
		}

	}

	// TODO: IF CURL runs into error, make sure we unlink this file
	curl_close($ch);
	fclose($tf); unlink($tf);
	unset($collection);

	if (!$mute) echo "Cachewarmer. Finished in " . date("H:i:s", time()-$start);
	Mage::log("Cachewarmer. Finished in " . date("H:i:s", time()-$start));
	}
}

$shell = new Cachewarmer_Model_Sitemap();
$mute = !empty($_GET['mute']);				// Mutes output so script can be used in cron: cachewarmer.php?mute=1 of for cron cachewarmer.php mute=1
$all = !empty($_GET['all']);				// If set warms all stores it can find: cachewarmer.php?all=1 of for cron cachewarmer.php all=1
$shell->warmCache($mute, $all);

/**
$snow = array();

// TODO: create option to run it multistore, use GET flag option for all, otherwise based on domain that is accessed
// TODO: Maybe create a flag to check for a string as passwd (so nobody miss-uses this to overload server)
$storeId = Mage::app()->getStore()->getId();

$baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

// TODO: re-use Mage sitemap class, dont copy code
// TODO: in such a way that also extensions like AW_BLOG are correctly loaded
$collection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($storeId);
foreach ($collection as $item) array_push($snow, htmlspecialchars($baseUrl . $item->getUrl()));

$collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
foreach ($collection as $item) array_push($snow, htmlspecialchars($baseUrl . $item->getUrl()));

$collection = Mage::getResourceModel('sitemap/cms_page')->getCollection($storeId);
foreach ($collection as $item) array_push($snow, htmlspecialchars($baseUrl . $item->getUrl()));



// TODO: actually check if this warms the cache as promised
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
// TODO: Create an verbose option to redirect output to browser
// Every page under the other with H2 TAG with counter, page name and timestamp
curl_setopt($ch, CURLOPT_FILE, $fp);

// TODO: Maybe group the output in per second batches, this way slow pages are more clear
$start=time();
foreach ($snow as $warmurl) {
	curl_setopt($ch, CURLOPT_URL, $warmurl);
	curl_exec($ch);
	echo date("H:i:s", time()) . ": " . $warmurl . "<br/>\r\n";
}

curl_close($ch);
fclose($tf);
unlink($tf);
unset($collection);
unset($snow);



**/