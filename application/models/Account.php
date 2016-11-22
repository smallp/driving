<?php
class Account extends CI_Model {
	const KEY='driving';
	const AVATAR='http://7xtdgv.com2.z0.glb.qiniucdn.com/FACE_BOY.jpg';//默认男生头像
	const AVATAR_GIRL='http://7xtdgv.com2.z0.glb.qiniucdn.com/FACE_GIRL.jpg';
	const AVATAR_TEA='http://7xtdgv.com2.z0.glb.qiniucdn.com/FACE_TEA.png';
	
	function checkTel($tel,$kind){
		if (!$tel||!is_numeric($tel)) throw new MyException('',MyException::INPUT_ERR);
		$res=$this->db->where(['tel'=>$tel,'kind'=>$kind])->get('account',1)->num_rows();
		return $res==0;
	}
	
	function check($data=NULL) {
		$data=$data?:getToken();
		if ($data['id']==0)
			return -1;
		$res=$this->db->find('account', $data['id'],'id','token,kind');
		if (empty($res)||$res['token']!=$data['token'])
			return -1;
		else{
			define('UID', $data['id']);
			return $res['kind'];
		}
	}
	
	//添加账号
	function add($input) {
		$data=['tel'=>$input['tel'],'token'=>md5(uniqid()),
				'kind'=>$input['kind'],
				'password'=>md5(md5($input['password']).SELF::KEY),
				'invite'=>substr(uniqid(), -8)
		];
		if ($input['kind']==0){
			$data['name']='学员'.substr($input['tel'],-4);
			$data['avatar']=self::AVATAR;
			$data['status']=1;
		}else{
			$data['name']='教练'.substr($input['tel'],-4);
			$data['avatar']=self::AVATAR_TEA;
		}
		if (!$this->db->insert('account',$data))
			return FALSE;
		$id=$this->db->insert_id();
		$this->db->where('id',$id)
			->update('account',['invite'=>substr($data['invite'].$id, -8)]);
		if ($input['kind']==0){
			$this->db->insert('user',['id'=>$id]);
		}else {
			$data=['id'=>$id,'orderInfo'=>'[]','phone'=>$input['tel']];
			$this->db->insert('teacher',$data);
		}
		return $this->login($input);
	}
	
	function modPwd($input) {
		$old=$this->db->find('account',UID,'id','password')['password'];
		if ($old==md5(md5($input['oldpwd']).SELF::KEY)){
			return $this->db->where('id',UID)
			->update('account',['password'=>md5(md5($input['newpwd']).SELF::KEY)]);
		}else throw new MyException('密码错误!',MyException::INPUT_ERR);
	}
	
	function modInfo($data) {
		return $this->db->where('id',UID)->update('account',$data);
	}
	
	//重置密码
	function resetPwd($input) {
		return $this->db->where('id',$input['id'])
			->update('account',['password'=>md5(md5($input['password']).SELF::KEY)]);
	}
	
	function login($data) {
		$user=$this->db->where(['tel'=>$data['tel'],'kind'=>$data['kind']])->get('account',1)->row_array();
		if (!$user) throw new MyException('用户不存在！',MyException::INPUT_ERR);
		if ($user['password']==md5(md5($data['password']).SELF::KEY)){
			$user['token'] = md5(uniqid().rand());
			if ($user['rongToken']==''){
				$this->load->library('rong');
				try {
					$token=$this->rong->RCgetToken($user);
				} catch (MyException $e) {
					error_log('rong error:'.$e->getCode());
					$token='';
				}
				$user['rongToken']=$token;//失败但是要正常登陆
			}
			$user=array_merge($user,$this->db->find($user['kind']==0?'user':'teacher', $user['id']));
			unset($user['password'],$user['orderInfo']);
			$this->db->where('id',$user['id'])->update('account',['token'=>$user['token'],'type'=>$data['type'],'rongToken'=>$user['rongToken']]);
			return $user;
		}else throw new MyException('密码错误!',MyException::INPUT_ERR);
	}
	
	//获取我的资金流水
	function money($page){
		$count=15;
		$data=$this->db->where('uid',UID)->order_by('id','desc')
			->get('money_log',$count,$page*$count)->result_array();
		return $data;
	}
	
	//标记为活跃便于统计
	function active() {
		return $this->db->simple_query('INSERT IGNORE INTO huoyue VALUES ('.UID.')');
	}
}