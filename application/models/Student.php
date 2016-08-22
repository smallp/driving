<?php
class Student extends CI_Model {
	function nearby($input) {
		if (empty($input['lat'])){
			$input['lat']=0;
			$input['lng']=0;
		}
		$where=["addrTime >="=>time()-1296000,'secret <'=>2];
		if (defined('UID')) $where['account.id !=']=UID;
		$this->db->select('account.id,name,avatar,gender,sign,lat,lng,secret,age')->from('user')->join('account', 'account.id=user.id')
			->where($where,NULL,FALSE);
		return $this->_near($input);
	}
	
	function nearTeacher($input) {
		if (empty($input['lat'])){
			$input['lat']=0;
			$input['lng']=0;
		}
		$this->db->select('account.id,name,avatar,grade,year,student,teacher.kind,lat,lng,secret')
			->join('account', 'account.id=teacher.id')->where('account.status',1);
		if ($t=$this->input->get('key'))
			$this->db->like('name',$t);
		if ($t=$this->input->get('address'))
			$this->db->like('account.address',$t);
		if ($kind=(int)$this->input->get('kind'))
			$this->db->where("mod(teacher.kind,$kind*2)>=$kind",NULL ,FALSE);
		if ($t=$this->input->get('sort')){
			//0综合排序 1人气 2距离 3评价
			switch ($t) {
				case 1:$this->db->order_by('student desc');
				break;
				case 2:$this->db->order_by("pow(lat-$input[lat],2)+pow(lng-$input[lng],2)",'asc')
					->where(["addrTime>="=>time()-1296000],NULL,FALSE);
				break;
				case 3:$this->db->order_by('grade desc');
				break;
				default:$this->db->order_by('grade desc,student desc');
				break;
			}
		}
		$res=$this->db->get('teacher',$input['count'],$input['count']*$input['page'])->result_array();
// 		$this->load->helper('distance');
		foreach ($res as &$item) {
// 			$item['distance']=GetDistance($item['lat'],$item['lng'],$input['lat'],$input['lng']);
			if ($kind)
				$item['kind']=$kind;//如果是科二科三都可以，显示搜索的
		}
		return $res;
	}
	
	function nearbyPoint($input){
		if (empty($input['lat'])){
			$input['lat']=0;
			$input['lng']=0;
		}
		if ($t=$this->input->get('key'))
			$this->db->like('account.name',$t);
		switch ($input['kind']) {//1场地 2教练 3学员
			case 1:$time=(int)$this->input->get('time');
			$this->db->where('UNIX_TIMESTAMP(time) >',$time,FALSE);
			$data=$this->db->select('place.*,(SELECT name FROM school WHERE place.school=school.id) school')
				->get('place')->result_array();
			return array_map(function($item){
				$item['pics']=json_decode($item['pics'],TRUE);
				return $item;
			}, $data);
			break;
			case 2:
				$tkind=(int)$this->input->get('tkind');
			$this->db->select('lat,lng,account.id,name,avatar,gender,grade,teacher.kind,year,account.address,phone')
				->from('teacher')->join('account', 'account.id=teacher.id')
				->where("teacher.kind%($tkind*2)>=$tkind AND status=1 AND secret=0")
				->order_by("pow(lat-$input[lat],2)+pow(lng-$input[lng],2)",'asc',FALSE);
// 				->limit($input['count'],$input['page']*$input['count']);
			break;
			case 3:
			$this->db->select('lat,lng,account.id,name,avatar,sign,gender,age')->from('user')
				->join('account', 'account.id=user.id')->where('secret',0)
				->order_by("pow(lat-$input[lat],2)+pow(lng-$input[lng],2)",'asc',FALSE)
				->limit($input['count'],$input['page']*$input['count']);
			break;
			default:throw new MyException('',MyException::INPUT_ERR);
			break;
		}
		return $this->db->get()->result_array();
	}
	
	function _near($input) {
		$res=$this->db->order_by("pow(lat-$input[lat],2)+pow(lng-$input[lng],2)",'asc',FALSE)
			->limit($input['count'],$input['page']*$input['count'])->get()
			->result_array();
// 		$this->load->helper('distance');
// 		foreach ($res as &$item) {
// 			$item['distance']=GetDistance($item['lat'],$item['lng'],$input['lat'],$input['lng']);
// 		}
		return $res;
	}
}