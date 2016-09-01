<?php
/**
 * 处理管理员账号、活动、参数设置等超级权限
 * 
 * @author small
 */
class FenXiaoController extends CI_Controller {
	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
	}
	
	function invite() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['ftel'=>'邀请者手机号','fname'=>'邀请者昵称','fkind'=>'邀请者类型','ttel'=>'被邀请者手机号','tname'=>'被邀请者昵称','tkind'=>'被邀请者类型','time'=>'邀请时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'fxinvite']);
		}else{
			$count=15;
			$this->db->limit($count,$count*$page);
			$this->db->start_cache();
			$this->load->model('back/export','m');
			$data=$this->m->FXInvite($this->input->get());
			$total=ceil($this->db->count_all_results()/$count);
			restful(200,['data'=>$data,'total'=>$total]);
		}
	}
	
	function money() {
		$page=$this->input->get('page');
		if ($page===NULL){
			$head=['ftel'=>'邀请者手机号','fname'=>'邀请者昵称','fkind'=>'邀请者类型','ttel'=>'被邀请者手机号','tname'=>'被邀请者昵称','amount'=>'提成金额','time'=>'提成时间'];
			$this->load->view('back/base',['head'=>$head,'url'=>'FXMoney']);
		}else{
			$count=15;
			$this->db->limit($count,$count*$page);
			$this->db->start_cache();
			$this->load->model('back/export','m');
			$data=$this->m->FXMoney($this->input->get());
			$total=ceil($this->db->count_all_results('invite_log')/$count);
			$sum=$this->db->select('sum(amount) sum')->get('invite_log')->row()->sum;
			restful(200,['data'=>['data'=>$data,'sum'=>$sum?:0],'total'=>$total]);
		}
	}
}
