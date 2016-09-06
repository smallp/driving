<?php
function getTime($h){
	return ((int)$h).(strpos($h,'.5')===FALSE?':00':':30');
};