<?php

class sv_operserv_main {

	function __construct($duck2fuck,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->admin = $args[0];
		$cli = $args[1];
		$this->cli = $args[1];
		$protofunc->sendUid(0,$args[1],$args[2],$args[3],$args[3],$args[4],$args[5]);
		$this->dbconn = $mods["%sv_database%"] ;
		regPrivmsgCallback($this,"cmd_opmode",$cli,"opmode");
		regPrivmsgCallback($this,"cmd_kill",$cli,"kill");
		regEvent($this,"on_signon","SIGNON");
	}

	function cmd_opmode($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!(isPrivate($d))) return;
		if ($protofunc->accnt[$f] != $this->admin) {
			$protofunc->send_notice($this->cli,$f,"Command error: You are not the admin.");
			return;
		}
		$protofunc->mode($mods["sv_register"]->cli,$s);
	}
	function cmd_kill($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!(isPrivate($d))) return;
		if ($protofunc->accnt[$f] != $this->admin) {
			$protofunc->send_notice($this->cli,$f,"Command error: You are not the admin.");
			return;
		}
		$protofunc->kill($mods["sv_register"]->cli,$s);
	}
}
