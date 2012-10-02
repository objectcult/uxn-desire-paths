<?php
/*
Display an aggregate of user's activity, optionally filtered by group, 
	and optionally bracketed by a start date and end date

* parse the URL params to get optional group filter and date range
* retrieve all the activity records sorted by type, tool_id_ref1, tool_id_ref2

* run through the results, accumulating totals for
	- occurrences of each tool
	- occurrences of each tool to tool connection
creating an array of

data["tool"] = {usecount: n, destcount: n, sourcecount: n, connections: array["desttool"]=concount }  ???

so we can sort data by increasing sourcecount, decreasing dest count
then display in column-major order, so tools that are sources but not targets line up along left edge


*/

require_once("_functions.php");
require_once("_helpers.php");

$maxtoolsize = 70;  // a radius
$mintoolsize = 15;
$minconsize = 3; // a stroke width
$maxconsize = 25;
$diffcolors = array(array("red"=>0,"green"=>204,"blue"=>0),array("red"=>204,"green"=>204,"blue"=>0),array("red"=>204,"green"=>0,"blue"=>0));
$worthcolors = array(array("red"=>65, "green"=>42, "blue"=>0),array("red"=>255,"green"=>204,"blue"=>49));

// get the activity for the given date range and group...
// to specify a date range, must at least specify start date; end date defaults to today
if (isset($_POST['sd'])) {$sdate=$_POST['sd'];} // start date
if (!isset($sdate) and isset($_GET['sd'])) {$sdate=$_GET['sd'];}
if (isset($_POST['ed'])) {$edate=$_POST['ed'];} // end date
if (!isset($edate) and isset($_GET['ed'])) {$edate=$_GET['ed'];}
if (isset($sdate)) {
	$act_sdate = date('Y-m-d', strtotime($sdate));
	if(isset($edate)) {
		$act_edate = date('Y-m-d', strtotime($edate));
	} else {
		$act_edate = date('Y-m-d');
	}
} else {
	$act_sdate = false;
	$act_edate = false;
}

if (isset($_POST['g'])) {$group=$_POST['g'];} // group to retrieve
if (!isset($group)) { 
	if (isset($_GET['g'])) {
		$group=$_GET['g'];
	} else {
		$group=false;
		}
}

$admin_user = ((isset($_SESSION['user'])) && userRole($_SESSION['user'])=='admin');
if ($admin_user) {
	if (isset($_POST['u'])) {$userid=$_POST['u'];} // individual user to retrieve
	if (!isset($userid)) { 
		if (isset($_GET['u'])) {
			$userid=$_GET['u'];
		}
	}
}
if (!isset($userid)) { $userid = false; }

$activities = getAggregateActivity($group,$userid,$act_sdate,$act_edate);

if (count($activities)>0) {
	$tools = array();

	$maxtooluse = 0;
	$mintooluse = 100000;
	$maxtooldiff = 0;
	$mintooldiff = 10000;
	$maxtoolworth = 0;
	$mintoolworth = 10000;
	$maxconuse = 0;
	$minconuse = 10000;
	$maxcondiff = 0;
	$mincondiff = 10000;
	$maxconworth = 0;
	$minconworth = 10000;
	$total_usecount = 0;
	$ntools = 0;
	$ncons = 0;
	foreach($activities as $activity) {
		$t1 = $activity['tool_id_ref1'];
		if (!array_key_exists($t1,$tools)) {$tools[$t1] = array("usecount"=>0, "difftotal"=>0, "diffavg"=>0, "worthtotal"=>0, "worthavg"=>0,  "targets"=>array(), "sourcecount"=>0, "comments"=>array());}
		if ($activity['type']=="connection") {
			$t2 = $activity['tool_id_ref2'];
			if (!array_key_exists($t2,$tools)) {$tools[$t2] = array("usecount"=>0, "difftotal"=>0, "diffavg"=>0, "worthtotal"=>0, "worthavg"=>0, "targets"=>array(), "sourcecount"=>0, "comments"=>array());}
			$tools[$t2]['sourcecount']++;
			if (!array_key_exists($t2,$tools[$t1]['targets'])) {$tools[$t1]['targets'][$t2]=array("concount"=>0, "difftotal"=>0, "diffavg"=>0, "worthtotal"=>0, "worthavg"=>0, "comments"=>array());}
			$tools[$t1]['targets'][$t2]['concount']++;
			$tools[$t1]['targets'][$t2]['difftotal'] += $activity['difficulty'];
			$tools[$t1]['targets'][$t2]['worthtotal'] += $activity['worth'];
			if ($activity['note'] && $activity['note']!="" && $activity['note']!=$__DEFAULT_NOTE) $tools[$t1]['targets'][$t2]['comments'][]=$activity['note'];
			$ncons++;
		} else {
			$tools[$t1]['usecount']++;
			$total_usecount++;
			$tools[$t1]['difftotal'] += $activity['difficulty'];
			$tools[$t1]['worthtotal'] += $activity['worth'];
			if ($activity['note'] && $activity['note']!="" && $activity['note']!=$__DEFAULT_NOTE) $tools[$t1]['comments'][]=$activity['note'];
			$ntools++;
		}		
	}
	// find the ranges for tool and connection ratings
	foreach ($tools as &$tool) {
		// set the average 
		$tool['diffavg'] = $tool['difftotal']/$tool['usecount'];
		$tool['worthavg'] = $tool['worthtotal']/$tool['usecount'];
		$mintooluse = min($mintooluse, $tool['usecount']);
		$maxtooluse = max($maxtooluse, $tool['usecount']);
		$maxtooldiff = max($maxtooldiff, $tool['diffavg']);
		$mintooldiff = min($mintooldiff, $tool['diffavg']);
		$maxtoolworth = max($maxtoolworth, $tool['worthavg']);
		$mintoolworth = min($mintoolworth, $tool['worthavg']);
		foreach ($tool['targets'] as &$target) {
			$target['diffavg'] = $target['difftotal']/$target['concount'];
			$target['worthavg'] = $target['worthtotal']/$target['concount'];
			$minconuse = min($minconuse, $target['concount']);
			$maxconuse = max($maxconuse, $target['concount']);
			$maxcondiff = max($maxcondiff, $target['diffavg']);
			$mincondiff = min($mincondiff, $target['diffavg']);
			$maxconworth = max($maxconworth, $target['worthavg']);
			$minconworth = min($minconworth, $target['worthavg']);
		}
	}
	// avoid div by zero in scaling arithmetic
	if ($maxtooluse==$mintooluse) {$mintooluse = $maxtooluse-1;} // so $maxtooluse - $mintooluse == 1
	if ($maxtooldiff==$mintooldiff) {$mintooldiff = $maxtooldiff-1;} // etc...
	if ($maxtoolworth==$mintoolworth) {$mintoolworth = $maxtoolworth-1;} 
	if ($maxconuse==$minconuse) {$minconuse = $maxconuse-1;}
	if ($maxcondiff==$mincondiff) {$mincondiff = $maxcondiff-1;} 
	if ($maxconworth==$minconworth) {$minconworth = $maxconworth-1;} 

	/* adjust usecount for connections, for testing, to see more variety...
	$maxconuse = 25;
	foreach ($tools as $tndx1 => $tool) {
		foreach ($tool['targets'] as $tndx2 => $target) {
			$tools[$tndx1]['targets'][$tndx2]['concount'] = rand($minconuse, $maxconuse);
		}
	}
	*/

	$stats = $ntools." tools, usage range: (".$mintooluse." - ".$maxtooluse.",) difficulty range: (".$mintooldiff." - ".$maxtooldiff."), value range: (".$mintoolworth." - ".$maxtoolworth.")<br />";
	$stats .= $ncons." connections, difficulty range: (".$mincondiff." - ".$maxcondiff."), value range: (".$minconworth." - ".$maxconworth.")<br />";
	//echo $stats;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>desire paths</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Steve Gano">
	<!-- Date: 2012-04-13 -->
	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/base/jquery-ui.css"></link>
	<link rel="stylesheet" type="text/css" href="css/jquery.tooltip.css"></link>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
<script src="js/jquery.jsPlumb-1.3.8-all.js"></script>
<script src="js/jquery.tooltip.js"></script>
<script>
var touchUI = navigator.userAgent.match(/iPad/i) != null;
var tools = new Array();
var connections = new Array();
var toolnames = new Array();

// vars & constants for screen geometry
var xincr = 200;  //  
var rowincr = 150;
var yincr = 100;
var yplusorminus=20;
var colmax = 5;
var rowndx = 0;
var maxrows;
var rowYinit = 100-yincr;
var rowXinit = 50;
var rowY = rowYinit;
var rowX = rowXinit;
var infoleft = '500px';
var infotop = '60px';

function filterAggregate() {
	// user has perhaps set filters, and requested a redraw
	var delim = "?";
	var sdate = $("#sdatepicker").val();
	if (sdate!="") {sdate = delim+"sd="+sdate; delim = "&";}
	var edate = $("#edatepicker").val();
	if (edate!="") {edate = delim+"ed="+edate; delim = "&";}
	var group = $("#selectg").val();
	if (group!="") {group = delim+"g="+group; delim = "&";}
<?php
	if ($admin_user) {
		echo "var user = $('#selectu').val();\n";
		echo "if (user!='') {user = delim+'u='+user; delim = '&';}\n";
	} else {
		echo "var user = '';\n";
	}
?>	
	self.location.href="aggregate.php"+sdate+edate+group+user;
	//alert ("aggregate.php"+sdate+edate+group+user);
}

function resetFilter() {
	$("#sdatepicker").val("");
	$("#edatepicker").val("");
	$("#selectg").val("");
<?php
	if ($admin_user) {
		echo "$('#selectu').val('');\n";
	}
?>
}
<?php
echo "var mintooluse = ".$mintooluse.";\n";
echo "var maxtooluse = ".$maxtooluse.";\n";
echo "var maxtooldiff = ".$maxtooldiff.";\n";
echo "var mintooldiff = ".$mintooldiff.";\n";
echo "var maxtoolworth = ".$maxtoolworth.";\n";
echo "var mintoolworth = ".$mintoolworth.";\n";
echo "var minconuse = ".$minconuse.";\n";
echo "var maxconuse = ".$maxconuse.";\n";
echo "var maxcondiff = ".$maxcondiff.";\n";
echo "var mincondiff = ".$mincondiff.";\n";
echo "var maxconworth = ".$maxconworth.";\n";
echo "var minconworth = ".$minconworth.";\n";

if (count($activities)>0) {
	$tarray = getTools();
	$tool_array = array();
	foreach($tarray as $trec) {
		$tool_array[$trec['tool_id']] = $trec['tool_name'];
		echo "toolnames[".$trec['tool_id']."]='".$trec['tool_name']."';\n";
	}
	// make javascript data table
	foreach ($tools as $tid => $tool) {
		$toolsize = $mintoolsize + ($maxtoolsize-$mintoolsize)*(($tool['usecount']-$mintooluse)/($maxtooluse-$mintooluse));
		$normdiff = (($tool['diffavg']-$mintooldiff)/($maxtooldiff-$mintooldiff));
		$diffcolor = colorRangeValue($diffcolors,$normdiff,true);
		$normworth = (($tool['worthavg']-$mintoolworth)/($maxtoolworth-$mintoolworth));
		$worthcolor = colorRangeValue($worthcolors,$normworth,true);
		echo "tools[".$tid."]=({'toolid': ".$tid.", 'usecount': ".$tool['usecount'].", 'radius':  ".$toolsize.", 'diffcolor': '".$diffcolor."', 'difficulty': ".$normdiff.", 'diffavg': ".$tool['diffavg'].", 'worthcolor': '".$worthcolor."', 'worth': ".$normworth.", 'worthavg': ".$tool['worthavg'].", 'targetcount': ".count($tool['targets']).", 'sourcecount': ".$tool['sourcecount'].", 'source_ep': 0, 'target_ep': 0, 'comments': ".json_encode($tool['comments'])."});\n";
		foreach ($tool['targets'] as $t2id => $target) {
			$consize = $minconsize + ($maxconsize-$minconsize)*(($target['concount']-$minconuse)/($maxconuse-$minconuse));
			$normdiff = (($target['diffavg']-$mincondiff)/($maxcondiff-$mincondiff));
			$diffcolor = colorRangeValue($diffcolors,$normdiff,true);
			$normworth = (($target['worthavg']-$minconworth)/($maxconworth-$minconworth));
			$worthcolor = colorRangeValue($worthcolors,$normworth,true);
			echo "connections.push({'source': ".$tid.", 'target': ".$t2id.", 'usecount': ".$target['concount'].", 'linewidth': ".$consize.", 'diffcolor': '".$diffcolor."', 'difficulty': ".$normdiff.", 'diffavg': ".$target['diffavg'].", 'worthcolor': '".$worthcolor."', 'worth': ".$normworth.", 'worthavg': ".$target['worthavg'].", 'comments': ".json_encode($target['comments'])."});\n";
		}
	}
}
?>


function drawToolMarker(tdiv,tcanvas,tool) {
	var context = tcanvas.getContext("2d");
	//var centerX = tcanvas.width / 2;
	//var centerY = tcanvas.height / 2;
	var radius = tool['radius'];
	var twid = (radius+Math.max(5,radius/10))*2;

	// set up tool label params, find max width
	context.font = "bold 12px Arial";
	context.textAlign = "center";
	context.textBaseline = "middle";
	var toolwords = toolnames[tool['toolid']].split(" "); // doesn't work for breaking on space...   .replace(/ /gi,'\n');
	for (var i=0; i<toolwords.length; i++) {
		twid = Math.max(twid,context.measureText(toolwords[i]).width);
		}

	$(tcanvas).attr('width',twid);
	$(tcanvas).attr('height',twid);
	var centerX = centerY = twid/2;

	// tool marker
	context.beginPath();
	context.arc(centerX, centerY, radius, 0, 2 * Math.PI, false);
	context.fillStyle = tool['diffcolor'];
	context.fill();
	context.lineWidth = Math.max(5,radius/10);
	context.strokeStyle = tool['worthcolor'];
	context.stroke();
	// reset tool label params
	context.font = "bold 12px Arial";
	context.textAlign = "center";
	context.textBaseline = "middle";
	context.fillStyle = "#333";
	for (var i=0; i<toolwords.length; i++) {
		context.fillText(toolwords[i],centerX,centerY+(i*14));
	}
};

function drawKey(kcanvas) {
	var diffcolors = new Array("#0C0","#CC0","#C00");   // from php diffcolors above
	var worthcolors = new Array("#412A00","#BDA231","#FFCC31");   // from php worthcolors above

	var context = kcanvas.getContext("2d");
	context.font = "bold 12px Arial";
	context.textBaseline = "middle";
	
	var ky = kx = 20;
	var dlabel = "difficulty: easy ";
	context.fillText(dlabel,kx,ky);
	kx += context.measureText(dlabel).width+25;
	for (var i=0;i<diffcolors.length;i++) {
		context.beginPath();
		context.arc(kx,ky,10,0, 2 * Math.PI, false);
		context.fillStyle = diffcolors[i];
		context.fill();
		context.closePath();
		kx += 30;
	}
	context.fillStyle = "black";
	context.fillText("difficult",kx-10,ky);

	ky = 50; kx = 20;
	var wlabel = "value: not much ";
	context.fillText(wlabel,kx,ky);
	kx += context.measureText(wlabel).width+20;
	for (var i=0;i<worthcolors.length;i++) {
		context.beginPath();
		context.arc(kx,ky,10,0, 2 * Math.PI, false);
		context.lineWidth = 5;
		context.strokeStyle = worthcolors[i];
		context.stroke();
		context.closePath();
		kx += 30;
	}
	context.fillStyle = "black";
	context.fillText("essential",kx-10,ky);
}

/* here's the logic table: 
	    B(1) |    no T no S  |   no T but S  |   T no S     |   T and S   
A(-1)        |               |               |              |             
no T no S    |        0      |      1        |    1         |      1      
no T but S   |       -1      | B'sS - A'sS   |    1         |      1      
T no S       |       -1      |     -1        | B'sT - A'sT  |     -1      
T and S      |       -1      |     -1        |    1         | B'sT - A'sT 

traverse that table left to right, top to bottom to get the following linear array;
A defines high order 2 bits, B low order 2 bits of the index into the table
*/
var toolSortOrder = new Array(0,1,1,1,-1,'sourcecount',1,1,-1,-1,'targetcount',-1,-1,-1,1,'targetcount');

function drawAggregate() {
	// draw the tools
	// first sort the tool array so that leftmost are tools that are not targets but have targets, then tools that are not targets but have no targets, then tools that are targets and have targets, then tools that are targets but have no targets
	var display_order = new Array();  // indirect array to sort tools, while preserving indices
	for (var tndx in tools) {
		tool = tools[tndx];
		display_order.push({"tid":tndx, "targetcount": tools[tndx]['targetcount'], "sourcecount": tools[tndx]['sourcecount'] });
	}

	display_order.sort(function(a,b) {
		// make a bit string index that captures state of each factor
		// A has targets | A has sources | B has targets | B has sources
		var bs = (a['targetcount']>0)?8:0;
		bs += (a['sourcecount']>0)?4:0;
		bs += (b['targetcount']>0)?2:0;
		bs += (b['sourcecount']>0)?1:0;
		var ret = toolSortOrder[bs];
		if (isNaN(ret)) {  // 
			ret = b[ret]-a[ret];
		}
		return ret;
	});
	
	maxrows = Math.round(display_order.length/colmax);
	
	for (var i=0;i<display_order.length; i++) {
		var tndx = display_order[i]['tid'];
		var tool = tools[tndx];
		var tpl = $("#proto").html();
		var newToolId = "tool"+tool['toolid'];
		var $newTool = $('<div class="tool_template" id="'+newToolId+'">'+tpl+'</div>');
		$("#container").append($newTool);
		$('#'+newToolId+' .tool_label').html(toolnames[tool['toolid']]);
		var newCanvasId = "canvas"+tool['toolid'];
		$('#'+newToolId+' canvas').attr('id',newCanvasId);
		var tcanvas = document.getElementById(newCanvasId);
		//drawToolMarker(tcanvas,tool);
		var tdiv = document.getElementById(newToolId);
		drawToolMarker(tdiv,tcanvas,tool);


		tool['source_ep'] = jsPlumb.addEndpoint(newToolId, {
			anchor:[0.5,0.5,0,0],
			endpoint:["Blank"],
			connector: "Bezier",
			maxConnections:-1
		});
		tool['target_ep'] = jsPlumb.addEndpoint(newToolId, {
			anchor:[0.5,0.5,0,0],
			endpoint:["Blank"],
			connector: "Bezier",
			maxConnections:-1
		});


		var yspacer = tool['radius']*2+tool['radius']/5+50;
		rowY += Math.max(yincr,yspacer)+(Math.floor(Math.random()*3.0)-1)*yplusorminus;  // a little noise to loosen grid
		$newTool.css('left',rowX);
		$newTool.css('top',rowY);  
		if (++rowndx>=maxrows) {
			rowndx = 0;
			rowY = rowYinit;
			rowX += xincr;
		}
		if (!touchUI) {
			jsPlumb.draggable(newToolId);
		} else {
			$("#"+newToolId).bind('mouseup', handleTouch);
		}
		
	}
	
	// make connections
	for (var cndx in connections) {
		var connection = connections[cndx];

		var con = jsPlumb.connect({
			anchors:["Center","Center"],
			//connector: "Flowchart",   // looks weird   
			//connector:[ "Bezier", { curviness:50 } ],   // DOESN'T WORK with anchor...
			endpointsOnTop: false,
			endpoint: "Blank",
			source:"tool"+connection['source'],
		   	target:"tool"+connection['target'],		   	
		   	paintStyle:{ 
				lineWidth:connection['linewidth'],
				strokeStyle:connection['diffcolor'],
				outlineWidth:2,
				outlineColor:connection['worthcolor']
			},
		   	hoverPaintStyle: {strokeStyle: "#666"},
	        	
		   	overlays : [["PlainArrow", {
		   					//cssClass:"l1arrow",
						   	paintStyle:{ 
								lineWidth:2,
								strokeStyle:connection['worthcolor']
							},
			   				location:0.5, width:(Math.max(15,connection['linewidth']*2))
   						}]]
			
		});
		$(con['canvas']).attr('id','con'+cndx);
		
	}
	$(".tool_template").css('z-index',100);  // bring tool to front
	$("._jsPlumb_connector").css('z-index',10);  // send connectors to the back
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

function toggleKey() {
	$("#key").left = $("#keytoggle").left;
	$("#key").top = $("#keytoggle").bottom;
	$("#key").fadeToggle(100);	
}

var visibleComment;
function showComments(ev) {
	// it's either conXX or canvasXX...get XX
	var aid = $(ev.target).attr('id');
	var toolP = aid.substr(0,3)=='can';
	var plen = (toolP)?6:3;
	var ndx = parseInt(aid.substr(plen,aid.length-plen),10);
	if (toolP) {
		var comments = tools[ndx]['comments'];
		var tn1 = toolnames[tools[ndx]['toolid']].toLowerCase();
	} else {
		var comments = connections[ndx]['comments'];
		var tn1 = toolnames[connections[ndx]['source']].toLowerCase();
		var tn2 = toolnames[connections[ndx]['target']].toLowerCase();	
	}
	if (comments.length>0) {
		var comstr = "<b>comments on "+tn1;
		if (!toolP) {comstr += " to "+tn2+ " connection";}
		comstr += "</b>";
		comstr += "<span class='close_box'>x</span><br /><br />";
		for (var i=0;i<comments.length;i++) {
			comstr += "<div class='comment_item'>&ldquo;"+comments[i]+"&rdquo;</div>";
		}
		$("#comments").html(comstr);
		$("#comments").fadeIn(100);	
		$("#comments").click(hideComments);
	}
	ev.stopPropagation();
	return false;
}

function hideComments() {
	$("#comments").fadeOut(100);	
}
function initToolTips() {
	$(".tool_template").tooltip({
		bodyHandler: function() {
			var tid = $(this).attr("id");
			var tn = tid.substr(4,tid.length-4);
			var tcc = tools[tn]['comments'].length;
			if (tcc>0) {
				var tc = "<br />"+tcc.toString()+" comment"+((tcc>1)?"s":"")+"; click to view.";
			}
			return "<b>"+toolnames[tn]+"</b><br />"+"usage: "+tools[tn]['usecount']+"<br />difficulty: "+Math.round(tools[tn]['difficulty']*100)+"%<br/>value: "+Math.round(tools[tn]['worth']*100)+"%"+tc;
		},
		showURL: false
	});
	$("._jsPlumb_connector").tooltip({
		bodyHandler: function() {
			var cid = $(this).attr("id");
			var cn = cid.substr(3,cid.length-3);
			var ccc = connections[cn]['comments'].length;
			if (ccc>0) {
				var cc = "<br />"+ccc.toString()+" comment"+((ccc>1)?"s":"")+"; click to view.";
			}
			var st = connections[cn].source;
			var tt = connections[cn].target;
			return "<b>"+toolnames[st]+" to "+toolnames[tt]+"</b><br />"+"usage: "+connections[cn]['usecount']+"<br />difficulty: "+Math.round(connections[cn]['difficulty']*100)+"%<br/>value: "+Math.round(connections[cn]['worth']*100)+"%"+cc;
		},
		showURL: false
	});
	$(".tool_template").click(showComments);
	$("._jsPlumb_connector").click(showComments);
	$("._jsPlumb_connector").css({cursor: "pointer"});
}

jsPlumb.ready(function() {
	$( "#sdatepicker" ).datepicker();
	$( "#edatepicker" ).datepicker();
	jsPlumb.setRenderMode(jsPlumb.CANVAS);
	drawKey(document.getElementById('keycanvas'));
	drawAggregate();
	if (!touchUI) {
	}
	initToolTips();
});

</script>
<style>
#container {
	width: 100%;
	height: 100%;
}
.tool_template {
	position: absolute;
	width:170px
	height:170px;
	cursor: pointer;
}
.tool_label {
	position: relative;
	left: 25%;
	top: 50%;
	font-size: 12px;
	font-weight: bold;
	cursor: move;
}
#parameterblock {
	display:inline-block;
	padding: 5px;
	background-color: #333;
	color: #ffffcc;
	width: 99%;
	position: relative;
	z-index:1000;
}
#key {
	display:none;
	position:absolute;
	left:10px;top:80px;
	background-color:#ccc;
	border:1px solid black;
	padding:5px;
	width:auto;
	height:80px;
	z-index:100000;
}
#keytoggle {
	display:inline-block;
	cursor:pointer;
	margin-right:50px;
	font-weight:bold;
	float:left;
}
#keytoggle a:hover {
	color:orange;
}
#comments {
	position: absolute;
	left: 33%;
	top: 100px;
	width: 40%;
	display: none;
	z-index: 5000;
	margin:20px;
	padding:10px;
	border:2px solid #333;
	background-color: #FFC;
	font-variant: small-caps;
	cursor: pointer;
}
.comment_item {
	font-family: Times, serif;
	font-size: 14px;
	color: #333;
	border-bottom: 1px dotted #CCC;
	margin-bottom: 10px;
	font-variant: normal;
}
.close_box {
	border: 2px solid #666;
	color: #666;
	padding: 0px 5px;
	position: relative;
	float: right;
}
</style>
</head>
<body>
<?php include('header.php'); ?>

<div id="parameterblock" >
	<div id="keytoggle"><a onclick="toggleKey()">KEY</a></div>
	<div style="float:left">
	<form id="paramterform">
	<b>Filter by </b>group: 
	<select id="selectg">
		<option value=''>all</option>
<?php
	$garray = getGroups();
	foreach ($garray as $g) {
		echo '<option '.(($g==$group)?"selected":"").'>'.$g.'</option>';
	}
?>

	</select>
<?php
// admins can see user names
if ($admin_user) {
	$uarray = getUsers('user');
	echo " User: <select id='selectu'>\n";
	echo "<option value=''>all</option>\n";
	foreach ($uarray as $u) {
		echo '<option value='.$u['user_id'].(($u['user_id']==$userid)?" selected":"").'>'.$u['username']."</option>\n";
	}
	echo '</select>';
}
?>
 from: <input type="text" id="sdatepicker" value="<?php echo $act_sdate ?>" /> to: <input type="text" id="edatepicker" value="<?php echo $act_edate ?>"/>  <input type="button" value="GO" onclick="filterAggregate();" />  <input type="button" value="RESET" onclick="resetFilter();" />
</div>
</div>

<div id="container">
<?php if (count($activities)==0) echo "<h2>No activity found for selected filters.</h2>\n"; ?>
</div>

<div class="tool_template" style="display:none;" id="proto">
	<canvas class="tool_marker"></canvas>
</div>
<div id="key">
	<canvas id="keycanvas"></canvas>
</div>

<div id="comments">
</div>

</body>
</html>
