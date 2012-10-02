<?php
require_once('_functions.php');
?>
<script>
function setVal(v) {
	document.getElementById('lval').value = v;
	return true;
}
</script>
<style>
body {
	font-family: Helvetica,Arial;	
}
div#header a:link, div#header a:visited {
	color: #ffc;
	text-decoration: none;
}
div#header a:hover {
	color: #f50;
}
</style>
<div id="header" style="background-color: #333; color:#ffc; height:25px; margin:0px; padding:5px;">
	<div style="float:left">
		<a href="index.php"><b>UxN digital tool landscape</b></a>
	</div>
	
	<div id="account_info" style="float:right">
<?php
	if (isset($_SESSION['user'])) {
		echo "Signed in as ".$_SESSION['user']." | <a href='logout.php'>Logout</a>";
	} else {
?>
	<form method="POST" action="login.php">username: <input type="text" name="username" size="24" /> password: <input type="password" name="password" size="24"><input type="hidden" id='lval' name="a" value="l" /><input type="submit" value="login" onClick="return setVal('l');"> | OR <input type="submit" value="register" onClick="return setVal('s');"></form><br />
<?php		
	}
?>	
	</div>
</div>
