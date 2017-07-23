<?php
class CommonController extends CI_Controller {
	const WEATHER=APPPATH.'controllers/back/weather.json';
	const ADDR=[
        ['lng'=>106.576245,'lat'=>29.532257,'name'=>'南岸区'],
        ['lng'=>106.549763,'lat'=>29.403961,'name'=>'巴南区'],
        ['lng'=>106.484708,'lat'=>29.487498,'name'=>'大渡口区'],
        ['lng'=>106.582012,'lat'=>29.564133,'name'=>'渝中区'],
        ['lng'=>106.466454,'lat'=>29.562562,'name'=>'沙坪坝区'],
        ['lng'=>106.22887,'lat'=>29.595229,'name'=>'璧山区'],
        ['lng'=>106.520352,'lat'=>29.513353,'name'=>'九龙坡区'],
        ['lng'=>106.637995,'lat'=>29.723908,'name'=>'渝北区'],
        ['lng'=>106.438068,'lat'=>29.808682,'name'=>'北碚区'],
        ['lng'=>106.555782,'lat'=>29.612689,'name'=>'江北区']
    ];
	function index(){
		echo 'this is driving';
	}

	function qiniu(){
		$this->load->model('account');
		if ($this->account->check()==-1) throw new MyException('',MyException::AUTH);
		$this->load->library('qiniu');
		restful(200,['token'=>$this->qiniu->uploadToken()]);
	}
	
	function addWebhook() {
		$this->load->library('ping');
		$data=$this->ping->webhook();
		switch ($data['type']) {
			case 'charge.succeeded':
				$charge=$this->db->find('charge', $data['data']['id']);
				if ($charge['status']!=0) restful(200);
				$this->db->trans_begin();
				$this->db->where('id',$data['data']['id'])
					->update('charge',['status'=>$data['data']['paid']?1:2,'paytime'=>time()]);
				if ($data['data']['paid']==TRUE){
					if ($charge['orderId']>0){//直接支付
						$this->load->model('order');
						define('UID', $charge['uid']);
						$this->order->pay($charge['orderId']);
					}else if ($charge['orderId']==0){//充值
						$this->db->insert('money_log',
								['uid'=>$charge['uid'],'content'=>"您已成功充值$charge[amount]元",'time'=>time(),'num'=>$charge['amount'],'realMoney'=>$charge['amount']]);
						$this->db->where('id',$charge['uid'])->step('user', 'money',TRUE,$charge['amount']);
					}else{
						$this->db->insert('money_log',
								['uid'=>$charge['uid'],'content'=>"您已成功充值$charge[amount]元购买花",'time'=>time(),'num'=>$charge['amount'],'realMoney'=>$charge['amount']]);
						$this->db->where('id',$charge['uid'])->step('user', 'flower',TRUE,$charge['amount']);
					}
				}
				$this->db->trans_complete();
				$flag=$this->db->trans_status();
				break;
			case 'refund.succeeded':
				if ($data['data']['status']=='pending'){//需要后台输入密码处理
					$flag=TRUE;
				}else{
					$flag=$this->db->where('id',$data['data']['id'])->update('refund',['status'=>$data['data']['succeed']==TRUE?1:2,'dealTime'=>time()]);
				}
				break;
			default:
				$flag=TRUE;
				break;
		}
		if ($flag) restful(200);
		else throw new MyException('',MyException::DATABASE);
	}
	
	function addAdminGo() {
		$data=$this->input->post(['user','password']);
		if (!$data) throw new MyException('',MyException::INPUT_MISS);
		if ($data['user']=='yiren'&&md5($data['password'].'yiren')=='d8de0a29d0f3bf3da05c20308704dc00'){
			session_start();
			$_SESSION['admin']=1;
			$_SESSION['name']='超级管理员';
			$_SESSION['pri']=[0,1,2,3,4,5,6,7,8];
			restful(200,'/back/back/');
		}else {
			$res=$this->db->find('admin',$data['user'],'user');
			if (!$res) throw new MyException('用户名不存在！',MyException::INPUT_ERR);
			if ($res['password']==md5($data['password'].'yiren')){
				session_start();
				$_SESSION['admin']=$res['id'];
				$_SESSION['name']=$res['name'];
				$_SESSION['pri']=json_decode($res['pri'],TRUE);
				restful(200,'/back/back/');
			}else throw new MyException('密码错误!',MyException::INPUT_ERR);
		}
	}
	
	function adminGo() {
		session_start();
		if (isset($_SESSION['admin'])){
			header('Location:/back/back/');
			exit();
		}else $this->load->view('back/login');
	}
	
	function importQuestion() {
		show_404();
		$pics=[];$index=0;$i=6;
		for ($i =0; $i < 13; $i++) {
			$data=file_get_contents("$i.json");
			$data=json_decode($data,TRUE);
			foreach ($data as &$value) {
				if (!empty($value['pics'])){
					$name="QUE$index.";
					$ext=explode('.', $value['pics']);
					$name.=end($ext);
					$pics[]=['org'=>$value['pics'],'now'=>$name];
					$index++;
					$value['pics']=$name;
				}
				$value['option']=json_encode($value['option']);
				$value['analy']=strip_tags($value['analy']);
			}
			$this->db->insert_batch('question',$data);
		}
		foreach ($pics as $item) {
			error_log("$item[org]	$item[now]\n",3,'pics.txt');
		}
	}
	
	function daily($word='') {
		// if (md5(md5($word).'fu*k')!='3877648649d01ec38736633246e106ae') show_404();
		$this->load->model('back/back');
		$data=$this->back->weather(self::ADDR);
		file_put_contents(self::WEATHER,json_encode($data));
		$this->back->statisticDaily();
		$this->back->daily();
		if (date('w')==1) $this->back->week();
		if (date('d')=='01') $this->back->month();
	}
	
	function hours($word='') {
		if (md5(md5($word).'fu*k')!='3877648649d01ec38736633246e106ae') show_404();
		$this->load->model('back/back');
		$this->back->hours();
	}
	
	function autoFinish($word='') {
		if (md5(md5($word).'fu*k')!='3877648649d01ec38736633246e106ae') show_404();
		$this->load->model('back/back');
		$this->back->autoFinish();
	}
	
	function build($table='') {
		$this->load->dbforge();
		$this->dbforge->column_cache($table);
	}
	
	function checkUpgrade($kind=0) {
		restful(200,$this->db->where('kind',$kind)->order_by('id','desc')->get('version',1)->row_array());
	}
	
	function weather() {
		$input=$this->input->get(['lat','lng']);
		$res='';
		$data=json_decode(file_get_contents(self::WEATHER),true);
		if ($input&&$input['lat']>0){
			$min=999999;
			foreach (self::ADDR as $value) {
				$dis=pow($input['lat']-$value['lat'],2)+pow($input['lng']-$value['lng'],2);
				if ($min>$dis){
					$min=$dis;
					$res=$value['name'];
				}
			}
		}else $res=self::ADDR[0]['name'];
		$data=$data[$res];
		$data['name']=$res;
		restful(200,$data);
	}
}
