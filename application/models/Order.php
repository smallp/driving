<?php
class Order extends CI_Model {
	const PARAM=APPPATH.'controllers/back/param.json';
	const CANCLE=6;//已取消
	const EXPIRE=5;//已过期
	const DONE=4;
	const TO_WRITE_COMMENT=3;
	const PAYED=2;
	const ERROR=7;
	const CLASS_TIME=40;//一节课40分钟
	
	function getList($page,$istea=FALSE) {
		$count=10;
		if ($istea){
			$this->db->select('`order`.info,`order`.id,`order`.kind,price,status,partner,`order`.time,(SELECT name FROM place WHERE place.id=info->"$[0].place") pname,uid')
				->where(['`order`.tid'=>UID,'thide'=>0]);
		}else{
			$kind=$this->input->get('kind');
			if ($kind!==NULL){
				$this->db->where(['order.kind'=>$kind,'order.status'=>2]);
			}
			$this->db->select('`order`.info,`order`.id,`order`.kind,`order`.status,zjType,partner,`order`.time,(SELECT name FROM place WHERE place.id=info->"$[0].place") pname,account.name tname,avatar,realPrice')
				->join('teacher', 'teacher.id=tid')
				->join('account', 'account.id=tid')->where(['`order`.uid'=>UID,'hide'=>0]);
		}
		$data=$this->db->order_by('`order`.id','desc')//->order_by('`order`.info->"$[0].index"','asc')
			->get('`order`',$count,$page*$count)->result_array();
		$index=[];$res=[];
		foreach ($data as $item) {
			$info=json_decode($item['info'],TRUE);
			if ($istea){
				if (in_array($info[0]['index'], $index)) continue;
				else $index[]=$info[0]['index'];
				$item['student']=$this->db->where_in('id',[$item['uid'],$item['partner']])
					->select('id,name,avatar')->get('account')->result_array();
				$item['orderTime']=$info[0]['time'];
			}else{
				if ($item['partner']==0) unset($item['partner']);
				else{
					$item['partner']=$this->db->find('account', $item['partner'],'id','id,name,avatar');
				}
			}
			$item['more']=count($info)>1;
			unset($item['info']);
			if (!$item['pname']) $item['pname']='';
			$res[]=$item;
		}
		return $res;
	}
	
	function item($id,$istea=FALSE) {
		if ($istea)
			$this->db->join('account', 'account.id=uid')->where(['order.id'=>$id,'tid'=>UID]);
		else $this->db->join('account', 'account.id=tid')->where(['order.id'=>$id,'uid'=>UID]);
		$data=$this->db->select('`order`.*,account.name tname,avatar,account.tel,zjType')
			->join('teacher', 'teacher.id=tid')
			->get('`order`',1)->row_array();
		if (!$data) throw new MyException('',MyException::GONE);
		
		if ($data['partner']==0){
			if ($istea){
				$data['partner']=[['name'=>$data['tname'],'avatar'=>$data['avatar'],'id'=>$data['uid'],'tel'=>$data['tel']]];
				unset($data['tname'],$data['avatar'],$data['uid']);
			}else {
				unset($data['partner']);
			}
			if ($data['status']<=self::PAYED){//已取消就不用管了
				require_once 'Notify.php';
				$cancle=$this->db->where('type BETWEEN '.Notify::STU_CANCLE_REQ.
					' AND '.Notify::STU_CANCLE_FAIL." AND (link=$data[id])")
					->get('notify',1)->row_array();
				if ($cancle){
					$data['cancle']=$cancle['type'];
					$data['cancleReplyer']=0;
				}else{
					$data['cancle']=1;
					$data['cancleReplyer']=0;
				}
			}
		}else{
			$data['partner']=$this->db->find('account', $data['partner'],'id','id,name,avatar,tel');
			if ($istea){
				$data['partner']=[['name'=>$data['tname'],'avatar'=>$data['avatar'],'id'=>$data['uid'],'tel'=>$data['tel']],$data['partner']];
				unset($data['tname'],$data['avatar'],$data['uid']);
			}
			if ($data['status']<=self::PAYED){//已取消就不用管了
				require_once 'Notify.php';
				$partner=$this->db->where(['uid'=>($istea?$data['partner'][1]['id']:$data['partner']['id']),'`order`.status <'=>SELF::EXPIRE,'tid'=>$data['tid'],'info'=>"CAST('$data[info]' AS JSON)"],NULL,FALSE)
					->select('id')->get('`order`',1)->row_array();
				$partner=$partner?:['id'=>0];
				$cancle=$this->db->where('type BETWEEN '.Notify::STU_CANCLE_REQ.
					' AND '.Notify::STU_CANCLE_FAIL.' AND (link='.$data['id'].' OR link='.$partner['id'].')')
					->get('notify',1)->row_array();
				if ($cancle){
					$data['cancle']=$cancle['type'];
					$data['cancleReplyer']=$cancle['uid'];
				}else{
					$data['cancle']=1;
					$data['cancleReplyer']=0;
				}
			}
		}
		$data['info']=json_decode($data['info'],TRUE);
		$data['now']=time();
		foreach ($data['info'] as &$value) {
			$pname=$this->db->find('place', $value['place'],'id','name');
			$value['place']=$pname?$pname['name']:'无场地';
			$log=$this->db->where(['date'=>$value['date'],'time'=>$value['time'],'tid'=>$data['tid']])
				->select('status,startTime')->get('teach_log',1)->row_array();
			$value['status']=$log['status'];
			$value['startTime']=$log['startTime'];
			$value['code']="date=$value[date]&time=$value[time]&t=";
		}
		return $data;
	}
	
	//教练可用时间
	function availTime($id) {
		$this->_removeOrder();
		$user=$this->db->find('account', UID,'id','status');
		if ($user['status']!=1) throw new MyException('',MyException::NO_RIGHTS);
		$data=$this->db->find('teacher',$id,'id','orderInfo');
		if (!$data) throw new MyException('此教练不存在！',MyException::GONE);
		$data=json_decode($data['orderInfo'],TRUE);
		$today=date('Y-m-d');
		$res=[];
		//获取有效订单并标记
		$arr=$this->db->select('distinct info,uid,partner')
			->where(['tid'=>$id,'status <'=>SELF::EXPIRE])
			->get('`order`')->result_array();
		$orders=[];
		foreach ($arr as $value) {
			$order=json_decode($value['info'],TRUE);
			foreach ($order as $item) {
				$item['uid']=$value['uid'];
				$item['partner']=$value['partner'];
				$orders[]=$item;
			}
		}
		foreach ($data as $value) {
			if ($value['date']>=$today){
				foreach ($orders as $key=>$order) {
					if ($value['date']==$order['date']&&$value['time']==$order['time']){
						$value['price']=-1;
						$value['uid']=$order['uid'];
						$value['partner']=$order['partner'];
						unset($orders[$key]);
						break;
					}
				}
				unset($value['place']);
				$value['price']=$value['price']>=0?
					$this->price($value['price']):$value['price'];
				$res[]=$value;
			}
		}
		return $res;
	}
	
	//教练自己的预约情况
	function availTimeTea() {
		$this->_removeOrder();
		$data=$this->db->find('teacher',UID,'id','orderInfo');
		if (!$data) throw new MyException('',MyException::GONE);
		$data=json_decode($data['orderInfo'],TRUE);
		$today=date('Y-m-d');
		$res=[];
		$arr=$this->db->select('id,info,partner,status')
			->where(['tid'=>UID,'status <'=>SELF::EXPIRE,'time >='=>time()-604800])//最近7天预约成功的记录
			->get('`order`')->result_array();
		$orders=[];
		foreach ($arr as $item) {
			$order=json_decode($item['info'],TRUE);
			array_walk($order, function(&$orderItem,$key,$item){
				$orderItem['partner']=$item['partner']>0;
				$orderItem['id']=$item['id'];
				$orderItem['status']=$item['status'];
			},$item);
			$orders=array_merge($orders,$order);
		}
		foreach ($data as $value) {
			if ($value['date']>=$today){
				$value['status']=0;//默认未预约
				foreach ($orders as $key=>$order) {
					if ($value['date']==$order['date']&&$value['time']==$order['time']){
						$value['status']=$order['partner']==0?1:2;//1单人 2约驾
						$value['id']=$order['id'];
						unset($orders[$key]);
						break;
					}
				}
				unset($value['place']);
				$res[]=$value;
			}
		}
		return $res;
	}
	
	function avaliPlace($input) {
		$data=$this->db->find('teacher',$input['id'],'id','orderInfo');
		if (!$data) throw new MyException('',MyException::GONE);
		$data=json_decode($data['orderInfo'],TRUE);
		$res=[];
		foreach ($data as $value) {
			if ($value['time']==$input['time']&&$value['date']==$input['date']){
				$res=$value;
				break;
			}
		}
		if (empty($res)) throw new MyException('这个时间段教练好像不能预约哦',MyException::GONE);//这个时间段，没有设置预约
		if (empty($res['place'])) return [];//科目三，直接返回
		return $this->db->where_in('id',$res['place'])->select('id,name')->get('place')->result_array();
	}
	
	function addOrder($input){
		$this->_removeOrder();
		//判断科目是否合法
		$kind=$this->db->find('teacher',$input['id'],'id','kind');
		if (!$kind) throw new MyException('此教练不存在',MyException::GONE);
		$kind=$kind['kind'];
		if ($kind%($input['kind']*2)<$input['kind'])
			throw new MyException('此教练没有这个服务哦！',MyException::INPUT_ERR);
		
		$orders=json_decode($input['info'],TRUE);
		if (!$orders)
			throw new MyException('',MyException::INPUT_ERR);
        if (count($orders)>1)
            throw new MyException('内测阶段请不要多选',MyException::INPUT_ERR);
        $have=$this->db->query('SELECT count(*) num FROM `order` WHERE status<4 AND uid=? AND tid=?'.
			" AND JSON_SEARCH(info->'$[*].date','one',?) IS NOT NULL",
			[UID,$input['id'],$orders[0]['date']])->row();
        if ($have->num>0)
        	throw new MyException('同一个教练一天只能约一个时段',MyException::INPUT_ERR);
		$res=['info'=>[]];
		$ignorePlace=$input['kind']>=2;
		$teaPriceTotal=0;
		$priceTotal=0;
		foreach ($orders as $order) {
			$order['tid']=$input['id'];
			$priceTea=$this->_dealOrder($order,$ignorePlace);
			$price=$this->price($priceTea,$input['partner']);
			$t=['time'=>(string)$order['time'],'date'=>$order['date']
					,'place'=>(int)$order['place'],'index'=>$order['date'].$order['time'],
					'price'=>$price,'priceTea'=>$input['partner']>0?floor($priceTea*1.2):$priceTea];
			$res['info'][]=$t;
			$teaPriceTotal+=$t['priceTea'];
			$priceTotal+=$t['price'];
		}
		usort($res['info'],function($a,$b){
			return $a['index']>$b['index']?1:-1;
		});
		$res['price']=$teaPriceTotal;
		$res['realPrice']=$priceTotal;
		$res['uid']=UID;
		$res['tid']=$input['id'];
		$res['kind']=$input['kind'];
		$res['info']=json_encode($res['info']);
		$res['status']=0;
		$res['time']=time();
		$res['partner']=$input['partner'];
		if ($input['partner']){
			if ($this->db->where(['fromid'=>UID,'toid'=>$input['partner']])
					->get('attention')->num_rows()==0)
				throw new MyException('',MyException::NO_RIGHTS);
			$partner=$res;
			$partner['uid']=$res['partner'];
			$partner['partner']=$res['uid'];
			if (!$this->db->insert('`order`',$partner))
				throw new MyException('',MyException::DATABASE);
			$this->load->model('notify');
			$this->notify->send(['uid'=>$input['partner'],'link'=>$this->db->insert_id()],Notify::TEA_SHARE_REQ);
		}
		return $this->db->insert('`order`',$res);
	}
	
	function pay($id) {
		$this->_removeOrder();
		$order=$this->db->find('`order`', $id);
		if (!$order) throw new MyException('',MyException::GONE);
		if ($order['uid']!=UID&&!isset($_SESSION['admin'])) throw new MyException('',MyException::NO_RIGHTS);
		if ($order['status']!=0) throw new MyException('订单状态不对！',MyException::CONFLICT);
		
		$this->load->model('notify');
		if ($order['partner']!=0){
			$partner=$this->db->where(['uid'=>$order['partner'],'tid'=>$order['tid'],'`order`.status <'=>SELF::EXPIRE,'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
				->select('id,uid,status')->get('`order`',1)->row_array();
			$request=$this->db->where('type='.Notify::TEA_SHARE_REQ.
				' AND (link='.$id.' OR link='.$partner['id'].')')
				->get('notify',1)->row_array();
			if ($request&&$request['uid']!=$order['uid']){
				throw new MyException('请等待对方同意后再支付',MyException::INPUT_ERR);
			}
		}else $partner=FALSE;
		
		$this->db->trans_start();
		$isMoney=$this->db->where(['orderId'=>$id,'status'=>1])->count_all_results('charge')==0;
		if ($isMoney){//使用学车币
			//资金流水
			$realM=0;$virM=0;
			$myMoney=$this->db->find('user', $order['uid'],'id','money,frozenMoney');
			$money=$order['realPrice'];
			if ($myMoney['money']+$myMoney['frozenMoney']<$money)
				throw new MyException('学车币不足，请先充值',MyException::NO_RIGHTS);
			$newOrder=['money'=>0,'frozenMoney'=>0];
			if ($myMoney['frozenMoney']>$money){
				$myMoney['frozenMoney']-=$money;
				$newOrder['frozenMoney']=$money;
				$virM=$money;
			}else {
				//优先使用不可提现币
				$money-=$myMoney['frozenMoney'];
				$newOrder['frozenMoney']=$myMoney['frozenMoney'];
				$myMoney['frozenMoney']=0;
				$virM=$myMoney['frozenMoney'];
				//不足部分用普通币
				$myMoney['money']-=$money;
				$newOrder['money']=$money;
				$realM=$money;
			}
			$this->db->where('id',$order['uid'])->update('user',$myMoney);
			//流水处理完成
		}else{//全用现金
			$realM=$money;
			$virM=0;
		}
		$this->db->insert('money_log',[
				'uid'=>$order['uid'],
				'num'=>$order['realPrice']*-1,
				'realMoney'=>$realM*-1,
				'virtualMoney'=>$virM*-1,
				'content'=>"练车花费$order[realPrice]学车币",
				'time'=>time()]
		);
		
		if ($partner){
			if ($partner['status']==0){//对方未付款，修改状态为等待对方支付
				$newOrder['status']=1;
				$this->db->where('id',$order['id'])->update('`order`',$newOrder);
			}else{//对方已付款，修改双方状态为等待学车
				$newOrder['status']=2;
				$this->db->where('id',$order['id'])->update('`order`',$newOrder);
				$this->db->where('id',$partner['id'])->update('`order`',['status'=>2]);
			}
		}else{
			$newOrder['status']=2;
			$this->db->where('id',$order['id'])->update('`order`',$newOrder);
		}
		//添加空白教学日志
		if ($newOrder['status']==2){
			$orderInfo=json_decode($order['info'],TRUE);
			$info=['uid'=>$order['uid'],'tid'=>$order['tid'],'partner'=>$order['partner'],'orderId'=>$order['id'],
					'price'=>$order['price']];
			if ($info['partner']>0) $info['price']*=2;//合拼，要把价格恢复回去
			array_walk($orderInfo, function(&$item,$key,$info){
				unset($item['index']);
				$item=array_merge($item,$info);
			},$info);
			reset($orderInfo);//insert_batch has bug if the point is at the last
			$this->db->insert_batch('teach_log',$orderInfo);
		}
		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
			throw new MyException('',MyException::DATABASE);
		if ($order['price']>$order['realPrice'])//添加活动记录
			$this->db->insert('activity_log',['uid'=>$order['uid'],'aid'=>6,'num'=>$order['price']-$order['realPrice']]);
		//支付完成，发送短信
		if ($newOrder['status']==2){
			$this->notify->send(['uid'=>$order['uid'],'link'=>$order['id']],Notify::YUE_SUCCESS);
			$name=[$this->db->find('user join account on account.id=user.id', $order['uid'],'account.id','tel,gender,name')];
			if ($partner){//我的订单id小，说明通知是给对方的，否则亦然
				$this->notify->send(['uid'=>$partner['uid'],'link'=>$partner['id']],Notify::YUE_SUCCESS);
				$order['id']>$partner['id']?$this->db->where(['type'=>Notify::TEA_SHARE_REQ,'uid'=>$order['uid'],'link'=>$order['id']])
					->update('notify',['type'=>Notify::TEA_SHARE_AC]):
				$this->db->where(['type'=>Notify::TEA_SHARE_REQ,'uid'=>$partner['uid'],'link'=>$partner['id']])
					->update('notify',['type'=>Notify::TEA_SHARE_AC]);
				$name[]=$this->db->find('user join account on account.id=user.id', $partner['uid'],'account.id','tel,gender,name');
			}
			$data=[];
			switch ($order['kind']) {
				case 1:$data['type']='科目二';$this->db->where('id',$order['uid'])->update('user',['level'=>2]);//进入科目二了
				break;
				case 2:$data['type']='科目三';$this->db->where('id',$order['uid'])->update('user',['level'=>3]);
				break;
				default:$data['type']='陪练陪驾';
				break;
			}
			$timeArr=[];
			foreach ($orderInfo as $item) {
				$timeArr[$item['date']][]=$item['time'];
			}
			foreach ($timeArr as $key=>$value) {
				$str=$this->mergeTime($value);
				$timeArr[$key]=date('n月j日',strtotime($key)).$str;
			}
			$data['time']=join('，', $timeArr);
			array_walk($name, function(&$item){
				$item['name']=$item['name'].(($item['gender']==0)?'先生':'女士');//mb_substr($item['realname'],0,1).(($item['gender']==0)?'先生':'女士');
			});
			$teaData=$data;
			$teaInfo=$this->db->find('teacher join account on account.id=teacher.id', $order['tid'],'account.id','tel,phone,realname');
			$data['teacher']=$teaInfo['realname'];//mb_substr($teaInfo['realname'], 0,1);
			$teaData['name']='';
			//@TODO 现在展示的是注册的手机号，后期可能需要修改成教练信息里面显示的手机号
			$data['tel']=$teaInfo['tel'];
			$data['place']='';//前一天才发送具体地址
			foreach ($name as $one) {//发送给学员
				$data['name']=$one['name'];
				$teaData['name'].=$one['name'];
				$this->notify->sendSms(Notify::SMS_YUE_STU,$one['tel'],$data);
			}
			$this->notify->send(['uid'=>$order['tid'],'link'=>$order['id']],Notify::YUE_SUCCESS_TEA);
			$this->notify->sendSms(Notify::SMS_YUE_TEA,$teaInfo['tel'],$teaData);
			//同步教练的空余时间
			$this->db->where('id',$order['tid'])->step('teacher','freeTime',false);
		}else{//拼教练，同意并支付步骤的推送
			$this->notify->send(['uid'=>$partner['uid'],'patlink'=>$partner['id'],'link'=>$id],Notify::TEA_SHARE_AC);
		}
	}
	
	//确认教学
	function certain($info,$isTea) {
		$log=$this->db->where($info)->get('teach_log',1)->row_array();
		if (!$log) throw new MyException('',MyException::GONE);
		$this->load->model('notify');
		if ($isTea){
			if ($log['tid']!=UID||$log['status']!=0) throw new MyException('',MyException::NO_RIGHTS);
			$logTime=$this->getTime($info);
			if ($logTime+3600>time()){//结束之前可以开始教学
				$newLog=['status'=>1,'startTime'=>time()];
				$order=$this->db->query("SELECT id,uid,money,frozenMoney FROM `order` WHERE info=(SELECT info FROM `order` WHERE id=$log[orderId]) AND tid=$log[tid] AND status=2")->result_array();
				$refund=$this->refundNum($log);
				if ($refund['refund']>0){
					$newLog['priceTea']=$refund['rest'];
					$newLog['price']='price-'.($log['partner']>0?$refund['refund']*2:$refund['refund']);
				}
				foreach ($order as $value) {
					if ($refund['refund']>0){
						$this->partRefund($value, $refund);
					}
					$this->notify->send(['uid'=>$value['uid'],'link'=>$value['id']],Notify::CERTAIN);
				}
				return $this->db->where('id',$log['id'])->update('teach_log',$newLog);
			}else throw new MyException('时间不对哦，请在练车开始半小时内确认练车',MyException::INPUT_ERR);
		}else{
			if (defined('UID')&&UID!=$log['uid']&&UID!=$log['partner'])
				throw new MyException('',MyException::NO_RIGHTS);
			if ($log['status']>=2)
				throw new MyException('',MyException::DONE);
			if ($log['status']==0){//教练没确认，学员只能在学车完成时间后确认教学
				throw new MyException('教练未开始教学，无法操作！',MyException::NO_RIGHTS);
			}
			$flag=$this->db->where('id',$log['id'])->update('teach_log',['status'=>2]);
			if ($flag){
				try {
					$this->finishOrder($log);
				} catch (Exception $e) {
					if ($e->getCode()==MyException::DATABASE){//退回
						$this->db->where('id',$log['id'])->update('teach_log',['status'=>1]);
						return FALSE;
					}
				}
				$order=$this->db->query("SELECT id,uid,money,frozenMoney FROM `order` WHERE info=(SELECT info FROM `order` WHERE id=$log[orderId])")->result_array();
				$this->load->helper('infoTime');
				$time=getTime($log['time']).'-'.getTime($log['time']+self::CLASS_TIME);
				foreach ($order as $item) {
					$this->notify->send(['uid'=>$item['uid'],'link'=>$item['id'],'text'=>"您预约的${time}时段，教练已完成教学"],Notify::CERTAIN_STU);
				}
				$this->notify->send(['uid'=>$log['tid'],'link'=>$log['orderId'],'text'=>"已完成预约的${time}教学计划"],Notify::CERTAIN_STU);
				return TRUE;
			}else return FALSE;
		}
	}
	
	/**
	 * 
	 * @param array $order
	 * @param array $refund
	 * @param bool $isAdmin 开始学车的退款或后台处理申述
	 * @throws MyException
	 * @return array 用户支出的学车币
	 */
	function partRefund($order,$refund,$isAdmin=FALSE) {
		$this->db->trans_begin();
		$set=['realPrice'=>'realPrice-'.$refund['refund'],
				'price'=>'price-'.$refund['teaCost'],
				'money'=>$order['money'],
				'frozenMoney'=>$order['frozenMoney']
		];
		//设定退款类型
		$realM=0;$virM=0;
		if ($order['money']+$order['frozenMoney']==0)
			$realM=$refund['refund'];
		else{
			if ($order['money']>=$refund['refund']){
				$realM=$refund['refund'];
				$set['money']=$order['money']-$refund['refund'];
			}else{
				$realM=$order['money'];
				$set['money']=0;
				$virM=$refund['refund']-$realM;
				$set['frozenMoney']=$order['frozenMoney']-$virM;
			}
		}
		$this->db->set(['money'=>'money+'.$realM,'frozenMoney'=>'frozenMoney+'.$virM],NULL,FALSE)
			->where('id',$order['uid'])->update('user');
		$this->db->insert('money_log',['uid'=>$order['uid'],'num'=>$refund['refund'],'time'=>time(),
					'realMoney'=>$realM,'virtualMoney'=>$virM,
					'content'=>$isAdmin?"处理异常订单，获得退款$refund[refund]学车币":"晚教学，获得退款$refund[refund]学车币"]);
		//更新订单
		$this->db->set($set,NULL,FALSE)->where('id',$order['id'])->update('`order`');
		$this->db->trans_complete();
		if ($this->db->trans_status()===FALSE)
			throw new MyException('',MyException::DATABASE);
		return ['realMoney'=>$set['money'],'virtualMoney'=>$set['frozenMoney']];
	}
	
	function commentList($count,$offset) {
		$data=$this->db->select('`order`.id,JSON_UNQUOTE(info->"$[0].date") date,place.name pname,account.name tname,avatar,order.kind,tcomment.`grade`')
		->join('tcomment', 'tcomment.id=`order`.id')->join('place', 'place.id=info->"$[0].place"','LEFT')->order_by('id','desc')
		->join('account', 'account.id=`order`.tid')->where(['`order`.uid'=>UID,'`order`.status'=>4])
		->get('`order`',$count,$offset)->result_array();
		return array_map(function($item){
			$item['pname']=$item['pname']?:'';
			return $item;
		}, $data);
	}
	
	function commentListTea($count,$offset) {
		$data=$this->db->select('`order`.id,JSON_UNQUOTE(info->"$[0].date") date,place.name pname,avatar,order.kind,tcomment.grade,tcomment.hide')
		->join('place', 'place.id=info->"$[0].place"','LEFT')->join('tcomment','tcomment.id=`order`.id')->order_by('id','desc')
		->join('account', 'account.id=tcomment.uid')->where(['`order`.tid'=>UID])
		->get('`order`',$count,$offset)->result_array();
		return array_map(function($item){
			if ($item['hide']){
				$item['name']='匿名';
				$item['avatar']=Account::AVATAR;
			}
			$item['pname']=$item['pname']?:'';
			return $item;
		}, $data);
	}
	
	function commentItem($id) {
		$data=$this->db->select('`order`.info,price,account.name tname,(SELECT grade FROM teacher WHERE teacher.id=order.tid) grade,avatar,tcomment.*')
		->join('`order`', 'tcomment.id=`order`.id') 
		->join('account', 'account.id=tcomment.tid')->where(['tcomment.id'=>$id,'tcomment.uid'=>UID])
		->get('tcomment',1)->row_array();
		if (!$data) throw new MyException('',MyException::GONE);
		$data['info']=json_decode($data['info'],TRUE);
		$data['pics']=json_decode($data['pics'],TRUE);
		foreach ($data['info'] as &$item) {
			$item['place']=$this->db->find('place', $item['place'],'id','name')['name'];
		}
		return $data;
	}
	
	function commentItemTea($id) {
		$data=$this->db->select('`order`.info,price,account.name tname,(SELECT grade FROM teacher WHERE teacher.id=order.tid) grade,avatar,tcomment.*')
		->join('`order`', 'tcomment.id=`order`.id') 
		->join('account', 'account.id=tcomment.tid')->where(['tcomment.id'=>$id,'tcomment.tid'=>UID])
		->get('tcomment',1)->row_array();
		if (!$data) throw new MyException('',MyException::GONE);
		$data['info']=json_decode($data['info'],TRUE);
		$data['pics']=json_decode($data['pics'],TRUE);
		foreach ($data['info'] as &$item) {
			$item['place']=$this->db->find('place', $item['place'],'id','name')['name'];
		}
		return $data;
	}
	
	/**
	 * Check whether the data is valid
	 * 
	 * @param	array include date,time,tid
	 * @return	void
	 */
	function _dealOrder($data,$ignorePlace=FALSE) {
		static $info=[];
		//提前2小时才可预约
		$target=$this->getTime($data);
		if ($target<time()-7200)
			throw new MyException('需要提前2小时才可预约呢！',MyException::INPUT_ERR);
		$have=$this->db->query('SELECT tid FROM `order` WHERE status<4 AND (tid=? OR uid=?)'.
				" AND JSON_SEARCH(info->'$[*].index','one',?) IS NOT NULL",
				[$data['tid'],UID,$data['date'].$data['time']])
			->row_array();
		if ($have)
			throw new MyException(($have['tid']==$data['tid'])?'此时间段已被预约！':'',MyException::CONFLICT);
		//检查时间和场地信息是否合法
        if (!$info){
        	$info=$this->db->find('teacher', $data['tid'],'id','orderInfo')['orderInfo'];
        	$info=json_decode($info,TRUE);
        }
		foreach ($info as $item) {
			if ($item['date']==$data['date']&&$item['time']==$data['time']){
				if (!$ignorePlace){
					if (!isset($data['place']))
						throw new MyException('',MyException::INPUT_MISS);
					if (!in_array($data['place'], $item['place']))
						throw new MyException('请重新选择场地',MyException::INPUT_ERR);
				}//科目3，不需要验证场地
				return $item['price'];
			}
		}
		throw new MyException('有时间段教练不提供服务哦！请刷新查看',MyException::INPUT_ERR);
	}
	
	/**
	 * remove expira order
	 *
	 * @return	null
	 */
	function _removeOrder() {
		$time=time()-900;
		//拼教练过期，一方支付了需要退款
		$expire=$this->db->where(['time <'=>$time,'status <='=>1])->get('`order`')->result_array();
		$this->load->model('notify');
		foreach ($expire as $value) {
			$this->_cancle($value);
// 			$this->db->where('link='.$value['id'].' AND type BETWEEN 400 AND 401')
// 				->update('notify',['type'=>Notify::TEA_SHARE_EXPIRA]);
			$this->notify->send($value,Notify::TEA_SHARE_EXPIRA);
		}
	}
	
	//返回0代表等待回应 1代表已取消 2代表等待审核
	function delOrder($id) {
		$order=$this->db->find('`order`', $id);
		if (!$order) throw new MyException('',MyException::GONE);
		//检查状态，如果已完成就报错
		if ($order['uid']!=UID||$order['status']>Order::PAYED) throw new MyException('订单已取消',MyException::CONFLICT);
		//设定取消原因
		$reason=$this->input->get('reason',TRUE);
		if (!$reason) throw new MyException('',MyException::INPUT_MISS);
		$this->db->where(['`order`.status <'=>SELF::EXPIRE,'tid'=>$order['tid'],'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
			->update('`order`',['reason'=>$reason]);
		
		$this->load->model('notify');
		if ($order['status']!=Order::PAYED){//未支付，要取消直接取消
			$this->_cancle($order);
			if ($order['partner']!=0){
				$partner=$this->db->where(['uid'=>$order['partner'],'`order`.status <'=>SELF::EXPIRE,'tid'=>$order['tid'],'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
					->get('`order`',1)->row_array();
				$this->_cancle($partner);
			}
			return 1;
		}
		if ($order['partner']!=0){//请求对方回应
			$partner=$this->db->where(['uid'=>$order['partner'],'`order`.status <'=>SELF::EXPIRE,'tid'=>$order['tid'],'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
				->select('id,uid')->get('`order`',1)->row_array();
			$this->notify->cancle($partner);
			return 0;
		}else{//判断时间，看是否需要审核
			$order['info']=json_decode($order['info'],TRUE);
			$target=$this->getTime($order['info'][0]);
			$err=$this->db->where(['orderId'=>$id,'status'=>1])->count_all_results('teach_log');//教练确认学车但是学员没确认的
			if ($target<time()||$err>0){//超过时间了或者有争议时段，设置成等待审核
				$again=$this->db->find('delOrderReq',$order['id'],'orderId');
				if ($again) throw new MyException('',MyException::DONE);
				$this->db->where(['orderId'=>$id,'status <'=>2])->update('teach_log',['status'=>5]);
				$this->db->insert('delOrderReq',['orderId'=>$order['id']]);
				if (!$this->db->insert('notify',
						['uid'=>UID,'type'=>Notify::STU_CANCLE_WAIT,'time'=>time(),'msg'=>'你已申请退款，请等待审核','link'=>$order['id']]))
					throw new MyException('',MyException::DATABASE);
				return 2;
			}else{//还没开始，可以取消
				$this->cancle($order);
				//恢复教练的空余时间
				$this->db->where('id',$order['tid'])->step('teacher','freeTime');
				return 1;
			}
		}
	}
	
	//没去过，取消支付成功的订单，需要发短信，确定哪些收手续费
	function cancle($order) {
		$sms=[$this->db->find('user join account on account.id=user.id', $order['uid'],'user.id','tel,name,gender')];
		switch ($order['kind']) {
			case 1:$type='科目二';
			break;
			case 2:$type='科目三';
			break;
			default:$type='陪练陪驾';
			break;
		}
		$sms[0]['data']=['type'=>$type];
		$now=time();
		$cost=0;$toTea=0;$income=0;
		if ($now-$order['time']>900){//15分钟内退款不收手续费
			if (is_array($order['info'])){
				$info=$order['info'];
				$order['info']=json_encode($order['info']);
			}else{
				$info=json_decode($order['info'],TRUE);
			}
			foreach ($info as $item) {
				$time=$this->getTime($item)-$now;
				if ($time<6*3600){
					$cost+=$item['priceTea'];
					$t=round($item['priceTea']*0.5);
					$toTea+=$t;
					$income+=$item['priceTea']-$t;
				}
				else if ($time<24*3600){
					$total=round($item['priceTea']*0.5);//在原价的基础上收手续费
					$cost+=$total;
					$t=round($total*0.15);
					$toTea+=$t;
					$income+=$total-$t;
				}
				else if ($time<48*3600){
					$total=round($item['priceTea']*0.1);
					$cost+=$total;
					$income+=$total;
				}//否则没手续费
			}
		}else $cost=0;
		$realM=0;$virM=0;//记录手续费类型
		
		$this->load->model('notify');
		if ($order['partner']!=0){//处理同伴的
			$partner=$this->db->where(['uid'=>$order['partner'],'tid'=>$order['tid'],'`order`.status <'=>SELF::EXPIRE,'info'=>"CAST('$order[info]' AS JSON)"],NULL,FALSE)
				->get('`order`',1)->row_array();
			$cost=ceil($cost/2);
			if ($partner){
				if ($partner['frozenMoney']+$partner['money']>0){
					if ($cost<=$partner['frozenMoney']){//优先返还可提现币
						$partner['frozenMoney']-=$cost;
						$virM+=$cost;
					}else{
						$rest=$cost-$partner['frozenMoney'];
						$virM+=$partner['frozenMoney'];
						$partner['frozenMoney']=0;
						$partner['money']-=$rest;
						$realM+=$rest;
					}
				}//否则直接第三方付款
				$this->_cancle($partner,$cost);
				$this->db->query("UPDATE `notify` SET type=? WHERE type=? AND (link=$partner[id] OR link=$order[id])",
						[Notify::TEA_SHARE_EXPIRA,Notify::TEA_SHARE_REQ]);
				$user=$this->db->find('user join account on account.id=user.id', $partner['uid'],'user.id','tel,name,gender');
				$user['data']=$sms[0]['data'];
				$sms[]=$user;
			}//否则代表处理过，但是异常中断，不用重复处理了
		}
		if ($order['frozenMoney']+$order['money']>0){
			if ($cost<=$order['frozenMoney']){//优先返还可提现币
				$order['frozenMoney']-=$cost;
				$virM+=$cost;
			}else{
				$rest=$cost-$partner['frozenMoney'];
				$virM+=$order['frozenMoney'];
				$order['frozenMoney']=0;
				$order['money']-=$rest;
				$realM+=$rest;
			}
		}//否则直接第三方付款
		$this->_cancle($order,$cost);
		
		foreach ($sms as $value) {
			$value['data']['name']=$value['name'].(($value['gender']==0)?'先生':'女士');
			$this->notify->sendSms(Notify::SMS_YUE_CANCLE_STU,$value['tel'],$value['data']);
		}
		//处理教练事件
		if ($toTea>0){
			$flag=$this->db->where('id',$order['tid'])->step('teacher', 'money',TRUE,$toTea);
			if (!$flag) return FALSE;
			if ($toTea<=$realM){
				$tRealM=$toTea;
				$tVirM=0;
				$realM-=$toTea;
			}else{
				$tRealM=$realM;
				$tVirM=$toTea-$realM;
				$realM=0;
				$virM-=$tVirM;
			}
			$log=['num'=>$toTea,'time'=>time(),
					'realMoney'=>$tRealM,'virtualMoney'=>$tVirM,
					'content'=>"取消订单，获得手续费${toTea}学车币",'type'=>2];
			$log['uid']=$order['tid'];
			$this->db->insert('money_log',$log);
		}
		$this->notify->send(['uid'=>$order['tid'],'link'=>$order['id']],Notify::CANCLE);
		
		if ($income>0){//记录平台抽成
			$this->db->insert('income',['type'=>1,'num'=>$income,
					'realMoney'=>$realM,'virtualMoney'=>$virM,'tid'=>$order['tid']]);
		}

		$tea=$this->db->find('teacher join account on account.id=teacher.id', $order['tid'],'teacher.id','tel,realname');
		$data=$sms[0]['data'];
		$data['name']=$tea['realname'];
		$this->notify->sendSms(Notify::SMS_YUE_CANCLE_TEA,$tea['tel'],$data);
		return TRUE;
	}
	
	/**
	 * 管理员处理时，方便调用内部函数
	 * @param $order
	 */
	function adminCancle($order,$cost) {
		$this->_cancle($order,$cost);
	}
	
	/**
	 * 取消订单，处理学员资金流
	 * 根据money+frozenMoney判断是学车币支付还是第三方直接支付
	 * 教练资金流由上级处理
	 * @param $order
	 * @throws MyException
	 */
	function _cancle($order,$cost=0) {
		$this->db->where(['orderId'=>$order['id'],'status'=>0])->delete('teach_log');
		if ($order['status']>self::DONE&&$order['status']!=self::ERROR)//订单已经失效了
			return TRUE;
		if ($order['status']==0){
			return $this->db->where('id',$order['id'])->update('`order`',['status'=>SELF::EXPIRE]);
		}else{
			$virM=0;$realM=0;
			$total=$order['money']+$order['frozenMoney'];
			if ($total<=0){//第三方直接支付，原路返回
				$this->load->library('ping');
				$charge=$this->db->where(['orderId'=>$order['id'],'uid'=>$order['uid'],'status'=>1])->get('charge')->row_array();
				if ($charge){
					$total=$charge['amount']-$cost;
					$realM=-$total;//款退回去了，所以需要减掉
					$refund=$this->ping->refund($charge['id'],$charge['uid'],$total);
					$this->db->insert('refund',$refund);
				}//否则就是后台直接修改的数据库，异常数据什么都不做
			}else{//返还学车币
				$virM=$order['frozenMoney'];
				$realM=$order['money'];
				$user=$this->db->find('user', $order['uid'],'id','money,frozenMoney');
				$this->db->trans_start();
				$user['money']+=$order['money'];
				$user['frozenMoney']+=$order['frozenMoney'];
				$this->db->where('id',$order['uid'])->update('user',$user);
			}

			$data=['money'=>0,'frozenMoney'=>0,'status'=>$order['status']<SELF::PAYED?SELF::EXPIRE:SELF::CANCLE];
			$this->db->where('id',$order['id'])->update('`order`',$data);
			$this->db->trans_complete();
			if ($this->db->trans_status() === FALSE)
				throw new MyException('',MyException::DATABASE);
			$this->db->insert('money_log',[
					'realMoney'=>$realM,'virtualMoney'=>$virM,
					'uid'=>$order['uid'],'num'=>$total,
					'content'=>"订单取消，已退款${total}学车币",'time'=>time()]
				);
			$this->load->model('notify');
			$this->notify->send(['uid'=>$order['uid'],'link'=>$order['id']],Notify::CANCLE);
			return TRUE;
		}
	}
	
	//检查是否完成订单
	function finishOrder($log) {
		$res=$this->db->query("SELECT id FROM teach_log WHERE orderId=$log[orderId] AND status!=2 AND status!=6");
		if ($res->num_rows()>0) return FALSE;//学员是否都确认了或者异常已经处理了
		$order=$this->db->find('`order`', $log['orderId']);
		if ($order['status']!=self::PAYED) return FALSE;//已经操作过了
		$where=['tid'=>$order['tid'],'info'=>"CAST('$order[info]' AS JSON)",'`order`.status'=>SELF::PAYED];
		//获取支付情况
		$orders=$this->db->where($where,NULL,FALSE)->get('`order`')->result_array();
		$ids=[];$realM=0;$virM=0;
		foreach ($orders as $value) {
			$ids[]=$value['id'];
			if ($value['money']+$value['frozenMoney']==0) $realM+=$value['realPrice'];
			else{
				$realM+=$value['money'];
			}
		}
		$virM=$order['price']-$realM;
		
		$this->db->trans_begin();
		//设置订单为待评价
		$this->db->where_in('id',$ids)->update('`order`',['status'=>SELF::TO_WRITE_COMMENT]);
		
		$ticheng=round($order['price']*$this->_rate()/100);
		$price=$order['price']-$ticheng;
		if ($virM>=$ticheng){
			$virM-=$ticheng;
			$ticheng=['realMoney'=>0,'virtualMoney'=>$ticheng];
		}else{
			$realM-=$ticheng-$virM;
			$ticheng=['realMoney'=>$ticheng-$virM,'virtualMoney'=>$virM];
			$virM=0;
		}
		$this->db->insert('income',['tid'=>$order['tid'],
				'realMoney'=>$ticheng['realMoney'],'virtualMoney'=>$ticheng['virtualMoney'],
				'num'=>$ticheng['realMoney']+$ticheng['virtualMoney']]);
		$this->db->insert('money_log',[
				'realMoney'=>$realM,'virtualMoney'=>$virM,
				'uid'=>$order['tid'],'num'=>$price,
				'content'=>"教学收入${price}学车币",'time'=>time(),'type'=>1]
			);
		$studyed=[['tid'=>$order['tid'],'uid'=>$order['uid']]];
		if ($order['partner']>0) $studyed[]=['tid'=>$order['tid'],'uid'=>$order['partner']];
		$this->db->insert_batch('studyed',$studyed,FALSE,TRUE);
		$insNum=$this->db->affected_rows();
		$this->db->where('id',$order['tid'])
			->set(['money'=>'money+'.$price,'student'=>'student+'.$insNum],NULL,FALSE)
			->update('teacher');
		
		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
			throw new MyException('',MyException::DATABASE);
		$this->_choucheng($order);
		if ($order['partner']!=0){
			$order['uid']=$order['partner'];
			$this->_choucheng($order);
		}
	}
	
	/**
	 * 处理分销抽成
	 * @param ArrayObject $order
	 */
	function _choucheng($order) {
		$list=[0.4,1.2,1.6,2.4];
		$inviter=$this->db->find('invite', $order['uid'],'toid');
		if (!$inviter) return;
		else $inviter=$inviter['fromid'];
		$num=$this->db->where('id in (SELECT toid FROM invite WHERE fromid='.$order['uid'].') AND kind=0')
			->count_all_results('account');
		if ($num<10) $index=0;
		elseif ($num<30) $index=1;
		elseif ($num<100) $index=2;
		else $index=3;
		$data=['orderId'=>$order['id'],'uid'=>$inviter];
		$amount=$list[$index];
		$data['amount']=$order['partner']==0?$amount:$amount/2;
		$this->db->insert('invite_log',$data);
		$this->db->where('id',$inviter)->step('account', 'inviteMoney',TRUE,$data['amount']);
		$this->db->where('id',$inviter)->step('user', 'money',TRUE,$data['amount']);
		$this->db->where('id',$inviter)->step('teacher', 'money',TRUE,$data['amount']);
		$this->db->insert('money_log',
			['virtualMoney'=>$data['amount'],'uid'=>$inviter,'num'=>$data['amount'],'content'=>"获得提成$data[amount]学车币",'time'=>time()]
		);
	}
	
	function price($price,$partner=0) {
		static $rate=-1;
		if ($rate==-1){
			$act=$this->db->find('activity',6);
			$rate=$act['status']==1?$act['discount']:0;
		}
		return round($price*($partner==0?1:0.6)*(100-$rate)/100);
	}
	
	//确定退款金额
	function refundNum($log) {
		$time=time()-$this->getTime($log);
		if ($time<300) return ['refund'=>0,'rest'=>$log['priceTea']];
		else{
			if ($time>=3600) throw new MyException('订单已过期',MyException::NO_RIGHTS);
			$time=floor($time/self::CLASS_TIME);//晚了多少分钟
			$refund=floor($log['priceTea']*$time/self::CLASS_TIME);
			if ($log['partner']>0){
				$refund=floor($refund/2);
				$rest=$log['priceTea']-$refund*2;
				$cost=$refund*2;
			}else{
				$rest=$log['priceTea']-$refund;
				$cost=$refund;
			}
			return ['refund'=>$refund,
					'rest'=>$rest,
					'time'=>$time,
					'teaCost'=>$cost
			];
		}
	}
	
	function getTime($info) {
		return (int)(strtotime("$info[date]")+60*$info['time']);
	}
	
	function mergeTime($times) {
		$times=array_unique($times);
		$this->load->helper('infoTime');
		$pre=-1;
		$str='';
		foreach ($times as $time) {
			if ($pre==-1){
				$str.=getTime($time);
				$pre=$time;
				continue;
			}
			if ($time==$pre+self::CLASS_TIME){
				$pre=$time;
				continue;
			}
			$str.='-'.getTime($pre)."、".getTime($time);
			$pre=$time;
		}
		$time=end($times);
		$str.='-'.getTime($time+self::CLASS_TIME);
		return $str;
	}
	
	/**
	 * 获取各种比率，教练的抽成比率或者过期的手续费
	 * @param bool $ticheng
	 * @return int
	 */
	function _rate() {
		$rate=file_get_contents(self::PARAM);
		$rate=json_decode($rate,TRUE);
		return $rate['ticheng'];
	}
}
