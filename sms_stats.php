
<?
include ("/docs/lib/include/scripts.php");
include ("/docs/lib/include/styles.php");
jQueryPlugins("jqueryUI","dataTables");
jQueryPlugins("jscharts");
?>

<script type="text/javascript">
$(function() {
    $('#tabs').tabs();
    $('#lc-single').dataTable({
	"aaSorting": [[1, "desc"]],
	  "iDisplayLength": 200,
	  });
    $('#sms-years-table').dataTable({

	  });

    $('#lc-multi').dataTable({
	"aaSorting": [[1, "desc"]],
	  "iDisplayLength": 200,
	  });

  });
</script>

<style>
.num { text-align: right; }
</style>

<h1>EZRA/SMS stats since August 2009</h1>
<?

/* STATS BY CARRIER */

$q = "SELECT * FROM sms_stats WHERE total > 0 ORDER BY total DESC, most_recent DESC";
$r = mysql_query ($q);
$most_recentest = 0;
while ($myrow = mysql_fetch_assoc($r)) {
  extract ($myrow);
  $rows1 .= "<tr><th>$carrier</th> <td class=num>$total</td> <td>$most_recent</td></tr>\n";
  if (strtotime($most_recent) > $most_recentest) { 
    $most_recentest = strtotime($most_recent); 
  } 
}
$most_recentest = date("Y-m-d", $most_recentest);
?>

<div id="tabs">
<ul>
 <li><a href="#tabs-1">Carriers</a></li>
 <li><a href="#tabs-2">Titles</a></li>
 <li><a href="#tabs-3">Call #s</a></li>
 <li><a href="#tabs-4">Locations</a></li>
 <li><a href="#tabs-5">Years</a></li>
</ul>

<div id="tabs-1">

<?
print "<table>\n";
print "<tr><th>Carrier</th><th>Total</th><th>Most Recent</th></tr>\n";
print "$rows1\n</table>\n";

/* STATS BY USER */ 

$q = "SELECT * FROM sms_users";
$r = mysql_query ($q);
$total_users = mysql_num_rows($r);
while ($myrow = mysql_fetch_assoc($r)) {
  extract($myrow);
  $total_n += $n;
}

$rows2 = "<tr><th>Total Users (Phones)</th> <td class=num>$total_users</td></tr>\n";
$rows2.= "<tr><th>Total Messages</th> <td class=num>$total_n</td></tr>\n";
$rows2.= "<tr><th>Most Recent Use</th> <td class=num>$most_recentest</td></tr>\n";
print "<table>\n$rows2</table>\n";
print "</div>";


print "<div id=\"tabs-2\">\n";
print "<a href=\"sms_dedup.php\" class=\"button\">De-dup entries</a>\n";


$q = "SELECT title, `call` , count( * ) AS circs FROM `sms_reqs` GROUP BY `title` ORDER BY `circs` DESC";
$r = mysql_query($q);
echo(MysqlResultsTable($r));
print "</div>\n";

/* STATS BY CALL # Range */
print "<div id=\"tabs-3\">\n";

$lcRows1 = TallyLC("single");
print "<table id=\"lc-single\">\n";
print "<thead><tr><th>Class</th><th>Count</th></thead>\n";
print "<tbody>$lcRows1</tbody>\n";
print "</table>\n";

$lcRows2 = TallyLC();
print "<table id=\"lc-multi\">\n";
print "<thead><tr><th>Class</th><th>Count</th></thead>\n";
print "<tbody>$lcRows2</tbody>\n";
print "</table>\n";


print "</div>\n"; //end tab-3


$q = "SELECT loc, count( * ) AS loc_ct FROM `sms_reqs` GROUP BY `loc` ORDER BY `loc_ct` DESC";
$r = mysql_query($q);
print "<div id=\"tabs-4\">\n";
print(MysqlResultsTable($r));
print "</div>\n"; //end tab-4


$q = "select year(timestamp) as year, count(`call`) as instances from `sms_reqs` group by year order by year asc";
$r = mysql_query($q);
$r2 = mysql_query($q);
print "<div id=\"tabs-5\">\n"; 
print '<div id="annual_trend"></div>'.PHP_EOL;
print(Trendline($r, 'annual_trend'));
print(MysqlResultsTable($r2, 'sms-years-table'));

print "</div>\n"; //end tab-5

print "</div>\n"; //end tabs div


function Trendline ($mysql_results, $div_id="trendline", $dateCol=0) {
    //expects a two-column results set, one date, one info
    $jsData = array();
    $tooltips = '';
    $infoCol = 1-$dateCol; // date:0, info:1; or vice versa
    while ($myrow = mysql_fetch_row($mysql_results)) {
        $info = $myrow[$infoCol];
        $date = $myrow[$dateCol];
        array_push($jsData, "[$date,$info]");
        $tooltips .= "myChart.setTooltip([$date,'$info']);\n";
        $labels .= "myChart.setLabelX([$date,'$date']);\n";
    }
    $jsChartData = implode(",", $jsData);
    $out = "<script>";
    $out .= "var myData = new Array($jsChartData);\n";
    $out .= "var myChart = new JSChart('$div_id', 'line');\n";
    $out .= "myChart.setSize(550,300);\n";
    $out .= "myChart.setTitle('EZRA SMS Requests by Year');\n";
    $out .= "myChart.setDataArray(myData);\n";
    $out .= "myChart.setFlagRadius(4);\n";
    $out .= "myChart.setFlagColor('#37379F');\n";
    $out .= $labels.$tooltips;
    $out .= "myChart.draw();";
    $out .= "</script>";
    return $out;
}



function TallyLC ($level) { 
  // takes call numbers and returns an assoc array of LC classes & counts
  // default is to show full 1-3 letter classes; 
  // if $level=="single" it'll do single-letter class instead
  $q = "SELECT `call` FROM `sms_reqs`";
  $r = mysql_query($q);
  $r2 = $r;

  $classes = array();
  if ($level == "single") {
    $mstring = "^([A-Z])";
  }
  else { 
    $mstring = "^([A-Z]+)";
  } 
  
while ($myrow = mysql_fetch_row($r)) { 
  $call = $myrow[0];
  if (preg_match("/$mstring/",$call,$m)) {
    $class = $m[1];
    $classes[$class]++;
  } //end if
 } //end while
ksort($classes);

 $rows = "";
foreach ($classes as $k => $v) {
  $rows .= "<tr><td>$k</td><td>$v</td></tr>\n";
} // foreach

 return $rows;
} //end function TallyLC
?>
