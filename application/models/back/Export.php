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
		$data=$this->db->select('charge.*,account.name user,tel,account.kind')
			->join('account', 'charge.uid=account.id')->where('charge.status',1)
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
			$this->db->where('uid',$key);
		}
		if (isset($limit['begin'])){
			$this->db->between('money_log.time',strtotime($limit['begin'],strtotime($limit['end'].' 23:59:59')));
		}
		$data=$this->db->join('account', 'money_log.uid=account.id')->order_by('money_log.id','desc')
			->select('account.tel,account.name,money_log.content,num,from_unixtime(money_log.time) time')
			->get('money_log')->result_array();
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
