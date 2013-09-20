<?php

session_start();

header("Content-type: application/json");

switch ($_GET['action']) {
case "register" :
	@touch('tmp/'.$_GET['id'].'/'.session_id());
	echo json_encode(array("status"=>"ok"));
	break;
case "unregister" :
	@unlink('tmp/'.$_GET['id'].'/'.session_id());
	echo json_encode(array("status"=>"ok"));
	break;
case "get_command" :
	$command_file='tmp/'.$_GET['id'].'/'.session_id();
	if (file_exists($command_file)) {
		if (filesize($command_file)>0) {
			echo file_get_contents($command_file);
			file_put_contents($command_file,"");
		} else {
			echo json_encode(array("status"=>"ok","command"=>""));
		}
	}
	else {
		echo json_encode(array("status"=>"error","command"=>"client not registered"));
	}
	break;
default:
	echo json_encode(array("status"=>"error","command"=>"no command given"));
}
?>
