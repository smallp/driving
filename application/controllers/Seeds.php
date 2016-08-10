<?php
class SeedsController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		if ($this->account->check()==-1)
			throw new MyException('',MyException::AUTH);
		$this->load->model('seeds','m');
	}

	function onesSeeds($uid=0){
		if (!is_numeric($uid)) throw new MyException('',MyException::INPUT_ERR);
		$page=$this->input->get('page',FALSE,0);
		$count=20;
		$this->db->where("seeds.uid=$uid");
		restful(200,$this->m->getSeeds($count,$page*$count));
	}

	function seeds($id=0){
		if ($id==0){	
			$page=$this->input->get('page',FALSE,0);
			$count=20;
			restful(200,$this->m->getSeeds($count,$page*$count));
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			$this->db->where("seeds.id=$id");
			$data=$this->m->getSeeds();
			if ($data){
				restful(200,$data[0]);
			}else throw new MyException('',MyException::GONE);
		}
	}
	function delSeeds($id=0){
		$uid=$this->db->find('seeds',$id,'id','uid');
		if (!$uid) throw new MyException('',MyException::GONE);
		else if ($uid['uid']!=UID) throw new MyException('',MyException::NO_RIGHTS);
		if ($this->db->where('id',$id)->delete('seeds')) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addSeeds(){
		$data=$this->db->create('seeds');
		$data['time']=time();
		$data['uid']=UID;
		if (is_null(json_decode($data['pics']))) throw new MyException('',MyException::INPUT_ERR);
		$data['pics']=gzcompress($data['pics']);
		if ($this->db->insert('seeds',$data)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addComment() {
		$data=$this->input->post(['fid','sid','content']);
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		if ($this->m->comment($data)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function delComment($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		if ($this->m->delComment($id)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addPraise() {
		$id=$this->input->post('id');
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		if ($this->m->praise($id,TRUE)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function delPraise($id=0){
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		if ($this->m->praise($id,FALSE)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function praise($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$res=$this->db->query("SELECT name,id,avatar,kind FROM account WHERE id IN (SELECT uid FROM praise WHERE sid=$id)")
			->result_array();
		restful(200,$res);
	}
}