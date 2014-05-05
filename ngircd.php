<?php

global $privcalls;
$privcalls = array();
global $debug;
$debug = true; if ($debug) {
                   fwrite(STDOUT,"! !WARNING! ! You are running the TS6 server protocol module in DEBUG mode. This means all input and output to the bot is logged to STDOUT.\r\n");
                   fwrite(STDOUT,"! !WARNING! ! THIS INCLUDES BOT PASSWORDS! If you do not want to see the passwords people register with your bot with, please C-c\r\n");
                   fwrite(STDOUT,"! !WARNING! ! and edit chts6.php to change \$debug = true; to \$debug = false;.\r\n");
}


function regPrivmsgCallback($object,$func,$client,$cmd){
	global $privcalls;
	// For client PRIVMSG callbacks all dests are the same
	$privcalls[$client][$cmd][] = array($object,$func);
}

class protocol{
	function ignore(){
		return;
	}
	function protocol_start($args){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($opMode != "serv") die("Ehm... aren't you supposed to be using ircv3c.php?");
		$confItems["SID"] = $args[0];
		regCallback($this,"protocol_privmsg","PRIVMSG");
		regCallback($this,"protocol_privmsg","SQUERY");
		regCallback($this,"irc_join","JOIN");
		regCallback($this,"irc_mode","MODE");
		regCallback($this,"irc_njoin","NJOIN");
		regCallback($this,"irc_part","PART");
		regCallback($this,"irc_nick","NICK");
		regCallback($this,"irc_server","SERVER");
		regCallback($this,"irc_ping","PING");
		regCallback($this,"irc_capab","PASS");
		regCallback($this,"irc_metadata","METADATA");
		$this->sw(sprintf("PASS %s 0210-IRC+ jaffabot|0.1:CLMSXo",$args[1]));
		$this->sw(sprintf("SERVER %s 1 :%s",$args[0],$args[2]));
	}

	function sw($mesg) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		fwrite($socket,$mesg."\r\n");
		if ($debug) fwrite(STDOUT,"Output ".$mesg."\n");
	}

	function irc_capab($get) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$payload = array_slice($get,1);
		foreach ($payload as $token) {
				$this->caps[$token] = true;
			}
		}
	}

	function sendUid($sender,$nick,$ident,$host,$dhost,$uid,$realname="* Unknown *"){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->cliID[$uid] = $nick;
		$this->sw(sprintf(":%s NICK %s 1 %s %s 2 +ioRB :%s",$confItems["SID"], $nick, $ident, $host, $realname));
		$this->sw(sprintf(":%s METADATA %s cloakhost :%s",$confItems["SID"], $nick, $dhost));
		if ($debug) $this->sw(sprintf(":%s WALLOPS :Fake user %s introduced. A module in this program probably caused this.",$confItems["SID"], $nick));
		return true; //user introduced
	}

	function isop($nick,$channel) {
		return (strpos($this->opmodes[$cmd[$nick]][$channel],"o") === false)?false:true;
	}

	function issop($nick,$channel) {
		return (strpos($this->opmodes[$cmd[$nick]][$channel],"a") === false)?(strpos($this->opmodes[$cmd[$nick]][$channel],"q") === false)?false:true;
	}

	function irc_metadata($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;

		switch ($args[2]) {
			case "accountname":
				if ($args["payload"] == "") {
					callEvents(array("cmd"=>"ACCOUNT","nick"=>$args[1],"action"=>"O"));
					break;
				}
				// This should ONLY ever happen because of a NetSplit that rejoins.
				// Anyone ever heard of "staying logged in thru a netsplit"?
				// It's probably kosher; no one should be running two sets of services on a network *sigh*. Update the arrays and let everyone know
				$this->accnt[$args[1]] = $args["payload"];
				callEvents(array("cmd"=>"ACCOUNT","nick"=>$args[1],"action"=>"I","acct"=>$args["payload"]));
				break;
			case "cloakhost":
				// This happens for anyone connecting or changing mode to +x
				$this->vhost[$args[1]] = $args["payload"];
				callEvents(array("cmd"=>"FAKEHOST","nick"=>$args[1],"hostname"=>$args["payload"]));
				break;
		}
	}

	function irc_nick($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;

		if (count($args) > 5) { /* Got the right number of arguments for a user introduction? */
			$this->ident[$args[1]] = $args[4];
			$this->rhost[$args[1]] = $args[5];
			$this->gecos[$args[1]] = $args["payload"];
			callEvents(array("cmd"=>"SIGNON","ident"=>$args[4],"realhost"=>$args[5],"nick"=>$args[1]));
		} else { /* This guy is changing his nickname; update the arrays. */
			$this->ident[$args[1]] = $this->ident[$args["sendernick"]];
			unset($this->ident[$args["sendernick"]]);

			$this->rhost[$args[1]] = $this->rhost[$args["sendernick"]];
			unset($this->rhost[$args["sendernick"]]);

			$this->gecos[$args[1]] = $this->gecos[$args["sendernick"]];
			unset($this->gecos[$args["sendernick"]]);

			$this->vhost[$args[1]] = $this->vhost[$args["sendernick"]];
			unset($this->vhost[$args["sendernick"]]);

			$this->accnt[$args[1]] = $this->accnt[$args["sendernick"]];
			unset($this->accnt[$args["sendernick"]]);

			// Tell everyone that this guy is now parking on a new nickname.
			// This is used by NSV and NickServ to name a few official modules.
			callEvents(array("cmd"=>"NICK","from"=>$args["sendernick"],"to"=>$args[1]));
		}
	}

	function sts_login($nick,$Â$accountname="",$vhost=""){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s METADATA %s accountname :%s",$confItems["SID"], $nick,$accountname));
		if ($vhost != "") $this->sw(sprintf(":%s METADATA %s cloakhost :%s",$confItems["SID"], $nick,$vhost));
		return true; //user logged in and vhosted
	}

	function sts_join($client,$channel) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s JOIN %s\x07ao",$this->cliID[$client],$channel));
	}

	function sts_part($client,$channel, $reason) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf(":%s PART %s :%s",$this->cliID[$client],$channel,$reason));
	}

	function parse($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($debug) fwrite(STDOUT,"Input  ".$get."\n");
		if ($get[0] == ":") $hasSrc = true;
		else $hasSrc = false;
		$msg = ($hasSrc) ? array_slice(explode(":",$get,3),1) : explode(":",$get,2);
		$comd = $msg[0];
		$payload = $msg[1];
		$cmd = explode(" ", $comd);
		$cmd["sendernick"] = $cmd[0]; // Useful! :P That is, if the sender is not a server. :P
		$cmd["cmd"] = $cmd[1];
		if ($payload != "") $cmd["payload"] = $payload;
		return $cmd;
	}

	function protocol_privmsg($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		// This parses PRIVMSGs. This should really be in the core... ugh
		$cmd = explode(" ",$get["payload"],2);
		foreach ($privcalls[array_search($get[1],$this->cliID)][$cmd[0]] as $callback) {
			call_user_func($callback,$get["sendernick"],$get[($cmd["hassource"])?2:1],$cmd[1]);
		}
	}

	function irc_ping($get){
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$this->sw(sprintf("PONG :%s",$get["payload"]));
	}

	function send_msg($type,$from,$to,$message) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		if ($type[0] == "p") $this->sw(sprintf(":%s PRIVMSG %s :%s", $this->enc($from),$to,$message));
		if ($type[0] == "P") $this->sw(sprintf(":%s PRIVMSG %s :%s", $this->enc($from),$to,$message));
		if ($type[0] == "n") $this->sw(sprintf(":%s NOTICE %s :%s", $this->enc($from),$to,$message));
		if ($type[0] == "N") $this->sw(sprintf(":%s NOTICE %s :%s", $this->enc($from),$to,$message));
	}

	function irc_join($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$chans = explode(",",$args[2]);
		foreach ($chans as $chandef) {
			$chandef = explode("\x07",$chandef);
			$chan = $chandef[0];
			$ops = $chandef[1];
			$this->inChans[$cmd["sendernick"]][$chan] = true;
			$this->opmodes[$cmd["sendernick"]][$chan] = $ops;
		}
	}

	function irc_njoin($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$sjnicks = explode(" ",$cmd["payload"]);
		foreach ($sjnicks as $nick) {
			for (i=strlen($nick);i>0;i--) {
				if (!(($nick[$i] == "~") or ($nick[$i] == "&")
					or ($nick[$i] == "@") or ($nick[$i] == "%") or ($nick[$i] == "+"))) continue;
			}
			$modestring = strtr("~&@%+","qaohv",substr($nick,0,0-$nickpos));
			$this->inChans[$cmd["sendernick"]][$get[1]] = true;
			if ($modestring) $this->opped[$cmd["sendernick"]][$uid] = $modestring;
		}
	}

	function irc_part($args) {
		global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug;
		$chans = explode(",",$args[2]);
		foreach ($chans as $chandef) {
			unset($this->inChans[$cmd["sendernick"]][$chan]);
			unset($this->opmodes[$cmd["sendernick"]][$chan]);
		}
	}

	function send_notice($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("Notice",$from,$to,$message); }
	function send_privmsg($from,$to,$message) { global $confItems, $file, $opMode, $Mline, $protofunc, $mods, $callbacks, $socket, $privcalls, $debug; $this->send_msg("PRIVMSG",$from,$to,$message); }
	function enc($uid) {
		if ($uid=="-Server-") return $Mline[0][1];
		return $this->cliID[$uid];
	}
}
