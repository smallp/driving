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
			$data=$this->db->find('account join teacher on teacher.id=account.id',$id,'account.id','account.name,flowRank');
			if (!$data) throw new MyException('此教练不存在！',MyException::GONE);
			$name=$data['name'];
		}else if ($id=$this->input->post('pid')){
			$table='place';
			$data=$this->db->find('place',$id,'id','name,flowRank');
			if (!$data) throw new MyException('此场地不存在！',MyException::GONE);
			$name='场地'.$data['name'];
		}else throw new MyException('',MyException::INPUT_ERR);
		if ($num<=0) throw new MyException('',MyException::INPUT_ERR);
		$this->db->trans_begin();
		$money=$this->db->query('SELECT money FROM user WHERE id=? FOR UPDATE',UID)->row_array()['money'];
		if ($money<$num) throw new MyException('余额不足，请充值',MyException::NO_RIGHTS);
		$this->db->where('id',UID)->set(['money'=>'money-'.$num,'sendFlow'=>'sendFlow+'.$num],null,false)->update('user');
		$this->db->where('id',$id)->step($table,'flower',true,$num);

		$total=$this->db->select('id,flowRank')->where('flowRank <=',$data['flowRank'])->order_by('flower desc,praise desc,id asc')->get($table)->result_array();
		$rank=1;
		foreach ($total as $key => $value) {
			if ($rank!=$value['flowRank']) $this->db->where('id',$value['id'])->update($table,['flowRank'=>$rank]);
			$rank++;
		}

		if ($this->db->trans_complete()){
			$this->db->insert('money_log',
				['uid'=>UID,'content'=>"您已成功送给$name${num}朵花",'time'=>time(),'num'=>-$num,'realMoney'=>-$num]
			);
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
			$data=$this->db->find('account join teacher on teacher.id=account.id',$id,'account.id','name,flowRank,flower,praise');
			if (!$data) throw new MyException('此教练不存在！',MyException::GONE);
			$name=$data['name'];
		}else if ($id=$this->input->post('pid')){
			$table='place';
			$data=$this->db->find('place',$id,'id','name,flowRank,flower,praise');
			if (!$data) throw new MyException('此场地不存在！',MyException::GONE);
			$name='场地'.$data['name'];
		}else throw new MyException('',MyException::INPUT_ERR);
		if ($num<=0) throw new MyException('',MyException::INPUT_ERR);
		$this->db->trans_begin();
		$praise=$this->db->query('SELECT praise FROM user WHERE id=? FOR UPDATE',UID)->row_array()['praise'];
		if ($praise<$num) throw new MyException('赞的数量不够！',MyException::INPUT_ERR);
		$inc=$this->db->where(['flowRank <'=>$data['flowRank'],'flower'=>$data['flower'],'praise >='=>$data['praise'],'praise <'=>$data['praise']+$num])->count_all_results($table);
		if ($inc>0) $this->db->between('flowRank',$data['flowRank']+1,$data['flowRank']+$inc)->step($table,'flowRank',true,1);
		$this->db->where('id',UID)->step('user','praise',false,$num);
		$this->db->where('id',$id)->set(['flowRank'=>'flowRank-'.$inc,'praise'=>'praise+'.$num],null,false)->update($table);
		if ($this->db->trans_complete()){
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}

	function placeFlower(){
		$count=10;
		$page=(int)$this->input->get('page');
		($key=$this->input->get('key'))&&$this->db->like('place.name',$key);
		$res=$this->db->select('place.id,place.address,place.name,place.intro,place.pics->"$[0]" pics,place.status,school.name school,area,flower,praise,flowRank')
			->join('school','place.school=school.id')->order_by('flowRank','asc')->where('place.status',1)
			->get('place',$count,$count*$page)
			->result_array();
		foreach ($res as &$value) {
			$value['pics']=[json_decode($value['pics'],TRUE)];
			$value['intro']=mb_substr($value['intro'], 0,15);
		}
		restful(200,$res);
	}

	function teaFlower(){
		$count=10;
		$page=(int)$this->input->get('page');
		($key=$this->input->get('key'))&&$this->db->like('name',$key);
		$res=$this->db->select('account.id,name,avatar,grade,year,student,teacher.kind,zjType,flower,praise,flowRank')
			->join('account', 'account.id=teacher.id')->where('account.status',1)->order_by('flowRank','asc')
			->get('teacher',$count,$count*$page)
			->result_array();
		restful(200,$res);
	}
	
	function teaPlace(){
		$count=10;
		$page=(int)$this->input->get('page');
		($key=$this->input->get('key'))&&$this->db->like('name',$key);
		$res=$this->db->select('account.id,name,avatar,grade,year,student,teacher.kind,zjType,addPlace,placeRank,placeLast')
			->join('account', 'account.id=teacher.id')->where('account.status',1)->order_by('placeRank','asc')
			->get('teacher',$count,$count*$page)
			->result_array();
		restful(200,$res);
	}
}
