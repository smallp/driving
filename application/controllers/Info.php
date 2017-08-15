<?php
class InfoController extends CI_Controller {
	function index(){
		$this->load->helper('infotime');
		$this->load->model ( 'account' );
		$isStu = $this->account->check()==0;
		$history= $this->db->where('`order`.status <=',5)->select('stu.name stu,tea.name tea,`order`.info->"$[0]" time')
		->join('account stu','stu.id=`order`.uid')
		->join('account tea','tea.id=`order`.tid')
		->order_by('`order`.id','desc')->get('`order`',10)->result_array();
		$res['history']=array_map(function($item){
			$time=json_decode($item['time']);
			$item['date']=$time->date;
			$item['time']=getTime($time->time).'-'.getTime($time->time+40);
			return $item;
		},$history);
		if ($isStu){
			$this->load->model ( 'student' );
			$res['todo']=$this->student->indexTodo();
			$res['recTeacher']=$this->student->indexRecTea();
		}
		restful(200,$res);
	}

	function nearbyPoint() {
		if (!($input=$this->input->get(['lat','lng','kind'])))
			throw new MyException('',MyException::INPUT_MISS);
		$input['page']=$this->input->get('page',FALSE,0);
		$input['count']=20;
		$this->load->model('Student','m');
		$data=$this->m->nearbyPoint($input);
		restful(200,$data);
	}
	
	function place($id=0) {
		$this->load->model('place','m');
		if ($id==0){
			restful(200,$this->m->placeList());
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			$data=$this->m->placeItem($id);
			restful(200,$data);
		}
	}

	function addPlace(){
		$this->load->model ( 'account' );
		if ($this->account->check()!=1)
			throw new MyException('',MyException::AUTH);
		$data=$this->db->create('place');
		$data['uid']=UID;
		$data['status']=-1;
		$flag=$this->db->insert('place',$data);
		if($flag){
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function placeTeacher($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$res=$this->db->select('account.id,name,avatar,grade,year,student,teacher.kind,zjType')
		->where("teacher.id IN (SELECT uid FROM tea_place WHERE pid=$id) AND account.status=1")
		->order_by('student','desc')->join('account', 'account.id=teacher.id')
		->get('teacher')->result_array();
		restful(200,$res);
	}
	
	function nearbyStudent() {
		$input=$this->input->get();
		$input['page']=$this->input->get('page',FALSE,0);
		$input['count']=$this->input->get('count',FALSE,10);
		$this->load->model('Student','m');
		$this->load->model('account');
		$this->account->check();
		restful(200,$this->m->nearby($input));
	}
	
	function nearTeacher() {
		if (!($input=$this->input->get(['lat','lng'])))
			throw new MyException('',MyException::INPUT_MISS);
		$input['page']=$this->input->get('page',FALSE,0);
		$input['count']=$this->input->get('count',FALSE,10);
		$this->load->model('Student','m');
		restful(200,$this->m->nearTeacher($input));
	}
	
	function school($city=0) {
		if ($city!=0)
			$this->db->where('city',$city);
		restful(200,$this->db->get('school')->result_array());
	}
	
	function placeBySchool($school=0) {
		if (!is_numeric($school)||$school<=0)
			throw new MyException('',MyException::INPUT_MISS);
		$this->db->select('place.id,place.name name,school.name school,JSON_UNQUOTE(pics->"$[0].url") pic,grade,place.intro,place.address,area')
			->join('school','place.school=school.id')->where('school.id',$school);
		restful(200,$this->db->get('place')->result_array());
	}
	
	function addContact() {
		$this->load->model('account');
		if ($this->account->check()==-1)
			throw new MyException('',MyException::AUTH);
		$input=json_decode($this->input->post('data'));
		if (!$input)
			throw new MyException('',MyException::INPUT_ERR);
		$res=$this->db->select('id,name,avatar,kind')->where_in('tel',$input,TRUE)
			->where('id NOT IN (SELECT toid FROM attention WHERE fromid='.UID.')')->get('account')->result_array();
		restful(200,$res);
	}
	
	function time($id=0) {
		if ($id==0){
			if ($this->account->check()!=1)
				throw new MyException('',MyException::AUTH);
			$id=UID;
		}
		$res=$this->db->find('teacher',$id,'id','startTime,endTime');
		if (!$res) throw new MyException('',MyException::GONE);
		else restful(200,$res);
	}

	function slide(){
		$data=file_get_contents(__DIR__.'/back/slide.json');
		$data=json_decode($data,true);
		$res=[];
		foreach ($data as $value) {
			if ($value['pic']!='') $res[]=$value;
		}
		restful(200,$res);
	}

	function slideView($id=-1)
	{
		$id=(int)$id;
		if ($id<0||$id>=6) throw new MyException('',MyException::INPUT_ERR);
		$data=file_get_contents(__DIR__.'/back/slide.json');
		$data=json_decode($data,true);
		$pic=$data[$id]['content'];
		if ($pic=='') die('<h1>此页面已不存在！</h1>');
		else $this->load->view('index/slide_view',['data'=>$pic]);
	}
	
	function law($id=0) {
		if ($id!=0){
			$res=$this->db->find('law', $id,'id','title,date,content');
			if (!$res) throw new MyException('',MyException::GONE);
			$res['content']=gzuncompress($res['content']);
			restful(200,$res);
		}else{
			$page=(int)$this->input->get('page');
			$count=20;
			$data=$this->db->select('id,title,content')->order_by('id','desc')->where('kind',0)
			->get('law',$count,$page*$count)->result_array();
			restful(200,array_map(function($e){
				$e['content']=gzuncompress($e['content']);
				$e['content']=mb_substr(trim(strip_tags($e['content'])), 0,25);
				return $e;
			}, $data));
		};
	}
	
	function skill($id=0) {
		if ($id!=0){
			$res=$this->db->find('law', $id,'id','title,date,content');
			if (!$res) throw new MyException('',MyException::GONE);
			$res['content']=gzuncompress($res['content']);
			restful(200,$res);
		}else{
			$page=(int)$this->input->get('page');
			$count=10;
			$data=$this->db->select('id,title,content,pic,date')->order_by('id','desc')->where('kind',1)
			->get('law',$count,$page*$count)->result_array();
			restful(200,array_map(function($e){
				$e['content']=gzuncompress($e['content']);
				$e['content']=mb_substr(trim(strip_tags($e['content'])), 0,100);
				$e['date']=str_replace('-', '.', $e['date']);
				return $e;
			}, $data));
		};
	}
	
	function addXia(){
		$data=$this->input->post();
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		if ($this->db->insert('xia',$data)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
}