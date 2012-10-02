<?php
/*
Edit one day's work:  User must be logged in to visit this page.

Display all activity for signed-in user for given date (default is current day).
Display menu of tools that can be referenced in an activity.
	- If user had admin privilegs, "ADD A TOOL" is also in menu, quick link to tool_admin.

Adding tools: 
	- select tool from menu, click ADD.  
		* If not already on stage, tool is added, and date-time-stamped, user-ID'd "tool usage" record is added to activity db .
	- drag tool on stage to reposition
	- click on tool name, pop-up appears:
			* user can add/edit comment and difficulty/value ratings
			* user can delete tool, and any connections it has.

Connecting tools:
	- drag a connection from right edge of tool to connect to left edge of another tool, 
		which creates a date-time-stamped, user-ID'd "connection" record to activity db, that references both tools.
	- click on icon at middle of connector, pop-up appears:
			* user can add/edit comment and difficulty/value ratings
			* user can delete connection

*/

require_once("_functions.php");

// must be signed in to edit...
if (!$_SESSION['user']) {
	$_SESSION['status_msg'] = "You must be signed in to edit your UxN journal.";  // this doesn't work
	header ('Location: login.php');
}
$u = $_SESSION['user'];
$tool_array = getTools();

// get the user's activity for the given day...
if (isset($_POST['activity_date'])) {$adate=$_POST['activity_date'];}
if (!isset($adate) and isset($_GET['activity_date'])) {$adate=$_GET['activity_date'];}
if (isset($adate)) {
	$act_date = date('Y-m-d', strtotime($adate));
} else {
	$act_date = date('Y-m-d');
} 

$activities = getUserDateActivity($u,$act_date);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>user_day_journal</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Steve Gano">
	<!-- Date: 2012-04-13 -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
<script src="js/jquery.jsPlumb-1.3.8-all.js"></script>
<script>
var touchUI = navigator.userAgent.match(/iPad/i) != null;

var tbndx = 0;
var dpndx = 0;
var toolnames = new Array();
var activities = new Array();
var sourceEPs = new Array();
var targetEPs = new Array();
var xincr = 300;  //  
var rowincr = 150;
var yincr = 100;
var yplusorminus=20;
var colmax = 3;
var rowndx = 0;
var maxrows;
var rowYinit = 100;
var rowXinit = 50;
var rowY = rowYinit;
var rowX = rowXinit;
var infoleft = '500px';
var infotop = '60px';
var diffColor = new Array("#3C3","#6C3","#CC3","#C63", "#C33");
<?php
foreach($tool_array as $trec) {
	echo "toolnames[".$trec['tool_id']."]=\"".$trec['tool_name']."\";\n";
}
// initialize the model
foreach($activities as $activity) {
	$tid2 = 0;
	if (isset($activity['tool_id_ref2'])) {$tid2=$activity['tool_id_ref2'];}
	echo "activities[".$activity['activity_id']."]= {'user_id_ref':".$activity['user_id_ref'].", 'date': '".$activity['activity_date']."', 'type': '".$activity['type']."', 'tool_id_ref1':".$activity['tool_id_ref1'].", 'tool_id_ref2':".$tid2.", 'difficulty':".$activity['difficulty'].", 'worth':".$activity['worth'].", 'note': '".$activity['note']."', 'source_ep': '', 'target_ep': ''};\n";
}

// set some variables
echo "var uname='".$u."';\n";
echo "var actdate='".$act_date."';\n";
?>
var difficulty_labels = new Array("easy","not bad","so-so","challenging","difficult");
var value_labels = new Array("worthless","marginal","take or leave","valuable","essential");
var display_order = new Array();

function initPaths() {
	// if there is any activity, make the graph for it.
	// do the tools that aren't targets first (so that targets are displayed on the right)
	// how many tools?   (and set a sort order while we're at it)
	var conorder = 1000;
	var ntools = 0; 
	for (var aid in activities) {
		if(activities[aid]['type']=="tool") {
			ntools++;
			// how many connections is this tool the target of, and the source of?
			var tref = activities[aid]['tool_id_ref1'];
			var nt = 0; ns=0;
		 	for (aid2 in activities) {
				if (tref == activities[aid2]['tool_id_ref2']) {nt++; }  // tool is target of this connection (only cons have ref2)
				if (activities[aid2]['type']=="connection"&&tref==activities[aid2]['tool_id_ref1']) {ns++; }  // tool is source
				}
			// Try to make a layout that minimizes tangled connections.
			// locate on left part of stage tools that are source, but not targets; 
			// locate in middle of stage, tools that are both source and targets; 
			// locate on right part of stage: unconnected tools
			// Calculate a weight that tools can be sorted on.
			// (This kinda works, not so great.)
			if (nt+ns==0) {nt=500;} else if (ns==0) {nt=100;}  else {nt = -ns;}
			display_order.push({"aid":aid, "ord":nt});  // 
		} else {
			display_order.push({"aid":aid, "ord":conorder});  // display connections last
		}
	}
	display_order.sort(function (a,b) {if (a['ord'] > b['ord']) {return 1;} else if(a['ord'] < b['ord']){return -1} else {return 0;}});
	
	maxrows = Math.round(ntools/colmax);
	
	for (var i=0;i<display_order.length;i++) {
		var aid = display_order[i]['aid'];
		var activity = activities[aid];
		switch (activity['type']) {
			case 'tool':
				makeTool(aid,activity['tool_id_ref1'],activity['note'],activity['difficulty'],activity['worth']);
				break;
			case 'connection':
				makeConnection(aid,activity['tool_id_ref1'],activity['tool_id_ref2'],activity['note'],activity['difficulty'],activity['worth']);
				break;
		}
	}
}

function makeToolUI() {
	var tid = parseInt($("#tool_menu").val(),10);
	// is an admin adding a tool?
	if (tid=='-1') {
		self.location.href="tool_admin.php";
		return;
	}
	// is tool already in this day's set?
	for (var aid in activities) {
		var activity = activities[aid];
		if (activity['type']=='tool' && activity['tool_id_ref1']==tid) {
			alert("That tool is already on the stage.");
			return;
		}
	}
	// add to database
	$.get("db_action.php", {'action': "insert", 'type': "tool", 'username': uname, 'activity_date': actdate, 'tool_id_ref1': tid, 'tool_id_ref2': "",  'difficulty': 2, 'worth': 2, 'note':""}, function(data,stat) {
		eval("var djson="+data);
		if (djson['status']>0) {
			activities[djson['status']]= djson['activity_record'];	
			makeTool(djson['status'],tid,"<?php echo $__DEFAULT_NOTE ?>",2,2);
		} else {
			updateStatus("Couldn't add tool, code="+djson['status']+", status="+stat);
		}
	});

	
}

function makeTool(aid,tid,tnote,tdiff,tworth) {
	var newTool = toolnames[tid];
	var tbid = "tbndx"+aid.toString();
	var tlid = "tlndx"+aid.toString();
	var tdid = "tdndx"+aid.toString();
	var tsid = "tsndx"+aid.toString();
	var ttid = "ttndx"+aid.toString();
	var innards = "<div class='infobox' id='"+tbid+"' style='display:none; position:absolute; left:"+infoleft+"; top:"+infotop+";'>"+newTool+$("#tool_template").html()+"</div>";
	$("#container").prepend("<div class='dtool' id='"+tdid+"' aid='"+aid+"' atype='tool'><span class='tlabel' id='"+tlid+"'><b>"+newTool+"</b></span></div>"+innards);
	$('#'+tbid+' input:radio[name=dov]').val([tworth]);
	$('#'+tbid+' input:radio[name=dod]').val([tdiff]);
	$('#'+tbid+' textarea').val([tnote]);
	$('#'+tbid+' textarea').change(setDirtyBit);
	$('#'+tbid+' input').change(setDirtyBit);
	$('#'+tbid+' input:button[name=save]').attr('aid',aid);
	$('#'+tbid+' input:button[name=save]').click(saveChanges);
	$('#'+tbid+' input:button[name=delete]').attr('aid',aid);
	$('#'+tbid+' input:button[name=delete]').click(removeActivity);
	
	rowY += yincr+(Math.floor(Math.random()*3.0)-1)*yplusorminus;  // a little noise to loosen grid
	$('#'+tdid).css('left',rowX);
	$('#'+tdid).css('top',rowY);  
	if (++rowndx>=maxrows) {
		rowndx = 0;
		rowY = rowYinit;
		rowX += xincr;
	}

	var tsep = jsPlumb.addEndpoint(tdid, { uuid: tsid }, {
		anchor:"RightMiddle",
		endpoint:["Dot", { width:20, height:20 }],
	  	hoverPaintStyle:{ fillStyle:"orange" },
		maxConnections:-1,		
		connector:[ "Bezier", { curviness: 150 } ], 
		paintStyle: {strokeStyle: diffColor[2], lineWidth: 8 },
		isSource: (!touchUI)
	});
	if (touchUI) {tsep.bind("click", startConnection);}
	activities[aid]['source_ep'] = tsep;
	sourceEPs.push(tsep);
	var ttep = jsPlumb.addEndpoint(tdid, { uuid: ttid }, {
		anchor:"LeftMiddle",
		endpoint:["Rectangle", { width:20, height:20 }],
		maxConnections:-1,
		isTarget: (!touchUI)
	});
	if (touchUI) {ttep.bind("click",finishConnection);}
	activities[aid]['target_ep'] = ttep;
	targetEPs.push(ttep);  // handy
	if (!touchUI) {
		jsPlumb.draggable(tdid);
	} else {
		$("#"+tdid).bind('mouseup', handleTouch);
	}
	
	$("#"+tlid).click(toggleInfobox);
	tbndx++;
}
var conSourceID, conSourceEP;
function startConnection(ep) {
	// if we're clicking the same source again, that's to exit the connection-making
	if (conSourceEP) {
		if (conSourceEP==ep) {cleanUpConnection(conSourceEP);}
		return false;
	}
	conSourceEP = ep;
	conSourceID = ep.elementId;  // source element to be connected
	ep.setPaintStyle({fillStyle: "green"});
	for (var i=0;i<targetEPs.length; i++) {
		targetEPs[i].setPaintStyle({fillStyle: "green"});
		targetEPs[i].setHoverPaintStyle({fillStyle: "orange"});
	}
	for (var i=0;i<sourceEPs.length; i++) {
		if (sourceEPs[i]!=ep) {
			sourceEPs[i].setHoverPaintStyle(null);
		}
	}
	return false;
}
function finishConnection(ep) {
	if (conSourceID) {
		conTargetID = ep.elementId;  // target element to be connected
		if (confirmUniqueConnection(conSourceID,conTargetID)) {
			makeConnectionUI(conSourceID,conTargetID);
		}
		cleanUpConnection();
	}
	return false;
}

function cleanUpConnection(ep) {
	// clean up
	for (var i=0;i<targetEPs.length; i++) {
		targetEPs[i].setPaintStyle({fillStyle: '#456'});
		targetEPs[i].setHoverPaintStyle(null);
	}
	conSourceEP.setPaintStyle({fillStyle: '#456'});
	for (var i=0;i<sourceEPs.length; i++) {
			sourceEPs[i].setHoverPaintStyle({fillStyle: "orange"});
	}
	conSourceID = null;
	conSourceEP = null;
}

function confirmUniqueConnectionUI(ep) {
	var sid = ep.sourceId;
	var tid = ep.targetId;
	if (confirmUniqueConnection(sid,tid)) {
		makeConnectionUI(sid,tid);
	}
	return false;  // because we made the connection ourselves
}
function confirmUniqueConnection(csid,ctid) {
	var aid1 = $("#"+csid).attr("aid");	// activity ID of source tool
	var aid2 = $("#"+ctid).attr("aid"); // activity ID of target tool
	var tid1 = activities[aid1]['tool_id_ref1'];   // tool ID of source tool
	var tid2 = activities[aid2]['tool_id_ref1'];   // tool ID of target tool
	// is connection already in this day's set?
	for (var aid in activities) {
		var activity = activities[aid];
		if (activity['type']=='connection' && activity['tool_id_ref1']==tid1 && activity['tool_id_ref2']==tid2) {
			alert("That connection has already been made.");
			return false;
		}
	}
	return true;
	
}
function makeConnectionUI(csid,ctid) {
	// function to create a new connection via the UI
	// 
	var aid1 = $("#"+csid).attr("aid");   //  activity ID of source tool
	var aid2 = $("#"+ctid).attr("aid");   // activity ID of target tool
	var tid1 = activities[aid1]['tool_id_ref1'];     // tool ID of source tool
	var tid2 = activities[aid2]['tool_id_ref1'];     // tool ID of target tool

	// add to database
	$.get("db_action.php", {'action': "insert", 'type': "connection", 'username': uname, 'activity_date': actdate, 'tool_id_ref1': tid1, 'tool_id_ref2': tid2,  'difficulty': 2, 'worth': 2, 'note':""}, function(data,stat) {
		eval("var djson="+data);
		if (djson['status']>0) {
			activities[djson['status']]= djson['activity_record'];	
			makeConnection(djson['status'],tid1,tid2,"Your comment here...",2,2);
		} else {
			updateStatus("Couldn't add connection, code="+djson['status']+", status="+stat);
		}
	});
}
function makeConnection(aid,tid1,tid2,cnote,cdiff,cworth) {
	// function to put an existing model connection on stage
	var dbid = "dbndx"+aid.toString();
	var dlid = "dlndx"+aid.toString();

	var conname = toolnames[tid1]+" to "+toolnames[tid2];
	var innards = "<div class='infobox' id='"+dbid+"' style='display:none; position:absolute; left:"+infoleft+"; top:"+infotop+";'>"+conname+"<br />"+$("#tool_template").html()+"</div>";
	$("#container").prepend(innards);
	var dlabel = "<div class='dlabel' id='"+dlid+"' aid='"+aid+"' atype='connection'><b> |||| </b></div>";

	$('#'+dbid+' input:radio[name=dov]').val([cworth]);
	$('#'+dbid+' input:radio[name=dod]').val([cdiff]);
	$('#'+dbid+' textarea').val([cnote]);
	$('#'+dbid+' textarea').change(setDirtyBit);
	$('#'+dbid+' input').change(setDirtyBit);
	$('#'+dbid+' input:button[name=save]').attr('aid',aid);
	$('#'+dbid+' input:button[name=save]').click(saveChanges);
	$('#'+dbid+' input:button[name=delete]').attr('aid',aid);
	$('#'+dbid+' input:button[name=delete]').click(removeActivity);
	//  need to get the tool elements to connect
	var aid1 = aid2 = 0;
	for (var taid in activities) {
		activity = activities[taid];
		if (activity['type'] != 'tool') {continue;}
		if (activity['tool_id_ref1'] == tid1) {
			aid1 = taid; 
			continue;
			}
		if (activity['tool_id_ref1'] == tid2) {
			aid2 = taid; 
			continue;
			}
	}

	var sel = activities[aid1]['source_ep'];
	var tel = activities[aid2]['target_ep'];
	var con = jsPlumb.connect({source:sel, target:tel, connector:[ "Bezier", { curviness: 150 } ], paintStyle: {strokeStyle: diffColor[cdiff], lineWidth: 8 }, scope: "aid"+aid, deleteEndpointsOnDetach:false });

	con.setLabel({label: dlabel, cssClass: "dpath", id: dlid});
	$("#"+dlid).click(toggleInfobox);

	$("._jsPlumb_connector").css('z-index',-1);  // send connectors to the back
}


var visibleInfobox;
var unsavedChanges = false;
function toggleInfobox() {
	// hacky..ids are same except for second letter; change "b" to "l"
	var boxidchar = $(this).attr('id').split("");
	boxidchar[1]="b";
	var boxid = "#"+boxidchar.join("");
	
	if ($(boxid).is(":visible")) {
		if (unsavedChanges) {
			if (!confirm("You have unsaved changes. Are you sure you want to continue?\n\nClick 'OK' to discard changes and continue.")) {
				return;
			}
		}
		$(boxid).hide();
		//$(boxid).css("z-index",-1);
		visibleInfobox = false;
	} else {
		// only one visible at a time
		if (visibleInfobox && (boxid != visibleInfobox)) {
			if (unsavedChanges) {
				if (!confirm("You have unsaved changes. Are you sure you want to continue?\n\nClick 'OK' to discard changes and continue.")) {
					return;
				}
			}
			$(visibleInfobox).hide();
			//$(visibleInfobox).css("z-index",-1);
		}
		$(boxid).show();
		$(boxid).css("z-index",1000);
		visibleInfobox = boxid;
	}
}



function updateStatus(msg) {
	$("#status_msg").html(msg);
	$("#status_msg").show();
}

function setDirtyBit(ev) {
	if (visibleInfobox) {
		$(visibleInfobox+" input:button[name=save]").attr('disabled',false);
		$(visibleInfobox+" input:button[name=save]").attr('value',"SAVE");
		unsavedChanges = true;
	}
}

function saveChanges(ev) {
	var aid = $(ev.target).attr('aid');
	// update the editable items—note, difficulty, worth—in the activity array and the database
	// get the form, based on activity ID
	var bid = "#"+(activities[aid]['type']=="tool"?"t":"d")+"bndx"+aid.toString();
	var n = $(bid+' textarea').val().replace(/(["'])/g, '\\$1').replace(/[\n]/g, ' ');
	var w = $(bid+' input:radio[name=dov]:checked').val();
	var d = $(bid+' input:radio[name=dod]:checked').val();
	$.get("db_action.php", {'action': "update", 'activity_id': aid, 'difficulty': d, 'worth': w, 'note': n}, function(data,stat) {
		eval("var djson="+data);
		if (djson['status']>0) {
			var daid = djson['activity_id'];
			activities[daid]['difficulty']=djson['difficulty'];	
			activities[daid]['worth']=djson['worth'];	
			activities[daid]['note']=djson['note'];	
			// update the element color
			if (activities[daid]=='connection') {
				var dlid = "dlndx"+daid.toString();
				var con = jsPlumb.select({scope:"aid"+daid}).setPaintStyle({ strokeStyle:diffColor[djson['difficulty']], lineWidth: 8 });
				jsPlumb.repaintEverything();
			}
		} else {
			updateStatus("Couldn't update item, code="+djson['status']+", status="+stat);
		}
		if (visibleInfobox) {
			$(visibleInfobox+" input:button[name=save]").attr('disabled',true);
			$(visibleInfobox+" input:button[name=save]").attr('value',"SAVED");
		};
		unsavedChanges = false;
	});
}

function removeActivity(ev) {
	var aid2del = $(ev.target).attr('aid');
	var activity2del = activities[aid2del];
	
	// confirm
	var atype = activity2del['type'];
	var tn1 = toolnames[activity2del['tool_id_ref1']];
	if (atype=="connection") {
		var tn2 = toolnames[activity2del['tool_id_ref2']];
	}
	var msg = "Are you sure you want to remove the "+atype+" "+tn1+((atype=='tool')?" and all its connections":" to "+tn2)+"?";
	if (!confirm(msg)) {return;}
	
	// remove from db, from model, from stage
	// if removing a tool, get its toolID to find connections to and from, remove those first
	var aids2del = new Array(aid2del);
	var toolid2del = activity2del['tool_id_ref1'];
	if (atype=="tool") {
		for (var aid in activities) {
			activity = activities[aid];
			if (activity['type']!='connection') {continue;}
			if (activity['tool_id_ref1']==toolid2del || activity['tool_id_ref2']==toolid2del) {
				aids2del.push(aid);
			}
		}
	}
	var aids2delclause = aids2del.join(",");
	$.get("db_action.php",{'action': "delete", 'type': atype, 'activity_ids': aids2delclause}, function(data,stat) {
		eval("var djson="+data);
		if (djson['status']>0) {
			// if the deletion was a tool, delete its endpoints and then its DOM elements (the item and its infobox), and the connection infoboxes
			if (djson['type']=="tool") {
				var activity = activities[aid2del];
				jsPlumb.deleteEndpoint(activity['source_ep']);
				jsPlumb.deleteEndpoint(activity['target_ep']);
				$("#tbndx"+aid2del.toString()).detach();	
				$("#tdndx"+aid2del.toString()).detach();
				for (var i=1; i<aids2del.length; i++) {
					$("#dbndx"+aids2del[i].toString()).detach();					
				}	
			} else {  // otherwise we have to delete the individual connection, and its infobox
				jsPlumb.select({scope:"aid"+aid2del}).detach();
				$("#dbndx"+aid2del.toString()).detach();					
			}
			// finally, remove activities from model
			for (var i=0; i<aids2del.length; i++) {
				delete activities[aids2del[i]];
			}
		}
	})
}
var touchUIElement;
var touchUIElementOffset;
function handleTouch(e) {
	if (touchUIElement) return false;
	if (($(e.target).attr('id')).substr(0,5)=='tdndx') {
		touchUIElement = $(e.target).attr('id');
		touchUIElementOffset = {dx: $(e.target).position().left-e.pageX, dy: $(e.target).position().top-e.pageY};
		$("#"+touchUIElement).unbind('mouseup',handleTouch);
		$("#"+touchUIElement).css("background-color","#ffcccc");
		$('html').bind('mouseup', relocatetouchUIElement);
	}
	return false;
}
function relocatetouchUIElement(e) {
	$('html').unbind('mouseup',relocatetouchUIElement);
	if (!touchUIElement) return;
	
	$("#"+touchUIElement).css('left',e.pageX+touchUIElementOffset.dx);
	$("#"+touchUIElement).css('top',e.pageY+touchUIElementOffset.dy);
	$("#"+touchUIElement).css('background-color','#ffffcc');
	jsPlumb.repaint(touchUIElement);
	$("#"+touchUIElement).bind('mouseup',handleTouch);
	touchUIElement = null;
	e.stopPropagation();
	return false;
}

jsPlumb.ready(function() {
	jsPlumb.setRenderMode(jsPlumb.CANVAS);
	initPaths();
	if (!touchUI) {
		jsPlumb.bind("jsPlumbConnection", makeConnectionUI);
		jsPlumb.bind("beforeDrop", confirmUniqueConnectionUI);
	}
});

</script>

<style>
.infobox {
	width: 350px;
	height: auto; //175px;
	padding: 5px;
	font-size: 10px;
	font-weight: bold;
	border: 2px gray solid;
	position: absolute;
	background-color: white;
	cursor: move;
}
.dtool {
	cursor: move;
	width: 200px;
	height: 20px;
	padding: 3px;	
	background: #ffffcc;
	border: 2px, dotted, gray;
	font-weight: bold;
	position: absolute;
	/* text-align: center; */
}
.tlabel {
	cursor: pointer;
	width: 100%;
	padding-left: 15px;
}
.dpath {
	cursor: pointer;
	background: #ffffcc;
	border: 2px dotted gray;
	padding: 3px;
	font-weight: bold;
	height:20px;
	opacity:0.6;
}
#headline {
	font-weight: bold;
	font-size: 18px;
	margin: 5px 0px;
}
#instructions {
	width:100%;
	font-size:14px;
}
#instructions ul {
	margin: 0px;
}
#status_msg {
	width: 100%;
	color: #D33;
	font-weight:bold;
	display:none;
	margin: 5px;
}
#container {
	width: 100%;
	height: 100%;
	background: #ffcccc;
}
#menus {
	margin: 5px;
}
</style>
<body>
<?php include('header.php'); ?>
<div id="headline">What tools did you use today? (<?php echo date('M j, Y',strtotime($act_date)) ?>)</div>
<div id="instructions" style="width:100%;" >
<div style="float:left;">
<ul><li>Select tool from menu and click "ADD".</li>
	<li>If one tool's output was the input to another tool, connect them:<br />
		Click/drag dot on right of source tool to square on left of target tool.</li>
</ul>
</div>
<div style="float:left;">
<ul>
	<li>Add a comment or rating about a tool by clicking on its name.</li>
	<li>Add a comment or rating about a connection by clicking on its [||||] icon.</li>
</ul>
</div>
</div>
<br clear="all" />
<div id="status_msg"></div>
<hr />
<form id="menus">
<select id="tool_menu">
<?php
foreach($tool_array as $trec) {
	echo "<option value='".$trec['tool_id']."'>".$trec['tool_name']."</option>";
}
if (userRole()=="admin") {
	echo "<option value='-1'>++ Add a Tool++</option>";
}
?>
</select>
<input type="button" onClick="makeToolUI()" value="ADD"/>
</form>
<div id="container">
</div>

<div id="tool_template" style="display:none;">
	<form>
	<textarea name="note" rows="6" cols="45"></textarea><br />
	<table border="0" width="100%">
	<tr><td><span style="font-weight:bold; font-variant: small-caps;">difficulty:</span></td>
	<td style="text-align:right">easy</td><td><input type="radio" name="dod" value="0" selected> <input type="radio" name="dod" value="1"> <input type="radio" name="dod" value="2"> <input type="radio" name="dod" value="3"> <input type="radio" name="dod" value="4"></td><td>difficult</td><td><input type="button" name="save" value="SAVE" disabled></td></tr>
	<tr><td><span style="font-weight:bold; font-variant: small-caps;">value:</span></td>
	<td style="text-align:right">worthless</td><td><input type="radio" name="dov" value="0" selected> <input type="radio" name="dov" value="1"> <input type="radio" name="dov" value="2"> <input type="radio" name="dov" value="3"> <input type="radio" name="dov" value="4"></td><td>essential</td><td align="right"><input type="button" name="delete" value="DELETE" ></td></tr>
	</table>
	</form>
</div>


</body>
</html>
