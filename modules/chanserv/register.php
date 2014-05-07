sv_register:: loaded.
<?php

// This file is part of Jaffabot; see LICENSE.md in the root of the project for more info.

class chanserv_register {
	function __construct($duud,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$cli = $args[0];
		$this->cli = $args[0];
		$this->dbconn = $mods["%sv_database%"] ;
		regPrivmsgCallback($this,"cmd_register",$cli,"register");
		regPrivmsgCallback($this,"cmd_up",$cli,"op");
		regPrivmsgCallback($this,"cmd_up",$cli,"up");
		regEvent($this,"do_up","JOIN");
	}

	function do_up($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		var_dump($args);
		$opGuy = $this->isOp($protofunc->accnt[$args["from"]],$args["chan"]);
		$modestring = $args["chan"];
		$modestring .= " +";
		$modestring .= $opGuy;
		$modestring .= " ";
		$modestring .= str_repeat($args["from"]." ",strlen($opGuy));
		$protofunc->mode($this->cli,$modestring);
	}

	function isOp($username,$chan) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$uobj = pg_fetch_array(pg_query($this->dbconn,"SELECT flags FROM channels WHERE lower(name) = '"
			.pg_escape_string($this->dbconn,strtolower($chan))."' and lower(nick) ='".pg_escape_string($this->dbconn,strtolower($username))."'"));
		if ($uobj["flags"]) return $uobj["flags"];
		return false;
	}

	function isReg($username) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$uobj = pg_fetch_object(pg_query($this->dbconn,"SELECT name FROM channels WHERE lower(name) = '".pg_escape_string($this->dbconn,strtolower($username))."'"));
		if ($username = $uobj->name) return true;
		return false;
	}

	function register_chan($username,$chan,$flags) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		pg_query($this->dbconn,"INSERT INTO channels VALUES ('"
			.pg_escape_string($this->dbconn,strtolower($chan))."','".pg_escape_string($this->dbconn,strtolower($username))."','"
			.pg_escape_string($this->dbconn,strtolower($flags))."')");
	}

	function cmd_register($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!(isPrivate($d))) return;
		if ($protofunc->accnt[$f] == "") {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL Command error: Please log in.");
			return;
		}
		if (!($this->dbconn)) {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL Command error: SV cannot access the DataBase at this time; ask your netadmin to restart Jaffabot.");
			return;
		}
		$shit = explode(" ",$s);
		$uname = $shit[0];
		if ($this->isReg($uname) and ($shit[1] == "")) {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL What the fuck a duck? Missing usernick");
			return;
		}
		if (!$this->isReg($uname) and ($shit[1] != "")) {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL What the fuck a duck? Pretty please register the channel to YOURSELF first.");
			return;
		}
		if ($this->isReg($uname) and ($shit[2] == "")) {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL What the fuck a duck? Missing user acslevel");
			return;
		}
		if ((strpos($shit[2],"q")) and !(strpos($this->isOp($protofunc->accnt[$f],$uname),"q"))) {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL What the fuck a duck? You cannot add a user at a higher access level than your own");
			return;
		}
		if ($this->isReg($uname) and (strpos($this->isOp($protofunc->accnt[$f],$uname),"a") === false)) {
			$protofunc->send_notice($this->cli,$f,"REGISTER FAIL What the fuck a duck? That channel is already registered.");
			return;
		}
		$protofunc->send_notice($this->cli,$f,"REGISTER SUCCEED Your channel has been registered or chanop added.");
		$this->register_chan(($this->isReg($uname))?$shit[1]:$protofunc->accnt[$f],$uname,($this->isReg($uname))?$shit[2]:"qao");
		$this->do_up(array("from"=>$f,"chan"=>$uname));
	}

	function cmd_up($f,$d,$s) {
		$this->do_up(array("from"=>$f,"chan"=>trim(explode(" ",$s)[0])));
	}
}
