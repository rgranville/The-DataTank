<?php
/**
 * This file prettyprints autogenerated API Documentation of a certain method of a certain module.
 * @package The-Datatank/docs
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jan Vansteenlandt <jan@iRail.be>
 * @author Pieter Colpaert   <pieter@iRail.be>
 */
ini_set("include_path", "../");
include_once ("modules/ProxyModules.php");
include_once ("TDT.class.php");

$module = $matches[1];
$method = $matches[2];

/*
 * There are two possibilities: the module/method is a local one OR the module/method is a remote one
 * We'll have to check our proxymodules in order to know which one it is.
 *
 * If it's a remote one we need to get the documentation through a documentation module which is
 * called TDTInfo, the method Module expects the mod="modulename" and meth="methodname" and returns it's
 * documentation.
 */
$methodname = $method;

if(array_key_exists($module, ProxyModules::$modules)) {
	/*
	 * If it's a proxymodule we need to split the URL listed in ProxyModules by the "/" sign
	 * and build the url from scratch.
	 */
	$moduleURL = ProxyModules::$modules[$module];
	$boom = explode("/", $moduleURL);
	// take third part of the explode = baseurl
	$url = "http://" . $boom[2] . "/TDTInfo/Module/?format=php&mod=" . $boom[3] . "&meth=" . $method;
} else {
	/*
	 * If it's not a proxymodule we ask our own module (yes the TDT documentation is a TDT module itself :D)
	 * to return the object with the proper documentation.
	 */
	$url = Config::$HOSTNAME . "TDTInfo/Module/?format=json&mod=" . $module . "&meth=" . $method;
}

$method = json_decode(TDT::HttpRequest($url) -> data);

include_once ("templates/TheDataTank/header.php");

echo "<h1>" . $module . "/" . $methodname . "</h1>";
//get a sequence of the parameters
$args = "";
if(sizeof(($method -> requiredparameter)) > 0) {
	$params = $method -> requiredparameter;
	$args = "?" . $params[0] . "=...";
	$i = 0;
	foreach($params as $var) {
		if($i != 0) {
			$args .= "&$var=...";
		}
		$i++;
	}
}
/* build the proper URL's to invoke when doing a call for a certain method */
$url = Config::$HOSTNAME . "$module/$methodname/$args";
echo "<a href=\"$url\">$url</a>";
echo "<h3>Description</h3>";
echo $method -> doc;
if(sizeof($method -> parameter) > 0) {
	echo "<h3>All possible parameters:</h3>";
	echo "<ul>\n";
	foreach($method->parameter as $var => $doc) {
		echo "<li><strong>$var:</strong> $doc\n";
	}
	echo "</ul>\n";
} else {
	echo "<h3>This method has no parameters.</h3>";
}

echo "<br/>";
echo "<a href=\"/docs/\">&laquo; Back to the datasets</a>";
include_once ("templates/TheDataTank/footer.php");?>