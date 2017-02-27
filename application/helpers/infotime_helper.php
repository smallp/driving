<?php
function getTime($time){
	$h=floor($time/60);
	$minite=$time%60;;
	return sprintf('%d:%\'02d',$h,$minite);
};