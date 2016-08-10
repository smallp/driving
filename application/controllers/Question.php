<?php
class QuestionController extends CI_Controller {
	function __construct() {
		parent::__construct ();
		// $this->load->model('Question','m');
	}
	function cache() {
		$this->load->model ( 'Question', 'm' );
		$this->m->cache ();
		restful ();
	}
	function addResult() {
		$input = $this->input->post ( [ 
				'cost',
				'grade',
				'type' 
		] );
		if (! $input)
			throw new MyException ( '', MyException::INPUT_MISS );
		$this->load->model ( 'account' );
		if ($this->account->check () == 0)
			$input ['uid'] = UID;
		else
			$input ['uid'] = 0;
		$input ['time'] = time ();
		if ($this->db->insert ( 'grade', $input ))
			restful ( 201 );
		else
			throw new MyException ( '', MyException::DATABASE );
	}
	function win() {
		$grade = ( int ) $this->input->get ( 'grade' );
		$total = $this->db->count_all ( 'grade' );
		$win = $this->db->where ( 'grade <=', $grade )->count_all_results ( 'grade' );
		restful ( 200, [ 
				'rate' => floor ( $win / $total * 100 ) 
		] );
	}
	
	/*
	 * function question($id=0){
	 * if ($id==0){//随机做题
	 * $id=($this->input->get('type'))?
	 * rand(1,Question::KEMU1):rand(Question::KEMU1,Question::KEMU2);
	 * }//else 顺序做题
	 * if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
	 * restful(200,$this->m->item($id));
	 * }
	 *
	 * function zhuanti($kind=0) {
	 * if ($kind==0||!is_numeric($kind))
	 * throw new MyException('',MyException::INPUT_ERR);
	 * $id=(int)$this->input->get('id');
	 * restful(200,$this->m->zhuanti($kind,$id));
	 * }
	 *
	 * function test() {
	 * $type=$this->input->get('type');
	 * restful(200,$this->m->test($type));
	 * }
	 *
	 * function total($kind=0) {
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * $data=$this->db->query("SELECT count(*) wrong FROM question WHERE kind=? AND id IN (SELECT qid FROM que_user WHERE relation=1 AND uid=?)",
	 * [(int)$kind==0,UID])->row_array();
	 * $data['total']=$kind==0?Question::KEMU1:Question::KEMU2;
	 * restful(200,$data);
	 * }
	 *
	 * function addTestResult() {
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * $data=$this->db->create('test_result');
	 * $data['uid']=UID;
	 * if ($this->db->insert('test_result',$data)) restful(201);
	 * else throw new MyException('',MyException::DATABASE);
	 * }
	 *
	 * function testResult($id=0){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * if ($id==0){
	 * $type=(int)$this->input->get('type');
	 * $data=$this->db->select('id,time,grade')
	 * ->where(['type'=>$type,'uid'=>UID])->get('test_result')->result_array();
	 * restful(200,$data);
	 * }else{
	 * if (!is_numeric($id)) throw new MyException('',MyException::INPUT_ERR);
	 * if ($data=$this->db->find('testresult', $id)){
	 * restful(200,$data);
	 * }else throw new MyException('',MyException::GONE);
	 * }
	 * }
	 *
	 * function Wrong($kid=''){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * $type=$this->input->get('type');
	 * if (!$type)
	 * throw new MyException('',MyException::INPUT_MISS);
	 * $this->db->where(['type'=>$type]);
	 * if (is_numeric($kid)){
	 * $id=(int)$this->input->get('id');
	 * if (!$id) throw new MyException('',MyException::INPUT_ERR);
	 * $data=$this->m->wrongItem(['kid'=>$kid,'id'=>$id,'relation'=>1]);
	 * }else {
	 * $data=$this->m->wrongList(1);
	 * }
	 * restful(200,$data);
	 * }
	 *
	 * function delWrong($id=0){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * if ($this->db->where(['uid'=>UID,'pid'=>$id,'relation'=>1])->delete('que_user')) restful();
	 * else throw new MyException('',MyException::DATABASE);
	 * }
	 *
	 * function addWrong(){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * $id=$this->input->post('id');
	 * if (!$id||!($id=json_decode($id,TRUE)))
	 * throw new MyException('',MyException::INPUT_ERR);
	 * if ($this->db->insert_batch('que_user',array_map(function($item){
	 * return ['qid'=>(int)$item,'uid'=>UID,'relation'=>1];
	 * }, $id),TRUE)) restful(201);
	 * else throw new MyException('',MyException::DATABASE);
	 * }
	 *
	 * function Collect($kid=''){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * $type=(int)$this->input->get('type');
	 * $this->db->where(['question.type'=>$type]);
	 * if (is_numeric($kid)){
	 * $id=(int)$this->input->get('id');
	 * if (!$id) throw new MyException('',MyException::INPUT_ERR);
	 * $data=$this->m->wrongItem(['kid'=>$kid,'id'=>$id,'relation'=>0]);
	 * }else {
	 * $data=$this->m->wrongList(0);
	 * }
	 * restful(200,$data);
	 * }
	 *
	 * function delCollect($id=0){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * show_404();
	 * if ($this->db->where(['uid'=>UID,'pid'=>$id,'relation'=>0])->delete('que_user')) restful();
	 * else throw new MyException('',MyException::DATABASE);
	 * }
	 *
	 * function addCollect(){
	 * $this->load->model('account');
	 * if ($this->account->check()!=0)
	 * throw new MyException('',MyException::AUTH);
	 * $id=(int)$this->input->post('id');
	 * if ($this->db->insert('que_user',['qid'=>$id,'uid'=>UID])) restful(201);
	 * else throw new MyException('',MyException::DATABASE);
	 * }
	 */
}
