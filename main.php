If your config file doesn't have a correctly formed m: (client)
or M: (server) line, this program will fuck itself over.

Only the first modproto% module will be included.

<?php
function __autoload($c) {
	require_once("./modules/".$c.".php");
}

//error_reporting(0);
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;

function parseConf($linename,$rehash) {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Returns an array containing at the first dimension
	// all lines with the $linename in the config
	// and at the second dimension the items on that line.
	// For .rehash requires to be done for every module
	if ($linename == "") return false;
	if (strcasecmp($rehash,"yes"))
		$file = file_get_contents("./jaffabot.conf");
	$filelines = explode("\n",$file);
	$lines = array();
	foreach ($filelines as $line) {
		$linearr = explode("%",$line);
		if ($linearr[0] == $linename)
			$lines[] = $linearr;
	}
	return $lines;
}

function Rehash() {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	$file = file_get_contents("./jaffabot.conf");
}

function regCallback($object, $func, $protocolWord){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	$callbacks[$protocolWord][] = array($object, $func);
}

function callCallbacks($get){
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Format for a parsed line is:
	/*
	 * $get[0] = Command (or any chosen special word, just has to be standard :P)
	 * $get[1] = Source (or local server if no source)
	 * $get[2] and so on = Rest of arguments
	 */
	if ($callbacks[$get["cmd"]])
		foreach ($callbacks[$get["cmd"]] as $callback) call_user_func($callback,array_slice($get,1));
}

Rehash();
$modeline = parseConf("mode","no");
$opMode = "client";
$protomod = parseConf("modproto","no");
$connect = parseConf("server","no");
$modules = parseConf("loadmod","no");
$socket = fsockopen($connect[0][1]);
if ($modeline[0][1] == "S") $opMode = "serv";
if ($opMode == "serv") {
	$Mline = parseConf("M","no");
} else {
	$Mline = parseConf("m","no");
}

require_once($protomod[0][1]);
$protofunc = new protocol();
$protofunc->protocol_start(array_slice($Mline[0],1)); // Protocols should follow the standard M:line setup
foreach ($modules as $mod) {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	// Modules take their options as an array.
	$mods[$mod[1]] = new $mod[1]($mod[1],array_slice($mod,2));
}
while (true) {
global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket;
	if (feof($socket)) die("Error  Socket fucked a duck");
	$get = fgets($socket,514);
	$got = $protofunc->parse(trim($get));
	callCallbacks($got);
}
