<?php
require_once("_functions.php");
$__REFERRER = "index.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>UxN Digital Tool Landscape Home Page</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Steve Gano">
	<!-- Date: 2012-04-12 -->

	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/base/jquery-ui.css"></link>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
<script>
function journalDate() {
	var jdate = $("#datepicker").val();
	if (jdate=="") {
		alert ("Click in the date field to display a calendar for picking a date.");
		return false;
	}
	self.location.href="user_day_journal.php?activity_date="+jdate;
}
$(function() {
	$( "#datepicker" ).datepicker();
});
</script>
</head>
<body>
<?php include('header.php'); ?>
<br clear="all" />
<hr>
<h3><a href="aggregate.php">View aggregate data.</a></h3>
<h3><a href="user_day_journal.php">View and edit today's digital tool journal.</a></h3>
<h3>View and edit a past day's digital tool journal. Select date: <input type="text" id="datepicker" /><input type="button" value="GO" onclick="journalDate();" /></h3>
<?php
if (userRole()=="admin") {
	echo '<h3><a href="tool_admin.php">Add or Edit a Tool</a></h3>';
}
?>
</body>
</html>
