<?php 
//获取2点之间的距离
function GetDistance($lat1, $lng1, $lat2, $lng2){
    $pi=Pi();
    $radLat1 = $lat1 * ($pi/ 180);
    $radLat2 = $lat2 * ($pi / 180);

    $a = $radLat1 - $radLat2; 
    $b = ($lng1 * ($pi / 180)) - ($lng2 * ($pi / 180)); 

    $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
    $s = $s * 6371004;
    $s = round($s);//unit is m
    return $s; 
}

/**
 * 
 * @param array $point
 * @param float $raidus 单位 米
 * @return array min/max lat/lng
 */
function GetRange($point,$raidus){
	//计算纬度
	$degree = 111293.636111;
	$radiusLat =$raidus / $degree;
	$res['minlat'] = $point['lat'] - $radiusLat; //得到最小纬度
	$res['maxlat'] = $point['lat'] + $radiusLat; //得到最大纬度
	//计算经度
	$mpdLng = $degree * cos($point['lat'] * (Pi() / 180));
	$radiusLng = $raidus/ $mpdLng;
	$res['minlng'] = $point['lng'] - $radiusLng;  //得到最小经度
	$res['maxlng'] = $point['lng'] + $radiusLng;  //得到最大经度
	return $res;
}
?>