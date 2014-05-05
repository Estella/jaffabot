sv_register:: loaded.
<?php

// This file is part of Jaffabot; see LICENSE.md in the root of the project for more info.

class sv_register {
	function __construct($duud,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$cli = $args[0];
		$this->cli = $args[0];
		$this->nonickreg = parseConf("sv_ns","no")[0][1];
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

	function on_signon($arr) {
		$nick = $arr["nick"];
		if ($this->nonickreg[0] == "y") {
			if ($this->isReg($nick)) {
				$protofunc->send_notice($this->cli,$nick,"SV Nick Service *** Hi. This is your nickname service speaking.");
				$protofunc->send_notice($this->cli,$nick,"                *** I've caught wind of allegations that the nick you're currently sitting on is");
				$protofunc->send_notice($this->cli,$nick,"                *** owned by someone else. Please log in to the correct account.");
				$protofunc->send_notice($this->cli,$nick,"        Warning *** The user who owns this nick may forcibly change your nickname.");
			}
		} else {
			if ($this->isReg($nick)) {
				$protofunc->send_notice($this->cli,$nick,"Reminder: You are using a nickname that is the same as someone else's");
				$protofunc->send_notice($this->cli,$nick,"          registered username.");
				$protofunc->send_notice($this->cli,$nick,"        \x02\x02  ");
				$protofunc->send_notice($this->cli,$nick,"          As a matter of courtesy, you should change your nickname");
				$protofunc->send_notice($this->cli,$nick,"          unless you know that the person who uses this username");
				$protofunc->send_notice($this->cli,$nick,"          does not use this nick.");
			}
		}
	}

	function isRightPass($username,$pass) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$checkeq = hash("sha512",hash("sha512",$pass));
		$uobj = pg_fetch_object(pg_query_params($this->dbconn,"SELECT password FROM usernames WHERE lower(username) = $1",array(strtolower($username))));
		if ($checkeq = $uobj->password) return true;
		return false;
	}

	function register_nick($username,$pass) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$checkeq = hash("sha512",hash("sha512",$pass));
		pg_query_params($this->dbconn,"INSERT INTO usernames ( $1, $2 )",array(strtolower($username),$checkeq));
	}

	function isReg($nick) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$uobj = pg_fetch_object(pg_query_params($this->dbconn,"SELECT username FROM usernames WHERE lower(username) = $1",array(strtolower($nick))));
		if ($uobj->username == strtolower($nick)) return true;
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
		$hashpass = hash("sha512",hash("sha512",($this->nonickreg[0] == "y") ? $shit[1] : $shit[0]));
		$uname = ($this->nonickreg[0] == "y") ? $shit[0] : f;
		if ((($this->nonickreg[0] != "y") ? $shit[1] : $shit[0]) == "") {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing password.");
			$protofunc->send_notice($this->cli,$f,"SYNTAX: REGISTER".($this->nonickreg[0] != "y")?" <accountname>":" "."<password>");
			return;
		}
		if (($this->nonickreg[0] != "y") and !($shit[0])) {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing arguments entirely.");
			$protofunc->send_notice($this->cli,$f,"SYNTAX: REGISTER".($this->nonickreg[0] != "y")?" <accountname>":" "."<password>");
			return;
		}
	}
	function cmd_login($from,$dest,$msg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if (!(isPrivate($dest))) return;
		if ($protofunc->accnt[$from] != "") {
			$protofunc->send_notice($this->cli,$f,"Command error: You are already logged into SV.");
			return;
		}
		if (!($this->dbconn)) {
			$protofunc->send_notice($this->cli,$f,"Command error: SV cannot access the DataBase at this time; ask your netadmin to restart Jaffabot.");
			return;
		}
		if ((($this->nonickreg[0] == "y") ? $shit[1] : $shit[0]) == "") {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing password.");
			$protofunc->send_notice($this->cli,$f,"SYNTAX: LOGIN".($this->nonickreg[0] != "y")?" <accountname>":" "."<password>");
			return;
		}
		if (($this->nonickreg[0] == "y") and !($shit[0])) {
			$protofunc->send_notice($this->cli,$f,"Your command syntax must have fucked a duck; missing arguments entirely.");
			$protofunc->send_notice($this->cli,$f,"SYNTAX: LOGIN".($this->nonickreg[0] != "y")?" <accountname>":" "."<password>");
			return;
		}
	}
}
