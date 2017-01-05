<?php
class TeacherController extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('account');
		if ($this->account->check()!=1)
			throw new MyException('',MyException::AUTH);
	}
	
	function info(){
		$data=$this->db->select('account.id,invite,status,checkInfo,name,avatar,push,secret,gender,bg,token,rongToken,teacher.*,teacher.school schoolId,(SELECT name FROM school WHERE school.id=teacher.school) school')
			->join('account', 'account.id=teacher.id')->where('teacher.id',UID)->get('teacher',1)->row_array();
		$this->account->active();
		$place=$this->db->select('id,name')->where('id in (SELECT pid FROM tea_place WHERE uid='.UID.')')
			->get('place')->result_array();
		$data['place']=$place;
		unset($data['orderInfo']);
		restful(200,$data);
	}
	
	function modInfo() {
		$input=$this->input->put(['name','gender','year','intro','phone','kind','school','zjType'],FALSE,TRUE);
		if ($t=$this->input->put('place')){
			$this->load->model('teacher');
			if (!$this->teacher->bindPlace($t))
				throw new MyException('',MyException::DATABASE);
		}
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		if (isset($input['phone'])&&strlen($input['phone'])!=11)
			throw new MyException('请检查输入的手机号码！',MyException::INPUT_ERR);
		$this->account->modInfo(['name'=>$input['name'],'gender'=>$input['gender']]);
		unset($input['name'],$input['gender']);
		if ($this->db->where('id',UID)->update('teacher',$input)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addInit() {
		$input=$this->input->post(['realname','school','zgz','idA','idB','kind','carId','carPic','jiazhao','zgType','zjType']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$this->db->where('id',UID)->update('account',['status'=>1,'checkInfo'=>'','name'=>$input['realname']]);
		$flag=$this->db->where('id',UID)->update('teacher',$input);
		if ($flag){
			$this->load->model('notify');
			$this->notify->send(UID,Notify::AUTH_PASS);
			restful();
		}
		else throw new MyException('',MyException::DATABASE);
	}
	
	function modSetting() {
		$data=[];
		if (($t=$this->input->put('push'))!==NULL)
			$data['push']=$t;
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		if ($this->db->where('id',UID)->update('teacher',$data)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function level() {
		$data=$this->db->find('teacher', UID,'id','grade');
		$data['time']=strtotime('today');
		$intro=file_get_contents(__DIR__.'/back/level.json');
		$data['intro']=json_decode($intro,TRUE);
		restful(200,$data);
	}
	
	function addOrderSet() {
		$input=$this->input->post(['data','price']);
		if ($input==NULL)
			throw new MyException('',MyException::INPUT_ERR);
		$input['data']=json_decode($input['data'],TRUE);
		if (!$input['data']||!is_numeric($input['price']))
			throw new MyException('',MyException::INPUT_ERR);
		$this->load->model('teacher','m');
		if ($this->m->addAvailTime($input)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function delOrderSet() {
		$input=$this->input->get(['date','time']);
		if (!$input)
			throw new MyException('',MyException::INPUT_ERR);
		$this->load->model('teacher','m');
		if ($this->m->delAvailTime($input)) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function availTime(){
		$this->load->model('order');
		restful(200,$this->order->availTimeTea());
	}
	
	function fans() {
		$res=$this->db->where("id in (SELECT fromid FROM attention_tea WHERE toid=".UID.")",NULL,FALSE)
			->select('id,name,avatar')->get('account')->result_array();
		restful(200,$res);
	}
	
	function statistics() {
		$input=$this->input->get(['page','type','order']);
		if (!$input)
			throw new MyException('',MyException::INPUT_MISS);
		$this->load->model('teacher','m');
		restful(200,$this->m->statistics($input));
	}
	
	function teachLog(){
		$page=$this->input->get('page',FALSE,0);
		$count=10;
		$data=$this->db->select('*,(SELECT name FROM place WHERE place.id=place) place')
			->where(['teach_log.status >'=>1,'tid'=>UID])->order_by('id','desc')
			->get('teach_log',$count,$page*$count)->result_array();
		$data=array_map(function($e){
			$e['avatars']=[];
			$avatars=$this->db->select('avatar')->where_in('id',[$e['uid'],$e['partner']])
				->get('account')->result_array();
			foreach ($avatars as $item) {
				$e['avatars'][]=$item['avatar'];
			}
			unset($e['uid'],$e['partner']);
			return $e;
		}, $data);
		restful(200,$data);
	}
	
	function teachLogId() {
		$input=$this->input->get(['date','time']);
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$input['tid']=UID;
		$log=$this->db->where($input)->get('teach_log',1)->row_array();
		if (!$log) throw new MyException('',MyException::GONE);
		else restful(200,['id'=>$log['id']]);
	}
	
	function modTeachLog($id=0){
		$data=$this->input->put('content');
		if (!$data||!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
		$log=$this->db->find('teach_log', $id);
		if (!$log) throw new MyException('',MyException::GONE);
		if ($log['tid']!=UID) throw new MyException('',MyException::NO_RIGHTS);
		if ($log['content']!='') throw new MyException('你已经写过了',MyException::CONFLICT);
		if ($log['status']==0)
			throw new MyException('开始教学后才能填写！',MyException::NO_RIGHTS);
		$this->load->model('order');
		if ($this->db->where('id',$id)->update('teach_log',['content'=>$data,'status'=>3])){
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function delPlace($id=0){
		if ($this->db->where(['uid'=>UID,'pid'=>$id])->delete('tea_place')) restful();
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addPlace(){
		$data=$this->input->post('data');
		$this->load->model('teacher');
		if ($this->teacher->bindPlace($data)) restful(201);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function recentOrder() {
		$data=$this->db->where('tid='.UID.' AND `order`.status BETWEEN 2 AND 4 AND `order`.time between '.(time()-604800).' AND '.time(),NULL,FALSE)
			->select('uid,partner,info,kind')->get('`order`')->result_array();
		$res=[];//$totalCache=[];
		$this->load->helper('infoTime');
		$this->load->model('back/export');
		while ($value=array_shift($data)) {
			if ($value['partner']!=NULL){//约架，需要查重
				for ($j =0,$lim=count($data); $j < $lim; $j++) {
					if ($data[$j]['info']==$value['info']){//找到同伴的订单了，删掉
						unset($data[$j]);
						break;
					}
				}
				// $uIndex=$value['uid']>$value['partner']?"$value[uid]_$value[partner]":"$value[partner]_$value[uid]";
			}//else $uIndex="$value[uid]_0";
			// if (in_array($uIndex,$totalCache)) $total=$totalCache[$uIndex];
			// else{
			// 	$t=$this->db->query('SELECT sum(price) totalPrice,sum(JSON_LENGTH(info)) totalNum FROM `order` WHERE uid=? AND partner=? AND status BETWEEN 2 AND 4',[$value['uid'],$value['partner']])
			// 		->row_array();
			// 	$total=$t;
			// 	$totalCache[$uIndex]=$t;
			// }
			$info=json_decode($value['info'],TRUE);
			$user=$this->db->select('id,tel,name,avatar')
				->where("id=$value[uid] OR id=$value[partner]")
				->get('account')->result_array();
			foreach ($info as $item) {
				// $item['totalPrice']=$total['totalPrice'];
				// $item['totalNum']=$total['totalNum'];
				$item['user']=$user;
				$item['kind']=$value['kind'];
				$item['time']=getTime($item['time']).'-'.getTime($item['time']+1);
				$item['place']=$this->export->getPlace($item['place']);
				$res[]=$item;
			}
		}
		restful(200,$res);
	}

	function onesOrder(){
		$input=$this->input->get(['uid','partner','page']);
		$count=10;
		if (!$input) throw new MyException('',MyException::INPUT_MISS);
		$orders=$this->db->select('info')->where(['uid'=>$input['uid'],'partner'=>$input['partner'],'tid'=>UID])->between('status',2,4)
			->order_by('id','desc')->get('`order`',$count,$count*$input['page'])->result_array();
		$res=[];
		foreach($orders as $order){
			$res=array_merge($res,json_decode($order['info'],TRUE));
		}
		$this->load->model('back/export');
		$this->load->helper('infoTime');
		foreach($res as &$item){
			$item['time']=getTime($item['time']).'-'.getTime($item['time']+1);
			$item['place']=$this->export->getPlace($item['place']);
		}
		if ($input['page']==0){
			$full=$this->db->query('SELECT sum(price) totalPrice,sum(JSON_LENGTH(info)) totalNum FROM `order` WHERE uid=? AND partner=? AND tid=? AND status BETWEEN 2 AND 4',[$input['uid'],$input['partner'],UID])
				->row_array();
			$full['data']=$res;
		}else $full=['data'=>$res];
		restful(200,$full);
	}
}
