<?php
class Export extends CI_Model {
	function order($limit) {
		$data=$this->db->query('SELECT `order`.kind,`order`.info,stu.name user,tea.name tea,tea.tel ttel,stu.tel,school.name school,from_unixtime(`order`.time) createTime FROM `order` '.
			' JOIN account stu ON order.uid=stu.id'.
			' JOIN account tea ON order.tid=tea.id'.
			' JOIN school ON school.id=(SELECT school FROM teacher WHERE teacher.id=order.tid)'.
			' WHERE `order`.time BETWEEN ? AND ? AND `order`.status BETWEEN 2 AND 4',
				[strtotime($limit['begin']),strtotime($limit['end'])])
		->result_array();
		$res=[];$kind=['1'=>'科目二','2'=>'科目三','4'=>'陪练陪驾'];
		foreach ($data as $order) {
			$info=json_decode($order['info'],TRUE);
			unset($order['info']);
			$order['kind']=$kind[$order['kind']];
			foreach ($info as $item) {
				$item['place']=$this->getPlace($item['place']);
				$item['time']=$item['time'].':00-'.($item['time']+1).':00';
				$res[]=array_merge($order,$item);
			}
		}
		return $res;
	}
	
	function income($limit) {
		if (isset($limit['begin']))
			$this->db->between('createTime', $limit['begin'], $limit['end'].' 23:59:59');
		if (isset($limit['uid']))
			$this->db->where('uid',$limit['uid']);
		$this->db->where('charge.status',1)->stop_cache();
		$data=$this->db->select('charge.*,account.name user,tel,account.kind')
			->join('account', 'charge.uid=account.id')
			->order_by('createTime','desc')->get('charge')->result_array();
		array_walk($data, function(&$item,$key,$info){
			$item['channel']=$info[$item['channel']-1];
			$item['kind']=$item['kind']?'教练':'学员';
		},['支付宝','微信','银行卡']);
		return $data;
	}
	
	function ticheng($limit) {
		if (isset($limit['begin']))
			$this->db->between('income.time', $limit['begin'], $limit['end'].' 23:59:59');
		if (isset($limit['uid']))
			$this->db->where('income.tid',$limit['uid']);
		$data=$this->db->select('income.*,account.name user,tel,(SELECT name FROM school WHERE school.id=(SELECT school FROM teacher WHERE teacher.id=income.tid)) school')
			->join('account', 'income.tid=account.id')
			->order_by('income.id','desc')->get('income')->result_array();
		array_walk($data, function(&$item,$key,$info){
			$item['type']=$info[(int)$item['type']];
			$item['school']=$item['school']?:'';
		},['教练提成','退款平台手续费','后台充值支出']);
		return $data;
	}
	
	function moneyLog($limit) {
		if (isset($limit['uid'])){
			$this->db->where('uid',$limit['uid']);
		}
		if (isset($limit['begin'])){
			$this->db->between('money_log.time',strtotime($limit['begin']),strtotime($limit['end'].' 23:59:59'));
		}
		$data=$this->db->join('account', 'money_log.uid=account.id')->order_by('money_log.id','desc')
			->select('account.tel,account.name,account.kind,money_log.content,num,from_unixtime(money_log.time) time')
			->get('money_log')->result_array();
		array_walk($data, function(&$item){
			$item['kind']=$item['kind']?'教练':'学员';
		});
		return $data;
	}
	
	function financeStat($limit) {
		$limit['count']=12;
		$this->db->start_cache();
		if (isset($limit['uid']))
			$this->db->where('uid',$limit['uid']);
		else{
			if ($limit['type']>0&&$limit['type']!=3){//统计学员、教练
				$this->db->join('account', 'money_log.uid=account.id AND account.kind='.($limit['type']-1));
			}
		}
		$this->db->stop_cache();
		switch ($limit['time']) {
			case 0:
				if (isset($limit['page'])){
					if (isset($limit['begin'])){
						$begintime=strtotime($limit['begin'])-$limit['count']*86400*$limit['page'];
						$endtime=$begintime+$limit['count']*86400;
					}else{
						$endtime=strtotime('tomorrow')-86400*$limit['count']*$limit['page'];
						$begintime=$endtime-86400*$limit['count'];
					}
				}else{
					$begintime=strtotime($limit['begin']);
					$endtime=strtotime($limit['end'].' 23:59:59');
				}
				$this->db->where("time BETWEEN $begintime AND $endtime",NULL,FALSE)
				->group_by('date_format(from_unixtime(time),"%Y-%m-%d")')->select('date_format(from_unixtime(time),"%Y-%m-%d") time');
				break;
			case 1:
				$this->db->simple_query('set sql_mode=""');
				if (isset($limit['page'])){
					if (isset($limit['begin'])){
						$begintime=strtotime('this monday',strtotime($limit['begin'])+$limit['count']*86400*7*$limit['page']);
						$endtime=$begintime+$limit['count']*86400*7;
					}else{
						$endtime=strtotime('+1 monday')-86400*7*$limit['count']*$limit['page'];
						$begintime=$endtime-86400*7*$limit['count'];
					}
				}else{
					$begintime=strtotime('this monday',strtotime($limit['begin']));
					$endtime=strtotime('next monday',strtotime($limit['end']));
				}
				$this->db->where("time BETWEEN $begintime AND $endtime",NULL,FALSE)->
					group_by('week(time)')->select('time');
				break;
			case 2:
				$nextMonth=strtotime(date('Y-m-01',strtotime('+1 month')));
				if (isset($limit['page'])){
					if (isset($limit['begin'])){//按月不分页
						$begintime=strtotime(substr($limit['begin'], 0,7).'-01');
						$endtime=strtotime(
							'+1 month -1 day',
							strtotime(
								substr($limit['end'], 0,7).'-01'
							)
						);
					}else{
						$endtime=strtotime('-'.($limit['count']*$limit['page']).' month',$nextMonth);
						$begintime=strtotime("-$limit[count] month",$endtime);
					}
				}else{
					$begintime=strtotime(substr($limit['begin'], 0,7).'-01');
					$endtime=strtotime(
						'+1 month -1 day',
						strtotime(
							substr($limit['end'], 0,7).'-01'
						)
					);
				}
				$this->db->between('time',$begintime,$endtime)
					->group_by('date_format(from_unixtime(time),"%Y/%m")')->select('date_format(from_unixtime(time),"%Y/%m") time');
				break;
			default:
				throw new MyException('',MyException::INPUT_ERR);
				break;
		}
		$data=$this->db->select('sum(realMoney) realMoney,sum(virtualMoney) virtualMoney')->get($limit['type']==3?'income':'money_log')->result_array();
		$this->load->helper('fill');
		switch ($limit['time']) {
			case 0:$data=fillDay($data, $begintime, $endtime);
				break;
			case 1:$data=fillWeek($data, $begintime, $endtime);
				break;
			case 2:$data=fillMonth($data, $begintime, $endtime);
				break;
		}
		return array_map(function($item){
			if (!isset($item['realMoney'])){
				$item['realMoney']=0;
				$item['virtualMoney']=0;
				$item['total']=0;
			}else $item['total']=$item['virtualMoney']+$item['realMoney'];
			return $item;
		}, $data);
	}
	
	function FXInvite($limit) {
		if (isset($limit['uid'])){
			$this->db->where('invite.fromid',$limit['uid']);
		}
		if (isset($limit['begin'])){
			$this->db->between('invite.time',$limit['begin'],$limit['end'].' 23:59:59');
		}
		$data=$this->db->join('account f', 'invite.fromid=f.id')
			->join('account t', 'invite.toid=t.id')->order_by('invite.time','desc')
			->select('f.tel ftel,f.name fname,f.kind fkind,t.tel ttel,t.name tname,t.kind tkind,time')
			->get('invite')->result_array();
		array_walk($data, function(&$item){
			$item['fkind']=$item['fkind']?'教练':'学员';
			$item['tkind']=$item['tkind']?'教练':'学员';
		});
		return $data;
	}
	
	function FXMoney($limit) {
		if (isset($limit['uid'])){
			$this->db->where('invite_log.uid',$limit['uid']);
		}
		if (isset($limit['begin'])){
			$this->db->between('invite_log.time',$limit['begin'],$limit['end'].' 23:59:59');
		}
		$this->db->stop_cache();
		$data=$this->db->join('`order`', 'invite_log.orderId=`order`.id')
			->join('account f', 'invite_log.uid=f.id')
			->join('account par', '`order`.partner=par.id','left')
			->join('account t', '`order`.uid=t.id')->order_by('invite_log.id','desc')
			->select('f.tel ftel,f.name fname,f.kind fkind,t.tel ttel,t.name tname,par.tel partel,par.name parname,invite_log.amount,invite_log.time')
			->get('invite_log')->result_array();
		array_walk($data, function(&$item){
			$item['fkind']=$item['fkind']?'教练':'学员';
			$item['parname']=$item['parname']?:'无';
			$item['partel']=$item['partel']?:'';
		});
		return $data;
	}
	
	function getPlace($id) {
		static $place=['0'=>'无场地'];
		if (isset($place[$id])) return $place[$id];
		else{
			$t=$this->db->find('place',$id,'id','name');
			if (!$t) return '场地已删除';
			else{
				$place[$id]=$t['name'];
				return $t['name'];
			}
		}
	}
}
