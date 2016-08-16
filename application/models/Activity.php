<?php
class Activity extends CI_Model {
	const CHARGE=1;//首冲有奖
	const SHARE=2;//首次分享
	const INFO=3;//完善信息
	const GUA=4;
	const ZHUAN=5;
	const LEARN=6;

	function first($type) {
		$user=$this->db->find('user', UID,'id','first,money,frozenMoney');
		$res=$user['first']&pow(2, $type-1);
		if ($res>0) throw new MyException('',MyException::DONE);
		$act=$this->db->find('activity', $type);
		if ($act['status']==0)
			return 0;
		switch ($type) {
			case self::CHARGE:
				$user['first']+=1;
				$money=$this->db->select('amount')->order_by('id','asc')
					->where(['uid'=>UID,'status'=>1])->get('charge',1)
					->row_array();
				if (!$money) return 0;
				else $money=$money['amount'];
				if ($user['money']<$money){//出错了，APP没能第一时间调用此接口
					$money=$user['money'];
					$user['money']=0;
				}else{
					$user['money']-=$money;
				}
				$user['frozenMoney']+=$money;
				$money=floor($money*$act['discount']/100);//money必须是活动送的量
				$user['frozenMoney']+=$money;
			break;
			case self::SHARE:
				$user['first']+=2;
				$money=$act['discount'];
				$user['frozenMoney']+=$money;
				$channel=(int)$this->input->get('channel');
				// $this->share($channel);
			break;
			case self::INFO:
				$user['first']+=4;
				$money=$act['discount'];
				$user['frozenMoney']+=$money;
			break;
			default: throw new MyException('',MyException::INPUT_ERR);
			break;
		}
		$this->db->insert('activity_log',['aid'=>$type,'uid'=>UID,'num'=>$money]);
		$this->db->insert('money_log',['content'=>"参加活动$act[title]获得${money}学车币",'uid'=>UID,'num'=>$money,'time'=>time()]);
		$flag=$this->db->where('id',UID)->update('user',$user);
		return $flag?$money:FALSE;
	}
	
	function share($channel) {
		$have=$this->db->get_where('share_log',['uid'=>UID,'time >'=>date('Y-m-d'),'channel'=>$channel],1)->row_array();
		if ($have) return FALSE;
		$this->db->insert('share_log',['uid'=>UID,'channel'=>$channel]);
		$this->db->query('update user SET zhuan=zhuan+1 AND gua=gua+1 WHERE id='.UID);
		return TRUE;
	}
	
	//网页端，所以需要用session处理
	function activities($data,$isGua=FALSE) {
		$num=rand(1,100);
		$col=$isGua?'gua':'zhuan';
		$user=$this->db->find('user', $_SESSION['id'],'id','frozenMoney,'.$col);
		if ($user[$col]==0)
			throw new MyException('次数用完了，第一次分享可以获得额外次数哦！',MyException::NO_RIGHTS);
		$user[$col]--;
		$now=0;$res=[];$text='参数错误！';
		foreach ($data as $key=>$item) {
			$now+=$item['rate'];
			if ($num<=$now){
				$res=$item;
				$user['frozenMoney']+=$item['coins'];
				break;
			}
		}
		if (empty($res)) return '参数错误！';
		if ($res['coins']>0){
			$this->db->insert('money_log',
					['uid'=>$_SESSION['id'],'num'=>$res['coins'],'content'=>"参加活动获得$res[coins]学车币",'time'=>time()]
					);
			$this->db->insert('activity_log',['uid'=>$_SESSION['id'],'aid'=>$isGua?4:5,'num'=>$res['coins']]);
			$this->db->where('id',$_SESSION['id'])->update('user',$user);
		}else $this->db->step('user', $col,FALSE);
		return $res['text'];
	}
}