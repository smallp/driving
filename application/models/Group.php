<?php
class Group extends CI_Model {
	function item($id) {
		if ($data=$this->db->find('`group`', $id)){
			$mem=$this->db->select('uid')->where('gid',$id)->get('group_mem')->result_array();
			$mem=array_map(function($item){
				return $item['uid'];
			},$mem);
			$data['member']=$this->db->select('id,name,avatar,kind')
				->where_in('id',array_values($mem))->get('account')->result_array();
			return $data;
		}else throw new MyException('',MyException::GONE);
	}
	
	function addMember($id,$mem) {
		$g=$this->db->find('`group`', $id);
		if (!$g) throw new MyException('',MyException::GONE);
        $num=$this->db->where('gid',$id)->count_all_results('group_mem');
        if ($num+count($mem)>$g['limit'])
            throw new MyException('群的人数达到了上限！',MyException::NO_RIGHTS);
        
        foreach ($mem as $item) {
            $data[]=['gid'=>$id,'uid'=>$item];
        }
        $this->db->insert_batch('group_mem',$data);
        $this->notify($mem,['name'=>$g['name'],'id'=>$id]);
        $mem=$this->db->where_in('id',$mem)->select('id,kind')->get('account')->result_array();
        foreach ($mem as &$item) {
            $item= ($item['kind']==0?'stu':'tea').$item['id'];
        }

		$res=$this->rong->addToGrp($id,$g['name'],$mem);
		if ($res===TRUE){
            
			array_walk($mem,function (&$e,$key,$id){
				$e=['uid'=>$e,'gid'=>$id];
			}, $id);
			reset($mem);
			return $this->db->insert_batch('group_mem',$mem,TRUE,TRUE);
		}else{
			error_log('rong error:'.$res);
			throw new MyException('',MyException::THIRD);
		}
	}

	function add($input) {
		$mem=array_map(function($item){
			return $item['id'];
		},$input);
		//取消从已有群创建的功能
// 		foreach ($input['grp'] as $item) {
// 			$grp=$this->db->where('gid',$item)->get('group_mem')->result_array();
// 			foreach ($grp as $uid) {
// 				$mem[]=$uid['uid'];
// 			}
// 		}
		$mem[]=UID;
		$mem=array_unique($mem);
		
		$name=$this->db->find('account', UID,'id','name')['name'];
        $name=$name.'创建的群';
		$data=['name'=>$name,'owner'=>UID,'notify'=>''];
		if ($this->db->insert('group',$data)) {
			$data=[];
			$id=$this->db->insert_id();
			foreach ($mem as $item) {
				$data[]=['gid'=>$id,'uid'=>$item];
            }
            $this->notify($mem,['name'=>$name,'id'=>$id]);
			$this->db->insert_batch('group_mem',$data);
			$mem=$this->db->where_in('id',$mem)->select('id,kind')->get('account')->result_array();
			foreach ($mem as &$item) {
				$item= ($item['kind']==0?'stu':'tea').$item['id'];
			}
			if (($err=$this->rong->createGrp($id,$name,$mem))===TRUE){
				return $id;
			}else{
				$this->db->delete('group',['id'=>$id]);
				$this->db->delete('group_mem',['gid'=>$id]);
				error_log('rong error:'.$err);
				throw new MyException('系统出错',MyException::THIRD);
			}
		}else throw new MyException('',MyException::DATABASE);
	}
    
    function notify($mem,$info){
        $this->load->model('notify');
        foreach($mem as $item){
        	if ($item!=UID)
            $this->notify->send(['uid'=>$item,'name'=>$info['name'],'id'=>$info['id']],Notify::GROUP);
        }
    }
}
