<?php

session_start();

header("Content-type: application/json");

$jsondb='tmp/sliders.json';
$controller_json=@json_decode(@file_get_contents($jsondb), true);
switch ($_GET['action']) {
	case "list" :
		echo json_encode($controller_json);
		break;
	case "set_sliders" :
		$controller_json[md5($_GET['url'])]=$_GET['url'];
		file_put_contents($jsondb, json_encode($controller_json));
		mkdir('tmp/'.md5($_GET['url']).'/');
		echo json_encode(array("status"=>"ok"));
		break;
	case "remove_sliders" :
		unset($controller_json[$_GET['id']]);
		file_put_contents($jsondb, json_encode($controller_json));
		echo json_encode(array("status"=>"ok"));
		break;
	case "slide_command" :
		$command_json=array('status'=>'ok', 'command'=>$_GET['command']);
		foreach (new DirectoryIterator('tmp/'.$_GET['id'].'/') as $fileInfo) {
			if($fileInfo->isDot() || $fileInfo->isDir()) continue;
			file_put_contents('tmp/'.$_GET['id'].'/'.$fileInfo->getFilename(), json_encode($command_json));
		}
		echo json_encode(array("status"=>"ok"));
		break;
	default:
		echo json_encode(array("status"=>"error","command"=>"no command given"));
	}
?>
