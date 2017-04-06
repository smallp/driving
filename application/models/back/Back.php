<?php
class Back extends CI_Model {
	const INDEX=APPPATH.'controllers/back/index.json';
	
	function updateNotify() {
		$done=[];$undone=[];
		$tea=$this->db->select('account.status,tid id,name,(select time from `order` where `order`.tid=badTeacher.tid AND status=6 order by id desc limit 1) time')
			->join('account', 'account.id=tid')->where('num>=5')
			->get('badTeacher')->result_array();
		$tea=array_walk($tea,function($item)use(&$done,&$undone){
			$item['name']=$item['name'].'有异常信息';
			$item['time']=date('Y-m-d H:i:s',$item['time']);
			$item['id']='/back/user/badTeacher?id='.$item['id'];
			if ($item['status']==1) $undone[]=$item;
			else $done[]=$item;
		});
		$order=$this->db->select('`order`.id,`order`.status,stu.name,par.name parname,`delOrderReq`.time')
		->join('account stu', 'stu.id=`order`.uid')->join('account par','par.id=`order`.partner','left')
		->join('delOrderReq','delOrderReq.status=0 AND `order`.id=delOrderReq.orderId')
		->get('`order`')->result_array();//->where('`order`.status',2)
		$order=array_walk($order,function($item)use(&$done,&$undone){
			$item['name']=($item['parname']?"$item[name]和$item[parname]":$item['name']).'有异常订单';
			$item['id']='/back/order/cancle?id='.$item['id'];
			if ($item['status']==2) $undone[]=$item;
			else $done[]=$item;
		});
		$sort=function($a,$b){
			if ($a['time']>$b['time']) return -1;
			else return 1;
		};
		usort($done, $sort);
		usort($undone, $sort);
		$_SESSION['notify']=['undone'=>$undone,'done'=>$done];
		// $_SESSION['notify']=array_merge($undone,$done);
	}
	
	function statistic() {
		$data=file_get_contents(Back::INDEX);
		$data=json_decode($data,TRUE);
		//学员比例
		$res=$this->db->query("SELECT count(*) num FROM account GROUP BY kind order by kind asc")->result_array();
		$user=[
				'stu'=>$res[0]['num'],
				'tea'=>$res[1]['num']
		];
		$user['total']=$user['tea']+$user['stu'];
		$date=date('Y-m-d');
		$user['new']=$this->db->where('regTime >',$date)->count_all_results('account');
		$data['user']=$user;
		
		//预约各科目的比
		$kind=$this->db->query('SELECT count(*) num,kind FROM `order` WHERE status<5 GROUP BY kind')
		->result_array();
		$res=['1'=>0,'2'=>0,'4'=>0];
		foreach ($kind as $value) {
			$res[$value['kind']]=$value['num'];
		}
		$data['order']=$res;
		return $data;
	}
	
	function statisticDaily() {
		$data=file_get_contents(self::INDEX);
		$data=json_decode($data,TRUE);
		$time=time()-86000;
		$date=date('Y-m-d',$time);
		$inc=$this->db->where('regTime >',$date)->count_all_results('account');
		array_shift($data['increase']);
		array_push($data['increase'], $inc);
		array_shift($data['date']);
		array_push($data['date'], $date);
		//活跃数
		$active=$this->db->count_all('huoyue');
		$this->db->simple_query('truncate table huoyue');
		array_shift($data['huoyue']);
		array_push($data['huoyue'],$active);
		
		//昨天订单总金额
		$money=$this->db->query('SELECT sum(realPrice) price FROM `order` WHERE status=2 AND time>?',$time)
			->row_array()['price'];
		$money=$money?:0;
		array_shift($data['money']['day']['count']);
		array_shift($data['money']['day']['date']);
		array_push($data['money']['day']['count'],$money);
		array_push($data['money']['day']['date'],$date);
		if (substr($date,7,2)=='01'){//每月1号，更新月数
			array_shift($data['money']['month']['count']);
			array_shift($data['money']['month']['date']);
			array_push($data['money']['month']['count'],$money);
			array_push($data['money']['month']['date'],substr($date,0,7));
		}else{
			$t=array_pop($data['money']['month']['count']);
			$t+=$money;
			array_push($data['money']['month']['count'],$t);
		}
		if (substr($date, 5,2)=='01'){//每年1月，更新年
			array_shift($data['money']['year']['count']);
			array_shift($data['money']['year']['date']);
			array_push($data['money']['year']['count'],$money);
			array_push($data['money']['year']['date'],substr($date,0,4));
		}else{
			$t=array_pop($data['money']['year']['count']);
			$t+=$money;
			array_push($data['money']['year']['count'],$t);
		}
		file_put_contents(self::INDEX, json_encode($data));
	}

	function weather($addr=[])
	{
		$method = "POST";
		$appcode = "c0a2878cfae44f6fb753d40df39d1752";
		$headers = array();
		array_push($headers, "Authorization:APPCODE " . $appcode);
		//根据API的要求，定义相对应的Content-Type
		array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
		$url = 'http://aliv2.data.moji.com/whapi/json/aliweather/briefforecast3days';

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$res=[];
		foreach ($addr as $value) {
			$bodys = "lat=$value[lat]&lon=$value[lng]&token=443847fa1ffd4e69d929807d42c2db1b";//南岸区
			curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
			$item=curl_exec($curl);
			$item=json_decode($item,true);
			if ($item['code']==0){
				$res[$value['name']]=$item['data']['forecast'][0];
			}
		}
		return $res;
	}
	
	function daily() {
		$this->db->where('id >',1)->update('user',['zhuan'=>1,'gua'=>3]);
		//处理过期订单
		$date=date('Y-m-d',strtotime('-2 day'));
		$log=$this->db->where(['date <='=>$date,'status'=>0])->get('teach_log')->result_array();
		$this->db->where(['date <='=>$date,'status'=>0])->update('teach_log',['status'=>4]);
		$this->load->model('order');
		$cancle=[];
		foreach ($log as $value) {
			if (!in_array($value['orderId'], $cancle)){
				$orderId=[$value['orderId']];
				$cancle[]=$value['orderId'];
				$message=['orderId'=>$value['orderId']];
				if ($value['partner']>0){
					$order=$this->db->find('`order`', $value['orderId'],'id','info,tid');
					$message['pOrderId']=$this->db->where(['uid'=>$value['partner'],'tid'=>$order['tid'],'`order`.status'=>Order::PAYED,'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
						->select('id')->get('`order`',1)->row_array()['id'];
					$orderId[]=$message['pOrderId'];
				}
				$this->db->insert('delOrderReq',$message);
				$this->db->where_in('id',$orderId)->update('`order`',['reason'=>'双方48小时后均无操作']);
			} 
		}
		$cancle&&$this->db->where_in('id',$cancle)->update('`order`',['status'=>Order::ERROR]);
		//处理自动完成失败了的订单
		$id=$this->db->select('`order`.id')
		->where('`order`.status=2 AND NOT EXISTS (SELECT id FROM teach_log WHERE orderId=`order`.id AND teach_log.status!=2 AND teach_log.status!=6)')
		->get('`order`')->result_array();
		if ($id){
			$id=array_map(function($item){
				return $item['id'];
			},$id);
			$logs=$this->db->where_in('orderId',$id)->get('teach_log')->result_array();
			$this->_finish($logs);
		}
		$this->freeTime();
	}

	//刷新教练的空余时间
	function freeTime(){
		$funIndex=function($item){
			return $item['date'].$item['time'];
		};
		$teacher=$this->db->select('id,orderInfo')->where('JSON_LENGTH(orderInfo)>0',null,false)->get('teacher')->result_array();
		$date=date('Y-m-d');
		foreach ($teacher as $value) {
			$oldInfo=json_decode($value['orderInfo'],true);
			$nowInfo=[];
			foreach ($oldInfo as $item) {
				if ($item['date']>=$date){
					$nowInfo[]=$item;
				}
			}
			if (count($oldInfo)>count($nowInfo))
				$value['orderInfo']=json_encode($nowInfo);
			$nowInfo=array_map($funIndex,$nowInfo);
			$arr=$this->db->select('info->"$[*].index" dt')
			->where(['tid'=>$value['id'],'status <'=>5,'time >='=>time()-604800])//最近7天预约成功的记录
			->get('`order`')->result_array();
			$arr=array_map(function($item){
				return json_decode($item['dt'],true)[0];
			},$arr);
			$value['freeTime']=count(array_diff($nowInfo,$arr));
			$this->db->where('id',$value['id'])->update('teacher',$value);
		}
	}
	
	function week() {
		//获取本周有效订单
		$data=$this->db->query('SELECT distinct info->"$[*].index" `index`,tid FROM `order` WHERE tid in (SELECT id FROM teacher WHERE inviteStatus=0) AND status<5 AND time>?',time()-84600*7)
			->result_array();//不直接用json_length是因为需要去重
		$res=[];
		foreach ($data as $item){
			if (!isset($res[$item['tid']])) $res[$item['tid']]=[];
			else if (count($res[$item['tid']])>=8) continue;
			$res[$item['tid']]=array_merge($res[$item['tid']],json_decode($item['index'],TRUE));
		}
		foreach ($res as $tid=>$arr) {
			if (count($arr)>=8){
				$invite=$this->db->find('invite', $tid,'toid','fromid');
				if ($invite){//被邀请的才处理
					$invite=$invite['fromid'];
					$amount=50;
					$this->db->where('id',$invite)->step('account', 'inviteMoney',TRUE,$amount);
					$this->db->where('id',$invite)->step('user', 'money',TRUE,$amount);
					$this->db->where('id',$invite)->step('teacher', 'money',TRUE,$amount);
					$this->db->where('id',$tid)->update('teacher',['inviteStatus'=>1]);
					$this->db->insert('money_log',
						['uid'=>$invite,'realMoney'=>$amount,'num'=>$amount,'content'=>"获得提成${amount}学车币",'time'=>time()]
					);
					$this->db->insert('invite_log_tea',
						['tid'=>$tid,'uid'=>$invite]
					);
				}
			}
		}
	}
	
	function month() {
		//获取上月有效订单
		$time=strtotime('-1 month');
		$data=$this->db->query('SELECT distinct info->"$[*].index" `index`,tid FROM `order` WHERE status<5 AND time>?',$time)
			->result_array();//不直接用json_length是因为需要去重
		$res=[];
		foreach ($data as $item){
			if (!isset($res[$item['tid']])) $res[$item['tid']]=[];
			$res[$item['tid']]=array_merge($res[$item['tid']],json_decode($item['index'],TRUE));
		}
		$this->load->model('notify');
		foreach ($res as $tid => $arr) {
			$orgGrade=(int)($this->db->find('teacher', $tid,'id','grade')['grade']);
			$num=count($arr);
			if ($num<132){//退到3星
				if ($orgGrade!=3){
					$this->db->where('id',$tid)->update('teacher',['grade'=>3]);
					$this->notify->send(['id'=>$tid,'grade'=>3],Notify::TEA_GRADE_DOWN);
				}else continue;
			}else if ($num>154){//有这么多课时了，不担心评价不够高
				if ($orgGrade!=5){
					$this->db->where('id',$tid)->update('teacher',['grade'=>5]);
					$this->notify->send(['id'=>$tid,'grade'=>5],Notify::TEA_GRADE_UP);
				}else continue;
			}else{
				switch ($orgGrade) {
					case 5://学时不够，降
						$this->notify->send(['id'=>$tid,'grade'=>4],Notify::TEA_GRADE_DOWN);
					break;
					case 4://不变
						continue;
					case 3://判断评分后升级
						$comment=$this->db->query('SELECT avg(grade) num FROM tcomment WHERE tid=? AND time>',[$tid,$time])
							->row_array();
						$comment=(int)$comment['num'];
						if ($comment>=3){
							$this->notify->send(['id'=>$tid,'grade'=>4],Notify::TEA_GRADE_UP);
						}
					break;
					default:
						continue;
					break;
				}
			}
		}
	}
	
	//提前发短信
	function hours() {
		$index=date('Y-m-d').(date('G')+1);
		$order=$this->db->where("status=2 AND JSON_SEARCH(info->'$[*].index','one','$index') IS NOT NULL",NULL,FALSE)
			->get('`order`')->result_array();
		if (!empty($order)){
			$tid=[];
			foreach ($order as $value) {
				$tid[]=$value['tid'];
			}
			$tid=array_unique($tid);
			$phone=$this->db->where_in('id',$tid)->select('tel')
				->get('account')->result_array();
			$phone=array_map(function($i){
				return $i['tel'];
			}, $phone);
			if ($phone){
				$phone=join(',', $phone);
				$this->load->model('notify');
				$this->notify->sendSms(Notify::SMS_YUE_NOTIFY,$phone);
			}
		}
	}
	
	//每10分钟自动确认时段，刷新过期订单
	function autoFinish() {
		$this->load->model('order');
		$where=['startTime <='=>time()-60*Order::CLASS_TIME,'status'=>1];
		$logs=$this->db->where($where)->get('teach_log')->result_array();
		$this->load->model('order');
		if ($logs){
			$this->db->where($where)->update('teach_log',['status'=>2]);
			$this->_finish($logs);
		}
		//在教学过程中填写了教学日志，也需要自动完成订单
		$logs=$this->db->between('startTime',time()-7200,time()-3600)->get('teach_log')->result_array();
		$this->_finish($logs);
		$this->order->_removeOrder();
		// $where['status']=0;
		//双方未确认学车的，设置为异常，等待用户自己申述
// 		$this->db->where($where)->update('teach_log',['status'=>4]);
	}

	function _finish($logs){
		foreach ($logs as $log) {
			try {
				$this->order->finishOrder($log);
			} catch (Exception $e) {
				if ($e->getCode()==MyException::DATABASE){//退回
					$this->db->where('id',$log['id'])->update('teach_log',['status'=>1]);
				}
			}
		}
	}
}