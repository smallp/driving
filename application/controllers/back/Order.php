<?php
/**
 * 处理约驾相关，教学日志、预约、评价等
 * 
 * @author small
 */
class OrderController extends CI_Controller {
	const DEL_ORDER=10;
	const ADD_ORDER=11;
	const DEL_COMMENT=12;
	
	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}
	
	function order($id=0) {
		if (is_numeric($id)&&$id!=0){
			$data=$this->db->select('`order`.*,from_unixtime(`order`.time) time,tea.name tea,tea.tel teaTel,stu.name stu,stu.tel stuTel,par.name partner')
				->join('account tea', 'tea.id=tid')->join('account stu', 'stu.id=`order`.uid')->where('`order`.id',$id)
				->join('account par','par.id=`order`.partner','left')
				->get('`order`')->row_array();
			if (!$data) throw new MyException('',MyException::GONE);
			$data['info']=json_decode($data['info'],TRUE);
			foreach ($data['info'] as &$value) {
				$pname=$this->db->find('place', $value['place'],'id','name');
				$value['place']=$pname?$pname['name']:'无场地';
				$value['status']=$this->db->where(['date'=>$value['date'],'time'=>$value['time'],'tid'=>$data['tid']])
				->get('teach_log',1)->row_array()['status'];
			}
			restful(200,$data);
		}else{
			$page=$this->input->get('page');
			if ($page===NULL) $this->load->view('back/order',['isDel'=>FALSE]);
			else{
				$this->load->model('back/money','m');
				$data=$this->m->orderList($page);
				restful(200,$data);
			}
		}
	}
	
	function addOrderView() {
		$this->load->view('back/addOrder');
	}

	function delOrder($id=0){
		$order=$this->db->find('`order`', $id);
		$this->load->model('order');
		if (!$order) throw new MyException('',MyException::GONE);
		if ($order['status']!=Order::PAYED) throw new MyException('订单状态不对',MyException::NO_RIGHTS);
		$param=$this->input->put(['tea','stu']);
		if (!$param) throw new MyException('',MyException::INPUT_MISS);
		$this->load->model('back/money','m');
		$this->m->cancle($order,$param);
		$this->db->insert('oprate_log',[
			'uid'=>$_SESSION['admin'],
			'link'=>$id,
			'text'=>"取消了一个订单，学员每人获得$param[stu]学车币，教练获得$param[tea]学车币",
			'type'=>self::DEL_ORDER
		]);
		restful();
	}
	
	function cancle() {
		$page=$this->input->get('page');
		if ($page===NULL) $this->load->view('back/order',['isDel'=>TRUE]);
		else{
			$this->load->model('back/money','m');
			$this->db->select('(SELECT name FROM admin WHERE id IN (SELECT uid FROM oprate_log WHERE link=`order`.id AND type='.self::DEL_ORDER.')) oprator');
			$this->db->start_cache();
			$this->db->join('delOrderReq', 'delOrderReq.orderId=`order`.id')->select('reason,dealTime');
			$data=$this->m->orderList($page);
			$data['data']=array_map(function($item){
				if ($item['dealTime']) $item['dealTime']=date('Y-m-d H:i:s',$item['dealTime']);
				else $item['dealTime']='';
				return $item;
			}, $data['data']);
			restful(200,$data);
		}
	}
	
	function availTime($id=0) {
		$this->load->model('order');
		define('UID', $id);
		$data=$this->order->availTime($id);
		restful(200,$data);
	}
	
	function avaliPlace() {
		$input=$this->input->get();
		$this->load->model('order');
		$data=$this->order->avaliPlace($input);
		restful(200,$data);
	}
	
	function addOrder() {
		$this->load->model('order');
		$input=$this->input->post(['info','id','partner','kind','uid']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		define('UID', $input['uid']);
		$this->order->addOrder($input);
		$orders=$this->db->where([
				'tid'=>$input['id'],
				'`order`.status <'=>Order::PAYED,
				'info'=>"CAST('$input[info]' AS JSON)"],NULL,FALSE)
			->select('id')->get('`order`')->result_array();
		try {
			$this->db->trans_begin();
			foreach ($orders as $order) {
				$this->order->pay($order['id']);
			}
			$this->db->trans_complete();
		} catch (MyException $e) {
			$this->db->trans_rollback();
			foreach ($orders as $order) {
				$this->db->where('id',$order['id'])->delete('`order`');
			}
			throw $e;
		}
		foreach ($orders as $order) {
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$order['id'],
				'text'=>"添加了一个订单，订单ID是$order[id]",
				'type'=>self::ADD_ORDER
			]);
		}
		restful(201);
	}
	
	function comment($id=0) {
		if ($id==0){
			$page=$this->input->get('page');
			if ($page===NULL)
				$this->load->view('back/comment');
			else {
				$this->db->start_cache();
				if ($uid=$this->input->get('uid'))
					$this->db->where('uid',$uid);
				if ($date=$this->input->get(['begin','end']))
					$this->db->where('tcomment.time BETWEEN '.strtotime($date['begin']).' AND '.strtotime($date['end']));
				$count=20;
				$data=$this->db->select('tcomment.id,stu.name stu,tea.name tea,`describe`,quality,attitude,teachTime,left(content,15) content,tcomment.time')
					->join('account stu','stu.id=tcomment.uid')
					->join('account tea','tea.id=tcomment.tid')
					->order_by('tcomment.id','desc')
					->get('tcomment',$count,$page*$count)->result_array();
				$total=$this->db->count_all_results();
				restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
			}
		}else {
			$data=$this->db->select('tcomment.*,stu.name stu,tea.name tea')
				->join('account stu','stu.id=tcomment.uid')
				->join('account tea','tea.id=tcomment.tid')
				->where('tcomment.id',$id)->get('tcomment')->row_array();
			if (!$data) throw new MyException('',MyException::GONE);
			$data['pics']=json_decode($data['pics'],TRUE);
			restful(200,$data);
		}
	}
	
	function delComment() {
		$id=$this->input->put('id');
		if (!is_array($id)) throw new MyException('',MyException::INPUT_ERR);
		$data=$this->db->select('uid,content')->where_in('id',$id)
			->get('tcomment')->result_array();
		if (empty($data)) restful();
		foreach ($data as $item) {
			$log[]=[
				'uid'=>$_SESSION['admin'],
				'link'=>$item['uid'],
				'text'=>"删除了教练评价$item[content]",
				'type'=>self::DEL_COMMENT
			];
		}
		$this->db->insert_batch('oprate_log',$log);
		if ($this->db->where_in('id',$id)->delete('tcomment'))
			restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function teachLog($id=0) {
// 		if ($id==0){
			$page=$this->input->get('page');
			$count=10;
			if ($page===NULL){
				$this->load->view('back/teachLog');
				return;
			}
			$this->load->model('back/money');
			$this->db->limit($count,$page*$count);
			$data=$this->money->teachLog();
			$data['total']=ceil($data['total']/$count);
			restful(200,$data);
// 		}else{
// 			$data=$this->db->select('stu.name stu,tea.name tea,teach_log.*,place.name')
// 				->join('account stu','account.id=stu.uid')->join('account tea','account.id=tea.tid')
// 				->join('place', 'place.id=teach_log.place','left')
// 				->get('teach_log')->row_array();
// 			if (!$data) throw new MyException('',MyException::GONE);
// 			else restful(200,$data);
// 		}
	}
	
	function downTeachLog() {
		$this->load->model('back/money');
		$data=$this->money->teachLog(TRUE)['data'];
		$res='';
		foreach ($data as $value) {
			$item='';
			$item.='教练：'.$value['teacher']."\n";
			$item.='学员：'.$value['student']."\n";
			$item.='时间：'.$value['time']."\n";
			$item.='场地：'.($value['place']?:'')."\n";
			$item.='日志：'.$value['content']."\n\n";
			$res.=$item;
		}
		$this->load->helper('download');
		force_download('教学日志.doc', $res);
	}
}
