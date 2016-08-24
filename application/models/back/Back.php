<?php
class Back extends CI_Model {
	const INDEX=APPPATH.'controllers/back/index.json';
	
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
	
	function financeStat($limit) {
		;
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
	
	function daily() {
		$this->db->where('id >',1)->update('user',['zhuan'=>1,'gua'=>3]);
		$date=date('Y-m-d',strtotime('-2 day'));
		$certain=$this->db->where(['status <'=>2,'date <='=>$date])->select('id,orderId,status,partner')
			->get('teach_log')->result_array();
		$this->db->where(['status'=>1,'date <='=>$date])->update('teach_log',['status'=>2]);
		//双方未确认学车的，设置为异常
		$this->db->where(['status'=>0,'date <='=>$date])->update('teach_log',['status'=>4]);
		$this->load->model('order');
		$cancle=[];
		foreach ($certain as $value) {
			if ($value['status']==1){
				try {
					$this->order->finishOrder($value);
				} catch (Exception $e) {
					if ($e->getCode()==MyException::DATABASE){//退回
						$this->db->where('id',$value['id'])->update('teach_log',['status'=>1]);
						return FALSE;
					}
				}
			}else{
				if (!in_array($value['orderId'], $cancle)){
					$cancle[]=$value['orderId'];
					$message=['orderId'=>$value['orderId'],'reason'=>'双方48小时后均无操作'];
					if ($value['partner']>0){
						$order=$this->db->find('`order`', $value['orderId'],'id','info,tid');
						$message['pOrderId']=$this->db->where(['uid'=>$value['partner'],'tid'=>$order['tid'],'`order`.status'=>Order::PAYED,'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
							->select('id')->get('`order`',1)->row_array()['id'];
					}
					$this->db->insert('delOrderReq',$message);
				}
			}
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
		$data=$this->db->query('SELECT distinct info->"$[*].index" index,tid FROM `order` WHERE status<5 AND time>?',$time)
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
			$phone=join(',', $phone);
			$this->load->model('notify');
			$this->notify->sendSms(Notify::SMS_YUE_NOTIFY,$phone);
		}
	}
}