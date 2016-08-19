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
