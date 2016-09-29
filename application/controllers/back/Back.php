<?php
/**
 * 处理概况和财务相关
 * 
 * @author small
 */
class BackController extends CI_Controller {
	const TIXIAN=15;
	const REFUND=16;
	
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
		$this->back->updateNotify();
		$this->load->view('back/index',$this->back->statistic());
	}
	
	//提现界面
	function outcome() {
		$page=$this->input->get('page');
		if ($page===NULL)
			$this->load->view('back/withdraw');
		else{
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('uid'))
				$this->db->where('uid',$key);
			if ($key=$this->input->get(['begin','end']))
				$this->db->between('createTime',$key['begin'],$key['end'].' 23:59:59');
			$this->db->stop_cache();
			$data=$this->db->select('account.name,account.kind,tel,tixian.*,(SELECT name FROM admin WHERE admin.id=(SELECT uid FROM oprate_log WHERE link=tixian.id AND type='.self::TIXIAN.')) oprator')
				->order_by('tixian.status asc,tixian.id desc')
				->join('account', 'account.id=tixian.uid')
				->get('tixian',$count,$page*$count)->result_array();
			$sum=$this->db->select('sum(amount) sum')->get('tixian')->row()->sum;
			$total=ceil($this->db->count_all_results('tixian')/$count);
			restful(200,['data'=>['data'=>$data,'sum'=>$sum?:0],'total'=>$total]);
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
			$user=$this->db->find('account', $tx['uid'],'id','kind,id,name');
			$this->db->where('id',$user['id'])->step($user['kind']?'teacher':'user', 'money',TRUE,$tx['amount']);//把扣的钱退回来
			$info=['text'=>$this->input->put('info',TRUE),'uid'=>$tx['uid']];
			$this->db->insert('money_log',
				['uid'=>$tx['uid'],'num'=>$tx['amount'],'realMoney'=>$tx['amount'],'content'=>"提现失败，退回$tx[amount]学车币",'time'=>time()]
			);
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"由于$info[text]拒绝了$user[name]的提现申请",
				'type'=>self::TIXIAN
			]);
		}else{
			$user=$this->db->find('account', $tx['uid'],'id','name');
			$info=$tx['uid'];
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"通过了$user[name]的提现申请",
				'type'=>self::TIXIAN
			]);
		}
		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
			throw new MyException('',MyException::DATABASE);
		$this->notify->send($info,$status==2?Notify::TX_FAIL:Notify::TX_PASS);
		restful(201);
	}
	
	function income() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['tel'=>'手机号','user'=>'用户','kind'=>'用户类型','channel'=>'渠道','amount'=>'充值金额','createTime'=>'充值时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'income']);
		}else{
			$count=15;
			$this->db->limit($count,$count*$page);
			$this->db->start_cache();
			$this->load->model('back/export','m');
			$data=$this->m->income($this->input->get());
			$sum=$this->db->select('sum(amount) sum')->get('charge')->row()->sum;
			$total=ceil($this->db->count_all_results('charge')/$count);
			restful(200,['data'=>['data'=>$data,'sum'=>$sum?:0],'total'=>$total]);
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
				else{
					$user=$this->db->find('account', $res['uid'],'id','name');
					$this->db->insert('oprate_log',[
						'uid'=>$_SESSION['admin'],
						'link'=>$res['index'],
						'text'=>"操作了$user[name]的退款申请",
						'type'=>self::TIXIAN
					]);
				}
				header("Location:$url",301);
			}
		}else{
			$count=15;
			$this->db->start_cache();
			if ($key=$this->input->get('uid'))
				$this->db->where('refund.uid',$key);
			if ($key=$this->input->get(['begin','end']))
				$this->db->between('dealTime',strtotime($key['begin']),strtotime($key['end'].' 23:59:59'));
			$this->db->stop_cache();
			$data=$this->db->select('refund.*,charge.channel,account.name user,account.kind,tel,from_unixtime(refund.dealTime) dealTime,charge.createTime,(SELECT name FROM admin WHERE admin.id=(SELECT uid FROM oprate_log WHERE link=refund.`index` AND type='.self::REFUND.')) oprator')
				->join('account','refund.uid=account.id')->join('charge','refund.chargeId=charge.id')->order_by('refund.status desc,id desc')
				->get('refund',$count,$page*$count)->result_array();
			array_walk($data, function(&$item){
				$item['oprator']=$item['oprator']?:($item['status']==0?'无':'系统');
			});
			$total=ceil($this->db->count_all_results('refund')/$count);
			$sum=$this->db->select('sum(amount) sum')->where('status',1)->get('refund')->row()->sum;
			restful(200,['data'=>['data'=>$data,'sum'=>$sum?:0],'total'=>$total]);
		}
	}

	function teaIncome(){
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['tel'=>'手机号','user'=>'教练名称','school'=>'所属驾校','num'=>'收入','type'=>'收入类型','time'=>'收入时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'teaIncome']);
		}else{
			$count=15;
			$limit=$this->input->get();
			$this->db->start_cache();
			if (isset($limit['begin']))
				$this->db->between('money_log.time',strtotime($limit['begin']),strtotime($limit['end'].' 23:59:59'));
			if (isset($limit['uid']))
				$this->db->where('money_log.uid',$limit['uid']);
			$this->db->where('money_log.type >',0)->stop_cache();
			$data=$this->db->select('money_log.num,money_log.type,tel,account.name user,from_unixtime(money_log.time) time,(SELECT name FROM school WHERE school.id=(SELECT school FROM teacher WHERE teacher.id=money_log.uid)) school')
				->join('account', 'money_log.uid=account.id')
				->order_by('money_log.id','desc')->get('money_log',$count,$page*$count)
				->result_array();
			$total=ceil($this->db->count_all_results('money_log')/$count);
			array_walk($data, function(&$item,$key,$type){
				$item['type']=$type[$item['type']-1];
			},['教学收入','退款手续费']);
			$sum=$this->db->select('sum(num) sum')->get('money_log')->row()->sum;
			restful(200,['data'=>['data'=>$data,'sum'=>$sum?:0],'total'=>$total]);
		}
	}

	function ticheng(){
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['tel'=>'手机号码','user'=>'用户名','school'=>'所属驾校','num'=>'金额','type'=>'类型','time'=>'处理时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'ticheng']);
		}else{
			$count=15;
			$this->db->limit($count,$count*$page);
			$this->db->start_cache();
			$this->load->model('back/export','m');
			$data=['data'=>$this->m->ticheng($this->input->get())];
			$data['total']=ceil($this->db->count_all_results()/$count);
			restful(200,$data);
		}
	}
	
	function tongji() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$this->load->view('back/tongji');
		}else{
			$input=$this->input->get();
			$input['count']=isset($input['count'])?(int)$input['time']:12;
			$input['time']=isset($input['time'])?(int)$input['time']:0;
			if (!isset($input['type'])) $input['type']=0;
			$this->load->model('back/export','m');
			$data=$this->m->financeStat($input);
			if (isset($input['begin'])){
				$begin=strtotime($input['begin']);
				$end=strtotime($input['end']);
				$this->db->between('money_log.time', $begin, $end);
			}else{
				$begin=strtotime('2016-08-24');
				$end=strtotime('tomorrow');
			}
			switch ($input['time']) {
				case 0:$total=ceil(($end-$begin)/86400/$input['count']);
				break;
				case 1:$total=ceil(($end-$begin)/(86400*7*$input['count']));
				break;
				default:$total=ceil(($end-$begin)/(86400*30*$input['count']));
				break;
			}
			$stat=$this->db->between('time', $begin, $end)
				->select('sum(realMoney) realMoney,sum(virtualMoney) virtualMoney')
				->get($input['type']==3?'income':'money_log')->row_array();
			$stat=$stat['realMoney']!==NULL?$stat:['realMoney'=>0,'virtualMoney'=>0];
			$stat['total']=$stat['realMoney']+$stat['virtualMoney'];
			restful(200,['total'=>$total,'data'=>['stat'=>$stat,'data'=>$data]]);
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