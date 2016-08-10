<?php
class FriendController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		if ($this->account->check()==-1)
			throw new MyException('',MyException::AUTH);
	}
	
	function friendReq() {
		$page=(int)$this->input->get('page');
		$count=10;
		$data=$this->db->where('uid',UID)->order_by('id','desc')
			->get('friendReq',$count,$count*$page)->result_array();
		restful(200,array_map(function ($item){
			$item['extra']=json_decode($item['extra'],TRUE);
			return $item;
		},$data));
	}

	function delfriendReq($id=0){
		$this->db->delete('friendReq',['uid'=>UID,'id'=>$id]);
		restful(204);
	}

	function addFriend(){
		$id=(int)$this->input->post('id');
	
		$this->load->model('notify','m');
		if ($this->m->attend($id)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addFriendRes(){
		$input=$this->input->post(['id','status']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		if (!is_numeric($input['id'])) throw new MyException('',MyException::INPUT_ERR);

		$this->load->model('notify','m');
		if ($this->m->attendRes($input)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function delFriend($id=0){
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$flag=$this->db->query("DELETE FROM attention WHERE (fromid=? AND toid=$id) OR (fromid=$id AND toid=?)",[UID,UID]);
		if ($flag){
			$this->load->model('notify','m');
			$this->db->where("((uid=$id AND link=".UID.") OR (uid=".UID." AND link=$id)) AND ".
				"type BETWEEN ".Notify::FRI_REQUEST.' AND '.Notify::FRI_REFUSE)
				->update('notify',['type'=>Notify::FRI_REFUSE]);
			$myInfo=$this->db->find('account',UID,'id','id,kind');
			$tarInfo=$this->db->find('account',$id,'id','id,kind');
			$this->load->library('rong');
			$this->rong->cmd($myInfo,$tarInfo);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function friends(){
		$res=$this->db->where("id in (SELECT toid FROM attention WHERE fromid=".UID.")",NULL,FALSE)
			->select('id,name,avatar,kind,tel')->get('account')->result_array();
		restful(200,$res);
	}
}
