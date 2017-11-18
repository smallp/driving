<?php
class Student extends CI_Model {
	//附近学员
	function nearby($input) {
		if (!isset($input['lat'])||empty($input['lat'])){
			$input['lat']=0;
			$input['lng']=0;
		}
		if (isset($input['distance'])){
			$input['distance']=(int)$input['distance'];
			if ($input['distance']>0&&$input['distance']<=3){
				$swi=[500,1000,2000];
				$this->load->helper('distance');
				$range=GetRange($input,$swi[$input['distance']-1]);
				$this->db->between('lat', $range['minlat'], $range['maxlat'])
				->between('lng', $range['minlng'], $range['maxlng']);
			}
		}
		if (isset($input['age'])){
			$input['age']=(int)$input['age'];
			if ($input['age']>0&&$input['age']<=3){
				$swi=[[18,25],[26,35],[36,45]];
				$swi=$swi[$input['age']-1];
				$this->db->between('age',$swi[0],$swi[1]);
			}
		}
		if (isset($input['gender'])&&$input['gender']>=0){
			$this->db->where('gender',(int)$input['gender']);
		}
		if (isset($input['key'])&&!empty($input['key'])){
			$this->db->like('name',$input['key']);
		}
		$where=['secret <'=>2];//"addrTime >="=>time()-1296000,
		if (defined('UID')) $where['account.id !=']=UID;
		$this->db->select('account.id,name,avatar,gender,sign,lat,lng,secret,age,sendFlow')->from('user')->join('account', 'account.id=user.id')
			->where($where,NULL,FALSE);
		return $this->_near($input);
	}
	
	//附近教练
	function nearTeacher($input) {
		if (empty($input['lat'])){
			$input['lat']=0;
			$input['lng']=0;
		}
		$this->db->select('account.id,name,avatar,grade,year,student,teacher.kind,lat,lng,secret,zjType,flower,praise')
			->join('account', 'account.id=teacher.id')->where('account.status',1);
		if ($t=$this->input->get('key'))
			$this->db->like('name',$t);
		if ($t=$this->input->get('address'))
			$this->db->like('account.address',$t);
		if ($kind=(int)$this->input->get('kind'))
			$this->db->where("mod(teacher.kind,$kind*2)>=$kind",NULL ,FALSE);
		if ($t=$this->input->get('zjType'))
			$this->db->where("zjType",$t);
		if ($t=$this->input->get('sort')){
			//0综合排序 1人气 2距离 3评价 4空余时间
			switch ($t) {
				case 1:$this->db->order_by('student desc');
				break;
				case 2:$this->db->order_by("pow(lat-$input[lat],2)+pow(lng-$input[lng],2)",'asc')
					->where(["addrTime>="=>time()-1296000],NULL,FALSE);
				break;
				case 3:$this->db->order_by('grade desc');
				break;
				case 4:$this->db->order_by('freeTime desc');
				break;
				default:$this->db->order_by('grade desc,student desc');
				break;
			}
		}
		$res=$this->db->get('teacher',$input['count'],$input['count']*$input['page'])->result_array();
		foreach ($res as &$item) {
			if ($kind)
				$item['kind']=$kind;//如果是科二科三都可以，显示搜索的
		}
		return $res;
	}
	
	//场地、教练、学员地图描点
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
				$this->db->select('lat,lng,account.id,name,avatar,gender,grade,teacher.kind,year,account.address,phone,zjType,flower,praise')
					->from('teacher')->join('account', 'account.id=teacher.id')
					->where(['status'=>1,'secret'=>0])
					->order_by("pow(lat-$input[lat],2)+pow(lng-$input[lng],2)",'asc',FALSE);
// 				->limit($input['count'],$input['page']*$input['count']);
				if ($tkind>0){
					$this->db->where("teacher.kind%($tkind*2)>=$tkind");
				}
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

	function indexTodo()
	{
		$mine=$this->db->where(['`order`.status'=>2,'uid'=>UID])->select('`order`.id,tea.name tea,`order`.info->"$[0]" time,`order`.kind')
			->join('account tea','tea.id=`order`.tid')
			->order_by('`order`.id','desc')->get('`order`',5)->result_array();
		$this->load->model('teacher');
		return array_map(function($item){
			$time=json_decode($item['time']);
			$item['date']=$time->date;
			if ($time->place==0) $item['place']='';
			else{
				$place=$this->db->find('place',$time->place,'id','name');
				$item['place']=$place?$place['name']:'';
			}
			$item['time']=getTime($time->time).'-'.getTime($time->time+Teacher::CLASS_TIME);
			return $item;
		},$mine);
	}

	//首页 最近教练
	function indexRecTea()
	{
		$sql='select account.id,name,avatar,grade,num,zjType,teacher.kind,lat,lng,flower,praise FROM (select tid,count(*) as num from `order` WHERE uid=? and status<=5 group by tid order by num desc limit 5) as t'.
		' JOIN account ON account.id=t.tid JOIN teacher ON teacher.id=t.tid';
		return $this->db->query($sql,UID)->result_array();
	}
}