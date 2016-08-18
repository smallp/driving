<?php
class Money extends CI_Model {
	function teachLog($download=FALSE) {
		$this->db->start_cache();
		if ($id=$this->input->get('stu'))
			$this->db->where('uid',$id);
		if ($id=$this->input->get('tea'))
			$this->db->where('tid',$id);
		if ($date=$this->input->get(['begin','end']))
			$this->db->where(['date >='=>$date['begin'],'date <='=>$date['end']]);
		$this->db->where('teach_log.status !=',0);
		$this->db->stop_cache();
		$this->db->select('stu.name student,tea.name teacher,date,teach_log.time,content,par.name partner,place.name place');
		$data=$this->db->order_by('teach_log.id','desc')
		->join('account stu','stu.id=teach_log.uid')->join('account tea','tea.id=teach_log.tid')
		->join('place', 'place.id=teach_log.place','left')
		->join('account par','par.id=`teach_log`.partner','left')
		->get('teach_log')->result_array();
		$total=$this->db->count_all_results('teach_log');
		
		$fun=function($h){
			return ((int)$h).(is_int($h)?':00':':30');
		};
		foreach ($data as &$value) {
			$value['content']=$download?$value['content']:nl2br($value['content']);
			$time=$value['date'];
			$time.=' '.$fun($value['time']).'-'.$fun($value['time']+1);
			$value['time']=$time;
			$value['place']=$value['place']?:'无';
			$value['student']=$value['partner']?$value['student'].'、'.$value['partner']:$value['student'];
		}
		return ['data'=>$data,'total'=>$total];
	}
	
	function orderList($page) {
		$this->db->start_cache();
		if ($id=$this->input->get('stu'))
			$this->db->where('`order`.uid',$id);
		if ($id=$this->input->get('tea'))
			$this->db->where('`order`.tid',$id);
		if ($id=$this->input->get('begin')){
			$time=['begin'=>strtotime($id),'end'=>strtotime($this->input->get('end'))];
			$this->db->where("`order`.time BETWEEN $time[begin] AND $time[end]");
		}
			
		$this->db->stop_cache();
		$count=10;
		$data=$this->db->select('`order`.id,`order`.kind,price,order.status,par.name partner,from_unixtime(`order`.time) time,tea.name tea,stu.name stu,realPrice,info->"$[0]" info')
			->join('account tea', 'tea.id=tid')->join('account stu', 'stu.id=`order`.uid')
			->order_by('`order`.id','desc')->join('account par','par.id=`order`.partner','left')
			->get('`order`',$count,$page*$count)->result_array();
		$total=$this->db->count_all_results('`order`');
		return ['data'=>$data,'total'=>ceil($total/$count)];
	}
	
	function cancle($order,$param) {
		$sms=[$this->db->find('user join account on account.id=user.id', $order['uid'],'user.id','tel,realname,gender')];
		switch ($order['kind']) {
			case 1:$type='科目二';
			break;
			case 2:$type='科目三';
			break;
			default:$type='陪练陪驾';
			break;
		}
		$sms[0]['data']=['type'=>$type];
		
		$this->load->model('notify');
		$cost=$this->_dealOrder($order, $param['stu']);
		$log=['num'=>$param['stu'],'time'=>time()];//钱包明细
		$log['content']="取消订单，已退款$log[num]学车币";
		if ($order['partner']!=0){//处理同伴的
			$partner=$this->db->where(['uid'=>$order['partner'],'tid'=>$order['tid'],'info'=>"CAST('$info' AS JSON)"],NULL,FALSE)
			->get('`order`',1)->row_array();
			$pcost=$this->_dealOrder($partner, $param['stu']);
			$this->order->adminCancle($partner,$pcost);
			
			//记录日志
			$log['uid']=$partner['uid'];
			$this->db->insert('money_log',$log);
			$user=$this->db->find('user join account on account.id=user.id', $partner['uid'],'user.id','tel,realname,gender');
			$user['data']=$sms[0]['data'];
			$sms[]=$user;
			$param['stu']*=2;
		}
		$this->order->adminCancle($order,$cost);
		
		foreach ($sms as $value) {
			$value['data']['name']=$value['realname'].(($value['gender']==0)?'先生':'女士');
			$this->notify->sendSms(Notify::SMS_YUE_CANCLE_STU,$value['tel'],$value['data']);
		}
		//处理教练事件
		$flag=$this->db->where('id',$order['tid'])->step('teacher', 'money',TRUE,$param['tea']);
		if (!$flag) return FALSE;
		$log['num']=$param['tea'];
		$log['content']="有订单被取消，获得$log[num]学车币";
		$log['uid']=$order['tid'];
		$log['type']=2;
		$this->db->insert('money_log',$log);
		$tea=$this->db->find('teacher join account on account.id=teacher.id', $order['tid'],'teacher.id','tel,realname');
		$data=$sms[0]['data'];
		$data['name']=$tea['realname'];
		$this->notify->sendSms(Notify::SMS_YUE_CANCLE_TEA,$tea['tel'],$data);

		//需要记录退款时平台的收支，此时活动开支已经计算了
		$this->db->insert('income',['type'=>1,'num'=>$order['realPrice']-$param['stu']-$param['tea'],'tid'=>$order['tid']]);
	}
	
	//退款时处理第三方、赠币、可提现币的具体数量
	function _dealOrder(&$order,$coins) {
		if ($order['money']+$order['frozenMoney']==0) return $order['realPrice']-$coins;
		else{
			if ($order['money']>=$coins){
				$order['money']=$coins;
				$order['frozenMoney']=0;
			}else{
				$order['frozenMoney']=$coins-$order['money'];
			}
			return 0;
		}
	}
}
