<?php
/**
 * 处理用户相关。审核、驾友圈、统计
 * 
 * @author small
 */
class UserController extends CI_Controller {
	const CHECK=1;//审核
	const FROZE=2;//冻结/恢复
	const CHARGE=3;//充值
	const GRADE=4;//修改星级
	const PRICE=5;//修改教练价格
	const SEEDS=6;//删除加油圈

	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}

	function seeds($id=0) {
		$page=$this->input->get('page');
		if ($id==0){
			if ($page===NULL)
				$this->load->view('back/seeds');
			else {
				$this->db->start_cache();
				if (($key=$this->input->get('key'))&&!empty($key))
					$this->db->like('content',$key);
				if ($key=$this->input->get('user'))
					$this->db->where('uid',$key);
				if ($key=$this->input->get(['begin','end']))
					$this->db->where('seeds.time BETWEEN '.strtotime($key['begin']).' AND '.strtotime($key['end']));
				$count=10;
				$data=$this->db->select('seeds.id,left(content,15) content,seeds.time,name')->join('account','account.id=seeds.uid')->order_by('seeds.id','desc')
					->get('seeds',$count,$page*$count)->result_array();
				$total=$this->db->count_all_results();
				restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
			}
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			$data=$this->db->select('seeds.id,content,pics,seeds.time,name,praise')->join('account','account.id=seeds.uid')
				->where('seeds.id',$id)->get('seeds')->row_array();
			if (!$data) throw new MyException('',MyException::GONE);
			$data['pics']=json_decode(gzuncompress($data['pics']),TRUE);
			restful(200,$data);
		}
	}

	function delSeed() {
		$id=$this->input->put('id');
		if (!is_array($id)) throw new MyException('',MyException::INPUT_MISS);
		$seeds=$this->db->select('uid,content')->where_in('id',$id)
			->get('seeds')->result_array();
		if (empty($seeds)) restful();
		foreach ($seeds as $item) {
			$log[]=[
				'uid'=>$_SESSION['admin'],
				'link'=>$item['uid'],
				'text'=>"删除了驾友圈$item[content]",
				'type'=>self::SEEDS
			];
		}
		if ($this->db->where_in('id',$id)->delete('seeds')){
			$this->db->where_in('sid',$id)->delete('scomment');
			$this->db->where_in('sid',$id)->delete('praise');
			$this->db->insert_batch('oprate_log',$log);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}

	function statistics() {
		$this->load->view('back/statistics');
	}

	function stuCheck() {
		$page=$this->input->get('page');
		if ($page===NULL)
			$this->load->view('back/stuCheck');
		else {
			$count=10;
			$data=$this->db->where(['status'=>0,'kind'=>0,'realname !='=>''])
				->select('tel,name,realname,peopleId,peoplePic,peoplePicB,account.id,regTime')
				->join('user', 'user.id=account.id')
				->get('account',$count,$page*$count)->result_array();
			$total=$this->db->where(['status'=>0,'kind'=>0,'realname !='=>''])
				->join('user', 'user.id=account.id')
				->count_all_results('account');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function modStatus($id=0) {
		$status=$this->input->put('status');
		if (!$id) throw new MyException('',MyException::INPUT_MISS);
		if ($status==1) $data=['status'=>1];
		else $data=['status'=>2,'checkInfo'=>$this->input->put('info',TRUE)];
		$flag=$this->db->where_in('id',$id)->update('account',$data);
		if ($flag){
			$this->load->model('notify');
			$data=$this->db->select('id,name,kind,tel')->where_in('id',$id)->get('account')->result_array();
			$log=[];
			if ($status==0){
				foreach ($data as $value) {
					$log[]=[
						'uid'=>$_SESSION['admin'],
						'link'=>$value['id'],
						'text'=>"审核通过账号$value[name]，手机$value[tel]",
						'type'=>self::CHECK
					];
					$this->notify->auth_pass($value);
				}
			}else{
				foreach ($data as $value) {//审核失败，直接发推送就OK
					$log[]=[
						'uid'=>$_SESSION['admin'],
						'link'=>$value['id'],
						'text'=>"审核失败账号$value[name]，手机$value[tel]",
						'type'=>self::CHECK
					];
					$this->notify->send($value['id'],Notify::AUTH_FAIL);
				}
			}
			$this->db->insert_batch('oprate_log',$log);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}

	function teaCheck() {
		$page=$this->input->get('page');
		if ($page===NULL)
			$this->load->view('back/teaCheck');
		else {
			$count=10;
			$data=$this->db->where(['status'=>0,'account.kind'=>1,'realname !='=>''])
				->select('account.id,tel,account.name,realname,zgz,idA,idB,carId,carPic,jiazhao,regTime,school.name school')
				->join('teacher', 'teacher.id=account.id')
				->join('school', 'teacher.school=school.id')
				->get('account',$count,$page*$count)->result_array();
			$total=$this->db->where(['status'=>0,'account.kind'=>1,'realname !='=>''])
				->join('teacher', 'teacher.id=account.id')
				->count_all_results('account');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function teacher($id=0) {
		$page=$this->input->get('page');
		if ($page===NULL)
			if ($id==0){
				$school=$this->db->get('school')->result_array();
				$this->load->view('back/teacher',['school'=>$school]);
			}else{
				$data=$this->db->select('teacher.*,account.name,tel,regTime,inviteMoney,addrTime,school.name school')
					->join('teacher', 'teacher.id=account.id')->join('school', 'school.id=teacher.school')
					->where('account.id',$id)->get('account')->row_array();
				if (!$data) throw new MyException('',MyException::GONE);
				restful(200,$data);
			}
		else {
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('key')){
				if (is_numeric($key)) $this->db->like('account.tel',$key);
				else $this->db->like('account.name',$key);
			}
			if ($key=$this->input->get('school')){
				$this->db->where('teacher.school',$key);
			}
			$this->db->where(['account.status >'=>0,'account.kind'=>1]);
			$this->db->stop_cache();
			$data=$this->db->join('teacher', 'teacher.id=account.id')->join('school', 'school.id=teacher.school')
				->select('account.tel,account.name,realname,account.id,money,status,grade,teacher.kind,school.name school,student,year,regTime')
				->get('account',$count,$page*$count)->result_array();
			$total=$this->db->count_all_results('account');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}
	
	function badTeacher() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$this->load->model('back/back');
			$this->back->updateNotify();
			$this->load->view('back/badTeacher');
		}else{
			$count=15;
			$data=$this->db->where('num >=',5)->join('teacher', 'teacher.id=badTeacher.tid')
			->join('account', 'account.id=teacher.id')->join('school', 'school.id=teacher.school')
			->select('account.tel,account.name,realname,account.id,money,teacher.kind,status,grade,school.name school,student,num,regTime')
			->get('badTeacher',$count,$page*$count)->result_array();
			$total=$this->db->count_all('badTeacher');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function student($id=0) {
		if ($id){
			$data=$this->db->select('user.*,name,tel,regTime,inviteMoney,addrTime')
				->join('user', 'user.id=account.id')
				->where('account.id',$id)->get('account')->row_array();
			if (!$data) throw new MyException('',MyException::GONE);
			$num=$this->db->query('SELECT sum(JSON_LENGTH(info)) num FROM `order` WHERE uid=? AND (status=3 OR status=4)',$id)
				->row_array();
			$data['learnTime']=$num['num']?:0;
			restful(200,$data);
		}
		$page=$this->input->get('page');
		if ($page===NULL)
			$this->load->view('back/student');
		else {
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('key')){
				if (is_numeric($key)) $this->db->like('account.tel',$key);
				else $this->db->like('account.name',$key);
			}
			$this->db->where(['account.status >'=>0,'account.kind'=>0]);
			$this->db->stop_cache();
			$data=$this->db->join('user', 'user.id=account.id')->order_by('account.id','desc')
				->select('account.tel,account.name,account.id,money,frozenMoney,status,level,regTime')
				->get('account',$count,$page*$count)->result_array();
			$total=$this->db->count_all_results('account');
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function addStudent(){
		$input=$this->input->post(['tel','name','gender','peopleId']);
		$this->load->model('account');
		if (!$this->account->checkTel($input['tel'],0))
			throw new MyException('此手机号已存在，请直接登录',MyException::CONFLICT);
		$input['kind']=0;
		$input['password']=md5(md5(md5($input['tel'].'123456')).Account::KEY);
		$input['avatar']=$input['gender']?Account::AVATAR_GIRL:Account::AVATAR;
		$input['status']=1;
		$input['invite']=uniqid();
		$peopleId=$input['peopleId'];
		unset($input['peopleId']);
		if (!$this->db->insert('account',$input))
			throw new MyException('',MyException::DATABASE);
		$id=$this->db->insert_id();
		$this->db->insert('user',['id'=>$id,'peopleId'=>$peopleId]);
		$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"注册了新账号$input[name]，手机$input[tel]",
				'type'=>self::CHECK
		]);
		restful(201);
	}

	function modMoney() {
		$input=$this->input->put(['money','type','id']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$input['type']=$input['type']=='add';
		if (!is_numeric($input['money'])||$input['money']==0) throw new MyException('',MyException::INPUT_ERR);
		$id=json_decode($input['id'],TRUE);
		if (!$id) throw new MyException('',MyException::INPUT_ERR);
		$org=$this->db->select('id,name,tel')->where_in('id',$id)->get('account')->result_array();
		if (!$org) throw new MyException('',MyException::GONE);
		$option=$input['type']?'充值':'取消充值';
		$log=[];$income=[];$mlog=[];
		$num=$input['money']*($input['type']?-1:1);
		foreach ($org as $item) {
			$log[]=[
				'uid'=>$_SESSION['admin'],
				'link'=>$item['id'],
				'text'=>"为手机号$item[tel]的学员$item[name]${option}了$input[money]学车币",
				'type'=>self::CHARGE
			];
			$income[]=['tid'=>$item['id'],'num'=>$num,'type'=>2,'virtualMoney'=>$num];
			$mlog[]=['uid'=>$item['id'],'num'=>$num,'virtualMoney'=>$num,'content'=>"客服人员${option}了$input[money]学车币"];
		}
		$this->db->where_in('id',$id)->step('user', 'frozenMoney',$input['type'],$input['money']);
		$this->db->insert_batch('oprate_log',$log);
		$this->db->insert_batch('income',$income);
		$this->db->insert_batch('money_log',$mlog);
		restful();
	}

	function froze($id=0){
		$status=(int)$this->input->get('status');
		$org=$this->db->find('account',$id,'id','name,tel');
		if (!$org) throw new MyException('',MyException::GONE);
		$this->db->where('id',$id)->set('status',$status)->update('account');
		$this->load->model('notify');
		$this->db->insert('oprate_log',[
			'uid'=>$_SESSION['admin'],
			'link'=>$id,
			'text'=>($status==2?'冻结':'解冻')."了$org[name]的账号，其手机号$org[tel]",
			'type'=>self::FROZE
		]);
		$this->notify->send($id,$status==2?Notify::FROZEN:Notify::UNFROZEN);
		restful(204);
	}

	function modLevel($id=0){
		$input=$this->input->put(['grade','price']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$org=$this->db->find('teacher',$id,'id','realname,grade');
		if (!$org) throw new MyException('',MyException::GONE);
		$this->db->where('id',$id)->update('teacher',$input);
		if ($org['grade']!=$input['grade']){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"修改教练$org[realname]的星级为$input[grade]，原来为$org[grade]",
				'type'=>self::GRADE
			]);
			$this->load->model('notify');
			$this->notify->send(['grade'=>$input['grade'],'id'=>$id],$org['grade']>$input['grade']?Notify::TEA_GRADE_DOWN:Notify::TEA_GRADE_UP);
		}
		restful(204);
	}
	
	function fundDetail() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['tel'=>'手机号','name'=>'用户名','kind'=>'用户类型','content'=>'说明','num'=>'金额','time'=>'时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'moneyLog']);
		}else {
			$count=15;
			$this->db->limit($count,$count*$page);
			$this->db->start_cache();
			$this->load->model('back/export','m');
			$data=$this->m->moneyLog($this->input->get());
			$total=$this->db->count_all_results();
			restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
		}
	}

	function userid() {
		$key=$this->input->get('key');
		if ($key===NULL||empty($key))
			throw new MyException('',MyException::INPUT_MISS);
		if (is_numeric($key))
			$this->db->like('tel',$key);
		$data=$this->db->or_like('name',$key)->select('id,name')->get('account')->result_array();
		restful(200,$data);
	}

	function me(){
		if ($_SESSION['admin']==0)
			echo '<script>alert("超级管理员不能修改信息哦！");history.back()</script>';
		else $this->load->view('back/me');
	}
}
