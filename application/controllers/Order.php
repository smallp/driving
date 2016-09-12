<?php
class OrderController extends CI_Controller {
	private $type;
	
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		$type=$this->account->check();
		if ($type==-1)
			throw new MyException('',MyException::AUTH);
		else $this->type=$type;
		$this->load->model('order','m');
	}
	
	function availTime($id=0){
		$id=$this->type==0?$id:UID;
		if (!is_numeric($id))
			throw new MyException('',MyException::INPUT_ERR);
		restful(200,$this->m->availTime($id));
	}
	
	function availPlace($id=0) {
		$input=$this->input->get('data');
		$input&&($input=json_decode($input,TRUE));
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$id=$this->type==0?$id:UID;
		$res=[];
		foreach ($input as $value) {
			$value['id']=$id;
			$res[]=$this->m->avaliPlace($value);
		}
		restful(200,$res);
	}

	function Order($id=0){
		if ($id==0){
			$page=$this->input->get('page',FALSE,0);
			restful(200,$this->m->getList($page,$this->type==1));
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			restful(200,$this->m->item($id,$this->type==1));
		}
	}
	
	function addOrder(){
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		$input=$this->input->post(['info','id','partner','kind']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		if ($this->m->addOrder($input)) restful(201,['id'=>$this->db->insert_id()]);
		else throw new MyException('',MyException::DATABASE);
	}
	
	//取消订单
	function delOrder($id=0) {
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		restful(200,['code'=>$this->m->delOrder($id)]);
	}
	
	function addDelReason() {
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		$reason=$this->input->post('reason',TRUE);
		$id=(int)$this->input->post('id');
		if (!$reason) throw new MyException('',MyException::INPUT_MISS);
		$order=$this->db->find('`order`', $id,'id','id,uid');
		if (!$order||$order['uid']!=UID)
			throw new MyException('',MyException::NO_RIGHTS);
		$flag=$this->db->where("orderId=$order[id] OR pOrderId=$order[id]")
			->update('delOrderReq',['reason'=>$reason]);
		if ($flag) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}

	//隐藏订单
	function hideOrder($id=0) {
		$order=$this->db->find('`order`',$id,'id','uid,tid');
		if (!$order) throw new MyException('',MyException::GONE);
		if ($order[$this->type?'tid':'uid']!=UID) throw new MyException('',MyException::NO_RIGHTS);
		$this->db->where('id',$id)->update('`order`',$this->type?['thide'=>1]:['hide'=>1]);
		restful(200);
	}
	
	//取消订单回馈
	function modOrderRes($id=0) {
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$order=$this->db->find('`order`', $id);
		if (!$order) throw new MyException('',MyException::GONE);
		$partner=$this->db->where(['uid'=>$order['partner'],'tid'=>$order['tid'],'`order`.status <'=>Order::EXPIRE,'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
			->select('id')->get('`order`',1)->row_array()['id'];
		$order['pOrderId']=$partner;
		$status=$this->input->put('status')==1;
		if ($status){
			//检查时间，是否可以直接取消
			$info=json_decode($order['info'],TRUE);
			$target=$this->m->getTime($info[0]);
			if ($target<time()){//超过时间了，设置成等待审核
				$ids=$this->db->where(['tid'=>$order['tid'],'`order`.status <'=>SELF::EXPIRE,'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
					->select('id')->get('`order`')->result_array();
				$this->db->insert('delOrderReq',['orderId'=>$ids[0]['id'],'pOrderId'=>$ids[1]['id']]);
				$this->load->model('notify');
				$this->notify->cancleRes($order,$status);
				$code=2;
			}else{//还没开始，可以取消
				$this->m->cancle($order);
				$code=1;
			}
		}else {//告诉对方拒绝了
			$this->load->model('notify');
			$this->notify->cancleRes($order,$status);
			$code=0;
		}
		restful(200,['code'=>$code]);
	}
	
	function payment($id=0) {
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$this->m->pay($id);//数据库有错会直接在事务中报错
		restful(200);
	}
	
	function addComment() {
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		$input=$this->db->create('tcomment');
		if (!isset($input['id'])) throw new MyException('',MyException::INPUT_MISS);
		$data=$this->db->find('`order`', $input['id'],'id','tid,uid,status');
		if (!$data) throw new MyException('',MyException::GONE);
		if ($data['uid']!=UID||$data['status']!=3) throw new MyException('',MyException::NO_RIGHTS);
		$input['tid']=$data['tid'];
		$input['uid']=UID;
		$input['time']=time();
		if ($this->db->insert('tcomment',$input)){
			$this->load->model('notify');
			$this->notify->send(
				['uid'=>$input['tid'],'link'=>$this->db->insert_id()],
				Notify::ORDER_COMMENT);
			$this->db->where('id',$input['id'])->update('`order`',['status'=>4]);
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function comment($id=0){
		if ($this->type==-1) throw new MyException('',MyException::AUTH);
		if ($id==0){
			$page=$this->input->get('page',FALSE,0);
			$count=10;$method='commentList'.($this->type?'Tea':'');
			restful(200,$this->m->$method($count,$page*$count));
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			$method='commentItem'.($this->type?'Tea':'');
			if ($data=$this->m->$method($id)){
				restful(200,$data);
			}else throw new MyException('',MyException::GONE);
		}
	}
	
	//拒绝拼教练，现在是取消订单
	// function shareRefuse($id=0) {
	// 	if ($this->type!=0) throw new MyException('',MyException::AUTH);
	// 	if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
	// 	$data=$this->db->find('notify', $id);
	// 	if (!$data) throw new MyException('',MyException::GONE);
	// 	$this->load->mode('notify');
	// 	if ($data['uid']!=UID||$data['type']!=Notify::TEA_SHARE_REQ) throw new MyException('',MyException::NO_RIGHTS);
	// 	$this->m->shareRefuse($data['link']);
	// 	restful(200);
	// }
	
	function addCertainTea() {
		if ($this->type!=1) throw new MyException('',MyException::AUTH);
		$input=$this->input->post('data');
		parse_str($input,$input);
		if (!$input||!isset($input['date'])||!isset($input['time']))
			throw new MyException('二维码有误，请重新扫描',MyException::INPUT_MISS);
		$flag=$this->m->certain(['date'=>$input['date'],'tid'=>UID,'time'=>$input['time']],TRUE);
		if ($flag) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addCertainStu() {
		if ($this->type!=0) throw new MyException('',MyException::AUTH);
		$input=$this->input->post(['tid','date','time']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$flag=$this->m->certain($input,FALSE);
		if ($flag) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addComplain() {
		$input=$this->input->post(['tid','date','time','lat','lng','address']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$log=$this->db->where(['date'=>$input['date'],'time'=>$input['time'],'tid'=>$input['tid']])
			->get('teach_log',1)->row_array();
		if (!$log) throw new MyException('',MyException::GONE);
		if ($this->type==0){
			if ($log['uid']!=UID&&$log['partner']!=UID)//不是学员
				throw new MyException('',MyException::NO_RIGHTS);
		}else if ($log['tid']!=UID)//不是教练
			throw new MyException('',MyException::NO_RIGHTS);
		$flag=$this->db->insert('complain',
			['logId'=>$log['id'],'uid'=>UID,'address'=>$input['address'],
				'lat'=>$input['lat'],'lng'=>$input['lng']]);
		if ($flag) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function scanInfo() {
		$input=$this->input->get(['tid','date','time']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$log=$this->db->where($input)->get('teach_log',1)->row_array();
		if (!$log) throw new MyException('',MyException::GONE);
		if ($log['status']!=0) throw new MyException('订单状态不对，请刷新重试',MyException::NO_RIGHTS);
		$data=$this->m->refundNum($log);
		if ($data['refund']==0) restful(200,'');
		else{
			$this->load->helper('infoTime');
			$time=getTime($log['time']).'-'.getTime($log['time']+1);
			if ($this->type==0) restful(200,"由于您预约时段为${time}，比预约时间晚$data[time]分钟进行教学，平台将会折合成$data[refund]学车币，返回到您的个人钱包中，请注意查收！感谢您的使用！");
			else {
				//拼教练，refund要*2
				$data['refund']=$log['partner']>0?$data['refund']*2:$data['refund'];
				restful(200,"学员预约时段为${time}，由于晚教学$data[time]分钟，平台将会折合成$data[refund]学车币退还至学员个人钱包中 ，望您下次按时教学，本次教学您共计收入$data[rest]学车币");
			}
		}
	}
}
