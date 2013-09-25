<?php

function update_coverage_db($coverage_data, $doctest_file, $coverage_db_file) {
        $db = new SQLite3($coverage_db_file);

        $db->exec('CREATE TABLE IF NOT EXISTS coverage_file (file_id INTEGER, file_name STRING UNIQUE, total_lines INTEGER, update_date DATE DEFAULT CURRENT_DATE, doctest INTEGER DEFAULT 0, PRIMARY KEY (file_id), UNIQUE(file_name))');
        $db->exec('CREATE TABLE IF NOT EXISTS coverage_data (file_id INTEGER not NULL, line_number INTEGER, covered BOOLEAN, line_type INTEGER, PRIMARY KEY (file_id, line_number))');
        $db->exec('CREATE TABLE IF NOT EXISTS coverage_origin (coverage_origin_id INTEGER not NULL, file_id INTEGER not NULL, parent_file_id INTEGER not NULL, PRIMARY KEY(coverage_origin_id) );');

        //Check if origin doctest file exists	
        $filename=realpath($doctest_file);
        $filecontent = file($filename);
        $num_lines = count($filecontent);
        $doctestfileid = $db->querySingle("SELECT file_id FROM coverage_file WHERE file_name='$filename'");
        if (!$doctestfileid) {
            $db->exec("INSERT INTO coverage_file (file_name,total_lines) VALUES ('$filename',$num_lines)");
            $doctestfileid = $db->querySingle("SELECT file_id FROM coverage_file WHERE file_name='$filename'");
        }

        foreach ($coverage_data as $filename => $filedata) {
    		if (!file_exists($filename)) {
    			continue;
            }

            $filename=realpath($filename);
            $num_lines = count($filedata);
            $filecontent = file($filename);
            $fileid = $db->querySingle("SELECT file_id FROM coverage_file WHERE file_name='$filename'");

            if (!$fileid) {
                $db->exec("INSERT INTO coverage_file (file_name,total_lines) VALUES ('$filename',$num_lines)");
                $fileid = $db->querySingle("SELECT file_id FROM coverage_file WHERE file_name='$filename'");
            } else {
                $db->exec("UPDATE coverage_file SET total_lines=$num_lines WHERE file_name='$filename' and total_lines != $num_lines");
            }

            @$db->exec("INSERT INTO coverage_origin (file_id,parent_file_id) VALUES ($fileid,$doctestfileid);");

            $counter=0;
            $classtotal=0;
            $classcovered=0;
            $functiontotal=0;
            $functioncovered=0;
            $debug=false;
            $updatesql='';
            // var_dump($filename, array_keys($filedata)); continue;
            foreach ($filedata as $line => $covered) {


                $counter++;
                $linetype=0;

                if (preg_match('/^[ \t]*class /',$filecontent[$line-1])) { $linetype=1; }
                if (preg_match('/^[ \t]*function /',$filecontent[$line-1])) { $linetype=2; }

                $linecovered = $db->querySingle("SELECT covered FROM coverage_data WHERE file_id=$fileid and line_number=$line and (covered=1 or covered=-2);");
                
                if ($debug) { echo "SELECT covered FROM coverage_data WHERE file_id=$fileid and line_number=$line;\n"; }

                if (empty($linecovered) || (($linecovered == -1 || $linecovered == -2) && $covered != -1 && $covered != -2)) {
                    if ($debug) echo "NOT COVERED $linecovered: $fileid,$line,$covered,$linetype\n";
                    $updatesql.="REPLACE INTO coverage_data (file_id,line_number,covered,line_type) VALUES ($fileid,$line,$covered,$linetype);\n";
                } else {
                    if ($debug) { echo "COVERED $linecovered: $fileid,$line,$covered,$linetype\n"; }
                }

                if ($counter >= 1000) {
                    $counter=0;
                    $db->exec($updatesql);

                    $updatesql="";
                }
            }
            if (($counter > 0) && (!empty($updatesql))) {
                $db->exec($updatesql);
            }
        }

}

