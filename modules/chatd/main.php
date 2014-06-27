<?php

define("UFL_SSL",                                0x00001);
define("UFL_IDENT",                              0x00002);

define("CFL_OP",                                 0x00001);
define("CFL_HOP",                                0x00002);
define("CFL_SPEAK",                              0x00004);

class chatd_main {
	function __construct($name,$args) {
		global $mods;
		$ports = parseConf("CH",false);
		$pssl = parseConf("CHssl",false);
		foreach ($ports as $port) $mods["%select%"]->listen($port[1],array($this,"accept"));
		foreach ($pssl as $port) $mods["%select%"]->listen_ssl($port[1],$port[2],array($this,"acceptssl"));
	}

	function accept($fd) {
	// New connection, create the local structures -- j4jackj
	// TODO: and send introduction over the botnet
	$this->users[(int)$fd] = new stdClass();
	$this->users[(int)$fd]->mode = 0;
	$GLOBALS["callbacks"]["%input%"][(int)$fd] = array($this,"p");
	}

	function acceptssl($fd) {
	// New connection, create the local structures -- j4jackj
	// TODO: and send introduction over the botnet
	$this->users[(int)$fd] = new stdClass();
	$this->users[(int)$fd]->mode = UFL_SSL;
	$GLOBALS["callbacks"]["%input%"][(int)$fd] = array($this,"p");
	}

	function canAuth($username,$pass) {
		global $mods;
		$pass = $mods["userlist"]->db["users"][$username]["pass"];
		$mods["userlist"]->save();
	}

	function p ($fd,$data) {
	$p = protocolParse($data);
	switch ($p[0]) {
		case "IDENTIFY"
	}
	}
}
