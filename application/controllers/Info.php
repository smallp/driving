<?php
class InfoController extends CI_Controller {
	function index(){
		$this->load->helper('infotime');
		$this->load->model ( 'account' );
		$isStu = $this->account->check()==0;
		$history= $this->db->where('`order`.status',2)->select('stu.name stu,tea.name tea,`order`.info->"$[0]" time')
		->join('account stu','stu.id=`order`.uid')
		->join('account tea','tea.id=`order`.tid')
		->order_by('`order`.id','desc')->get('`order`',20)->result_array();
		$res['history']=array_map(function($item){
			$time=json_decode($item['time']);
			$item['date']=$time->date;
			$item['time']=getTime($time->time).'-'.getTime($time->time+0.75);
			return $item;
		},$history);
		if ($isStu){
			$mine=$this->db->where(['`order`.status'=>2,'uid'=>UID])->select('tea.name tea,`order`.info->"$[0]" time,`order`.kind')
			->join('account tea','tea.id=`order`.tid')
			->order_by('`order`.id','desc')->get('`order`',5)->result_array();
			$res['todo']=array_map(function($item){
				$time=json_decode($item['time']);
				$item['date']=$time->date;
				$item['place']=$this->db->find('place',$time->place,'id','name')['name'];
				$item['time']=getTime($time->time).'-'.getTime($time->time+0.75);
				return $item;
			},$mine);
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
	
	function time() {
		$data=file_get_contents(__DIR__.'/back/time.json');
		restful(200,['time'=>json_decode($data,TRUE)]);
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
}