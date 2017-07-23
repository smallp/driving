<?php
class ActivityController extends CI_Controller {
	const PATH=__DIR__.'/back/activity.json';
	function __construct() {
		parent::__construct();
		$this->load->model('activity','m');
		//网页进入，身份验证不同，需要各自独立处理
	}
	
	function activity() {
		$this->load->model('account');
		if (($kind=$this->account->check())==-1)
			throw new MyException('',MyException::AUTH);
		$data=$this->db->select('id,title,intro,pic,detail')->where(['status'=>1,'kind'=>$kind])->get('activity')->result_array();
		restful(200,$data);
	}
	
	function first() {
		$this->load->model('account');
		if ($this->account->check()!=0)
			throw new MyException('',MyException::AUTH);
		$type=(int)$this->input->get('type');
		if (($money=$this->m->first($type))!==FALSE) restful(200,['info'=>$money]);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function share() {
		$this->load->model('account');
		if ($this->account->check()!=0)
			throw new MyException('',MyException::AUTH);
		if ($this->m->share((int)$this->input->get('channel'))) restful(200,['code'=>1]);
		else restful(200,['code'=>0]);
	}
	
	function zhuan($getNum=FALSE) {
		session_start();
		$data=file_get_contents(ActivityController::PATH);
		$data=json_decode($data,TRUE);
		if ($getNum){
			if (!isset($_SESSION['id']))
				throw new MyException('',MyException::AUTH);
			$text=$this->m->activities($data['zhuan'],FALSE);
			restful(200,['info'=>$text]);
		}else{
			$input=$this->input->get(['id','token']);
			if (!$input) die('请使用APP打开！');
			$this->load->model('account');
			if ($this->account->check($input)!=0)
				die('<h1>请先登录</h1>');
			$_SESSION['id']=UID;
			$data=array_map(function($item){
				return $item['text'];
			}, $data['zhuan']);
			$this->load->view('activity/zhuan',$data);
		}
	}
	
	function gua($getNum=FALSE) {
		session_start();
		$data=file_get_contents(ActivityController::PATH);
		$data=json_decode($data,TRUE);
		if ($getNum){
			if (!isset($_SESSION['id']))
				throw new MyException('',MyException::AUTH);
			$text=$this->m->activities($data['gua'],TRUE);
			restful(200,['info'=>$text]);
		}else{
			$input=$this->input->get(['id','token']);
			if (!$input) die('请使用APP打开！');
			$this->load->model('account');
			if ($this->account->check($input)!=0)
				die('<h1>请先登录</h1>');
			$data=array_map(function($item){
				return $item['text'];
			}, $data['gua']);
			$_SESSION['id']=UID;
			$this->load->view('activity/gua',$data);
		}
	}

	function addFlower(){
		$this->load->model('account');
		if ($this->account->check()!=0)
			throw new MyException('',MyException::AUTH);
		$num=(int)$this->input->post('num');
		if ($id=$this->input->post('tid')){
			$table='teacher';
			$name=$this->db->find('account',$id,'id','name');
			if (!$name) throw new MyException('此教练不存在！',MyException::GONE);
			$name=$name['name'];
		}else if ($id=$this->input->post('pid')){
			$table='place';
			$name=$this->db->find('place',$id,'id','name');
			if (!$name) throw new MyException('此场地不存在！',MyException::GONE);
			$name='场地'.$name['name'];
		}else throw new MyException('',MyException::INPUT_ERR);
		if ($num<=0) throw new MyException('',MyException::INPUT_ERR);
		$this->db->trans_begin();
		$flower=$this->db->query('SELECT flower FROM user WHERE id=? FOR UPDATE',UID)->row_array()['flower'];
		if ($flower<$num) throw new MyException('花的数量不够！',MyException::INPUT_ERR);
		$this->db->where('id',UID)->step('user','flower',false,$num);
		$this->db->where('id',$id)->step($table,'flower',true,$num);
		if ($this->db->trans_complete()){
			// $this->db->insert('money_log',
			// 	['uid'=>UID,'content'=>"您已成功送给$name${num}朵花",'time'=>time(),'num'=>$charge['amount'],'realMoney'=>$charge['amount']]
			// );
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}

	function addPraise(){
		$this->load->model('account');
		if ($this->account->check()!=0)
			throw new MyException('',MyException::AUTH);
		$num=(int)$this->input->post('num');
		if ($id=$this->input->post('tid')){
			$table='teacher';
			$name=$this->db->find('account',$id,'id','name');
			if (!$name) throw new MyException('此教练不存在！',MyException::GONE);
			$name=$name['name'];
		}else if ($id=$this->input->post('pid')){
			$table='place';
			$name=$this->db->find('place',$id,'id','name');
			if (!$name) throw new MyException('此场地不存在！',MyException::GONE);
			$name='场地'.$name['name'];
		}else throw new MyException('',MyException::INPUT_ERR);
		if ($num<=0) throw new MyException('',MyException::INPUT_ERR);
		$this->db->trans_begin();
		$praise=$this->db->query('SELECT praise FROM user WHERE id=? FOR UPDATE',UID)->row_array()['praise'];
		if ($praise<$num) throw new MyException('赞的数量不够！',MyException::INPUT_ERR);
		$this->db->where('id',UID)->step('user','praise',false,$num);
		$this->db->where('id',$id)->step($table,'praise',true,$num);
		if ($this->db->trans_complete()){
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}
}
