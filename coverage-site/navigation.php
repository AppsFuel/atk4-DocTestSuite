<?php
$starttime=microtime(true) * 1000;

?>
<HTML><HEAD><TITLE>Appsfuel coverage
<?
$conntime=microtime(true)*1000;
include "library_site.php";

$version ="Dew-Code's PHP Source Code Viewer 1.2 ";
if ($_GET["filename"]){echo $_GET["filename"];} 
?>
</TITLE>
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Bootstrap -->
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
<style type="text/css">
.code_cell {padding: 12x;}
ol  {padding: -18px;}
ol li {padding: 8px;}
.nav_cell {padding: 12px; }
.anav {text-align: right; }
.heading {background: #000000; padding: 2px; text-align: center; color: White; font-weight: bold; font-size: smaller;}
a {padding: 2px;}
</style>
<script src="http://code.jquery.com/jquery.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="http://www.datatables.net/release-datatables/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf-8">
			/* Default class modification */
			$.extend( $.fn.dataTableExt.oStdClasses, {
				"sSortAsc": "header headerSortDown",
				"sSortDesc": "header headerSortUp",
				"sSortable": "header"
			} );

			/* Table initialisation */
			$(document).ready(function() {
				$('#dataTable').dataTable( {
					"sDom": "<'row-fluid'<'span8'l><'span8'f>r>t<'row'<'span8'i><'span8'p>>",
					"iDisplayLength": -1,
    					"aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
					"fnFooterCallback": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
						var iTotalMarket = 0;
						var totalCoverd = 0;
						var TotalLines = 0;
						var c = 0;
						aiDisplay=','+aiDisplay+',';
						
						for ( var i=0 ; i<aaData.length ; i++ )
						{
							
							if (aiDisplay.indexOf(','+i+',') != -1){
								iTotalMarket += aaData[i][5]*1;
								totalCoverd += Math.round((aaData[i][2]*1) * (aaData[i][5]*1 / 100));
								TotalLines += aaData[i][2]*1;
							}
						}
			
						/* Modify the footer row to match what we want */
						var nCells = nRow.getElementsByTagName('th');
						nCells[1].innerHTML = 'Lines: '+ TotalLines +' Covered: '+totalCoverd +
							' Coverage: '+ Math.round(totalCoverd / TotalLines * 100) +'%';
					}
				} );
			} );
		</script>
</HEAD><BODY>
<div class="navbar navbar-inverse navbar-fixed-top">
<div class="navbar-inner">
        <div class="container">
<?php 

if(empty($_GET["filename"])){
	$anav .=" <a class=\"brand\" href=/navigation.php style=\"".(!$_GET['doctest'] && !$_GET['notdoctest']?"color: white;":"")."\">Home</a> <a class=\"brand \" style=\"".($_GET['doctest']?"color: white;":"")."\" href=\"".($_GET['doctest'] ? "?query=".$_GET['query'] : "?doctest=1&query=".$_GET['query'])."\">@Url</a> <a class=\"brand \" style=\"".($_GET['notdoctest']?"color: white;":"")."\" href=\"".($_GET['notdoctest'] ? "?query=".$_GET['query'] : "?notdoctest=1&query=".$_GET['query'])."\">!Url</a>     <form class=\"navbar-search pull-right\" method=get> <font color=white>Advanced filename search</font> <input type=hidden name=doctest value=\"".$_GET['doctest']."\"> <input type=hidden name=notdoctest value=\"".$_GET['notdoctest']."\"><input type=\"text\" name=\"query\" value=\"".$_GET['query']."\" class=\"search-query\" placeholder=\"Ex: not like '%atk%' \"></form> </div></div></div>\n";
	echo $anav;

	echo "
    <div class=\"row-fluid\" style=\"margin-top: 45px\">
      <div class=\"span12\">\n";
	echo "\n
	<table id=\"dataTable\" class=\"table table-condensed table-bordered table-hover\">
	<thead style=\"background-color: #000000\">
		<tr>
			<td style=\"0px solid;\"><strong><p class=\"text-warning\">#</p></strong></p></td>
			<td style=\"0px solid;\"><strong><p class=\"text-warning\">Filename</strong></p></td>
			<td style=\"0px solid;\"><strong><p class=\"text-warning\">Lines</strong></p></td>
			<td style=\"0px solid;\"><strong><p class=\"text-warning\">Date</strong></p></td>
			<td style=\"0px solid;\"><strong><p class=\"text-warning\">Url</strong></p></td>
			<td style=\"0px solid;\"><strong><p class=\"text-warning\">Coverage %</strong></p></td>
		</tr>
	</thead>
	<tbody>\n";
	foreach (read_file_covered($db, "", $_GET['doctest'], $_GET['notdoctest'], $_GET['query']) as $row) {
		$trow="";	
		$counter++;
		
		foreach ($row as $key=>$value) {
			$perc = floor($value);
			if ($perc==100) $rowclass="success";
			elseif($perc>=80 && $perc < 100) $rowclass="warning";
			else $rowclass="error";

			if ($key=="coverage_origin_id") continue;
			if ($key=="file_id") $value=$counter;
			if ($key=="coverage") $value=sprintf("%03d",floor($value));//." %"; 
			if ($key=="file_name"){ 
				if (strstr($value,"https://"))
					$trow.= "<td><a target=\"blank\" href=\"$value\">$value</a></td>\n";
				else
					$trow.= "<td><a href=\"?filename=$value\">$value</a></td>\n";
			}
			else
				$trow.= "<td>$value</td>\n";
		}
		print "<tr class=\"$rowclass\">".$trow;
		print "</tr>";
	}
	echo "
	</tbody><tfoot><tr><th colspan=2></th><th colspan=4></th></tr></tfoot></table>\n";
	//getDirList($db, "/mnt/dadanet2/appsfuelbe2/code");
	//echo "
	//</td><td width=\"161\"0 align=center valign=top class=anav> <p> <br></p>";
}
else {
	$filename=$_GET["filename"];
	try {
	$coverage = read_coverage_data($db, $filename,true);
	$filecoverage = read_file_covered($db, $filename);
	$origins = read_file_origins($db, $filename);
	} catch (Exception $e){
		print "<pre>";
		var_dump($e);
		debug_print_backtrace();
		exit();
	}

	$perc = floor($filecoverage[0]['coverage']);
	if ($perc==100) $rowclass="progress-success";
	elseif($perc>=80 && $perc < 100) $rowclass="progress-warning";
	else $rowclass="progress-danger";

	$file = $filename;
	$content = highlight_file($filename, TRUE);
	$Lines = substr_count($content, "<br />") + 1;
	$SizeofFile = number_format(filesize($file), 0, ' ', ' ');
	$lastModified = date( "F d Y H:i:s.", filemtime($file));
	echo "<div class=anav> <a class=\"brand\" href=/navigation.php >Home</a>
<!--<a class=\"brand\" href=/source/>PHP Source Code Viewer</a>--> ";
    	if (empty($_GET["ln"])){ echo "<a class=\"brand\" href=./?filename=". $filename."&ln=1>Hide line #</a>";}
        else {
		echo "<a class=\"brand\" href=./?filename=". $filename.">Show line #</a>";
	}

	echo "<form class=\"navbar-search pull-right\" method=get><font color=white>Advanced filename search</font><input type=\"text\" name=\"query\" value=\"".$_GET['query']."\" class=\"search-query\" placeholder=\"Ex: not like '%atk%'\"></form>  /div><dov class=heading> </dov></div></div></div></div>
<dov class=\"container\" style=\"margin-top: 70px;\">
    <div class=\"row\" style=\"margin-top: 43px;\">
      <div class=\"span12\">
	<div><strong>Filename:</strong> $filename</div>
	<div><strong>Lines:</strong> $Lines&nbsp;&nbsp;&nbsp;&nbsp;<strong>Size:</strong>$SizeofFile <strong>bytes</strong>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Modified:</strong> $lastModified</div>
			<strong>File coverage</strong> ".floor($filecoverage[0]['coverage'])." %
			<div class=\"progress $rowclass progress-striped\">
  				<div class=\"bar\" style=\"width: ".floor($filecoverage[0]['coverage'])."%;\"></div>
			</div>";
        echo "
	<script>
	ftoggle=0;
	function toggle(id, ftoggle){ 
		if (!ftoggle) {ftoggle=1;}
		else {ftoggle=0;}
		if (ftoggle==1) {document.getElementById(id).style.display=''; document.getElementById('collaps').innerHTML='<strong>-</strong>'; }
		else {document.getElementById(id).style.display='none'; document.getElementById('collaps').innerHTML='<strong>+</strong>';}
		return ftoggle;
	}
	</script>";	
	echo "<div><strong>Origin doctest <span class=\"label label-info\">".count($origins)."</span> </strong> <button class=\"btn btn-mini btn-inverse\" onclick=\"javascript: ftoggle=toggle('origin', ftoggle);\"><div id=collaps><strong>+</strong></div></button></div><div id=origin style=\"display:none\">";
	foreach ($origins as $origin){ 
		if ($origin['file_name'] != $_GET['filename']){?>
    	<div class="alert alert-info" style="margin-bottom: -10px;">
    		<a href="?filename=<?echo $origin['file_name']?>"><? echo $origin['file_name'] ?></a>
    	</div>
	<?	}
		else {
?>
    	<div class="alert alert-info" style="margin-bottom: -10px;">
    		<a href="#">ITSELF</a>
    	</div>
	<?	
		}
	}
	echo "</div><br><br><table width=\"100%\" style=\"background-color: white\"><tr><td width=\"90%\" class=code_cell  valign=top></div></div></div></div>";
	$lines = explode("<br />", $content);
	$counter=0;
	foreach ($lines as $line)
	{
		$counter++;
		if (isset($coverage['lines'][$counter])) {
			if ($coverage['lines'][$counter] == 1 || $coverage['lines'][$counter] == -2) $color="98fb98"; elseif($coverage['lines'][$counter] == -1) $color="#fa8072";
		}
		else $color="#ffffff";
				print "<div style=\"height: 16px;background-color:$color\"><code style=\"background-color:#ffffff; padding: 0px; background-color:$color; border: 0px solid #e1e1e8;\">";
		if (!empty($_GET["ln"])) print $line."<br>\n";
		else print "<p class=\"label label-info\">".sprintf("%04d",$counter)."</p> ".$line."<br>\n";
		print "</code></div>";
	}
	echo "</td><td width=\"130\" valign=top align=center class=nav_cell> <br>
	<br>
	</td></tr></table>";
	}
?> 
<!--<div class=anav><a href=#top> TOP </a> :: <a href=./?>PHP Source Code Viewer</a>&nbsp; &nbsp; &nbsp; &nbsp; Powered by <a target=_blank href=http://www.dew-code.com/><?php echo $version;?></a></div>-->
</div></div></dov>
</BODY> </HTML>
