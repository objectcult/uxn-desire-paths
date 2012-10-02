<?php

function colorRangeValue($colarray,$val,$hex=false) {
	// return a color that is val% of the way through the colors in colarray
	//  val is 0 - 1
	// colarray may have any number of colors in it; colors are array("red"=>rval, "green"=>gval, "blue"=>bval)
	// first determine which 2 colors val falls between, then extrapolate between those two colors
	
	// 0 and 1 are easy; we're assuming that colarray has more than one value
	if ($val==0.0 || $val==1.0) {
		$ndx = floor($val*(count($colarray)-1));
		return ($hex?"#".hexcolor($colarray[$ndx]['red'],$colarray[$ndx]['green'],$colarray[$ndx]['blue']):$colarray[$ndx]);
		}
	if ($val==1.0) {return $colarray[count($colarray)-1];}
	$ndx = floor($val*(count($colarray)-1));
	$segpct = $val - $ndx/count($colarray);
	$c1 = $colarray[$ndx];
	$c2 = $colarray[$ndx+1];
	$rcr = $c1['red']+($segpct*($c2['red']-$c1['red']));
	$rcg = $c1['green']+($segpct*($c2['green']-$c1['green']));
	$rcb = $c1['blue']+($segpct*($c2['blue']-$c1['blue']));
	if ($hex) {
		return "#".hexcolor($rcr,$rcg,$rcb);
	} else {
		return array("red"=>$rcr, "green"=>$rcg, "blue"=>$rcb);
	}
}

function hexcolor($r,$g,$b) {
	// return a valid color string for given r,g,b values, two places for each value
	if ($r==0) {
		$rv = "00";
	} else {
		$rv = "".dechex($r);
		if ($r<16) {$rv = "0".$rv; }
	}
	if ($g==0) {
		$gv = "00";
	} else {
		$gv = "".dechex($g);
		if ($g<16) {$gv = "0".$gv; }
	}
	if ($b==0) {
		$bv = "00";
	} else {
		$bv = "".dechex($b);
		if ($b<16) {$bv = "0".$bv; }
	}
	return $rv.$gv.$bv;
}
?>