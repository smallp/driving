<?php
/**
*处理驾校、场地等信息
*
*@author small
*/
class InfoController extends CI_Controller{
	const PLACE=13;
	const SCHOOL=14;
	function __construct(){
		parent::__construct();
		session_start();
		if(!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}

	function addPlace(){
		$data=$this->db->create('place');
		$flag=$this->db->insert('place',$data);
		if($flag){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$this->db->insert_id(),
				'text'=>"添加场地$data[name]",
				'type'=>self::PLACE
			]);
			restful(201);
		}
		else throw new MyException('',MyException::DATABASE);
	}
	
	function place($id=0){
		if($id==0){
			$page=$this->input->get('page');
			if ($page===NULL){
				$school=$this->db->get('school')->result_array();
				$this->load->view('back/place',['school'=>$school]);
			}else{
				$count=15;
				if ($key=$this->input->get('key'))
					$this->db->like('place.name',$key);
				$data=$this->db->select('place.id,place.name,place.address,admin,tel,school.name school,status')
					->join('school', 'school.id=place.school')
					->get('place',$count,$page*$count)->result_array();
				$total=$key?1:$this->db->count_all('place');
				restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
			}
		}else{
			if(!is_numeric($id))
				throw new MyException('',MyException::INPUT_ERR);
			$data=$this->db->select('place.*')->where('place.id',$id)
				->get('place')->row_array();
			if($data){
				$data['pics']=json_decode($data['pics'],TRUE);
				restful(200,$data);
			}else throw new MyException('',MyException::GONE);
		}
	}
	
	function newPlace($id=0){
		if($id==0){
			$page=$this->input->get('page');
			if ($page===NULL){
				$school=$this->db->get('school')->result_array();
				$this->load->view('back/newPlace',['school'=>$school]);
			}else{
				$count=15;
				if ($key=$this->input->get('key'))
					$this->db->like('place.name',$key);
				if ($key=$this->input->get('status'))
					$this->db->where('place.status',$key-2);
				$total=$this->db->where('uid !=',0)->count_all_results('place',false);
				$data=$this->db->select('place.id,place.time,place.name,place.address,admin,place.tel,school.name school,place.status,account.name teacher')
					->join('school', 'school.id=place.school')
					->join('account', 'account.id=place.uid')
					->get('',$count,$page*$count)->result_array();
				restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
			}
		}else{
			if(!is_numeric($id))
				throw new MyException('',MyException::INPUT_ERR);
			$data=$this->db->where('id',$id)->get('place')->row_array();
			if($data){
				$data['pics']=json_decode($data['pics'],TRUE);
				restful(200,$data);
			}else throw new MyException('',MyException::GONE);
		}
	}

	function modPlaceStatus($id=0){
		$status=(int)$this->input->put('status');
		$place=$this->db->find('place',$id,'id','uid,status');
		if (!$place||$place['status']!=-1) throw new MyException('',MyException::DONE);
		if ($status==0){
			$reason=$this->input->put('reason');
			if (empty($reason)) throw new MyException('',MyException::INPUT_MISS);
			$data=['status'=>0,'ps'=>$reason];
		}else{
			$user=$this->db->find('teacher',$place['uid'],'id','placeRank,addPlace');
			$incNum=$this->db->where(['placeRank <'=>$user['placeRank'],'addPlace'=>$user['addPlace']])->count_all_results('teacher');
			$this->db->between('placeRank',$user['placeRank']-$incNum,$user['placeRank']-1)->step('teacher','placeRank');
			$this->db->where('id',$place['uid'])->set(['placeRank'=>'placeRank - '.$incNum,'addPlace'=>'addPlace+1'],'',false)->update('teacher');
			$data=['status'=>1];
		}
		if ($this->db->where('id',$id)->update('place',$data)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modPlace($id=0){
		$data=$this->db->create('place',FALSE);
		if($this->db->where('id',$id)->update('place',$data)){
			$this->db->insert('oprate_log',[
					'uid'=>$_SESSION['admin'],
					'link'=>$id,
					'text'=>"修改场地$data[name]",
					'type'=>self::PLACE
			]);
			restful(200);
		}else throw new MyException('',MyException::DATABASE);
	}
	
	//批量隐藏/显示场地
	function delPlace(){
		$id=$this->input->put('id');
		if (!is_array($id)) throw new MyException('',MyException::INPUT_ERR);
		if($this->db->where_in('id',$id)->set('status','1-status',FALSE)->update('place'))
			restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function school($id=0){
		if ($id>0){
			$data=$this->db->select('school.*,citycode.name city')->where('school.id',$id)
				->join('citycode', 'citycode.id=school.city')->get('school')
				->row_array();
			if (!$data) throw new MyException('',MyException::GONE);
			else restful(200,$data);
		}
		$page=$this->input->get('page');
		if($page===NULL)
			$this->load->view('back/school');
		else{
			$count=10;
			if ($key=$this->input->get('key'))
				$this->db->like('school.name',$key);
			else $this->db->limit($count,$page*$count);
			$data=$this->db->select('school.id,school.name,car,citycode.name city,(SELECT count(*) FROM teacher WHERE school=school.id) teacher,(SELECT count(*) FROM place WHERE school=school.id) place,address')
				->join('citycode', 'citycode.id=school.city')->get('school')
				->result_array();
			$total=$key?1:$this->db->count_all('school');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}
	
	function delSchool($id=0){
		$num=$this->db->where('school',$id)->count_all_results('teacher');
		if ($num) throw new MyException('此驾校还有教练，请先处理完。',MyException::INPUT_ERR);
		if($this->db->where('id',$id)->delete('school'))
			restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modSchool($id=0){
		$data=$this->db->create('school',FALSE);
		if($this->db->where('id',$id)->update('school',$data))
			restful();
		else
			throw new MyException('',MyException::DATABASE);
	}
	
	function addSchool(){
		$data=$this->db->create('school');
		if($this->db->insert('school',$data))
			restful(201);
		else
			throw new MyException('',MyException::DATABASE);
	}
	
	function law($kind=0){
		$page=(int)$this->input->get('page');
		$count=20;
		$this->db->start_cache();
		$data=$this->db->select('id,title,content')->order_by('id','desc')->where('kind',$kind)
		->get('law',$count,$page*$count)->result_array();
		$data=array_map(function($e){
			$e['content']=gzuncompress($e['content']);
			$e['content']=mb_substr(trim(strip_tags($e['content'])), 0,25);
			return $e;
		}, $data);
		$total=$this->db->count_all_results();
		$this->load->view('back/law',['kind'=>$kind,'data'=>$data,'page'=>$page,'total'=>ceil($total/$count)]);
	}
	
	function delLaw($id=0){
		if ($id==0){
			$id=$this->input->put('id');
		}else $id=[$id];
		if (!$id) throw new MyException('',MyException::INPUT_MISS);
		if ($this->db->where_in('id',$id)->delete('law')){
			foreach ($id as $item) {
				unlink("data/law$item.html");
			}
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function modLaw($id=0){
		$data=$this->db->create('law',FALSE);
		$data['content']=$this->input->put('content',FALSE);
		if ($data['content']){
			$this->_writeLaw(['id'=>$id,'content'=>$data['content']]);
			$data['content']=gzcompress($data['content']);
		}else unset($data['content']);
		if (isset($data['pic'])&&empty($data['pic']))
			unset($data['pic']);
		if ($this->db->where('id',$id)->update('law',$data)){
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function addLaw(){
		$data=$this->db->create('law');
		$content=$this->input->post('content',FALSE);
		$data['content']=gzcompress($content);
		$data['date']=date('Y-m-d');
		if ($this->db->insert('law',$data)){
			$id=$this->db->insert_id();
			$this->_writeLaw(['id'=>$id,'content'=>$content]);
			echo '<script>alert("添加成功！");history.back();</script>';
		}
		else throw new MyException('',MyException::DATABASE);
	}
	
	function _writeLaw($data) {
		$tpl=file_get_contents(VIEWPATH.'law.tpl');
		$content=str_replace('{content}', $data['content'],$tpl);
		file_put_contents("data/law$data[id].html", $content);
	}
	
	function feedback() {
		$page=$this->input->get('page');
		if($page===NULL)
			$this->load->view('back/feedback');
		else{
			$count=20;
			$data=$this->db->select('tel,name,kind,content,feedback.time')
				->join('account', 'account.id=feedback.uid')
				->get('feedback',$count,$page*$count)->result_array();
			$total=$this->db->count_all('feedback');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function enroll(){
		$page=$this->input->get('page');
		if($page===NULL)
			$this->load->view('back/enroll');
		else{
			$count=20;
			if ($key=$this->input->get('key')){
				if (is_numeric($key)) $this->db->like('tel',$key);
				else $this->db->like('name',$key);
			}
			if ($begin=$this->input->get('begin')){
				$begin=strtotime($begin);
				$end=strtotime($this->input->get('end'))+86400;
				$this->db->between('time',$begin,$end);
			}
			$total=$this->db->count_all_results('enroll',false);
			$data=$this->db->get('',$count,$page*$count)->result_array();
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}
	
	function newTeacher(){
		$page=$this->input->get('page');
		if($page===NULL)
			$this->load->view('back/newTeacher');
		else{
			$count=15;
			if ($key=$this->input->get('name'))
				$this->db->like('new_teacher.name',$key);
			if ($key=$this->input->get('tel'))
				$this->db->like('new_teacher.tel',$key);
			$total=$this->db->count_all_results('new_teacher',false);
			$data=$this->db->select('new_teacher.*,account.name user')->join('account','account.id=new_teacher.uid')
			->get('',$count,$page*$count)->result_array();
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function xia(){
		$page=$this->input->get('page');
		if ($page===null){
			$this->load->view('back/xia');
		}else{
			$count=10;
			if ($t=$this->input->get(['begin','end'])){
				$this->db->between('time',$t['begin'],$t['end'].' 23:59:59');
			}
			$total=$this->db->count_all_results('xia',false);
			$data=$this->db->order_by('id','desc')->get('',$count,$page*$count)->result_array();
			restful(200,$data);
		}
	}
}