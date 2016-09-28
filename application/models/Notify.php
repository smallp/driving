<?php
class Notify extends CI_Model {
	const NOTIFY=1;
	const AUTH_PASS=2;
	const TX_PASS=3;//提现成功
	const TX_FAIL=4;
	const TIXIAN=5;
	// const MONEY_REFUSE=6;
	const FROZEN=7;
	const UNFROZEN=8;
	const TEA_GRADE_UP=9;//教练等级
	const TEA_GRADE_DOWN=10;
	
	const FRI_REQUEST=100;
	const FRI_AC=101;
	const FRI_REFUSE=102;
	const COMMENT=201;
	const REPLY=202;
	const PRAISE=203;
	const TEA_SHARE_REQ=400;
	const TEA_SHARE_AC=401;
	const TEA_SHARE_REFUSE=402;
	const TEA_SHARE_EXPIRA=403;
	const TEA_SHARE_INFO=404;
	const STU_CANCLE_REQ=500;
	const STU_CANCLE_AC=501;
	const STU_CANCLE_REFUSE=502;
	const STU_CANCLE_WAIT=503;
	const STU_CANCLE_FAIL=504;
	const STU_CANCLE_DONE=505;
	const YUE_SUCCESS=601;
	const YUE_SUCCESS_TEA=602;
	const CERTAIN=603;
	const CERTAIN_STU=606;//自动或学员确认结束教学
	const CANCLE=604;
	const EXPIRE=605;
    const AUTH_FAIL=610;
    const GROUP=611;
    const ORDER_COMMENT=700;
	
	const SMS_AUTH_STU=1370669;
	const SMS_AUTH_TEA=1370677;
	const SMS_TOMORROW_TEA=1370687;//预约前一天提醒
	const SMS_YUE_TEA=1370695;//预约成功，教练通知
	const SMS_YUE_STU=1370671;//预约成功和前一天对学员提醒
	const SMS_YUE_CANCLE_STU=1386293;
	const SMS_YUE_CANCLE_TEA=1386301;
	const SMS_YUE_NOTIFY=1531216;
	
	function attend($id) {
		if ($id==UID) throw new MyException('',MyException::INPUT_ERR);
		$res=$this->db->find('account', $id);
		if (!$res) throw new MyException('',MyException::GONE);
// 		$res=$this->db->query("SELECT count(*) num FROM friendReq WHERE type=? AND time>? AND (uid=? AND link=$id)",[self::FRI_REFUSE,strtotime('today'),UID])
// 			->row()->num;
// 		if ($res>=3)
// 			throw new MyException('请求数过多，添加同一好友次数受限',MyException::NO_RIGHTS);
		$res=$this->db->where(['toid'=>UID,'fromid'=>$id])->count_all_results('attention');
		if ($res) throw new MyException('你们已经是好友了',MyException::CONFLICT);
		$res=$this->db->query("SELECT uid,link FROM friendReq WHERE type=100 AND ((uid=? AND link=$id) OR (uid=$id AND link=?))",[UID,UID])
			->row_array();
		if ($res){
			if ($res['uid']==UID){//对方已申请好友
				return $this->send($id, self::FRI_AC);
			}else throw new MyException('你已经发出请求了',MyException::CONFLICT);
		}
		return $this->send($id, self::FRI_REQUEST);
	}
	
	function attendRes($input) {
		$info=$this->db->find('friendReq',$input['id']);
		if ($info['uid']!=UID)
			throw new MyException('',MyException::NO_RIGHTS);
		if ($info['type']!=self::FRI_REQUEST)
			throw new MyException('',MyException::NO_RIGHTS);
		return $this->send($info['link'], $input['status']?self::FRI_AC:self::FRI_REFUSE);
	}
	
	function cancle($data) {
		$res=$this->db->query("SELECT uid FROM notify WHERE type=".self::STU_CANCLE_REQ." AND link=$data[id]")
			->row_array();
		if ($res){
			throw new MyException('已经有取消订单请求了哦！',MyException::CONFLICT);
		}
		return $this->send($data, self::STU_CANCLE_REQ);
	}
	
	function cancleRes($data,$status) {
		return $this->send($data,$status?self::STU_CANCLE_AC:self::STU_CANCLE_REFUSE);
	}
	
	function auth_pass($user) {
		if ($user['kind']){
			$info=$this->db->find('teacher', $user['id'],'id','gender,realname');
		}else $info=$this->db->find('account join user where user.id=account.id', $user['id'],'account.id','gender,account.name realname');
		$data=['name'=>$info['realname'].(($info['gender']==0)?'先生':'女士')];
		$this->send($user['id'], SELF::AUTH_PASS);
		$this->sendSms($user['kind']?SELF::SMS_AUTH_TEA:SELF::SMS_AUTH_STU,
				$user['tel'], $data);
		return TRUE;
	}

	function send($id,$type) {
		if (defined('UID'))
			$name=$this->db->find('account', UID,'id','name')['name'];
		switch ($type) {
			case self::COMMENT:
				$text="${name}在驾友圈评论了你";
				$flag=$this->db->insert('notify',['link'=>$id['sid'],'uid'=>$id['uid'],'type'=>$type,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::REPLY:
				$text="${name}在驾友圈回复了你";
				$flag=$this->db->insert('notify',['link'=>$id['sid'],'uid'=>$id['uid'],'type'=>$type,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::PRAISE:
				$text="${name}在驾友圈给你点赞了";
				$flag=$this->db->insert('notify',['link'=>$id['sid'],'uid'=>$id['uid'],'type'=>$type,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::FRI_REQUEST:
				$text="${name}申请添加好友";
				$myInfo=$this->db->find('account',UID,'id','kind,avatar,name');
				$flag=$this->db->insert('friendReq',['link'=>UID,'uid'=>$id,'type'=>$type,'msg'=>$text,'time'=>time(),'extra'=>json_encode($myInfo)]);
				
// 				$this->load->library('rong');
// 				$tarInfo=$this->db->find('account',$id,'id','kind,id');
// 				$this->rong->newFriend(UID,$tarInfo,$text);
// 				return TRUE;
			break;
			case self::FRI_AC:
				$text="${name}通过了你的好友申请";
				$myInfo=$this->db->find('account',UID,'id','id,kind,avatar,name');
				$flag=$this->db->where(['type'=>self::FRI_REQUEST,'uid'=>UID,'link'=>$id])->update('friendReq',['type'=>self::FRI_AC]);
				$this->db->insert('friendReq',['link'=>UID,'uid'=>$id,'type'=>$type,'msg'=>$text,'time'=>time(),'extra'=>json_encode($myInfo)]);
				$flag&&$this->db->insert_batch('attention',[['fromid'=>UID,'toid'=>$id],['fromid'=>$id,'toid'=>UID]]);
				
// 				$this->load->library('rong');
// 				$tarInfo=$this->db->find('account',$id,'id','kind,id');
// 				$this->rong->info($myInfo,$tarInfo);
// 				$this->rong->info($tarInfo,$myInfo);
// 				return TRUE;
			break;
			case self::FRI_REFUSE:
				$text="${name}拒绝了你的好友申请";
				$myInfo=$this->db->find('account',UID,'id','id,kind,avatar');
				$flag=$this->db->where(['type'=>self::FRI_REQUEST,'uid'=>UID,'link'=>$id])->update('friendReq',['type'=>self::FRI_REFUSE]);
				$this->db->insert('friendReq',['link'=>UID,'uid'=>$id,'type'=>$type,'msg'=>$text,'extra'=>json_encode($myInfo),'time'=>time()]);
				
// 				$this->load->library('rong');
// 				$tarInfo=$this->db->find('account',$id,'id','kind,id');
// 				$this->rong->newFriend(UID,$tarInfo,$text);
// 				$flag=TRUE;
// 				return TRUE;
			break;
			//拼教练时，link为被邀请方的订单id
			case self::TEA_SHARE_REQ:
				$text="${name}请求拼教练";
				$flag=$this->db->insert('notify',['link'=>$id['link'],'uid'=>$id['uid'],'type'=>$type,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::TEA_SHARE_REFUSE:
				$text="${name}拒绝了你的拼教练申请";
				$flag=$this->db->where(['type'=>self::TEA_SHARE_REQ,'uid'=>UID,'link'=>$id['link']])->update('notify',['type'=>self::TEA_SHARE_REFUSE]);
				$id=$id['uid'];//修改给自己的消息，给对方推送
			break;
			case self::TEA_SHARE_EXPIRA:
				$text="您有订单已过期";
				$flag=$this->db->insert('notify',['link'=>$id['id'],'uid'=>$id['uid'],'type'=>$type,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];//修改给自己的消息，给对方推送
			break;
			//id包括同伴的id uid，同伴的订单id patlink，自己的订单id link
			case self::TEA_SHARE_AC:
				$text="${name}同意了你的拼教练申请，请赶快支付";
				$this->db->where(['type'=>self::TEA_SHARE_REQ,'uid'=>UID,'link'=>$id['link']])->update('notify',['type'=>$type]);
				$flag=$this->db->insert('notify',['link'=>$id['patlink'],'uid'=>$id['uid'],'type'=>self::TEA_SHARE_INFO,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			//取消订单时，$id是接受人的订单信息
			case self::STU_CANCLE_REQ:
				$text="${name}请求取消订单";
				$flag=$this->db->insert('notify',['link'=>$id['id'],'uid'=>$id['uid'],'type'=>self::STU_CANCLE_REQ,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::STU_CANCLE_REFUSE:
				$text="${name}拒绝了你的取消订单申请";
				$flag=$this->db->where(['type'=>self::STU_CANCLE_REQ,'uid'=>UID,'link'=>$id['id']])->update('notify',['type'=>self::STU_CANCLE_REFUSE]);
				$this->db->insert('notify',['link'=>$id['pOrderId'],'uid'=>$id['partner'],'type'=>$type,'msg'=>$text,'time'=>time()]);
				$id=$id['partner'];//修改给自己的消息，给对方推送
			break;
			case self::STU_CANCLE_AC:
				$text="${name}同意了你的取消订单申请，审核后订单即会取消";
				$flag=$this->db->where(['type'=>self::STU_CANCLE_REQ,'uid'=>UID,'link'=>$id['id']])->update('notify',['type'=>self::STU_CANCLE_WAIT]);
				$this->db->insert('notify',['link'=>$id['pOrderId'],'uid'=>$id['partner'],'type'=>self::STU_CANCLE_WAIT,'msg'=>$text,'time'=>time()]);
				$id=$id['partner'];//修改给自己的消息，给对方推送
			break;
			case self::TIXIAN:
				$text="您已提交提现申请，请等待审核！";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id,'link'=>0,'msg'=>$text,'time'=>time()]);
			break;
			case self::TX_PASS:
				$text="你的提现申请已通过！";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id,'link'=>0,'msg'=>$text,'time'=>time()]);
			break;
			case self::TX_FAIL:
				$text="$id[text]，你的提现申请未通过";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>0,'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::AUTH_PASS:
				$text="您的身份验证已通过";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id,'link'=>0,'msg'=>$text,'time'=>time()]);
			break;
			case self::AUTH_FAIL:
				$text="您的身份验证失败，请重新提交";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id,'link'=>0,'msg'=>$text,'time'=>time()]);
			break;
			case self::FROZEN:
				$text="您的账户已被冻结，如有疑问请咨询客服";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id,'link'=>0,'msg'=>$text,'time'=>time()]);
			break;
			case self::UNFROZEN:
				$text="您的账户已解冻";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id,'link'=>0,'msg'=>$text,'time'=>time()]);
			break;
			case self::YUE_SUCCESS:
				$text="您已成功预约教练，请按时到场学习";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['link'],'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::CANCLE:
				$text="您有订单被取消，请查看确认";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['link'],'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::EXPIRE:
				$text="您有订单已过期，请查看确认";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['id'],'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
			break;
			case self::YUE_SUCCESS_TEA:
				$text="您有新的预约信息，请按时教学";
				$flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['link'],'msg'=>$text,'time'=>time()]);
				$id=$id['uid'];
                break;
            case self::GROUP:
                $text="${name}拉您加入群$id[name]";
                $flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['id'],'msg'=>$text,'time'=>time()]);
                $id=$id['uid'];
                break;
            case self::CERTAIN:
                $text="${name}教练已开始教学";
                $flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['link'],'msg'=>$text,'time'=>time()]);
                $id=$id['uid'];
                break;
            case self::CERTAIN_STU:
				$text=$id['text'];
                $flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['link'],'msg'=>$text,'time'=>time()]);
                $id=$id['uid'];
                break;
            case self::ORDER_COMMENT:
                $text="你有新的教学评价";
                $flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['uid'],'link'=>$id['link'],'msg'=>$text,'time'=>time()]);
                $id=$id['uid'];
                break;
            case self::TEA_GRADE_UP:
                $text="恭喜您成功升为$id[grade]星教练";
                $flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['id'],'link'=>0,'msg'=>$text,'time'=>time()]);
                $id=$id['id'];
                break;
            case self::TEA_GRADE_DOWN:
                $text="很遗憾，由于您的教学时间不够，您被调整为$id[grade]星教练，请再接再厉";
                $flag=$this->db->insert('notify',['type'=>$type,'uid'=>$id['id'],'link'=>0,'msg'=>$text,'time'=>time()]);
                return TRUE;
			
			default:
				throw new MyException('',MyException::INPUT_ERR);
			break;
		}
		if (!$flag) return FALSE;
		$user=$this->db->find('account',$id,'id','id,kind,type,push');
		if (!$user) throw new MyException('此用户不存在！',MyException::GONE);
		$push=(int)$type/100;
		if ($push==2){
			if ($user['push']%2==0) return TRUE;//驾友圈，检查第三位是不是1
		}else if ($user['push']/4<1) return TRUE;//系统推送，检查第一位是不是1
		$this->load->library('rong');
		$this->rong->push($user,['text'=>$text,'type'=>$type]);
		return TRUE;
	}
	
	/**
	 * send sms to target
	 */
	function sendSms($tplId,$phone,$data=[],$time=1) {
		$arr=[];
		foreach ($data as $key=>$value) {
			$arr[]="#$key#=$value";
		}
		$arr=join('&', $arr);
		log_message('debug', "$arr tpl:$tplId tel:$phone");
		$arr=urlencode($arr);
		$com='nohup python3 /var/var/lib/sms.py -t '.$time." -v '$arr' --tpl $tplId $phone &";
		pclose(popen($com, 'r'));
	}
}