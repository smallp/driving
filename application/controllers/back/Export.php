<?php
class ExportController extends CI_Controller {
	private $limit;
	function __construct() {
		parent::__construct();
		session_start();
		if (!isset($_SESSION['admin'])){
			header('Location:/common/admingo');
			exit();
		}
		$limit=$this->input->get(['begin','end']);
		if (!$limit) die('<h1>请设定好时间！</h1>');
		$this->limit=$limit;
		$this->load->model('back/export','m');
	}
	
	//活动支出
	function activity() {
		$data=$this->db->query('SELECT activity_log.*,activity.title name,account.name user,tel FROM activity_log '.
			'JOIN activity ON activity_log.aid=activity.id'.
			' JOIN account ON activity_log.uid=account.id'.
			' WHERE activity_log.time BETWEEN ? AND ?',[$this->limit['begin'],$this->limit['end']])
			->result_array();
		$head=['name'=>'活动名称','user'=>'参与用户','tel'=>'手机号','num'=>'活动赠币','time'=>'赠送时间'];
		$this->_download($data,$head,'活动支出');
	}

	//充值记录
	function income() {
		$data=$this->m->income($this->limit);
		$head=['tel'=>'手机号','user'=>'用户','kind'=>'用户类型','channel'=>'渠道','amount'=>'充值金额','createTime'=>'充值时间'];
		$this->_download($data,$head,'充值记录');
	}

	//提现记录
	function outcome($kind=NULL) {
		$data=$this->db->query('SELECT tixian.*,account.name user,tel,account.kind FROM tixian '.
			' JOIN account ON tixian.uid=account.id'.($kind===NULL?'':' AND account.kind='.((int)$kind)).
			' WHERE tixian.createTime BETWEEN ? AND ? AND tixian.status=1',[$this->limit['begin'],$this->limit['end'].' 23:59:59'])
			->result_array();
		$head=['tel'=>'手机号','user'=>'用户','kind'=>'用户类型','channel'=>'渠道','target'=>'账号','amount'=>'提现金额','createTime'=>'提现时间'];
		array_walk($data, function(&$item,$key,$info){
			$item['channel']=$info['channel'][$item['channel']-1];
			$item['kind']=$info['kind'][$item['kind']-1];
		},['channel'=>['支付宝','银行卡'],'kind'=>['学员','教练']]);
		$this->_download($data,$head,'提现记录');
	}
	
	//退款记录
	function refund() {
		$data=$this->db->query('SELECT refund.*,charge.channel,account.name user,tel,from_unixtime(refund.dealTime) time FROM refund '.
			' JOIN account ON refund.uid=account.id'.
			' JOIN charge ON refund.chargeId=charge.id'.
			' WHERE refund.dealTime BETWEEN ? AND ? AND refund.status=1',
				[strtotime($this->limit['begin']),strtotime($this->limit['end'])])
			->result_array();
		$head=['tel'=>'手机号','user'=>'用户昵称','channel'=>'渠道','amount'=>'退款金额','time'=>'退款时间'];
		array_walk($data, function(&$item,$key,$info){
			$item['channel']=$info[$item['channel']-1];
		},['支付宝','微信']);
		$this->_download($data,$head,'退款记录');
	}
	
	//消费记录
	function order() {
		$data=$this->m->order($this->limit);
		$head=['tel'=>'学员手机号','user'=>'学员昵称','ttel'=>'教练手机号','tea'=>'教练昵称','school'=>'所属驾校','date'=>'日期','time'=>'时段','place'=>'场地','priceTea'=>'原价','price'=>'实际支付','kind'=>'消费类型','createTime'=>'下单时间'];
		$this->_download($data,$head,'消费记录');
	}
	
	//教练收入
	function teaIncome() {
		$data=$this->db->query('SELECT money_log.num,account.name user,tel,from_unixtime(money_log.time) time,(SELECT name FROM school WHERE school.id=(SELECT school FROM teacher WHERE teacher.id=money_log.uid)) school FROM money_log '.
			' JOIN account ON money_log.uid=account.id'.
			' WHERE money_log.time BETWEEN ? AND ? AND money_log.type>0',
				[strtotime($this->limit['begin']),strtotime($this->limit['end'])])
			->result_array();
		$head=['tel'=>'手机号','user'=>'教练名称','school'=>'所属驾校','num'=>'收入','time'=>'收入时间'];
		$this->_download($data,$head,'教练收入');
	}
	
	//平台资金记录
	function ticheng() {
		$data=$this->m->ticheng($this->limit);
		$head=['tel'=>'手机号','user'=>'用户名','school'=>'所属驾校','num'=>'金额','type'=>'类型','time'=>'处理时间'];
		$this->_download($data,$head,'平台资金记录');
	}
	
	//用户资金明细
	function moneyLog() {
		$data=$this->m->moneyLog($this->limit);
		$head=['tel'=>'手机号','name'=>'用户名','kind'=>'用户类型','content'=>'说明','num'=>'金额','time'=>'时间'];
		$this->_download($data,$head,'用户资金明细');
	}
	
	function _download($data,$head,$filename='') {
		$this->load->library('phpexcel');
		$this->phpexcel->setActiveSheetIndex(0);
		$this->phpexcel->getProperties()->setCreator("Small")
			->setTitle($filename)
			->setSubject("Yiren");
		$sheet=$this->phpexcel->setActiveSheetIndex(0);
		$fields = array_keys($head);
		$col = 0;
		foreach ($fields as $field){
			$sheet->setCellValueByColumnAndRow($col, 1, $head[$field]);
			$col++;
		}
		$row = 2;
		foreach ($data as $value) {
			$col=0;
			foreach ($fields as $key) {
				$sheet->setCellValueByColumnAndRow($col++, $row, $value[$key]);;
			}
			$row++;
		}
		$objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, 'Excel2007');
		$filename.=date('Y-m-d');
		header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$filename.xls\"");
        header('Cache-Control: max-age=0');
		$objWriter->save('php://output');
	}
}
