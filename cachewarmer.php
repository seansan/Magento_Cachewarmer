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

// define('MAGENTO_ROOT', getcwd());
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
umask(0);

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

// TODO: IF CURL runs into error, make sure we unlink this file
$tf = "REMOVE_CURL_TMP.txt";
$fp = fopen($tf, "w");

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

echo "\n\nFinished in " . date("H:i:s", time()-$start);