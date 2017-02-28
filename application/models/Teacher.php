<?php
class Teacher extends CI_Model {
	function teacherInfo($id) {
		$data=$this->db->select('account.id,name,avatar,grade,intro,year,student,teacher.kind,gender,carPic,zjType')
			->join('account', 'account.id=teacher.id')->where('teacher.id',$id)->get('teacher',1)->row_array();
		if (!$data)
			throw new MyException('',MyException::GONE);
		$data['place']=$this->db->select('id,name,pics')
			->where("id IN (SELECT pid FROM tea_place WHERE uid=$id )")
			->get('place')->result_array();
		$pics=[];
		foreach ($data['place'] as &$value) {
			$t=json_decode($value['pics'],TRUE);
			$pics=array_merge($pics,$t);
			unset($value['pics']);
			if (count($pics)>=2){
				$pics=array_slice($pics, 0,2);
				break;
			}
		}
		array_unshift($pics, ['url'=>$data['carPic']]);
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
		$price=$input['price'];
		$input=$input['data'];
		foreach ($input as &$value) {
			if ($value['date']<$time)
				throw new MyException('时间错误',MyException::INPUT_ERR);//非法时间
			if ($value['time']<300)
				throw new MyException('系统更新，请更新到新版本',MyException::INPUT_ERR);
			$value['price']=$price;
		}
		$data=$this->db->find('teacher',UID,'id','orderInfo');
		$data=json_decode($data['orderInfo'],TRUE);
		$res=$input;
		foreach ($data as $origin) {
			foreach ($input as $item) {
				if ($origin['time']==$item['time']&&$origin['date']==$item['date']){//已经添加了
					throw new MyException('有时间段重复，请检查',MyException::CONFLICT);
				}
			}
			$res[]=$origin;
		}
		usort($res,function($a,$b){
			if ($a['date']==$b['date'])
				return $a['time']<$b['time'];
			else return $a['date']<$b['date'];
		});
		return $this->db->where('id',UID)->set('freeTime=freeTime+'.count($input),'',false)->update('teacher',['orderInfo'=>json_encode($res)]);
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
			if ($value['time']==$input['time']&&$value['date']==$input['date']){
				continue;
			}
			$res[]=$value;
		}
		return $this->db->where('id',UID)->set('freeTime=freeTime-1','',false)->update('teacher',['orderInfo'=>json_encode($res)]);
	}
	
	function statistics($input) {
		$this->db->simple_query('set sql_mode=""');
		$endtime=strtotime($input['end'])+86400;
		$begintime=strtotime($input['begin']);
		$this->db->where("UNIX_TIMESTAMP(time) BETWEEN $begintime AND $endtime",NULL,FALSE)
			->group_by('date_format(time,"%Y-%m-%d")')->select('date_format(time,"%m-%d") time,count(*) num,sum(price) price');
		$this->db->where(['tid'=>UID,'status <'=>5,'status >'=>1]);
		$data=$this->db->get('statistics')->result_array();
		return $this->_fillDay($data,$begintime,$endtime);
	}

	function statisticsDay($day) {
		$this->db->simple_query('set sql_mode=""');
		$time=strtotime('-7 day');
		$this->db->where('info->"$[0].date"='.$this->db->escape_str($day),null,false)
			->where(['time >='=>$time,'tid'=>UID,'status <'=>5,'status >'=>1])
			->group_by('info->"$[0].time"',false)->order_by('info->"$[0].time"','desc')->select('info->"$[0].time" time,sum(price) price');
		$data=$this->db->get('`order`')->result_array();
		$res=[];
		$head=current($data);
		for ($i=360; $i < 1440; $i+=40) { 
			if ($head['time']!=$i) $res[]=['time'=>$i,'price'=>0];
			else{
				$res[]=$head;
				$head=next($data)?:['time'=>0];
			}
		}
		return $res;
	}
	
	//没有数据的日期补0
	function _fillDay($data,$begintime,$endtime) {
		$p=current($data);
		$p=$p?$p['time']:'';//不用判断p是否为false了
		$res=[];
		while ($begintime<$endtime) {
			$time=date('m-d',$begintime);
			if ($p==$time){
				$res[]=current($data);
				$p=next($data);
				$p=$p?$p['time']:'';
			}else $res[]=['time'=>$time,'num'=>0,'price'=>0];
			$begintime+=86400;
		}
		return $res;
	}
}