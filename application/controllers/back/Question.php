<?php
class QuestionController extends CI_Controller {
	const KIND=17;
	const QUESTION=18;

	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}
	
	function cache() {
		ignore_user_abort(TRUE);
		$this->load->model('Question','m');
		$this->m->cache();
		restful();
	}

	function question($id=0){
		if ($id==0){
			$page=$this->input->get('page');
			if ($page===NULL){
				$data=$this->db->get('que_kind')->result_array();
				$this->load->view('back/question',['kind'=>$data]);
			}else{
				$count=15;
				$this->db->start_cache();
				if ($key=$this->input->get('key'))
					$this->db->like('content',$key);
				$data=$this->db->select('question.id,left(content,15) content,que_kind.name kind,question.type')
					->join('que_kind', 'que_kind.id=question.kind')
					->get('question',$count,$page*$count)->result_array();
				$total=$this->db->count_all_results();
				restful(200,['data'=>$data,'total'=>ceil($total/$count)]);
			}
		}else{
			if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
			$data=$this->db->where('question.id',$id)->get('question')->row_array();
			if ($data){
				restful(200,$data);
			}else throw new MyException('',MyException::GONE);
		}
	}
	
	function delQuestion(){
		$id=$this->input->put('id');
		if (!is_array($id)) throw new MyException('',MyException::INPUT_ERR);
		$data=$this->db->select('id,content')->where_in('id',$id)->get('question')->result_array();
		$log=[];
		foreach ($data as $value) {
			$log[]=[
				'uid'=>$_SESSION['admin'],
				'link'=>$value['id'],
				'text'=>"删除题目$value[content]",
				'type'=>self::QUESTION
			];
		}
		if ($this->db->where_in('id',$id)->delete('question')){
			$this->db->insert_batch('oprate_log',$log);
			$this->db->query('UPDATE que_kind SET num=(SELECT count(*) FROM question WHERE question.kind=que_kind.id)');
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function modQuestion($id=0){
		$data=$this->db->create('question',FALSE);
		if ($this->db->where('id',$id)->update('question',$data)){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"修改题目$data[content]",
				'type'=>self::QUESTION
			]);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function addQuestion(){
		$data=$this->db->create('question');
		if ($this->db->insert('question',$data)){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$this->db->insert_id(),
				'text'=>"添加题目$data[content]",
				'type'=>self::QUESTION
			]);
			$this->db->where('id',$data['kind'])->step('que_kind', 'num');
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}

	function kind(){
		$data=$this->db->select('que_kind.*')->order_by('type','asc')->get('que_kind')->result_array();
		$this->load->view('back/queKind',['kind'=>$data]);
	}
	
	function delKind($id=0){
		$data=$this->db->find('que_kind', $id);
		if (!$data) throw new MyException('',MyException::GONE);
		if ($data['num']>0) throw new MyException('此专题下有题目，请删除后再处理专题！',MyException::CONFLICT);
		if ($this->db->where('id',$id)->delete('que_kind')){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"删除专题$data[name]",
				'type'=>self::KIND
			]);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function modKind($id=0){
		$data=$this->db->create('que_kind',FALSE);
		if ($this->db->where('id',$id)->update('que_kind',$data)){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$id,
				'text'=>"修改专题$data[name]",
				'type'=>self::KIND
			]);
			restful();
		}else throw new MyException('',MyException::DATABASE);
	}
	
	function addKind(){
		$data=$this->db->create('que_kind');
		if ($this->db->insert('que_kind',$data)){
			$this->db->insert('oprate_log',[
				'uid'=>$_SESSION['admin'],
				'link'=>$this->db->insert_id(),
				'text'=>"添加专题$data[name]",
				'type'=>self::KIND
			]);
			restful(201);
		}else throw new MyException('',MyException::DATABASE);
	}
}