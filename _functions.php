<?php

require_once('_config.php');

$__RETURN_CODES = array();
$__RETURN_CODES['LOGIN FAIL'] = 0;
$__RETURN_CODES['NO USER'] = -1;
$__RETURN_CODES['BAD PARAMETERS'] = -1;
$__RETURN_CODES['USER EXISTS'] = -2;
$__RETURN_CODES['TOOL EXISTS'] = -2;
$__RETURN_CODES['REGISTER FAIL'] = -3;
$__RETURN_CODES['TOOL EDIT FAIL'] = -3;
$__RETURN_CODES['ACTIVITY EDIT FAIL'] = -3;
$__RETURN_CODES['QUERY FAIL'] = -4;
$__HOMEPAGE = "index.php";

session_start();
$_SESSION['status_msg'] = "";
function doQ($qry) {
	global $__DBSERVER, $__DBUSER, $__DBPW,$__DBNAME;
	$con = mysql_connect($__DBSERVER, $__DBUSER, $__DBPW) or die("Could not connect to database on ".$__DBSERVER.", U:".$__DBUSER." P:".$__DBPW);
	mysql_select_db($__DBNAME) or die ("Could not select database ".$__DBNAME);
	$qR = mysql_query($qry, $con);
	global $last_insert_id;
	$last_insert_id = mysql_insert_id();	
	mysql_close($con);
	return $qR;
}

function login($u,$p='') {
	global $__RETURN_CODES;
	$qry = "SELECT * FROM users WHERE username='".$u."'";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['NO USER'];
	}
	$row = mysql_fetch_assoc($res);
	if ($p!=$row['password']) {
		return $__RETURN_CODES['LOGIN FAIL'];
	} else {
		session_start();
		$_SESSION['user']=$u;
		return $row['user_id'];
	}
}

function newUser($u,$p='',$g='') {
	global $__RETURN_CODES,$last_insert_id;
	// check if user name exists
	$qry = "SELECT * FROM users WHERE username='".$u."'";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['REGISTER FAIL'];
	}
	if (mysql_num_rows($res)>0) {
		return $__RETURN_CODES['USER EXISTS'];
	}
	$qry = "INSERT INTO users SET username='".$u."', password='".$p."', org='".$g."', role='user'";
	$res = doQ($qry);
	if ($last_insert_id > 0) {
		$_SESSION['user']=$u;
		return($last_insert_id);
	} else {
		return $__RETURN_CODES['REGISTER FAIL'];
	}
}

function userRole($u='',$lP=true) {
	global $__RETURN_CODES;
	$lu =  (isset($_SESSION['user']))?$_SESSION['user']:"";
	// default, must be logged in
	if ($lP && $lu=="") return $__RETURN_CODES['NO USER'];
	if (!$lP && $u=="") return $__RETURN_CODES['NO USER'];
	$uc = ($lP)?$lu:$u;
	$qry = "SELECT role FROM users WHERE username='".$uc."'";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	if (mysql_num_rows($res)==0) {
		return $__RETURN_CODES['NO USER'];
	}
	$row = mysql_fetch_assoc($res);
	return $row['role'];
}

function getUsers($ur=null) {
	global $__RETURN_CODES;
	$qry = "SELECT * FROM users ".((isset($ur))?"WHERE role = '".$ur."'":"");
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	$uarray = array();
	while ($row=mysql_fetch_assoc($res)) {
		array_push($uarray,$row);
	}
	return $uarray;
}

function getGroups() {
	global $__RETURN_CODES;
	$qry = "SELECT DISTINCT org FROM users ORDER BY org ASC";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	$garray = array();
	while ($row=mysql_fetch_assoc($res)) {
		array_push($garray,$row['org']);
	}
	return $garray;
}
function getTools($tid=null) {
	global $__RETURN_CODES;
	$qry = "SELECT * FROM tools ".((isset($tid))?"WHERE tool_id = ".$tid:"")." ORDER BY tool_name ASC";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	$tarray = array();
	while ($row=mysql_fetch_assoc($res)) {
		array_push($tarray,$row);
	}
	return $tarray;
}

function addTool($tn,$td,$u) {
	global $__RETURN_CODES,$last_insert_id;
	// check if tool already exists
	$qry = "SELECT * FROM tools WHERE tool_name='".$tn."'";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	if (mysql_num_rows($res)>0) {
		return $__RETURN_CODES['TOOL EXISTS'];
	}
	$qry = "INSERT INTO tools SET tool_name='".$tn."', description='".$td."', added_by=(SELECT user_id FROM users WHERE username='".$u."')";
	$res = doQ($qry);
	if ($last_insert_id > 0) {
		return($last_insert_id);
	} else {
		return $__RETURN_CODES['TOOL EDIT FAIL'];
	}
}

function updateTool($tid,$tn,$td) {
	global $__RETURN_CODES;
	$qry = "UPDATE tools SET tool_name='".$tn."', description='".$td."' WHERE tool_id = ".$tid;
	$res = doQ($qry);
	return $res;
}

function addActivity($un,$ad,$ty,$tr1,$tr2="",$dv,$wv,$an=""){
	global $__RETURN_CODES,$last_insert_id;
	// check optional params
	$tr2param = ''; if ($tr2!='') {$tr2param = ', tool_id_ref2='.$tr2;}
	$noteparam = ''; if ($an!='') {$noteparam = ', note="'.$an.'"';}
	$qry = "INSERT INTO activity SET user_id_ref=(SELECT user_id FROM users WHERE username='".$un."'), activity_date='".$ad."', type='".$ty."', tool_id_ref1=".$tr1.$tr2param.", difficulty=".$dv.", worth=".$wv.$noteparam;
	$res = doQ($qry);
	if ($last_insert_id > 0) {
		return($last_insert_id);
	} else {
		return $__RETURN_CODES['ACTIVITY EDIT FAIL'];
	}
}

function updateActivity($aid,$dv,$wv,$an) {
	global $__RETURN_CODES;
	$qry = "UPDATE activity SET difficulty=".$dv.", worth=".$wv.", note='".$an."' WHERE activity_id=".$aid;
	$res = doQ($qry);
	return $res;
}

function deleteActivities($aids) {  // a csv list as string
	global $__RETURN_CODES;
	$qry = "DELETE FROM activity WHERE activity_id IN(".$aids.")";
	$res = doQ($qry);
	// return ids
	if ($res>0) { // something got deleted, hopefully everything in the list
		return explode(",",$aids);
	} else {
		return $_RETURN_CODES['QUERY FAIL'];
	}
}

function getUserDateActivity($u,$dt) {
	global $__RETURN_CODES;
	$qry = "SELECT * FROM activity WHERE user_id_ref=(SELECT user_id FROM users WHERE username='".$u."') AND activity_date='".$dt."'";
	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	$act_array = array();
	while ($row=mysql_fetch_assoc($res)) {
		array_push($act_array,$row);
	}
	return $act_array;	
}
function getAggregateActivity($g,$u,$sd,$ed) {
	global $__RETURN_CODES;
	if ($u) {  // user filter trumps group
		$wc = "user_id_ref = ".$u;
	} else {
		if ($g) {$wc = "user_id_ref IN (SELECT user_id FROM users WHERE org = '".$g."') ";}
	}
	if ($sd) {$wc = (($g)?$wc." AND ":"")."activity_date >= '".$sd."' AND activity_date <= '".$ed."'";}
	if (isset($wc)) {$wc = "WHERE ".$wc;} else {$wc="";}
	$qry = "SELECT * FROM activity ".$wc." ORDER BY type DESC, tool_id_ref1 ASC, tool_id_ref2 ASC";

	$res = doQ($qry);
	if (!$res) {
		return $__RETURN_CODES['QUERY FAIL'];
	}
	$act_array = array();
	while ($row=mysql_fetch_assoc($res)) {
		array_push($act_array,$row);
	}
	return $act_array;	
}

function reloadReferrer() {
	global $__REFERRER, $__HOMEPAGE;
	// reloads the referring page (as set by each landing page), or goes home
	if (isset($__REFERRER)) {
		$goto = 'Location: '.$__REFERRER;
	} else {
		$goto = 'Location: '.$__HOMEPAGE;
	}
	header( $goto );
}
?>
