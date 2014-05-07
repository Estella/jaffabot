sv_register:: loaded.
<?php

// This file is part of Jaffabot; see LICENSE.md in the root of the project for more info.

class sv_register {
	function __construct($duud,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$cli = $args[0];
		$this->cli = $args[0];
		$this->nonickreg = parseConf("sv_ns","no")[0][1];
		$this->vhostsuff = parseConf("sv_ns","no")[0][2];
		$svdb = parseConf("sv_db","no");
		$mods["%sv_database%"] = pg_connect($svdb[0][1]);
		$this->dbconn = $mods["%sv_database%"] ;
		regPrivmsgCallback($this,"cmd_register",$cli,"register");
		regPrivmsgCallback($this,"cmd_drop",$cli,"drop");
		regPrivmsgCallback($this,"cmd_login",$cli,"login");
		if ($this->nonickreg[0] == "y") {
			regPrivmsgCallback($this,"cmd_ghost",$cli,"ghost");
			regPrivmsgCallback($this,"cmd_nslogin",$cli,"identify");
		}
		regEvent($this,"on_signon","SIGNON");
		if ($this->nonickreg[0] == "y")
			regEvent($this,"on_nickchg","NICK");
	}

	function login_user ($nick,$username) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$protofunc->sts_login($nick,$username,$username.".".$this->vhostsuff);
	}

	function on_signon($arr) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$nick = $arr["nick"];
		if ($this->isReg($nick) and (strtolower($protofunc->accnt[$nick]) == strtolower($nick))) return;
		if ($this->nonickreg[0] == "y") {
			if ($this->isReg($nick)) {
				$protofunc->send_notice($this->cli,$nick,"SV Nick Service *** Hi. This is your nickname service speaking.");
				$protofunc->send_notice($this->cli,$nick,"                *** I've caught wind of allegations that the nick you're currently sitting on is");
				$protofunc->send_notice($this->cli,$nick,"                *** owned by someone else. Please log in to the correct account.");
				$protofunc->send_notice($this->cli,$nick,"        Warning *** The user who owns this nick may forcibly change your nickname.");
			}
		}
	}

	function isRightPass($username,$pass) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$checkeq = hash("sha512",hash("sha512",$pass));
		$uobj = pg_fetch_object(pg_query($this->dbconn,"SELECT password FROM usernames WHERE lower(name) = '".pg_escape_string($this->dbconn,strtolower($username))."'"));
		if ($checkeq = $uobj->password) return true;
		return false;
	}

	function register_nick($username,$pass) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$checkeq = hash("sha512",hash("sha512",$pass));
		pg_query($this->dbconn,"INSERT INTO usernames VALUES ('".pg_escape_string($this->dbconn,strtolower($username))."','".pg_escape_string($this->dbconn,$checkeq)."')");
	}

	function isReg($nick) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$uobj = pg_fetch_object(pg_query($this->dbconn,"SELECT name FROM usernames WHERE lower(name) = '".pg_escape_string($this->dbconn,strtolower($nick))."'"));
		if ($uobj->name == strtolower($nick)) return true;
		return false;
	}

	function isIdentified($unick,$regnick) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($protofunc->accnt[$unick] == $regnick) return true;
		return false;
	}

	function cmd_register($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!(isPrivate($d))) return;
		if ($protofunc->accnt[$f] != "") {
			$protofunc->send_notice($this->cli,$f,"Command error: You are already logged into SV.");
			return;
		}
		if (!($this->dbconn)) {
			$protofunc->send_notice($this->cli,$f,"Command error: SV cannot access the DataBase at this time; ask your netadmin to restart Jaffabot.");
			return;
		}
		$shit = explode(" ",$s);
		$pass = ($this->nonickreg[0] != "y") ? $shit[1] : $shit[0];
		$uname = ($this->nonickreg[0] != "y") ? $shit[0] : $f;
		if ($this->isReg($uname)) {
			$protofunc->send_notice($this->cli,$f,"What the fuck a duck? That ".($this->nonickreg[0] != "y")?"account":"nickname"." is already registered.");
			return;
		}
		if (!isset($shit[0]) or (trim($shit[0]) == "")) {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing arguments entirely.");
			if ($this->nonickreg[0] != "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: REGISTER <accountname> <password>");
			if ($this->nonickreg[0] == "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: REGISTER <password>");
			return;
		}
		if ((($this->nonickreg[0] != "y") ? $shit[1] : $shit[0]) == "") {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing password.");
			if ($this->nonickreg[0] != "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: REGISTER <accountname> <password>");
			if ($this->nonickreg[0] == "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: REGISTER <password>");
			return;
		}
		if ($this->nonickreg[0] != "y") $protofunc->send_notice($this->cli,$f,"REGISTER Your account has been registered.");
		if ($this->nonickreg[0] == "y") $protofunc->send_notice($this->cli,$f,"REGISTER Your nick has been registered.");
		$this->register_nick($uname,$pass);
		$this->login_user($from,$uname);
	}
	function cmd_login($from,$dest,$msg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!(isPrivate($dest))) return;
		if ($protofunc->accnt[$from] != "") {
			$protofunc->send_notice($this->cli,$from,"Command error: You are already logged into SV.");
			return;
		}
		if (!($this->dbconn)) {
			$protofunc->send_notice($this->cli,$from,"Command error: SV cannot access the DataBase at this time; ask your netadmin to restart Jaffabot.");
			return;
		}
		$f = $from;
		$shit = explode(" ",$msg);
		$pass = ($this->nonickreg[0] != "y") ? $shit[1] : $shit[0];
		$uname = ($this->nonickreg[0] != "y") ? $shit[0] : $from;
		if (count($shit) == 2) $uname = $shit[0];
		if (count($shit) == 2) $pass = $shit[1];
		if ((($this->nonickreg[0] != "y") ? $shit[1] : $shit[0]) == "") {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing password.");
			if ($this->nonickreg[0] != "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: LOGIN <accountname> <password>");
			if ($this->nonickreg[0] == "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: LOGIN <password>");
			return;
		}
		if (!($shit[0])) {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing arguments entirely.");
			if ($this->nonickreg[0] != "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: LOGIN <accountname> <password>");
			if ($this->nonickreg[0] == "y") $protofunc->send_notice($this->cli,$f,"SYNTAX: LOGIN <password>");
			return;
		}
		if ($this->isRightPass($uname,$pass)) {
			$protofunc->send_notice($this->cli,$f,"What the fuck a duck? Someone got their ".($this->nonickreg[0] != "y")?"account":"nickname"."'s password right for once! You are now logged in.");
			$this->login_user($from,$uname);
			return;
		} else {
			$protofunc->send_notice($this->cli,$f,"Wrong password for this username or username not registered.");
		}
	}
}
