<? error_reporting(-1); ?>

<HTML>
<HEAD><TITLE>Usage Statistics: Faster?</TITLE>

<LINK REL=StyleSheet HREF="/lib/style.css" TYPE="text/css">
</HEAD>

<?

?>



<?
include("/docs/lib/include/scripts.php");
include("usage_scripts.php");
extract($_REQUEST);
?>

<style>
.highlight { background-color: yellow }
</style>
<script type="text/javascript">
$(document).ready(function() {
    $('table tr').click(function() {
	$(this).parent().children().children().removeClass('highlight');
	$(this).children('td').addClass('highlight');
      });


  });
</script>


<script>
jQuery.expr[':'].iContains = function(a,i,m){
  return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase())>=0;
};

function HideNonMatch(search) {
  $('tr.filterable:not(:iContains('+search+'))').each(function() {
      $(this).hide();
    });

}

function FilterDB () {
  var search = $('#q').val();
  if (search.length == 0) {
    $('tr.filterable').each(function() {
	//alert ($(this).text());
	$(this).show();
      });
  }
  else {
    var terms=search.split(" ");
    for (x in terms) {
      HideNonMatch(terms[x]); //(search);
    }
  } //end else if not zero-length search
} //end FilterDB

$(document).ready(function() {
    $('#filter').html('<form method="get" autocomplete="off"><label for="q">Filter databases by title: </label><input type="text" value="" name="q" id="q" placeholder="Enter database name" size="50"/></form>');

    var search = $('#q').val();
    $("#q").keyup(function() {
	$("tr.filterable").show();
	FilterDB();
      });
    $("#q").focus(function() {
	FilterDB();
      });
  });
</script>


<?

///////////////////////////////////////////////////////////////////////
//        Show detailed access log for a particular month
//////////////////////////////////////////////////////////////////////?

if ($detail && $detail_month && $detail_year) {
  MonthDetails ($detail, $detail_month, $detail_year); //detail = db_id
 } 

////////////////////////////////////////////////////////////////////////
//          Show monthly graph for a particular database
////////////////////////////////////////////////////////////////////////

elseif ($graph) {
  MonthlyGraph($graph);
} // end if graph


///////////////////////////////////////////////////////////////////////
//        Default: One-year or all-year summary
///////////////////////////////////////////////////////////////////////
else { // if not graph
  $first_year = 2001;  // year the database began
  $this_year  = date("Y"); // this year
  if ((! $show_year)||($show_year == $this_year)) { 
    $show_year = $this_year; // use this year if none specified
  } 

  // allow user to choose a different year, but default to this year
  
  for ($i=$first_year; $i <= $this_year; $i++) {
    if ($i == $show_year) { $selected = " SELECTED";}
    else { $selected = ""; }
    $options = "  <option value=\"$i\"$selected>$i</option>\n" . $options;
  }
  $selected = "";
  if ($show_year == "all") { $selected = " SELECTED"; }
  $options .= "<option value=\"all\"$selected>All years</option>\n";
  
  if ($debug) { print "<P>Until_month=$until_month</P>\n"; }
  
  //display the year-select pulldown
  print "<form action=\"$_SERVER[SCRIPT_NAME]\" name=\"year_select\" method=\"get\">\n <strong>Stats for year:</strong> <select name=\"show_year\" onChange=\"document.year_select.submit()\">\n$options </select>\n</form>\n";

  print ('<div id="filter"></div>');

  if ($show_year == "all") { print "<p>Note: 2001 stats start in September</p>\n"; }

  

  ///////////////////////////////
  // Show all years' summary
  ///////////////////////////////

    if ($show_year == "all") {
      ShowAllYearsStats();
    }

  //////////////////////////////////////////////////////////////////////
  //          Show one year's stats
  //////////////////////////////////////////////////////////////////////

  else { // if not show-all
    $q = "SELECT * FROM usage_stats WHERE month like '$show_year%'";
    $r = mysql_query($q);
    $first_month = 12;
    
    while ($myrow=mysql_fetch_assoc($r)) {
      extract($myrow);
      $month = date("n",strtotime($month));
      $count[$db_id][$month] = $hits;
      $yr_ct[$db_id] += $hits;
      if ($month > $until_month) { $until_month = $month; } 
      if ($month < $first_month) { $first_month = $month; }
      //  print "Count[$db_id][$month] = $hits<br>\n";
    }
    
    // print_r($count);
    // print "<h2>until: $until_month</h2>\n";
    
    $top = "<table>\n<tr><th align=left>Title</th>";
    for ($i = $first_month; $i <= $until_month; $i++) {
      $month_abbr = (date("M Y",mktime (0,0,0,$i,1,$show_year)));
      $top .= "<th>$month_abbr</th>\n"; 
    } // end for each month of the year 
    $top .= "<th>Total</th>\n";

    print "$top</tr>\n";
  
    
    
    $q2 = "SELECT title,url,ID FROM db_new order by title";
    $r2 = mysql_query($q2);
    while ($myrow=mysql_fetch_assoc($r2)) {
      extract($myrow);
      $id = $ID;
      for ($i = $first_month; $i <= $until_month; $i++) {
	//    print "count[$id][$i] : $count[$id][$i]<br>\n";
	$hits = $count[$id][$i]; // hits per db per month
	$month_all_db_total[$i] += $hits;
	if ($hits == "") { $hits = 0; }
	$thislist .= "<td>$hits</td>";
      } // foreach month

      $thislist .= "<td>$yr_ct[$id]</td>";
	

      $size = sizeof($count[$id]); // see if there are any stats for this year&db
      if ($size > 0) { print "<tr class=\"filterable\"><td><a href=\"$url\">$title</a> [<a href=\"$_SERVER[SCRIPT_NAME]?graph=$id\">graph</a>]</td> $thislist</tr>\n"; }
      $thislist = "";
    } // end while each db
    foreach ($month_all_db_total as $db => $count) {
      $totals .= "<th>$count</th> ";
      $grand_total += $count;
    }
    print "<tr><th>Grand Totals</th> $totals <th>$grand_total</th></tr> \n";
    print "</table>\n";
    $grand_total = 0;
    $month_all_db_total = array();
  } // end else if not show-all
} // end else if no graph
  
  Breadcrumb("$_SERVER[SCRIPT_NAME]");
  
?>
