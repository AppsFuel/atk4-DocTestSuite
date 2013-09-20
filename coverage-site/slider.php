<!DOCTYPE html>
<html>
<head>
<title>Slider casts manager</title>
<!-- Bootstrap -->
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
</head>
<body>
<center>
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script>
function  sendcommand(command)
{
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","slider_controller.php?action=slide_command&id=<?echo $_GET['id']?>&command="+command, false);
	xmlhttp.send();
	var obj = JSON.parse(xmlhttp.responseText);
	console.debug(obj);
}
</script><?
$controller_url="http://coverage2.appsfuel.com/slider_controller.php?";
$slide_list = json_decode(file_get_contents($controller_url."action=list"),true);

if ($_GET['id']) {
	?>
	<h1>Slide url: <?echo $slide_list[$_GET['id']];?></h1><br><br><iframe width=600 height=400 src="<?echo $slide_list[$_GET['id']];?>"></iframe><br>
	<button class="btn btn-primary btn-large" type="button" onclick="sendcommand('left');"><h1>Left</h1></button> 
	<button class="btn btn-primary btn-large" type="button" onclick="sendcommand('right');"><h1>Right</h1></button> 
	<?
}
else
{
	?><h1>Current slider cast</h1>
<table class="table table-striped table-bordered table-condensed">
    <thead>
        <tr>
            <th>#</th>
            <th>Url</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
<?
	foreach ($slide_list as $slideid=>$url) {?>
        <tr>
            <td><?echo $slideid?></td>
            <td><?echo $url?></td>
            <td><?print '<a class="btn btn-primary btn-large" href="slider.php?id='.$slideid.'">Control</a>';?>
            <?print '<a class="btn btn-primary btn-large" onclick="alert(\'Sicuro?\')" href="#">Delete</a>';?></td>
        </tr><?
	}?>
    </tbody>
</table>
<button class="btn" type="button" onclick="sendcommand('right');"><h1>Add slider</h1></button> 
<?
}
?>
</body>
</html>
