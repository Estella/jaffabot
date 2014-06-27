<?php

class vhostserv{
	function __construct($duck2fuck,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$protofunc->sendUid(0,$args[0],$args[1],$args[2],$args[2],$args[3],$args[4]);
		$cli = $args[3];
		var_dump($this->goodmodes);
		$this->cli = $args[3];
		//regPrivmsgCallback($this,"cmd_request",$cli,"request");
		regPrivmsgCallback($this,"cmd_vhost",$cli,"vhost");
		regPrivmsgCallback($this,"cmd_dohost",$cli,"dohost");
		regPrivmsgCallback($this,"cmd_addadmin",$cli,"addadmin");
		regEvent($this,"do_vhost","ACCOUNT");
	}

	function cmd_vhost($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!isPrivate($d)) return;
		if (!$mods["userlist"]->db["hsadmin"][strtolower($protofunc->accnt[$f])]) return; //f them :)
		$protofunc->send_notice($d,$f,"Ok, trying to set vhost into userlist");
		$mods["userlist"]->db["hsvhost"][explode(" ",strtolower($s))[0]] = explode(" ",$s)[1];
	}

	function cmd_dohost($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!isPrivate($d)) return;
		$this->do_vhost(array("nick"=>array_search(strtolower(explode(" ",$s)[0]), $protofunc->accnt),"acct"=>explode(" ",$s)[0]));
	}

	function cmd_addadmin($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!isPrivate($d)) return;
		if (!$mods["userlist"]->db["hsadmin"][strtolower($protofunc->accnt[$f])] and $mods["userlist"]->db["hsadmin"]) return;
		$protofunc->send_notice($d,$f,"Ok, trying to set vhostserv admin into userlist");
		$mods["userlist"]->db["hsadmin"][strtolower(trim($s))] = true;
	}

	function do_vhost($a) {
		global $mods, $protofunc;
		$protofunc->sts_sh($a["nick"],$mods["userlist"]->db["hsvhost"][$a["acct"]]);
	}
}
