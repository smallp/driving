<?php
class Question extends CI_Model {
	const KEMU1=10;
	const KEMU2=10;
	
	function cache() {
		$index=[];
		$data=$this->db->get('que_kind')->result_array();
		file_put_contents('data/ques_kind.json', json_encode($data));
		$data=$this->db->get('question')->result_array();
		for ($i = 0,$total=count($data); $i < $total; $i+=200) {
			$t=array_slice($data, $i,200);
			file_put_contents("data/$i.json", json_encode(array_map([$this,'_dealData'], $t)));
			$index[]="$i.json";
		}
		file_put_contents('data/index.json', json_encode($index));
	}
	
	/*function item($id) {
		$data=$this->db->find('question', $id);
		if (!$data) throw new MyException('',MyException::GONE);
		return $this->_dealData($data);
	}
	
	function zhuanti($kind,$id) {
		$res=$this->db->where(['kind',$kind])->order_by('id','asc')->get('question',1,$id)->row_array();
		if ($res)
			return $this->_dealData($res);
		else throw new MyException('',MyException::GONE);
	}
	
	function test($kind) {
		$limit=$kind?
				[1,Question::KEMU1]:[Question::KEMU1,Question::KEMU2];
		$arr=[];
		do{
			for ($i = count($arr); $i < 3; $i++) {
				$arr[]=rand($limit[0],$limit[1]);
			}
			$arr=array_unique($arr);
		}while (count($arr)<3);
		$arr=$this->db->where_in('id',$arr)->get('question')->result_array();
		return array_map([$this,'_dealData'], $arr);
	}
	
	//错题或收藏题目详情
	function wrongItem($input) {
		$where=['uid'=>UID,'relation'=>$input['relation']];
		if ($input['kid']) $where['kid']=$input['kid'];
		$this->db->join('question', 'id=qid')->where($where)
			->select('question.*')->limit(1,$input['id']);
		return $this->_dealData($this->db->get('que_user')->row_array());
	}
	
	//错题或收藏题目列表
	function wrongList($relation) {
		return $this->db->select('que_kind.name,que_kind.id,count(*) num')
			->join('que_kind', 'que_kind.id=kind')->group_by('kind')
			->where("question.id IN (SELECT qid FROM que_user WHERE uid=".UID." AND relation= $relation )")
			->get('question')->result_array();
	}*/
	
	function _dealData($item) {
		$item['option']=json_decode($item['option'],TRUE);
		return $item;
	}
}