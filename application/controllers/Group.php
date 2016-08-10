<?php
class GroupController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		if ($this->account->check()==-1)
			throw new MyException('',MyException::AUTH);
		$this->load->model('group','m');
		$this->load->library('rong');
	}

	function Group($id=0){
		if ($id==0){
			$data=$this->db->where('id in (SELECT gid FROM group_mem WHERE uid="'.UID.'")')->get('group')->result_array();
			restful(200,$data);
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			restful(200,$this->m->item($id));
		}
	}
	
	function delGroup($id=0){
		$data=$this->db->find('`group`', $id);
		if (!$data||$data['owner']!=UID)
			throw new MyException('',MyException::NO_RIGHTS);
		if ($this->db->where('id',$id)->delete('`group`')) {
			$this->db->where('gid',$id)->delete('group_mem');
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function modGroup($id=0){
		$data=$this->db->create('group',FALSE);
		$g=$this->db->find('`group`', $id);
// 		if (!$g||$g['owner']!=UID)
// 			throw new MyException('',MyException::NO_RIGHTS);
		if ($this->db->where('id',$id)->update('`group`',$data)){
			if (isset($data['name']))
				$this->rong->refreshGrp($id,$data['name']);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function addGroup(){
		$mem=$this->input->post('data');
		$mem&&($mem=json_decode($mem,TRUE));
		if (!$mem)
			throw new MyException('',MyException::INPUT_ERR);
		$id=$this->m->add($mem);
		restful(201,$this->m->item($id));
	}
	
	function delMe($id){
		$this->db->where(['uid'=>UID,'gid'=>$id])->delete('group_mem');
		$this->rong->quitGrp($id,UID);
		restful();
	}
	
	function addMember($id=0) {
		$mem=$this->input->post('data');
		$mem&&($mem=json_decode($mem,TRUE));
		if (!$mem)
			throw new MyException('',MyException::INPUT_ERR);
		$res=$this->m->addMember($id,$mem);
		if ($res!==FALSE) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
}
