<?php
/**
 * 处理概况和财务相关
 * 
 * @author small
 */
class BackController extends CI_Controller {
	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}
	
	function index() {
		$this->load->model('back/back');
		$this->load->view('back/index',$this->back->statistic());
	}
	
	function outcome() {
		$page=$this->input->get('page');
		if ($page===NULL)
			$this->load->view('back/withdraw');
		else{
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('uid'))
				$this->db->where('uid',$key);
			$this->db->stop_cache();
			$data['data']=$this->db->select('account.name,tel,tixian.*')->order_by('tixian.status asc,tixian.id desc')
				->join('account', 'account.id=tixian.uid')
				->get('tixian',$count,$page*$count)->result_array();
			$data['total']=ceil($this->db->count_all_results('tixian')/$count);
			restful(200,$data);
		}
	}
	
	//提现处理
	function modTixian($id=0) {
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$tx=$this->db->find('tixian', $id);
		if (!$tx||$tx['status']!=0) throw new MyException('你已经操作过了',MyException::CONFLICT);
		$status=(int)$this->input->put('status');
		if ($status!=1&&$status!=2)
			throw new MyException('',MyException::INPUT_ERR);
		$this->load->model('notify');
		$this->db->trans_begin();
		$this->db->where('id',$id)->update('tixian',['status'=>$status,'time'=>time()]);
		if ($status==2){
			$user=$this->db->find('account', $tx['uid'],'id','kind,id');
			$this->db->where('id',$user['id'])->step($user['kind']?'teacher':'user', 'money',TRUE,$tx['amount']);//把扣的钱退回来
			$info=['text'=>$this->input->post('info',TRUE),'uid'=>$tx['uid']];
			$this->db->insert('money_log',
					['uid'=>$tx['uid'],'num'=>$tx['amount'],'content'=>"提现失败，退回$tx[amount]学车币",'time'=>time()]
				);
		}else{
			$info=$tx['uid'];
		}
		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
			throw new MyException('',MyException::DATABASE);
		$this->notify->send($info,$status==2?Notify::TX_FAIL:Notify::TX_PASS);
		restful(201);
	}
	
	function income() {
		$page=$this->input->get('page');
		if ($page===NULL)
			$this->load->view('back/recharge');
		else{
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('uid'))
				$this->db->where('uid',$key);
			if (isset($data['etime']))
				$this->db->where(['createTime >='=>$data['btime'],'createTime <'=>$data['etime']]);
			$data['data']=$this->db->select('name,tel,amount,createTime time,channel')->where('charge.status',1)
				->join('account', 'account.id=charge.uid')
				->get('charge',$count,$page*$count)->result_array();
			$data['total']=ceil($this->db->count_all_results()/$count);
			restful(200,$data);
		}
	}
	
	function finance() {
		$this->load->view('back/finance');
	}
	
	function refund($id='') {
		$page=$this->input->get('page');
		if ($page===NULL){
			if ($id=='')
				$this->load->view('back/refund');
			else{
				$res=$this->db->find('refund',$id,'id');
				if (!$res) throw new MyException('',MyException::INPUT_ERR);
				if ($res['status']!=0) throw new MyException('',MyException::DONE);
				$this->load->library('ping');
				$res=$this->ping->getRefund($res['chargeId'],$res['id']);
				$url=strstr($res['failure_msg'],'http');
				if ($url==FALSE) $url='/back/back/refund';
				header("Location:$url",301);
			}
		}else{
			$count=15;
			$data=$this->db->select('refund.*,charge.channel,account.name user,tel,from_unixtime(refund.dealTime) dealTime,charge.createTime')
				->join('account','refund.uid=account.id')->join('charge','refund.chargeId=charge.id')->order_by('refund.status desc,id desc')
				->get('refund',$count,$page*$count)->result_array();
			$total=ceil($this->db->count_all('refund')/$count);
			restful(200,['data'=>$data,'total'=>$total]);
		}
	}

	function teaIncome(){
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['user'=>'教练名称','school'=>'所属驾校','num'=>'收入','type'=>'收入类型','time'=>'收入时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'teaIncome']);
		}else{
			$count=15;
			$data=$this->db->query('SELECT money_log.num,money_log.type,account.name user,from_unixtime(money_log.time) time,(SELECT name FROM school WHERE school.id=(SELECT school FROM teacher WHERE teacher.id=money_log.uid)) school FROM money_log '.
				' JOIN account ON money_log.uid=account.id'.
				' WHERE money_log.type>0 ORDER BY money_log.id desc limit ?,?',
					[$page*$count,$count])->result_array();
			$total=ceil($this->db->where('money_log.type>0')->count_all_results('money_log')/$count);
			array_walk($data, function(&$item,$key,$type){
				$item['type']=$type[$item['type']-1];
			},['教学收入','退款手续费']);
			restful(200,['data'=>$data,'total'=>$total]);
		}
	}

	function ticheng(){
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['user'=>'用户名','school'=>'所属驾校','num'=>'金额','type'=>'类型','time'=>'处理时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'ticheng']);
		}else{
			$count=15;
			$data=$this->db->query('SELECT income.*,account.name user,(SELECT name FROM school WHERE school.id=(SELECT school FROM teacher WHERE teacher.id=income.tid)) school FROM income '.
				' JOIN account ON income.tid=account.id'.
				' ORDER BY income.id desc limit ?,?',[$page*$count,$count])->result_array();
			$total=ceil($this->db->count_all('income')/$count);
			array_walk($data, function(&$item,$key,$type){
				$item['type']=$type[(int)$item['type']];
				$item['school']=$item['school']?:'';
			},['教练提成','退款平台手续费','后台充值支出']);
			restful(200,['data'=>$data,'total'=>$total]);
		}
	}
	
	function qiniu() {
		$this->load->library('qiniu');
		restful(200,['token'=>$this->qiniu->uploadToken()]);
	}
	
	function logout() {
		session_destroy();
		header('Location:/common/admingo');
	}
}