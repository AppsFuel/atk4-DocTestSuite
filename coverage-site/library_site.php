<?php

session_start();



class dbmysql {
        private $link;
        function __construct() {
                $this->link = mysql_connect('localhost', 'root', '');
                mysql_select_db("developer_coverage");
                if (!$this->link) die("Non mi riesco a connettere al db");
        }

        function exec($sql, $noerr=0) {
                $res = mysql_query($sql);
                if (!$res && !$noerr) {
                        print mysql_error()." \nERROR->$sql<br>";
                        echo "ERROR: $sql<br>";
                }
                return $res;
        }

        function query($sql, $noerr=0) {
                $res = mysql_query($sql);
		$this->res = $res;
		return $this;
        }

	function fetchArray()
	{
                $row=mysql_fetch_assoc($this->res);
		return $row;
	}

        function querySingle($sql) {
                $res = mysql_query($sql);
                $row= mysql_fetch_assoc($res);
                foreach ($row as $key=>$value)
                {
                        return $value;
                }
        }
        function beginTransaction()
        {
                mysql_query("SET AUTOCOMMIT=0");
                mysql_query("START TRANSACTION");
        }
        function commitTransaction()
        {
                mysql_query("COMMIT");
        }
}

//$db = new SQLite3("/dev/shm/doctest_coverage.db", SQLITE3_OPEN_READONLY);
//$db = new SQLite3("/tmp/site_coverage.db", SQLITE3_OPEN_READONLY);

$db = new dbmysql();

function read_coverage_data($db, $filename, $lines=false) {
	//echo "$filename";
	$result = null;
	$filename=realpath($filename);
	//if (!$_SESSION[db]) $_SESSION[db] = new SQLite3("/dev/shm/doctest_coverage.db", SQLITE3_OPEN_READONLY);
        $query = $db->query("SELECT file_id, total_lines, doctest FROM coverage_file WHERE file_name='$filename'");
	$row = $query->fetchArray();
        $fileid = $row['file_id'];
        $totlines = $row['total_lines'];
        $doctest = $row['doctest'];
	//print "SELECT file_id FROM coverage_file WHERE file_name='$filename'\n";
	
	if (empty($fileid)) return Array("coverage"=>null, "lines"=>null, "total_lines"=>null, "doctest"=>null);

	if ($lines)
	{
        	$query=$db->query("SELECT * FROM coverage_data WHERE file_id=$fileid order by line_number ASC");
		while ($row = $query->fetchArray()) {
    			$result[$row['line_number']]=$row['covered'];
		}
	}

	$sql="select ((ROUND(B.covered_lines) / ROUND(A.total_lines)) * 100) as coverage  from coverage_file A, (select file_id,count(*) as covered_lines from coverage_data where covered=1 group by file_id) B where A.file_id = B.file_id and A.file_id=$fileid;";
	//echo $sql."<br>";
        $coverageavg = $db->querySingle($sql);
	//var_dump($coverageavg);
	return Array("coverage"=>$coverageavg, "lines"=>$result, "total_lines"=>$totlines, "doctest"=>$doctest);
}

function read_file_covered($db, $filename="", $doctest="", $notdoctest="", $query="") {
	if ($query) { $addquery=" AND A.file_name $query";}
	$union=" union select A.*,0 from coverage_file A  where A.file_id not in (select distinct(file_id) from coverage_data) $addquery;";
	if ($filename) { $addsql=" AND A.file_name='$filename'"; $union="";}
	if ($doctest) { $addsql.=" AND A.doctest=1"; $union="";}
	if ($notdoctest) {$addsql.=" AND A.doctest=0"; $union=" union select A.*,0 from coverage_file A  where A.doctest=0 $addquery and A.file_id not in (select distinct(file_id) from coverage_data);";}
	$sql="select A.*, ((ROUND(B.covered_lines) / ROUND(A.total_lines)) * 100) as coverage  from coverage_file A, (select file_id,count(*) as covered_lines from coverage_data where covered=1 or covered=-2 group by file_id) B where A.file_id = B.file_id $addsql $addquery $union";
	//echo $sql;
        $query = $db->query($sql);
	while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
    		$result[]=$row;
	}

	//var_dump($coverageavg);
	return $result;
}

function read_file_origins($db, $filename) {
	$sql="select file_name from coverage_file where file_id IN (select a.parent_file_id from coverage_origin as a, coverage_file b where a.file_id = b.file_id and b.file_name ='$filename');";
        $query = $db->query($sql);
	while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
    		$result[]=$row;
	}

	//var_dump($coverageavg);
	return $result;
}


function getDirList ($db, $dirName) {
	$d = dir($dirName);
	if (is_link($d)) return;
	while($entry = $d->read()) {
	if ($entry != "." && $entry != ".." ) {
	if (is_dir($dirName."/".$entry)) {
	getDirList($db, $dirName."/".$entry);
	} else {
	//echo $dirName."/".$entry."<br>\n";
	if (
	    (preg_match('/\.php$/', $entry))
	    && 
	    (!preg_match('/index\.php$/', $entry))
	){

	//if (ereg('htdocs\/atk', $dirName) || ereg('htdocs\/af-common', $dirName)) return;
	try {
	$coverage=read_coverage_data($db, $dirName."/".$entry);
	} catch (Exception $e) {
		print "<pre>";var_dump($e);
		debug_print_backtrace();
		exit();
	}
	if ($coverage['coverage']) $perc=$coverage['coverage']; else $perc=0;
	if ($perc == "100" ) $color="#98fb98"; //light green
	elseif ($perc>0) $color="orange";
	elseif ($perc == 0 ) $color="#fa8072"; //light red
	//var_dump($avg_coverage);
	if (realpath($dirName."/".$entry) == $dirName."/".$entry) {
	if (empty($_GET['only_covered']) || ($_GET['only_covered'] && !is_null($coverage['coverage'])))
		if (empty($_GET['only_doctest']) || ($_GET['only_doctest'] == 1 && $coverage['doctest'] == 1 ))
			echo "<div style=\"background-color:$color\"><a href=./?filename=".$dirName."/".$entry.">".$dirName."/".$entry."</a> ($perc %) - Lines ".$coverage['total_lines']."</div>\n";
	}
	}


	// If you need to list a file with odd name or no extension , just add an ereg line for it  'blah$' denotes ends with blah
	//if (ereg('directory$', $entry)){echo "<a href=./?filename=".$dirName."/".$entry.">".$dirName."/".$entry."</a><br>\n";}


	// You can also list other file extension like this
	//if (ereg('\.log$', $entry)){echo "<a href=./?filename=".$dirName."/".$entry.">".$dirName."/".$entry."</a><br>\n";}
	//if (ereg('\.txt$', $entry)){echo "<a href=./?filename=".$dirName."/".$entry.">".$dirName."/".$entry."</a><br>\n";}

	//if (ereg('\.html$', $entry)){echo "<a href=./?filename=".$dirName."/".$entry.">".$dirName."/".$entry."</a><br>\n";}


	if(!empty($_GET["g"])){
	// you can list other graphic types like this.
	//if (eregi('\.BMP$', $entry)){echo $linkto ."=".$dirName."/".$entry.">".$dirName."/".$entry."</a><br>\n";}

	if($_GET["g"]<2){$linkto = "<a target=_blank href"; $linkend = "</a>";}
	if($_GET["g"]>1){$linkto = "<img align=middle src";$linkend = "";}
	//if (eregi('\.jpg$', $entry)){echo $linkto ."=".$dirName."/".$entry.">".$dirName."/".$entry.$linkend ."<br>\n";}
	//if (eregi('\.gif$', $entry)){echo $linkto ."=".$dirName."/".$entry.">".$dirName."/".$entry.$linkend ."<br>\n";}
	//if (eregi('\.ico$', $entry)){echo $linkto ."=".$dirName."/".$entry.">".$dirName."/".$entry.$linkend."<br>\n";}
	//if (eregi('\.png$', $entry)){echo $linkto ."=".$dirName."/".$entry.">".$dirName."/".$entry.$linkend."<br>\n";}
	}

	}
	}
	}
	$d->close();
}
