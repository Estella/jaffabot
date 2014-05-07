sv_register:: loaded.
<?php

// This file is part of Jaffabot; see LICENSE.md in the root of the project for more info.

class sv_help {
	function __construct($duud,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$cli = $args[0];
		$this->cli = $args[0];
		$this->nonickreg = parseConf("sv_ns","no")[0][1];
		$this->vhostsuff = parseConf("sv_ns","no")[0][2];
		$this->dbconn = $mods["%sv_database%"] ;
		regPrivmsgCallback($this,"cmd_help",$cli,"help");
		regEvent($this,"on_signon","SIGNON");
		if ($this->nonickreg[0] == "y")
			regEvent($this,"on_nickchg","NICK");
	}

	function cmd_help($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		switch (strtolower(trim($s))) {
			case "":
				$helpfile = explode("\n",file_get_contents("./helpmain.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
			case "login":
				$helpfile = explode("\n",file_get_contents("./helplog.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
			case "register":
				$helpfile = explode("\n",file_get_contents("./helpreg.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
			case "chanserv":
				$helpfile = explode("\n",file_get_contents("./helpcs.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
			case "chanserv register":
				$helpfile = explode("\n",file_get_contents("./helpcsreg.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
			case "chanserv acsadd":
				$helpfile = explode("\n",file_get_contents("./helpcsacsadd.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
			case "chanserv acsdel":
				$helpfile = explode("\n",file_get_contents("./helpcsacsdel.hlp"));
				foreach ($helpfile as $helpline) {
					$protofunc->send_notice($this->cli,$f,$helpline);
				}
				break;
		}
		if ($this->nonickreg[0] != "y") $protofunc->send_notice($this->cli,$f,"--===[ End of help ]===--");
	}
}
