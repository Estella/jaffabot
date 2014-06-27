Userlist module loaded
<?php

class userlist {
	function __construct($me,$args) {
		$this->dbfile = $args[0];
		$this->db = json_decode(file_get_contents($args[0]),true);
		regEvent($this,"save","PRIVMSG");
		return;
	}
	function save($args) {
		file_put_contents($this->dbfile,json_encode($this->db));
	}
}
