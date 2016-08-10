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
}
