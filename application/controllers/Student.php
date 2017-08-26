<?php
class StudentController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		if ($this->account->check()!=0)
			throw new MyException('',MyException::AUTH);
	}

	function addEnroll(){
		$input=$this->db->create('enroll');
		$telCheck=$this->input->post(['type','code']);
		if (stripos($input['tel'], '0000')==FALSE){
			$this->load->helper('mob');
			mobValidate($input['tel'], $telCheck['code'],$telCheck['type']);
		}
		$input['time']=time();
		$input['uid']=UID;
		if ($this->db->insert('enroll',$input)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function info($id=0){
		if ($id==0||$id==UID){
			$data=$this->db->select('tel,account.id,invite,status,checkInfo,name,avatar,token,push,secret,gender,bg,rongToken,user.*')
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

	function addInviteTea(){
		$input=$this->input->post(['tel','name','area','school']);
		if (!$input||!is_numeric($input['tel'])) throw new MyException('',MyException::INPUT_MISS);
		if ($this->db->where(['tel'=>$input['tel'],'kind'=>1])->count_all_results('account')!=0)
			throw new MyException('此教练已经入驻蚁众平台',MyException::DONE);
		if ($this->db->where('tel',$input['tel'])->count_all_results('new_teacher')!=0)
			throw new MyException('已向此教练发送邀请',MyException::CONFLICT);
		$input['uid']=UID;
		if ($this->db->insert('new_teacher',$input)){
			$this->load->model('notify');
			$this->notify->sendSms(Notify::SMS_NEW_TEACHER,$input['tel'],['name'=>$input['name']]);
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}

	/*function addFlower(){
		$num=(int)$this->input->post('num');
		if ($num<=0) throw new MyException('',MyException::INPUT_ERR);
		$this->db->trans_begin();
		$user=$this->db->query('SELECT money FROM user WHERE id=? FOR UPDATE',UID)->row_array();
		if ($user['money']<$num) throw new MyException('学车币不足，请先充值。',MyException::NO_RIGHTS);
		$this->db->where('id',UID)->set(['money'=>'money -'.$num,'flower'=>'flower + '.$num],'',false)->update('user');
		$this->db->insert('money_log',
		['uid'=>UID,'content'=>"您已成功购买花",'time'=>time(),'num'=>-$num,'realMoney'=>-$num]);
		if ($this->db->trans_complete()) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}*/
}