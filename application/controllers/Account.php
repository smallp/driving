<?php
class AccountController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('Account','m');
	}
	
// 	function checkTel() {
// 		if ($this->m->checkTel($this->input->get('tel'))) restful(200);
// 		else throw new MyException('',MyException::CONFLICT);
// 	}
	
	function addTelcheck() {
		$input=$this->input->post(['tel','code','type']);
		if (!$input) throw new MyException('',MyException::INPUT_ERR);
		if (stripos($input['tel'], '0000')==FALSE){
			$this->load->helper('mob');
			mobValidate($input['tel'], $input['code'],$input['type']);
		}
		session_start();
		$_SESSION['tel']=$input['tel'];
		restful(201);
	}

	function addAccount(){
		$data=$this->input->post(['tel','password','type','kind']);
		if (!$data) throw new MyException('',MyException::INPUT_ERR);
		if (!$this->m->checkTel($data['tel'],$data['kind']))
			throw new MyException('此账号已存在！',MyException::CONFLICT);
		if (($code=$this->input->post('code'))&&strlen($code)!=0){
			$user=$this->db->find('account', $code,'invite','id');
			if (!$user) throw new MyException('此邀请码不存在！请重新检查',MyException::INPUT_ERR);
			$code=$user['id'];
		}else $code=0;
		session_start();
		if (!isset($_SESSION['tel'])||$_SESSION['tel']!=$data['tel'])
			throw new MyException(json_encode($_SESSION),MyException::INPUT_ERR);
		if ($data=$this->m->add($data)){
			session_destroy();
			if ($code>0)
				$this->db->insert('invite',['fromid'=>$code,'toid'=>$data['id']]);
			restful(201,$data);
		}
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addToken() {
		$input=$this->input->post(['tel','password','type','kind']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		restful(200,$this->m->login($input));
	}
	
	function addPassword() {
		$data=$this->input->post(['tel','password','kind']);
		if (!isset($data)) throw new MyException('',MyException::INPUT_MISS);
		session_start();
		if (!isset($_SESSION['tel'])||$_SESSION['tel']!==$data['tel'])
			throw new MyException('',MyException::INPUT_ERR);
		$user=$this->db->where(['tel'=>$data['tel'],'kind'=>$data['kind']])->select('id')->get('account',1)->row_array();
		if (!$user) throw new MyException('请先注册！',MyException::GONE);
		$data['id']=$user['id'];
		if ($this->m->resetPwd($data)){
			session_destroy();
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function addFeedback() {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$data=$this->input->post('content');
		if ($this->db->insert('feedback',['content'=>$data,'uid'=>UID])) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modAddress() {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$data=$this->input->put(['lat','lng','address']);
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		$data['addrTime']=time();
		if ($this->m->modInfo($data)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modPassword(){
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$input=$this->input->put(['oldpwd','newpwd']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$this->m->modPwd($input) AND restful();
	}
	
	function modInfo(){
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$data=$this->input->put(['bg','secret','push'],FALSE,TRUE);
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		if ($this->db->where('id',UID)->update('account',$data)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modAvatar(){
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$avatar=$this->input->put('avatar');
		if (!$avatar) throw new MyException('',MyException::INPUT_MISS);
		if ($this->m->modInfo(['avatar'=>$avatar])) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function rongToken() {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$info=$this->db->find('account', UID,'id','id,avatar,name,kind');
		$this->load->library('rong');
		try {
			$token=$this->rong->RCgetToken($info);
		} catch (MyException $e) {
			throw new MyException($e->getCode(),MyException::THIRD);
		}
		$this->db->where('id',UID)->update('account',['rongToken'=>$token]);
		restful(200,['token'=>$token]);
	}
	
	function moneyLog() {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$page=$this->input->get('page',FALSE,0);
		restful(200,$this->m->money($page));
	}
	
	function money() {
		$type=$this->m->check();
		if ($type==-1)
			throw new MyException('',MyException::AUTH);
		if ($type) $this->db->from('teacher')->select('money');
		else $this->db->from('user')->select('money,frozenMoney');
		restful(200,$this->db->where('id',UID)->get()->row_array());
	}
	
	function notify() {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$page=$this->input->get('page',FALSE,0);
		$count=$this->input->get('count',FALSE,20);
		$data=$this->db->where('uid='.UID,NULL,FALSE)->order_by('id','desc')
			->get('notify',$count,$page*$count)->result_array();
		restful(200,$data);
	}
	
	function delNotify($id=0) {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
// 		$input=$this->input->put('id');
// 		$ids=json_decode($input,TRUE);
		if (!$id) throw new MyException('',MyException::INPUT_ERR);
// 		$num=$this->db->where('id',$id)->where('uid',UID)->count_all_results('notify');
// 		if ($num!=count($ids)) throw new MyException('',MyException::NO_RIGHTS);
		if ($this->db->where(['id'=>$id,'uid'=>UID])->delete('notify')) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function brief($id=0){
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		if ($data=$this->db->find('account', $id,'id','id,name,avatar,bg,gender')){
			restful(200,$data);
		}else throw new MyException('',MyException::GONE);
	}
	
	function teacher($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$type=$this->m->check();
		$this->load->model('teacher');
		$data=$this->teacher->teacherInfo($id);
		if ($type>=0){
			$data['relation']=$this->db->where(['fromid'=>UID,'toid'=>$id])->get('attention')->num_rows()>0;
			if ($type==0)
				$data['cared']=$this->db->where(['fromid'=>UID,'toid'=>$id])->count_all_results('attention_tea')>0;
		}
		restful(200,$data);
	}
	
	function student($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$type=$this->m->check();
		$data=$this->db->select('user.id,name,gender,sign,age,avatar')
			->join('account', 'account.id=user.id')->where('user.id',$id)->get('user',1)->row_array();
		if ($data){
			if ($type>=0)
				$data['relation']=$this->db->where(['fromid'=>UID,'toid'=>$id])->get('attention')->num_rows()>0;
			restful(200,$data);
		}else throw new MyException('',MyException::GONE);
	}
	
	function search() {
		$key=$this->input->get('key');
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		if (!$key) throw new MyException('',MyException::INPUT_ERR);
		$isfriend=(int)$this->input->get('relation');
		$this->db->start_cache();
		$this->db->select('account.id,name,avatar,account.kind');
		(is_numeric($key)&&strlen($key)==11)?
		$this->db->where('tel',$key):$this->db->like('name',$key);//根据电话搜索或者根据关键字搜索
		$this->db->where('account.id '.($isfriend?'':'not ').'in (SELECT toid FROM attention WHERE fromid='.UID.') AND account.id!='.UID);
		$this->db->stop_cache();
		$sql=$this->db->join('account', 'user.id=account.id')->get_compiled_select('user');
		$sql2=$this->db->join('account', 'teacher.id=account.id')->get_compiled_select('teacher');
		restful(200,$this->db->query("($sql) UNION ($sql2)")->result_array());
	}
	
	function teaComment($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$page=$this->input->get('page',FALSE,0);
		$count=15;
		$this->load->model('teacher');
		restful(200,$this->teacher->teaComment($id,$count,$page*$count));
	}
	
	function teacherPics($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$res=$this->db->find('teacher', $id,'id','carPic');
		if (!$res) throw new MyException('',MyException::GONE);
		else $res=[$res['carPic']];
		$data=$this->db->select('pics->"$[*].url" pics')->where("id IN (SELECT pid FROM tea_place WHERE uid=$id)")
			->get('place')->result_array();
		foreach ($data as $value) {
			$res=array_merge($res,json_decode($value['pics'],TRUE));
		}
		restful(200,$res);
	}
	
	function addInvite() {
		$type=$this->m->check();
		if ($type==-1)
			throw new MyException('',MyException::AUTH);
		$code=$this->input->post('code');
		if (!$code) throw new MyException('',MyException::INPUT_MISS);
		$user=$this->db->find('account', $code,'invite','id');
		if (!$user) throw new MyException('此邀请码不存在！请重新检查',MyException::INPUT_ERR);
		$have=$this->db->find('invite',UID,'toid');
		if ($have) throw new MyException('',MyException::DONE);
		if ($this->db->insert('invite',['fromid'=>$user['id'],'toid'=>UID])) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function inviteCode() {
		if ($this->m->check()==-1)
			throw new MyException('',MyException::AUTH);
		$res=$this->db->where('id',UID)->select('inviteMoney,invite,(SELECT count(*) FROM invite WHERE fromid=account.id) num')
			->get('account')->row_array();
		restful(200,$res);
	}
}