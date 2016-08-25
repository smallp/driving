<?php
//没有数据的日期补0
function fillDay($data,$begintime,$endtime) {
	$p=current($data);
	$p=$p?$p['time']:'';//不用判断p是否为false了
	$res=[];
	while ($begintime<$endtime) {
		$time=date('Y-m-d',$begintime);
		if ($p==$time){
			$res[]=current($data);
			$p=next($data);
			$p=$p?$p['time']:'';
		}else $res[]=['time'=>$time,'num'=>0];
		$begintime+=86400;
	}
	return $res;
}

//没有数据的周补0
function fillWeek($data,$begintime,$endtime) {
	$data=$data?:['time'=>$endtime+1];
	$p=current($data);
	$prebegin=$begintime;
	$res=[];
	while ($begintime<$endtime) {
		$begintime+=604800;
		$res[]=['time'=>date('m-d',$prebegin).'至'.date('m-d',$begintime),
				'num'=>$p['time']<=$begintime?$p['num']:0];//在区间内，设值，否则是0
		if ($p['time']<=$begintime)
			$p=next($data);
			$p=$p?:['time'=>$endtime+1];
			$prebegin=$begintime;
	}
	return $res;
}

//没有数据的月份补0
function fillMonth($data,$begintime,$endtime) {
	$data=$data?:[['time'=>'']];//设为空，不会被匹配到
	$p=current($data);
	$res=[];
	$time=$begintime;
	while ($next=next($data)) {//范围为整个数组
		$time=strtotime('+1 Month',$time);
		while (date('Y/m',$time)!=$next['time']) {
			$res[]=['time'=>date('Y/m',$time),'num'=>0];
			$time=strtotime('+1 Month',$time);
		}
		$res[]=$next;
		$p=$next;
	}
	while ($time<=$endtime) {
		$item=['time'=>date('Y/m',$time)];
		if ($p['time']==$item['time']){
			$item['num']=$p['num'];
			$p=next($data);
		}else $item['num']=0;//在区间内，设值，否则是0
		$res[]=$item;
		$time=strtotime('+1 Month',$time);
	}
	return $res;
}