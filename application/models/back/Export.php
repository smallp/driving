<?php
class Export extends CI_Model {
	function order($limit) {
		$data=$this->db->query('SELECT `order`.kind,`order`.info,stu.name user,tea.name tea,tea.tel ttel,stu.tel,school.name school,from_unixtime(`order`.time) time FROM `order` '.
			' JOIN account stu ON order.uid=stu.id'.
			' JOIN account tea ON order.tid=tea.id'.
			' JOIN school ON school.id=(SELECT school FROM teacher WHERE teacher.id=order.tid)'.
			' WHERE `order`.time BETWEEN ? AND ? AND `order`.status BETWEEN 2 AND 4',
				[strtotime($limit['begin']),strtotime($limit['end'])])
		->result_array();
		$res=[];$place=['0'=>'无场地'];$kind=['1'=>'科目二','2'=>'科目三','4'=>'陪练陪驾'];
		foreach ($data as $order) {
			$info=json_decode($order['info'],TRUE);
			unset($order['info']);
			$order['kind']=$kind[$order['kind']];
			foreach ($info as $item) {
				if (isset($place[$item['place']])) $item['place']=$place[$item['place']];
				else{
					$t=$this->db->find('place',$item['place'],'id','name');
					if (!$t) $item['place']='场地已删除';
					else{
						$place[$item['place']]=$t['name'];
						$item['place']=$t['name'];
					}
				}
				$res[]=array_merge($order,$item);
			}
		}
		return $res;
	}
}
