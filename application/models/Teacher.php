<?php
class Teacher extends CI_Model {
	function teacherInfo($id) {
		$data=$this->db->select('account.id,name,avatar,grade,intro,year,student,teacher.kind,gender')
			->join('account', 'account.id=teacher.id')->where('teacher.id',$id)->get('teacher',1)->row_array();
		if (!$data)
			throw new MyException(MyException::GONE);
		$data['place']=$this->db->select('id,name,pics')
			->where("id IN (SELECT pid FROM tea_place WHERE uid=$id )")
			->get('place')->result_array();
		$pics=[];
		foreach ($data['place'] as $value) {
			$t=json_decode($value['pics'],TRUE);
			$pics=array_merge($pics,$t);
			if (count($pics)>=3){
				$pics=array_slice($pics, 0,3);
				break;
			}
		}
		$data['pics']=$pics;
		$data['comment']=$this->teaComment($id,1);
		$data['comment']=$data['comment']?$data['comment'][0]:NULL;
		$data['comment']['total']=$this->db->where('tid',$id)->count_all_results('tcomment');
		return $data;
	}
	
	function teaComment($id,$size=20,$offset=NULL) {
		$res=$this->db->select('name,avatar,content,time,pics,hide')	
			->where('tid',$id)->join('account', 'account.id=tcomment.uid')->order_by('tcomment.id','desc')
			->get('tcomment',$size,$offset)->result_array();
		return array_map(function($item){
			if ($item['hide']){
				$item['name']='匿名';
				$item['avatar']=Account::AVATAR;
			}
			$item['pics']=json_decode($item['pics'],TRUE);
			return $item;
		}, $res);
	}
	
	function teaPlace($id) {
		return $this->db->select('id,name,JSON_UNQUOTE(pics->"$[0].url") pics,grade')
			->where("id IN (SELECT pid FROM tea_place WHERE uid=$id )")
			->get('place')->result_array();
	}
	
	function bindPlace($data) {
		$data=json_decode($data,TRUE);
		if (!$data) throw new MyException('',MyException::INPUT_ERR);
		if (count($data)>3) throw new MyException('限制绑定3个场地',MyException::INPUT_ERR);
		$this->db->delete('tea_place',['uid'=>UID]);
		return $this->db->insert_batch('tea_place',array_map(function ($place){
			return ['uid'=>UID,'pid'=>$place];
		}, $data));
	}
	
	//教练添加预约时间
	function addAvailTime($input) {
		$time=date('Y-m-d');
// 		$place=[];//自动绑定
		foreach ($input as $value) {
			if ($value['date']<$time)
				throw new MyException('时间错误',MyException::INPUT_ERR);//非法时间
// 			$place=array_merge($place,$value['place']);
		}
		$data=$this->db->find('teacher',UID,'id','orderInfo');
		$data=json_decode($data['orderInfo'],TRUE);
		$res=$input;
		foreach ($data as $value) {
			if ($value['date']<$time)//删除过期的
				continue;
			foreach ($input as $item) {
				if ($value['time']==$item['time']&&$value['date']==$item['date']){//已经添加了
					throw new MyException('有时间段重复，请检查',MyException::CONFLICT);
				}
			}
			$res[]=$value;
		}
		usort($res,function($a,$b){
			if ($a['date']==$b['date'])
				return $a['time']<$b['time'];
			else return $a['date']<$b['date'];
		});
// 		array_walk($place, function (&$e){
// 			$e=['pid'=>$e,'uid'=>UID];
// 		});
// 		$this->db->insert_batch('tea_place',$place,TRUE,TRUE);
		return $this->db->where('id',UID)->update('teacher',['orderInfo'=>json_encode($res)]);
	}
	
	//教练删除预约时间
	function delAvailTime($input) {
		$time=date('Y-m-d');
		$sql="SELECT id FROM driving.`order` where tid=? AND (status=0 OR status=2) AND JSON_SEARCH(info->'$[*].index','one',?) IS NOT NULL LIMIT 1";
		/*foreach ($input as $value) {
			$num=$this->db->query($sql,$value['date'].$value['time']);
			if ($num->num_rows()!=0)
				throw new MyException('时间段已经有预约了，不可删除',MyException::INPUT_ERR);
		}*/
		$num=$this->db->query($sql,[UID,$input['date'].$input['time']]);
		if ($num->num_rows()!=0)
			throw new MyException('时间段已经有预约了，不可删除',MyException::INPUT_ERR);
		$data=$this->db->find('teacher',UID,'id','orderInfo');
		$data=json_decode($data['orderInfo'],TRUE);
		$res=[];
		foreach ($data as $value) {
			if ($value['date']<$time)//删除过期的
				continue;
			if ($value['time']==$input['time']&&$value['date']==$input['date']){
				continue;
			}
			$res[]=$value;
		}
		return $this->db->where('id',UID)->update('teacher',['orderInfo'=>json_encode($res)]);
	}
	
	//type 1预约量，订单数 2收入，元 3时长，课时。order 1周 2月 3年。 page 前第X个周期的数据
	function statistics($input) {
		switch ($input['order']) {
			case 1:
				$this->db->simple_query('set sql_mode=""');
				$endtime=strtotime('tomorrow')-2592000*$input['page'];//一月的时间
				$begintime=$endtime-2592000;
				$this->db->where("UNIX_TIMESTAMP(time) BETWEEN $begintime AND $endtime",NULL,FALSE)
					->group_by('date_format(time,"%Y-%m-%d")')->select('date_format(time,"%m/%d") time');
			break;
			case 2:
				$this->db->simple_query('set sql_mode=""');
				$this->db->group_by('week(time,3)')->select('UNIX_TIMESTAMP(time) time');
			break;
			case 3:
				$this->db->group_by('date_format(time,"%Y/%m")')->select('date_format(time,"%Y/%m") time');
			break;
			default:
				throw new MyException(MyException::INPUT_ERR);
			break;
		}
		$this->db->where(['tid'=>UID,'status <'=>5,'status >'=>1]);
		switch ($input['type']) {
			case 1:$this->db->select('count(*) num');
			break;
			case 2:$this->db->select('sum(price) num');
			break;
			case 3:$this->db->select('sum(JSON_LENGTH(info)) num');
			break;
			default:
				throw new MyException(MyException::INPUT_ERR);
			break;
		}
		$data=$this->db->get('statistics')->result_array();
		switch ($input['order']) {
			case 1:return $this->_fillDay($data,$begintime,$endtime);
			break;
			case 2:
				return $this->_fillWeek($data);
			break;
			case 3:
				return $this->_fillMonth($data);
			break;
		}
	}
	
	//没有数据的日期补0
	function _fillDay($data,$begintime,$endtime) {
		$p=current($data);
		$p=$p?$p['time']:'';//不用判断p是否为false了
		$res=[];
		while ($begintime<$endtime) {
			$time=date('m/d',$begintime);
			if ($p==$time){
				$res[]=current($data);
				$p=next($data);
				$p=$p?$p['time']:'';
			}else $res[]=['time'=>$time,'num'=>0];
			$begintime+=86400;
		}
		return $res;
	}
	
	//没有数据的周补0
	function _fillWeek($data) {
		$endtime=strtotime('+0 Week Monday');
		$begintime=strtotime('-14 Week Monday');
		$data=$data?:['time'=>$endtime+1];
		$p=current($data);
		$prebegin=$begintime;
		$res=[];
		while ($begintime<$endtime) {
			$begintime+=604800;
			$res[]=['time'=>date('m/d',$prebegin).'至'.date('m/d',$begintime),
					'num'=>$p['time']<=$begintime?$p['num']:0];//在区间内，设值，否则是0
			if ($p['time']<=$begintime)
				$p=next($data);
			$p=$p?:['time'=>$endtime+1];
			$prebegin=$begintime;
		}
		return $res;
	}
	
	//没有数据的月份补0
	function _fillMonth($data) {
		$endtime=strtotime('+0 Month');
		$begintime=strtotime('-12 Month');
		$data=$data?:[['time'=>'']];//设为空，不会被匹配到
		$p=current($data);
		$res=[];
		$time=$begintime;
		while ($next=next($data)) {//范围为整个数组
			$time=strtotime('+1 Month',$time);
			while (date('Y/m',$time)!=$next['time']) {
				$res[]=['time'=>date('Y/m',$time),'num'=>0];
				$time=strtotime('+1 Month',$time);
			}
			$res[]=$next;
			$p=$next;
		}
		while ($time<=$endtime) {
			$item=['time'=>date('Y/m',$time)];
			if ($p['time']==$item['time']){
				$item['num']=$p['num'];
				$p=next($data);
			}else $item['num']=0;//在区间内，设值，否则是0
			$res[]=$item;
			$time=strtotime('+1 Month',$time);
		}
		return $res;
	}
}