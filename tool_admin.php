<?php

/*
Add or edit a tool in the tool database, which is the tool list offered on the user_day_journal page.
Must be logged in with admin privileges to visit this page.

*/

require_once("_functions.php");
$tid = -1;
if (isset($_POST['tid'])) {$tid=$_POST['tid'];}

// user must be logged in admin
$u = $_SESSION['user'];
if (userRole($u)!='admin') {
	reloadReferrer();
}

// get the current list of tools
$tool_array = getTools();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>UxN Tool Admin</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Steve Gano">
	<!-- Date: 2012-04-13 -->

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>

<script>
var unsavedChanges = false;
function refreshToolInput() {
	if (unsavedChanges) {
		if (!confirm("You have unsaved changes. Are you sure you want to continue?\n\nClick 'OK' to discard changes and continue.")) {
			return;
		}
	}
	var tid = $("#tool_menu option:selected").val();
	if (tid!=-1) {  // edit tool, get current values
		$.get("db_action.php", {'action': "tool_get", 'tool_id': tid}, function(data,stat) {
				eval("var djson="+data);
				if (djson['status']>0) {
					$("#tool_name").val(djson['tool_name']);
					$("#tool_description").val(djson['description']);
					$("#tool_edit input:button").val("UPDATE");
				} else {
					alert("Couldn't get info for "+("#tool_menu option:selected").text()+" \nstatus="+stat);
				}
			});
		} else {  // add tool
			$("#tool_name").val("");
			$("#tool_description").val("");			
			$("#tool_edit input:button").val("ADD");
		}
	setDirtyBit(false);
}
function updateToolInfo() {
	var tn = $.trim($("#tool_name").val());
	if (tn=="") {
		alert ("Tool name can't be blank.");
		return;
	}
	var td = $.trim($("#tool_description").val());
	var tid = $("#tool_menu option:selected").val();
	if (tid!=-1) {  // edit tool, get current values
		$.get("db_action.php", {'action': "tool_update", 'tool_id': tid, 'tool_name': tn, 'description': td}, function(data,stat) {
				eval("var djson="+data);
				var tn = djson['tool_name'];
				if (djson['status']>0) {
					setDirtyBit(false);
					// in case name changed
					$("#tool_menu option:selected").text(tn);
				} else {
					alert("Couldn't update "+tn+" \nstatus="+stat);
				}
			});
		} else {  // add tool
			$.get("db_action.php", {'action': "tool_add", 'tool_name': tn, 'description': td, 'added_by': "<?php echo $u ?>"}, function(data,stat) {
					eval("var djson="+data);
					if (djson['status']>0) {
						setDirtyBit(false);
						// add to menu
						$("#tool_menu option:selected").before("<option value='"+djson['status']+"' selected>"+tn+"</option>");
					} else {
						alert("Couldn't add "+tn+" \nstatus="+stat);
					}
				});
		}
}
function setDirtyBit(state) {
	if (state==null) state = true;
	$("#tool_edit input:button").attr('disabled',!state);
	unsavedChanges = state;
}

$(document).ready(function () {
	$("#tool_name").change(setDirtyBit);
	$("#tool_description").change(setDirtyBit);
	$("#tool_menu").change(refreshToolInput);
	$("#tool_edit input:button").click(updateToolInfo);
}
)
</script>
</head>
<body>
<?php include('header.php'); ?>
<h3>UxN Tool Admin</h3>
<?php 
/* 
Display a menu of existing tools. Invite user to add a tool.

If a tool is selected from menu, display name & description in edit boxes, display Update button, disabled until a change.
( TBD, "delete" button:  if there are no activities that reference that tool, no problem, 
	but what happens to activities that reference a tool that is deleted?  Maybe delete just sets a flag that removes it from
	the tool menu on user_day_journal?  OR, delete with extreme prejudice: after confirm, delete all activities that use that tool.)

*/
?>
<form id="tool_edit" method="POST">
Select a tool: <select id="tool_menu">
<?php
foreach($tool_array as $trec) {
	echo "<option value='".$trec['tool_id']."' ".(($tid==$trec['tool_id'])?"selected":"").">".$trec['tool_name']."</option>";
}
if (userRole()=="admin") {
	echo "<option value='-1' ".(($tid==-1)?"selected":"").">++ Add a Tool++</option>";
}
?>
</select><br />
Tool name: <input id="tool_name" type="text" size="40" onInput='setDirtyBit();' /><br />
Tool description (optional):<br /><textarea id="tool_description" cols="40" rows="5" onInput='setDirtyBit();' ></textarea><br />
<input type="button" disabled value="ADD"/>
</form>
<p>NOTE: Currently it is not possible to remove tools once they are added.</p>
</body>
</html>