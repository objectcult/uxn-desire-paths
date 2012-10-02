<?php
/* 

- generate activity data over a period of time, 
- default 100 records (override with n=x in url)
- randomly select activity date within period
- distribute evenly over all registered users

uck...more complicated than that...need to generate tool refs and connections...
maybe 2/3 tools and 1/3 connections???
- distribute in rough bell curve over random sorting of tools, including some that get 0
*/

require_once("_functions.php");

$user_array = getUsers();

$tool_array = getTools();
// generate activity for the given date range and group...
if (isset($_POST['n'])) {$nrec=$_POST['n'];} // number of records
if (!isset($nrec) and isset($_GET['n'])) {$nrec=$_GET['sd'];}
if (!isset($nrec)) {$nrec=100;}
$tool_dist = array();
$sect = count($tool_array)/5;
$ntoolrec = ((2*$nrec)/3)+$sect;  // because $sect are going to be skipped, but we still want $nrec total
$nconrec = $nrec-$ntoolrec;
$lndx = 0;
// fill tool dist array with #of instances to generate for each tool
//  ndx to tool dist is not ndx to tools, but iterator;  
for ($i=0;$i<$sect;$i++) { // 20% get 0
	$tool_dist[$i]=0;
	$lndx = $i;
}
$lndx++;
for ($i=$lndx;$i<($sect*2);$i++) { // 20% get 5%; 80% of nrec remains
	$tool_dist[$i]=$ntoolrec/20;
	$lndx = $i;
}
$lndx++;
for ($i=$lndx;$i<($sect*3);$i++) { // 20% get 10%; 60% of nrec remains
	$tool_dist[$i]=$ntoolrec/10;
}
$lndx++;
for ($i=$lndx;$i<($sect*4);$i++) { // 20% get 3%; 40% of nrec remains
	$tool_dist[$i]=$ntoolrec/33;
}
$lndx++;
for ($i=$lndx;$i<($sect*5);$i++) { // 20% get 2%; 20% of nrec remains
	$tool_dist[$i]=$ntoolrec/50;
}
$lndx++;
for ($i=$lndx;$i<count($tool_array);$i++) { // rest get 5%;
	$tool_dist[$i]=$ntoolrec/20;
}


// to specify a date range, must at least specify start date; end date defaults to today
if (isset($_POST['sd'])) {$sdate=$_POST['sd'];} // start date
if (!isset($sdate) and isset($_GET['sd'])) {$sdate=$_GET['sd'];}
if (!isset($sdate)) {$sdate = date('Y-m-d',time()-(60*60*24*30));} // a month ago
$act_sdate = strtotime($sdate);
if (isset($_POST['ed'])) {$edate=$_POST['ed'];} // end date
if (!isset($edate) and isset($_GET['ed'])) {$edate=$_GET['ed'];}
if(!isset($edate)) {$edate = date('Y-m-d',time());}
$act_edate = strtotime($edate);
$date_span = $act_edate - $act_sdate;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>generate data</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Steve Gano">
	<!-- Date: 2012-04-25 -->
</head>
<body>
<?php
echo "<h3>Generating data for date range ".date('Y-m-d',$act_sdate)." to ".date('Y-m-d',$act_edate)." (".$date_span." seconds)</h3>";
$total = 0;
$tool_pool = array();
foreach ($tool_dist as $tc) {
	// generate $tc records for a randomly-selected tool
	$tndx = rand(1,count($tool_array))-1;
	$tool_rec = array_splice($tool_array,$tndx,1);
	$tool_id = $tool_rec[0]['tool_id'];
	if ($tc > 0) {array_push($tool_pool,$tool_id);}
	for ($i=1;$i<=$tc;$i++) {
		$rndx = rand(1,count($user_array))-1;
		$user_name = $user_array[$rndx]['username'];
		$date = date('Y-m-d',$act_sdate + rand(0,$date_span));
		$rdiff = rand(0,4);
		$rworth = rand(0,4);
		$res = addActivity($user_name,$date,'tool',$tool_id,NULL,$rdiff,$rworth,"");
		if ($res<0) {
			echo "ERROR: ".$res."<br />";
		} else {
			echo ++$total.": (".$user_name.", '".$date."', 'tool', ".$tool_id.", NULL, ".$rdiff.", ".$rworth.", '')<br />";
		}
	}
}
for ($i=1;$i<=$nconrec;$i++) {
	// generate connection records
	$tndx1 = rand(1,count($tool_pool))-1;
	$tool_id1 = $tool_pool[$tndx1];
	do {$tndx2 = rand(1,count($tool_pool))-1;} while ($tndx2==$tndx1);
	$tool_id2 = $tool_pool[$tndx2];
	$rndx = rand(1,count($user_array))-1;
	$user_name = $user_array[$rndx]['username'];
	$date = date('Y-m-d',$act_sdate + rand(0,$date_span));
	$rdiff = rand(0,4);
	$rworth = rand(0,4);
	$res = addActivity($user_name,$date,'connection',$tool_id1,$tool_id2,$rdiff,$rworth,"");
	if ($res<0) {
		echo "ERROR: ".$res."<br />";
	} else {
		echo ++$total.": (".$user_name.", '".$date."', 'tool', ".$tool_id1.", ".$tool_id2.", ".$rdiff.", ".$rworth.", '')<br />";
	}
}

?>
</body>
</html>
