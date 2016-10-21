<?php
class Seeds extends CI_Model {
	/*
	 * 获取评论，以及删除评论后清理数据
	 */
	function getComment($id,$limit=NULL) {
		$comment=$this->db->order_by('scomment.id','asc')->select('scomment.*,account.name,account.avatar,account.kind,f.name fname')
			->join('account', 'account.id=scomment.uid')->join('account f', 'f.id=scomment.fid','left')
			->where('sid',$id)->get('scomment',$limit)->result_array();//先发表的放前面
		return $comment;
	}
	
	function getSeeds($limit=NULL,$offset=NULL) {
		$data=$this->db->select('seeds.*,name,avatar,account.kind')->join('account','account.id=seeds.uid')->order_by('seeds.id','desc')
			->where("(uid in (SELECT toid FROM attention WHERE fromid=".UID.") OR uid=".UID.")")
			->get('seeds',$limit,$offset)->result_array();
		foreach ($data as &$value) {
			$value['pics']=json_decode(gzuncompress($value['pics']),TRUE);
			$value['comment']=$value['commentCount']==0?[]:$this->getComment($value['id'],$offset==NULL?NULL:10);
			$value['praised']=$this->db->where(['sid'=>$value['id'],'uid'=>UID])->get('praise')->num_rows()==1;
			$value['praiseMem']=$this->db->select('id,avatar,kind')
				->where("id in (SELECT uid FROM praise WHERE sid=$value[id])")
				->get('account',8)->result_array();
		}
		return $data;
	}
	
	function comment($input) {
		$this->load->model('notify');
		if ($input['fid']!=0){
			if (!$this->db->find('account', $input['fid'],'id','id'))
				throw new MyException('此人不存在',MyException::GONE);
			$autId=$input['fid'];
			$type=Notify::REPLY;
		}else {
			$autId=$this->db->find('seeds',$input['sid'],'id','uid')['uid'];
			$type=Notify::COMMENT;
		}
		$input['uid']=UID;
		$input['time']=time();
		if ($this->db->insert('scomment',$input)){
			$this->db->where('id',$input['sid'])->step('seeds', 'commentCount');
			if (UID!=$autId)
				$this->notify->send(['uid'=>$autId,'sid'=>$input['sid']],$type);
			return TRUE;
		}else return FALSE;
	}
	
	function delComment($id) {
		$data=$this->db->find('scomment', $id,'id','uid,sid');
		if (!$data||$data['uid']!=UID) throw new MyException('',MyException::NO_RIGHTS);
		if (!$this->db->where('id',$id)->delete('scomment')) throw new MyException('',MyException::DATABASE);
		$this->db->step('seeds', 'commentCount',FALSE,1);
		return TRUE;
	}
	
	function praise($id,$add=TRUE) {
		$have=$this->db->where(['sid'=>$id,'uid'=>UID])->get('praise')->num_rows()==1;
		if ($have==$add)
			throw new MyException('',MyException::CONFLICT);
		$this->db->where('id',$id)->step('seeds','praise',$add);
		if ($this->db->affected_rows()==0) throw new MyException('',MyException::GONE);
		
		if ($add){
			$method='insert';
			$autId=$this->db->find('seeds', $id,'id','uid')['uid'];
			if (UID!=$autId){//给自己的不推送
				$this->load->model('notify');
				$this->notify->send(['uid'=>$autId,'sid'=>$id],Notify::PRAISE);
			}
		}else $method='delete';
		if ($this->db->$method('praise',['sid'=>$id,'uid'=>UID])){
			return TRUE;
		}else{
			$this->db->where('id',$id)->step('seeds','praise',!$add);
			return FALSE;
		}
	}
}
