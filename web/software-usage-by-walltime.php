<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Revision info:
# $HeadURL: http://svn.osc.edu/repos/pbstools/releases/pbstools-2.0/web/software-usage-by-walltime.php $
# $Revision: 236 $
# $Date: 2008-02-29 10:15:26 -0500 (Fri, 29 Feb 2008) $
require_once 'page-layout.php';
require_once 'dbutils.php';
require_once 'metrics.php';
require_once 'site-specific.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

$title = "Software usage by job length";
if ( isset($_POST['system']) )
  {
    $title .= " on ".$_POST['system'];
  }
if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
     $_POST['start_date']!="" )
  {
    $title .= " started on ".$_POST['start_date'];
  }
 else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	   $_POST['start_date']!="" &&  $_POST['end_date']!="" )
   {
     $title .= " started between ".$_POST['start_date']." and ".$_POST['end_date'];
   }
 else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
   {
     $title .= " started after ".$_POST['start_date'];
   }
 else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
   {
     $title .= " started before ".$_POST['end_date'];
   }
page_header($title);

# list of software packages
$packages=software_list();

# regular expressions for different software packages
$pkgmatch=software_match_list();

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db = db_connect();
    foreach ($keys as $key)
      {
	if ( $key!='system' && $key!='start_date' && $key!='end_date' )
	  {
	    echo "<H3><CODE>".$key."</CODE></H3>\n";
	    $sql = "SELECT ".xaxis_column("walltime").",COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, SUM(TIME_TO_SEC(cput))/3600.0 AS cpuhours_alt,MIN(TIME_TO_SEC(walltime)) AS hidden FROM Jobs WHERE system LIKE '".$_POST['system']."' AND username IS NOT NULL AND ( script IS NOT NULL AND ";
	    if ( isset($pkgmatch[$key]) )
	      {
		$sql .= $pkgmatch[$key];
	      }
	    else
	      {
		$sql .= "script LIKE '%".$key."%' OR software LIKE '%".$key."%'";
	      }
	    $sql .= " ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY walltime UNION SELECT 'TOTAL:' AS walltime,COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, SUM(TIME_TO_SEC(cput))/3600.0 AS alt_cpuhours, 100000000 AS hidden FROM Jobs WHERE system LIKE '".$_POST['system']."' AND username IS NOT NULL AND ( ";
	    if ( isset($pkgmatch[$key]) )
	      {
		$sql .= $pkgmatch[$key];
	      }
	    else
	      {
		$sql .= "script LIKE '%".$key."%' OR software LIKE '%".$key."%'";
	      }
	    $sql .= " ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) ORDER BY hidden;";
            #echo "<PRE>".htmlspecialchars($sql)."</PRE>";
	    $result = db_query($db,$sql);
	    echo "<TABLE border=1>\n";
	    echo "<TR><TH>walltime</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>cpuhours_alt</TH></TR>\n";
	    while ($result->fetchInto($row))
	      {
		$rkeys=array_keys($row);
		echo "<TR>";
		foreach ($rkeys as $rkey)
		  {
		    if ( $rkey!="hidden" )
		      {
			$data[$rkey]=array_shift($row);
			echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
		      }
		  }
		echo "</TR>\n";
	      }
	    echo "</TABLE>\n";
	  }
      }
    db_disconnect($db);
    bookmarkable_url();
  }
else
  {
    begin_form("software-usage-by-walltime.php");

    system_chooser();
    date_fields();

    checkboxes_from_array("Packages",$packages);

    end_form();
  }

page_footer();
?>