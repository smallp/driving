<?php
if (!function_exists('mb_detect_encoding')) {
  throw new Exception('Pingpp needs the Multibyte String PHP extension.');
}

// Pingpp singleton
require(dirname(__FILE__) . '/ping/Pingpp.php');

// Utilities
require(dirname(__FILE__) . '/ping/Util/Util.php');
require(dirname(__FILE__) . '/ping/Util/Set.php');
require(dirname(__FILE__) . '/ping/Util/RequestOptions.php');

// Errors
require(dirname(__FILE__) . '/ping/Error/Base.php');
require(dirname(__FILE__) . '/ping/Error/Api.php');
require(dirname(__FILE__) . '/ping/Error/ApiConnection.php');
require(dirname(__FILE__) . '/ping/Error/Authentication.php');
require(dirname(__FILE__) . '/ping/Error/InvalidRequest.php');
require(dirname(__FILE__) . '/ping/Error/RateLimit.php');
require(dirname(__FILE__) . '/ping/Error/Channel.php');

// Plumbing
require(dirname(__FILE__) . '/ping/JsonSerializable.php');
require(dirname(__FILE__) . '/ping/PingppObject.php');
require(dirname(__FILE__) . '/ping/ApiRequestor.php');
require(dirname(__FILE__) . '/ping/ApiResource.php');
require(dirname(__FILE__) . '/ping/SingletonApiResource.php');
require(dirname(__FILE__) . '/ping/AttachedObject.php');
require(dirname(__FILE__) . '/ping/Collection.php');

// Pingpp API Resources
require(dirname(__FILE__) . '/ping/Charge.php');
require(dirname(__FILE__) . '/ping/Refund.php');
require(dirname(__FILE__) . '/ping/RedEnvelope.php');
require(dirname(__FILE__) . '/ping/Event.php');
require(dirname(__FILE__) . '/ping/Transfer.php');
require(dirname(__FILE__) . '/ping/Customer.php');
require(dirname(__FILE__) . '/ping/Source.php');
require(dirname(__FILE__) . '/ping/Card.php');
require(dirname(__FILE__) . '/ping/Token.php');
require(dirname(__FILE__) . '/ping/CardInfo.php');
require(dirname(__FILE__) . '/ping/SmsCode.php');

// wx_pub OAuth 2.0 method
require(dirname(__FILE__) . '/ping/WxpubOAuth.php');

class Ping {
	private $api_key = 'sk_live_PG00iLr9mHaHefnfT0GmvX58';#sk_test_HG0yzPSSGunTnbvjDSj1CWTO
	private $app_id = 'app_WLez1OnnD88CWrHa';

	function __construct() {
		\Pingpp\Pingpp::setApiKey($this->api_key);
	}
	
	function pay($input) {
		$channel = strtolower($input['channel']);
		$amount = $input['amount'];
		$orderNo =uniqid(UID);
		$extra = array();
// 		switch ($channel) {
// 		    case 'alipay_wap':
// 		        $extra = array(
// 		            'success_url' => 'http://www.baidu.com',
// 		            'cancel_url' => 'http://120.27.55.225/'
// 		        );
// 		        break;
// 		}
		
		// 设置 API Key
		\Pingpp\Pingpp::setPrivateKeyPath(__DIR__ . '/ping/money.pem');
		try {
		    $ch = \Pingpp\Charge::create(
		        array(
		            'subject'   => '蚁人约驾',
		            'body'      => "充值${amount}元",
		            'amount'    => $amount*100,//ping++是以分为单位，需要转换
		            'order_no'  => $orderNo,
		            'currency'  => 'cny',
		            'extra'     => $extra,
		            'channel'   => $channel,
		            'time_expire'   =>time()+1800,//半小时内支付有效
		            'client_ip' => $_SERVER['REMOTE_ADDR'],
		            'app'       => array('id' => $this->app_id)
		        )
		    );
		    return $ch;
		} catch (\Pingpp\Error\Base $e) {
		    // 捕获报错信息
		    if ($e->getHttpStatus() != NULL) {
		        header('Status: ' . $e->getHttpStatus());
		        echo $e->getHttpBody();
		    } else {
		        echo $e->getMessage();
		    }
		}
	}

	function refund($id,$uid,$amount=0){
		$ch = \Pingpp\Charge::retrieve($id);
		$data=['description'=>'蚁众驾退款'];
		if ($amount>0) $data['amount']=$amount*100;
		$re = $ch->refunds->create($data);
		$data=['id'=>$re->id,
			'amount'=>$amount,
			'chargeId'=>$id,
			'uid'=>$uid
			];
		return $data;
	}

	function getRefund($cid,$rid){
		$ch = \Pingpp\Charge::retrieve($cid);
		$re = $ch->refunds->retrieve($rid);
		return $re;
	}
	
	//验证并返回hook数据
	function webhook() {
		$raw_data = file_get_contents('php://input');
		$signature = isset($_SERVER['HTTP_X_PINGPLUSPLUS_SIGNATURE']) ? $_SERVER['HTTP_X_PINGPLUSPLUS_SIGNATURE']: NULL;
		$result = openssl_verify($raw_data, base64_decode($signature), file_get_contents(__DIR__ . "/ping/ping.pem"), 'sha256');
        if ($result !== 1) {
			restful(400,'verification failed');
		}
		$event = json_decode($raw_data, true);
		return ['type'=>$event['type'],'data'=>$event['data']['object']];
	}
}