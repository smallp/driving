<?php
function getTime($time){
	$h=floor($time);
	$minite=($time-$h)*60;
	return sprintf('%d:%\'02d',$h,$minite);
};