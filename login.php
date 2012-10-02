<?php

require_once('_functions.php');


$do_register = false;
// are we signing in or signing up?
if(isset($_POST['a'])) {
	$do_register = ($_POST['a']=='s');
} elseif (isset($_GET['a'])) {
	$do_register = ($_GET['a']=='s');
}

// was data posted? 
if (isset($_POST['a'])) {
	if (isset($_POST['username']) && strlen($_POST['username'])>0) {
		$u = $_POST['username'];
		$p = (isset($_POST['password']))?$_POST['password']:'';
		if ($_POST['a']=="l") {
			$res = login($u,$p);
			if ($res > 0) {  // success, session is started
				reloadReferrer();
				return;
			}
			switch ($res) {
				case $__RETURN_CODES['NO USER']:
					$_SESSION['status_msg'] = "No account for user name \"".$u."\"<br />";
					break;
				case $__RETURN_CODES['LOGIN FAIL']:
					$_SESSION['status_msg'] = "User name or password are incorrect.<br />";
					break;
				}
		} else {
			$g = (isset($_POST['group']))?$_POST['group']:'';
			$res = newUser($u,$p,$g);
			if ($res > 0) { // success, session is started
				reloadReferrer();
				return;
			}
			$do_register = true;
			switch ($res) {
				case $__RETURN_CODES['USER EXISTS']:
					$_SESSION['status_msg'] = "User name ".$u." already exists, try another.<br />";
					break;
				case $__RETURN_CODES['REGISTER FAIL']:
					$_SESSION['status_msg'] = "Registration failed. Please try again.<br />";
					break;
				}
			}
		} else {
			$_SESSION['status_msg'] = "Please enter user name and password.<br />";
		}
	}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>login</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Steve Gano">
	<!-- Date: 2012-04-12 -->
<style>
.login_block {
	margin: auto;
	width: 400px;
	height: 300px;
}
#status_msg{
	color: #cc0000;
	font-weight: bold;
}
</style>
<script>
function confirmPW() {
	if (document.forms['register'].elements['username'].value.length==0) {
		document.getElementById('status_msg').innerHTML = "Please enter a username and password.";
		return false;		
	}
	if (document.forms['register'].elements['group'].value=="none") {
		document.getElementById('status_msg').innerHTML = "Please select the group you belong to.";
		return false;
	}
	var p1 = document.forms['register'].elements['password'].value;
	var p2 = document.forms['register'].elements['password2'].value;
	if (p1 && p2) {
		if (p1 == p2) {
			return true;
		} else {
			document.forms['register'].elements['password'].value="";
			document.forms['register'].elements['password2'].value="";
			document.getElementById('status_msg').innerHTML = "Passwords don't match. Try again.";
			return false;
		}
	} else {
		document.getElementById('status_msg').innerHTML = "Please enter password twice to confirm.";
		return false;
	}
}
</script>
</head>
<body>
<?php include('header.php'); ?>
<div class="login_block">
<?php
if ($do_register) {
?>
	<form method="POST" id="register" action="login.php" onSubmit="return confirmPW()">
		<h4>Create an account</h4>
		<span id="status_msg"><?php echo $_SESSION['status_msg']?></span><br />
		username: <input name="username" type="text" size="40" /><br />
		password: <input name="password" type="password" size="40" /><br />
		confirm password: <input name="password2" type="password" size="40" /><br />
		group:<select name="group"><option value="none">Pick One</option><option value ="AMNH">AMNH</option><option value="Cooper-Hewitt">Cooper-Hewitt</option></select><br />
		<input type="hidden" name="a" value="s">
		<input type="submit" value="Register" /> or <a href="?a=l">Login</a>
	</form>
<?php
} else {
?>
	<form method="POST" id="login" action="login.php">
		<h4>Log in</h4>
		<span id="status_msg"><?php echo $_SESSION['status_msg']?></span><br />
		username: <input name="username" type="text" size="40" /><br />
		password: <input name="password" type="password" size="40" /><br />
		<input type="hidden" name="a" value="l">
		<input type="submit" value="Login" /> or <a href="?a=s">Register</a>
	</form>
<?php	
}

?>
</div>
</body>
</html>
