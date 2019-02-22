<?
  function MonthDetails ($db_id, $detail_month, $detail_year) {
  // $url_result = mysql_query("SELECT title,url,old_url FROM db_new where ID = '$detail' order by title",$db);
    $q = "SELECT title,url FROM db_new where ID = '$db_id' order by title";
    $url_result = mysql_query($q);
    // print ($q);
 while ($myrow = mysql_fetch_row($url_result)) {
   $title=$myrow[0];
   $url = $myrow[1];
   $old_url = $myrow[2];
   $url_string = "(url = '$url' or url = '$db_id')";
   if ($old_url) { 
     $url_string = "($url_string or url = '$old_url')";
   } // end if old_url
 } // end while fetch url_result
 
 print "<h2>Detailed Access Statistics for <cite>$title</cite> in $detail_month $detail_year</h2>\n";

 $start = strtotime("$detail_month 1, $detail_year");
 $end = strtotime("$detail_month 28, $detail_year");
 $start = date("Y-m-d",$start);
 if ($order) {
   $order = "order by $order";
 }
 else { $order = "order by date"; }
 $this_year = date("Y");
 $end = date("Y-m-t",$end); // end date is replaced with last day of month
 if (($detail_year > 2005) && ($detail_year < $this_year)) {
   $suffix= "_".$detail_year;
 }
 elseif ($detail_year <= 2005) {
   $suffix = "_2001_2005";
 }
 else { $suffix = ""; }

 $where = "where $url_string and date between '$start' and '$end'";
   // $query = "SELECT * FROM redir_log where $url_string and date between '$start' and '$end' $order";

 $query = "SELECT * FROM redir_log$suffix $where $order";
  // Show distinct # of IP address
  $q = "SELECT distinct(ip) from redir_log$suffix $where";
  $r = mysql_query($q);
  print "<p><b>Distinct IPs:". mysql_num_rows($r) ."</b></p>\n";

 $results = mysql_query ($query);
 $QUERY_STRING = $_SERVER[QUERY_STRING];
 print "<table border=0 cellspacing=5>\n";
 if (preg_match("/(.*)\&order=[a-zA-Z]+(.*)/",$QUERY_STRING, $matches)) {
   $QUERY_STRING = "$matches[1]" . "$matches[2]";
 }
 print "<tr><th><a href=\"$_SERVER[REQUEST_URI]&order=date\">Date</a></th> <th><a href=\"$_SERVER[REQUEST_URI]&order=ip\">IP Address</a></th> <th><a href=\"$_SERVER[REQUEST_URI]&order=referer\">Referer</a></th></tr>\n";
 print ("<h4>Uses: " . mysql_num_rows($results) . "</h4>\n");
 while ($myrow = mysql_fetch_row($results)) {
   $count++;
   //   if (round($count/2) == ($count/2)) {
   if (isEven($count)) {
     $bg="#cccccc";
   }
   else { $bg = "#eeeeee"; }
   $date =$myrow[1];
   $ip =  $myrow[2];
   /*
   if (! preg_match("/136\.227/",$ip,$matches)) {
     $host = "<BR>" . gethostbyaddr("$ip");
   }
       else { $host = ""; }
   */
   $referer = $myrow[3];
   print "<tr><td bgcolor=$bg>$date</td> <td bgcolor=$bg>$ip</td> <td bgcolor=$bg>$referer</td></tr>\n";
 }
 print "</table>\n";

 // print "<P>$query</P>";
 print "<P><a href=\"$_SERVER[SCRIPT_NAME]\">Return to all usage statistics</a></P>\n";
  } //end function MonthDetails



function MonthlyGraph ($graph) {
  if ($graph == "all") { $where = ""; }
  else { $where = "WHERE db_id = '$graph'"; }
  $url_result = mysql_query("SELECT title FROM db_new where ID = '$graph'");
  while ($myrow = mysql_fetch_assoc($url_result)) {
    extract($myrow);
    $title = "$title\n";
  }

  $q = "SELECT * FROM usage_stats $where order by month ASC";
  $r = mysql_query($q);
  
  while ($myrow=mysql_fetch_assoc($r)) {
    extract($myrow);
    $year = date("Y",strtotime($month));
    $sum[$year] += $hits;
    if ($sum[$year] > $biggest_year) { $biggest_year=$sum[$year]; } 
    $monthly[$month] += $hits;
    if ($monthly[$month] > $biggest_month) { $biggest_month=$monthly[$month]; } 
    $total += $hits;
  } //end while myrow

  print "<h2>Usage Statistics for <cite>$title</cite></h2>\n";
  print "<h3>Total accesses: $total</h3>\n";
  
  print "<h4>Monthly Stats</h4>\n";
  foreach ($monthly as $month=>$hits) {
    $month = date("M Y",strtotime($month));
    $mo = date ("M",strtotime($month));
    $yr = date("Y",strtotime($month));
    if ($biggest_month > 100) { $height = round(($hits/$biggest_month)*100); }
    else { $height = $hits; }
    $table .= "<td style=\"vertical-align: bottom\"><a href=\"$_SERVER[SCRIPT_NAME]?detail=$graph&detail_month=$mo&detail_year=$yr\"><img src=\"/lib/images/redblock.gif\" width=40 height=\"$height\" title=\"$month: $hits\" border=0></a></td>\n";
    $legend.= "<th>$month<br>$hits</th>\n";
  }
  
  print "<table>\n<tr>$table</tr> <tr>$legend</tr></table>\n";
  $table = $legend = "";
  
  print "<h4>Annual Stats</h4>\n";
  foreach ($sum as $year=>$hits) {
    $height = round(($hits/$biggest_year)*100);
    $table .= "<td style=\"vertical-align: bottom\"><img src=\"/lib/images/redblock.gif\" width=40 height=\"$height\" title=\"$month: $hits\" border=0></td>\n";
    $legend.= "<th>$year:<br> $hits</td>\n";
    // print "$year: $hits<br>\n";
  } // end foreach
  print "<table>\n<tr>$table</tr> <tr>$legend</tr></table>\n";
  
  print "<p><a href=\"$_SERVER[SCRIPT_NAME]\">Return to all usage statistics</a></p>\n";
} //end function MonthlyGraph





    function ShowAllYearsStats() {
      $q = "SELECT * FROM usage_stats";
      $r = mysql_query($q);
      $years = array(); // initialize list of years
      
      while ($myrow=mysql_fetch_assoc($r)) {
	extract($myrow);
	$year = date("Y",strtotime($month));
	$count[$db_id][$year] += $hits;
	$yearsum[$year] += $hits;
	$years[$year]++;
      } // end while myrow
      
      //    print_r($count);
      
      ksort($years);
      foreach ($years as $k=>$v) {
	$header .= "<th>$k</th>\n";
      }
      $header = "<tr><th>Database</th> $header </tr>\n";
      
      
      $q2 = "SELECT title,url,ID FROM db_new order by title";
      $r2 = mysql_query($q2);
      
      while ($myrow=mysql_fetch_assoc($r2)) {
	extract($myrow);
	$id = $ID;
	foreach ($years as $yr => $v) {
	  $hits = $count[$id][$yr];
	  if ($hits == "") { $hits = 0; }
	  $thislist .= "<td>$hits</td>";
	}
	$size = sizeof($count[$id]); // see if there are any stats for this year&db
	if ($size > 0) { $lines .= "<tr class=\"filterable\"><td><a href=\"$url\">$title</a> [<a href=\"$_SERVER[SCRIPT_NAME]?graph=$id\">graph</a>]</td> $thislist</tr>\n"; 
      }
	$thislist = "";
      } // end while each db
      
      print "<table>$header $lines</table>\n";

      print_r($yearsum);
    } // end function ShowAllYearsStats
    



?>
