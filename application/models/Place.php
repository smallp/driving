<?php
class Place extends CI_Model {
	function placeList() {
		$page=(int)$this->input->get('page');
		$count=10;
		$limit=$this->input->get(['lat','lng']);
		if (!$limit||empty($limit['lat'])){
			$limit=['lat'=>0,'lng'=>0];
		}
		if ($input=$this->input->get('address'))
			$this->db->like('place.address',$input);
		
		if (($input=$this->input->get('key'))&&!empty($input)){
			$this->db->group_start();
			$this->db->like('place.name',$input)->or_like('school.name',$input);
			$this->db->group_end();
		}
		if ($input=$this->input->get('sort')){
			//0综合排序 1人气 2距离 3评价
			switch ($input) {
				case 1:$this->db->order_by('rank desc');
				break;
				case 2:$this->db->order_by("pow(lat-$limit[lat],2)+pow(lng-$limit[lng],2)",'asc');
				break;
				case 3:$this->db->order_by('grade desc');
				break;
				default:$this->db->order_by('grade desc,rank desc');
				break;
			}
		}
		$this->load->helper('distance');
		$res=$this->db->select('place.id,place.address,place.name,place.lat,place.lng,place.intro,place.pics->"$[0]" pics,place.grade,school.name school,area')
			->join('school','place.school=school.id')->get('place',$count,$count*$page)
			->result_array();
		foreach ($res as &$value) {
			$value['distance']=GetDistance($value['lat'],$value['lng'],$limit['lat'],$limit['lng']);
			$value['pics']=[json_decode($value['pics'],TRUE)];
			$value['intro']=mb_substr($value['intro'], 0,15);
		}
		return $res;
	}
	
	function placeItem($id) {
		$data=$this->db->where('place.id',$id)->join('school', 'school.id=place.school')->select('place.*,school.name school')->get('place',1)->row_array();
		if (!$data) throw new MyException('',MyException::GONE);
		$data['pics']=json_decode($data['pics'],TRUE);
		return $data;
	}
}