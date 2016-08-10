<?php 
	function mobValidate($userTel, $userCode,$type){
		$appKey=['120f1ff5728f4','1303daebef8b0','1480703c9aa4d','120f88e798918'];
		// 请求参数
		$params = array(
			'appkey' => $appKey[$type-1],
			'phone' => $userTel,
			'zone' => '86',
			'code' => $userCode
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://webapi.sms.mob.com/sms/verify'); // 请求地址
		// 以返回的形式接收信息
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 设置为POST方式
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		// 不验证https证书
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 超时设置
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
			'Accept: application/json',
		));

		$response = curl_exec($ch);
		curl_close($ch);
		if ($response){
			$response=json_decode($response,TRUE);
			if ($response['status']==200) return TRUE;
			else if ($response['status']==468) throw new MyException('验证码错误',MyException::INPUT_ERR);
		}
		throw new MyException('验证码平台出错',MyException::THIRD);
	}
?>