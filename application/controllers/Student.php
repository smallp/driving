<?php
class StudentController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		if ($this->account->check()!=0)
			throw new MyException('',MyException::AUTH);
	}
	
	function info($id=0){
		if ($id==0||$id==UID){
			$data=$this->db->select('tel,account.id,status,checkInfo,name,avatar,token,push,secret,gender,bg,rongToken,user.*')
				->join('account', 'account.id=user.id')->where('user.id',UID)->get('user',1)->row_array();
			$this->account->active();
			restful(200,$data);
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			$data=$this->db->select('user.id,name,gender,sign,age,avatar,bg')
				->join('account', 'account.id=user.id')->where('user.id',$id)->get('user',1)->row_array();
			if ($data){
				$data['relation']=$this->db->where(['fromid'=>UID,'toid'=>$id])->get('attention')->num_rows()>0;
				restful(200,$data);
			}else throw new MyException('',MyException::GONE);
		}
	}
	
	//修改用户信息
	function modInfo(){
		$data=$this->input->put(['name','gender','age','sign']);
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		$account=['name'=>$data['name'],'gender'=>$data['gender']];
		if ($data['gender']==1){
			$user=$this->db->find('account',UID,'id','avatar');
			if ($user['avatar']==Account::AVATAR)
				$account['avatar']=Account::AVATAR_GIRL;
		}
		$this->account->modInfo($account);
		if ($this->db->where('id',UID)
				->update('user',['age'=>$data['age'],'sign'=>$data['sign']]))
			restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addIdentify() {
		throw new MyException('此功能暂未开启',MyException::NO_RIGHTS);
		$data=$this->input->post(['realname','peopleId','peoplePic','peoplePicB']);
		if (!$data||empty($data['peopleId'])) throw new MyException('',MyException::INPUT_MISS);
		$this->db->where('id',UID)->update('account',['status'=>1,'checkInfo'=>'']);
		if ($this->db->where('id',UID)->update('user',$data)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addTeacher(){
		$id=(int)$this->input->post('id');
		if ($this->db->insert('attention_tea',['fromid'=>UID,'toid'=>$id])) restful(201);
		else throw new MyException('',MyException::DATABASE);
// 		$this->load->model('notify','m');
// 		if ($this->m->attend($id)) restful(201);
// 		else throw new MyException('',MyException::DATABASE);
	}
	
	function delTeacher($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		if ($this->db->where(['fromid'=>UID,'toid'=>$id])->delete('attention_tea')) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function teachers(){
		$res=$this->db->where("id in (SELECT toid FROM attention_tea WHERE fromid=".UID.")",NULL,FALSE)
			->select('id,name,avatar')->get('account')->result_array();
		restful(200,$res);
	}
}