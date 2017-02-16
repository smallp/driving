<?php
class MoneyController extends CI_Controller {
	private $type;
	function __construct() {
		parent::__construct ();
		$this->load->model ( 'account' );
		$this->type = $this->account->check();
		if ($this->type == - 1)
			throw new MyException ( '', MyException::AUTH );
	}
	function addCharge() {
		$input = $this->input->post ( [ 
				'channel',
				'amount' 
		] );
		if (! $input || ! is_numeric ( $input ['amount'] ))
			throw new MyException ( '', MyException::INPUT_MISS );
		$amount = ( int ) $input ['amount'];
		$orderId = ( int ) $this->input->post ( 'order' );
		if ($orderId) { // 直接第三方支付
			$order = $this->db->find ( '`order`', $orderId, 'id', 'uid,realPrice' );
			if (! $order || $order ['uid'] != UID || $order ['realPrice'] != $amount)
				throw new MyException ( '', MyException::INPUT_ERR );
		}
		
		$this->load->library ( 'ping' );
		$res = $this->ping->pay ( $input );
		// 数据库金额以元为单位
		$data = [ 
				'id' => $res ['id'],
				'uid' => UID,
				'amount' => $amount,
				'order' => $res ['order_no'],
				'channel' => $res ['channel'] == 'alipay' ? 1 : 2,
				'orderId' => $orderId 
		];
		$flag = $this->db->insert ( 'charge', $data );
		if ($flag)
			restful ( 200, $res );
		else
			throw new MyException ( '', MyException::DATABASE );
	}
	function addTixian() {
		$input = $this->input->post ( [ 
				'amount',
				'channel',
				'target','name'
		]);
		if (!$input) throw new MyException ( '', MyException::INPUT_MISS );
		if ($this->type==1){
			$day=date('N');//$day<4||$day>5 /星期五
			if ($day!=4) throw new MyException ('每周四为提现日', MyException::NO_RIGHTS );
		}
		$t=$this->input->post('phone');
		if ($t) $input['phone']=$t;
		$table = $this->type ? 'teacher' : 'user';
		$user = $this->db->find ( $table, UID, 'id', 'money' );
		if ($user ['money'] < $input ['amount'])
			throw new MyException ( '余额不足', MyException::NO_RIGHTS );
		if ($this->db->where ( [ 
				'uid' => UID,
				'status' => 0 
		] )->count_all_results ( 'tixian' ) > 0)
			throw new MyException ( '请等待之前的申请处理后再提交', MyException::CONFLICT );
		$input ['uid'] = UID;
		$user ['money'] -= $input ['amount'];
		$this->db->trans_begin ();
		$this->db->where ( 'id', UID )->update ( $table, $user );
		$this->db->insert ( 'tixian', $input );
		$this->db->trans_complete ();
		if ($this->db->trans_status () === FALSE)
			throw new MyException ( '', MyException::DATABASE );
		else {
			$this->db->insert ( 'money_log', [ 
					'uid' => UID,
					'num' => $input ['amount'] * - 1,
					'realMoney' => $input ['amount'] * - 1,
					'content' => "提现支出$input[amount]学车币",
					'time' => time () 
			] );
			$this->load->model('notify');
			$this->notify->send(UID,Notify::TIXIAN);
			restful ();
		}
	}
	
	function txOption() {
		$channel=$this->input->get('channel');
		$res=$this->db->select('channel,target,phone,name')->where(['uid'=>UID,'channel'=>$channel])
			->order_by('id','desc')->get('tixian',1)
			->row_array();
		if ($res) restful(200,$res);
		else restful(200,['target'=>'']);
	}
	
	function sellMoney() {
		$this->db->select('money,inviteMoney')->where('account.id',UID);
		$this->type?
			$this->db->join('teacher', 'teacher.id=account.id'):
			$this->db->join('user', 'user.id=account.id')->select('frozenMoney');
		$data=$this->db->get('account',1)->row_array();
		$today=$this->db->query('SELECT sum(amount) num FROM invite_log WHERE uid=? AND time>?',[UID,date('Y-m-d')])
			->row_array()['num'];
		$today=$today?$today:0;
		$data['todayMoney']=$today;
		restful(200,$data);
	}
	
	function sellLog() {
		$page=(int)$this->input->get('page');
		$count=15;
		$data=$this->db->select('name,avatar,amount,UNIX_TIMESTAMP(time) time')
			->where('invite_log.uid',UID)->order_by('invite_log.id','desc')
			->join('account', 'account.id=(SELECT uid FROM `order` WHERE id=invite_log.orderId)')
			->get('invite_log',$count,$count*$page)->result_array();
		restful(200,$data);
	}
	
	function sellMoneyLog() {
		$page=(int)$this->input->get('page')+1;
		$begin=strtotime("-$page month",strtotime('tomorrow'));//要包括今天
		$end=strtotime("+1 month",$begin);
		$limit=[date('Y-m-d',$begin),date('Y-m-d',$end)];
		$data=$this->db->select('sum(amount) sum,date_format(time,"%m/%d") date')
			->where('uid='.UID." AND time BETWEEN '$limit[0]' AND '$limit[1]'",NULL,FALSE)->group_by('date')
			->get('invite_log')->result_array();
		
		$p=current($data);
		$p=$p?:['date'=>''];//不用判断p是否为false了
		$res=[];
		while ($begin<$end) {
			$time=date('m/d',$begin);
			if ($p['date']==$time){
				$res[]=['time'=>$time,'num'=>$p['sum']];
				$p=next($data);
				$p=$p?:['date'=>''];
			}else $res[]=['time'=>$time,'num'=>0];
			$begin+=86400;
		}
		restful(200,$res);
	}
}
