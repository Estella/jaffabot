<?php

class popup {
	function __construct($duck2fuck,$args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->cli = $args[0];
		$protofunc->sendUid(0,$args[0],$args[1],$args[2],$args[2],$args[3],$args[4]);
		regPrivmsgCallback($this,"cmd_show",$args[0],"show");
		regPrivmsgCallback($this,"cmd_showmrc",$args[0],"showmrc");
		regPrivmsgCallback($this,"cmd_figlet",$args[0],"figlet");
	}
	function cmd_show($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$ch = explode(" ",$s);
		if (!$protofunc->opmodes[$f][strtolower($ch[0])])
		{
			$protofunc->send_privmsg($this->cli,$ch[0],"was called but not allowed to answer");
			return; // Dafiq is the user doing trying to set up a show without status +qaohv? DAFIQ
		}
		$protofunc->mode($this->cli,$ch[0]." +m");
		$helpfile = explode("\n",file_get_contents("/srv/ftp/".$ch[1]));
		$protofunc->sts_join($this->cli,$ch[0]);
		foreach ($helpfile as $helpline) {
			$protofunc->send_privmsg($this->cli,$ch[0],$helpline);
		}
		$protofunc->sts_part($this->cli,$ch[0],"I have done my duty.");
		$protofunc->mode($this->cli,$ch[0]." -m");
	}
	function mrchelper($s,&$args,$ch) {
		$helpfile = explode("\n",file_get_contents($s.".spec"));
		foreach ($helpfile as $helpline) {
			if ($helpline == "") continue;
			echo $helpline.PHP_EOL;
			$help = explode(":",$helpline);
			if (strtolower($help[0]) != strtolower($ch[2])) continue;
			if ($help[2][0] == "%") {
				$shat = substr($help[2],1);
				$reppairs[$help[1]] = str_repeat(" ",strlen( strtr($args[substr($help[2],1)-1],"="," ") ));
			} else if ($help[2][0] == "&") {
				$firstspace = round((($help[3] - strlen($args[substr($help[2],1)-1])) / 2),0,PHP_ROUND_HALF_UP);
				$secspace = round((($help[3] - strlen($args[substr($help[2],1)-1])) / 2),0,PHP_ROUND_HALF_DOWN);
				$reppairs[$help[1]] = str_repeat(" ",$firstspace).strtr($args[substr($help[2],1)-1],"="," ").str_repeat(" ",$secspace);
			} else if ($help[2][0] == "!") {
				$shit = explode("$",$help[2]);
				$shat = $shit[0];
				$reppairs[$help[1]] = strtr($args[substr($help[2],1)-1],"="," ")[$shit[1]];
				if ($reppairs[$help[1]] == "") $help[3];
			}
		}
		return $reppairs;
	}
	function cmd_showmrc($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$ch = explode(" ",$s);
		if (!$protofunc->opmodes[$f][strtolower($ch[0])]) return; // Dafiq is the user doing trying to set up a show without status +qaohv? DAFIQ
		$args = array_slice($ch,3);
		$reppairs = $this->mrchelper("/srv/ftp/".$ch[1],$args,$ch);
		$helpfile = explode("\n\n",file_get_contents("/srv/ftp/".$ch[1]));
		$protofunc->mode($this->cli,$ch[0]." +m");
		$protofunc->sts_join($this->cli,$ch[0]);
		$keyarr = array_keys($helpfile,"");
		foreach ($helpfile as $helpline) {
			$lines = explode("\n",$helpline,2);
			if (strtolower($lines[0]) != strtolower("[".$ch[2]."]")) continue;
			$lines = explode("\n",$lines[1]);
			foreach ($lines as $line) {
				$line = str_replace("/say ","",$line);
				if ($reppairs) $protofunc->send_privmsg($this->cli,$ch[0],strtr($line,$reppairs));
				else $protofunc->send_privmsg($this->cli,$ch[0],$line);
			}
		}
		$protofunc->sts_part($this->cli,$ch[0],"I have done my duty.");
		$protofunc->mode($this->cli,$ch[0]." -m");
	}
	function cmd_figlet($f,$d,$s) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$ch = explode(" ",$s,3);
		if (count($ch) < 3) {
			$helpfile = array(
			"Command error: You did not specify a text.",
			"SYNTAX: FIGLET #channel (<toiletfilterlist>|none)[::font] <text>",
			);
			foreach ($helpfile as $helpline) {
				$protofunc->send_privmsg($this->cli,$ch[0],$helpline);
			}
			return;
		}
		$font = explode("::",$ch[1],2);
		if (!$protofunc->opmodes[$f][strtolower($ch[0])]) return; // Dafiq is the user doing trying to set up a show without status +qaohv? DAFIQ
		$mystring = sprintf("/usr/bin/env toilet%s%s%s%s --irc -- %s",($font[0] == "none")?" ":" -F",($font[0] == "none")?"":escapeshellarg($font[0]),($font[1])?" -f":"",($font[1])?escapeshellarg($font[1]):"",escapeshellarg($ch[2]));
		$myshit = `$mystring`;
		$helpfile = explode("\n",$myshit);
		$protofunc->mode($this->cli,$ch[0]." +m");
		$protofunc->sts_join($this->cli,$ch[0]);
		foreach ($helpfile as $helpline) {
			$protofunc->send_privmsg($this->cli,$ch[0],$helpline);
		}
		$protofunc->sts_part($this->cli,$ch[0],"I have done my duty.");
		$protofunc->mode($this->cli,$ch[0]." -m");
	}
}
