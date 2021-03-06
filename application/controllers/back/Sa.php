<?php
/**
 * 处理管理员账号、活动、参数设置等超级权限
 * 
 * @author small
 */
class SaController extends CI_Controller {
	const SYSTEM=APPPATH.'controllers/back/param.json';
	const TIME=APPPATH.'controllers/back/time.json';
	const LEVEL=APPPATH.'controllers/back/level.json';
	const ACTIVITY=APPPATH.'controllers/back/activity.json';
	const SLIDE=APPPATH.'controllers/back/slide.json';
	
	const PUSH=7;
	const MODACTIVITY=8;
	const MODSYSTEM=9;
	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}
	
	function admin($id=0) {
		if ($id!=0){
			$data=$this->db->find('admin', $id);
			if (!$data) throw new MyException('',MyException::GONE);
			$data['pri']=json_decode($data['pri'],TRUE);
			unset($data['password']);
			restful(200,$data);
		}
		$priList=['系统设置','学员端管理','教练端管理','驾校管理','用户中心','财务管理','消息推送','活动设置','分销管理'];
		$res=$this->db->where('id >',1)->select('user,name,pri,id')->get('admin')->result_array();
		foreach ($res as &$value) {
			$pri=json_decode($value['pri'],TRUE);
			$value['pri']=[];
			foreach ($pri as $item) {
				$value['pri'][]=$priList[$item];
			}
			$value['pri']=join($value['pri'],'、');
		}
		$this->load->view('back/admin',['data'=>$res]);
	}
	
	function addAdmin(){
		$data=$this->db->create('admin');
		$again=$this->db->find('admin', $data['user'],'user');
		if ($again) throw new MyException('已有此用户名',MyException::CONFLICT);
		$data['password']=md5($data['password'].'yiren');
		$this->db->insert('admin',$data)?restful(201):restful(200,'请重试');
	}
	
	function modAdmin($id=0){
		$data=$this->db->field(['id','password'],'admin')->create('',FALSE);
		$this->db->where('id',$id)->update('admin',$data)?restful(201):restful(200,'请重试');
	}
	
	function modPassword($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$pwd=$this->input->put('password');
		if (!$pwd) throw new MyException('',MyException::INPUT_MISS);
		$flag=$this->db->where('id',$id)->update('admin',['password'=>md5($pwd.'yiren')]);
		if ($flag) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modInfo() {
		$input=$this->input->put(['oldPwd','newPwd','name']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		if ($input['oldPwd']!=''){
			$pwd=$this->db->find('admin', UID,'id','password');
			if ($pwd['password']!=md5($input['oldPwd'].'yiren')) throw new MyException('密码错误！',MyException::INPUT_ERR);
			$data=['password'=>md5($input['newPwd'].'yiren'),'name'=>$input['name']];
		}else $data=['name'=>$input['name']];
		if ($this->db->where('id',UID)->update('admin',$data)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function adminLog(){
		$page=$this->input->get('page');
		if ($page===NULL){
			$admin=$this->db->get('admin')->result_array();
			$this->load->view('back/adminLog',['admin'=>$admin]);
		}else{
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('admin'))
				$this->db->where('uid',$key);
			if ($key=$this->input->get('order'))
				$this->db->where(['type >='=>10,'type <='=>11,'link'=>$key]);
			else if ($key=$this->input->get('user'))
				$this->db->where(['type !='=>10,'type !='=>11,'link'=>$key]);
			if ($date=$this->input->get(['begin','end']))
				$this->db->where(['time >='=>$date['begin'],'time <='=>$date['end'].' 23:59:59']);
			$data=$this->db->select('name,text,time')->order_by('oprate_log.id','desc')
				->join('admin', 'admin.id=oprate_log.uid')
				->get('oprate_log',$count,$page*$count)->result_array();
			$total=$this->db->count_all_results();
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}
	
	function delAdmin($id=0){
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		if ($this->db->where('id',$id)->delete('admin')) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function activity($id=0) {
		$id=(int)$id;
		$page=$this->input->get('page');
		if ($page===NULL){
			$data=['head'=>[],'key'=>[]];
// 			$title=['首冲有奖','首次分享有奖','完善信息有礼','刮刮乐','大转盘','学车有优惠','推荐注册有奖','每周奖励政策','分享得现'];
			switch ($id) {
				case 1:
					$data=['head'=>['首充学车币数量'],'key'=>['amount']];
					break;
				case 2:
					$data=['head'=>['分享渠道'],'key'=>['channel']];
					break;
				case 4:
					$config=file_get_contents(self::ACTIVITY);
					$data['option']=json_decode($config,TRUE)['gua'];
					break;
				case 5:
					$config=file_get_contents(self::ACTIVITY);
					$data['option']=json_decode($config,TRUE)['zhuan'];
					break;
				default:
					# code...
					break;
			}
			$data['id']=$id;
			$this->load->view('back/activity',$data);
		}else{
			$count=15;
			$this->db->start_cache();
			switch ($id) {
				case 1:
					$this->db->select('(SELECT amount FROM charge WHERE charge.status=1 AND charge.uid=activity_log.uid limit 1) amount',FALSE);
					break;
				case 2:
					$this->db->select('(SELECT channel FROM share_log WHERE share_log.uid=activity_log.uid limit 1) channel',FALSE);
					break;
				default:
					break;
			}
			$this->db->where('aid',$id);
			$this->db->stop_cache();
			$data=$this->db->select('num,activity_log.time,name,tel')
				->join('account', 'account.id=activity_log.uid')
				->get('activity_log',$count,$page*$count)->result_array();
			if ($id==2){
				array_walk($data, function(&$item){
					$item['channel']=$item['channel']==1?'QQ':'微信';
				});
			}
			$total=$this->db->where('aid',$id)->count_all_results('activity_log');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}
	
	function activityInfo($id=0) {
		$data=$this->db->find('activity',$id);
		if (!$data) throw new MyException('',MyException::GONE);
		restful(200,$data);
	}
	
	function modActivity($id=0) {
		$data=$this->db->create('activity',FALSE);
		if (isset($data['pic'])&&empty($data['pic']))
			unset($data['pic']);
		$flag=$this->db->where('id',$id)->update('activity',$data);
		if ($flag){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"修改了第${id}个活动的参数",
				'type'=>self::MODACTIVITY
			]);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function system() {
		$file=file_get_contents(self::SYSTEM);
		$data=json_decode($file,TRUE);
		$file=file_get_contents(self::LEVEL);
		$data['level']=json_decode($file,TRUE);
		$this->load->view('back/system',$data);
	}
	
	function modSystem() {
		$input=$this->input->put();
		$log=[
			'uid'=>$_SESSION['admin'],
			'type'=>self::MODSYSTEM
		];
		if (isset($input['level'])){
			$log['text']='教练升星福利';
			file_put_contents(self::LEVEL, json_encode($input['level']));
		}else{
			$data=file_get_contents(self::SYSTEM);
			$data=json_decode($data,TRUE);
			$data=array_merge($data,$input);
			file_put_contents(self::SYSTEM, json_encode($data));
			$log['text']='修改了其他系统参数';
		}
		$this->db->insert('oprate_log',$log);
		restful();
	}
	
	function push() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$this->load->view('back/push');
		}else{
			$count=10;
			$data=$this->db->select('name,text,oprate_log.time,oprate_log.link')->order_by('oprate_log.id','desc')
				->join('admin', 'admin.id=oprate_log.uid')->where('oprate_log.type',self::PUSH)
				->get('oprate_log',$count,$page*$count)->result_array();
			$total=$this->db->where('type',self::PUSH)->count_all_results('oprate_log');
			$data=['data'=>$data,'total'=>ceil($total/$count)];
			restful(200,$data);
		}
	}
	
	function addPush() {
		$input=$this->input->post(['type','content']);
		$this->load->library('umeng');
		$this->umeng->backSend($input['content'],$input['type']);
		$people=['所有人','学员','教练'];
		$this->db->insert('oprate_log',[
			'uid'=>$_SESSION['admin'],
			'link'=>$input['type'],
			'text'=>"发送给".$people[(int)$input['type']]."推送：$input[content]",
			'type'=>self::PUSH
		]);
		restful();
	}
	
	function version() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$this->load->view('back/version');
		}else{
			$count=10;
			$data=$this->db->order_by('id','desc')
				->get('version',$count,$page*$count)->result_array();
			$total=$this->db->count_all('version');
			$data=['data'=>$data,'total'=>ceil($total/$count)];
			restful(200,$data);
		}
	}
	
	function addVersion() {
		$input=$this->db->create('version');
		$input['info']=str_replace("\r\n","\n", $input['info']);
		$this->db->insert('version',$input);
		restful();
	}

	function slide(){
		$data=file_get_contents(self::SLIDE);
		$data=json_decode($data,true);
		$this->load->view('activity/slide',['data'=>$data]);
	}

	function addSlide(){
		$data=$this->input->post('data');
		if (!$data||!json_decode($data,true)) throw new MyException('',MyException::INPUT_MISS);
		file_put_contents(self::SLIDE,$data);
		restful();
	}
}
