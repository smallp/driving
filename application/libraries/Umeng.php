<?php
class Umeng {
	CONST ANDROID_KEY        = "571d7f9467e58e69f5000d93";
	CONST ANDROID_SECRET     = "nxliaejz8badr3kxms9ljqtgbeqhmmus";
	CONST IOS_KEY_STU        = "571d78cc67e58ecdbb00265f";
	CONST IOS_SECRET_STU     = "vidgcolw9ktn4jbzjkwkbofeocy3oa1t";
	CONST IOS_KEY_TEA        = "573fee8767e58e672400254d";
	CONST IOS_SECRET_TEA     = "zcbqdtrgfixodpgddbqfz6royi3bppoz";

	function send($info,$target,$isIos){
		$cla=$this->_getinstance($info, $isIos,$target['kind']);
		$cla->setPredefinedKeyValue("type","customizedcast");
		$cla->setPredefinedKeyValue("alias_type",$target['kind']?'TEA':'STU');
		$cla->setPredefinedKeyValue("alias",$target['id']);
		try {
			$cla->send();
		} catch (Exception $e) {
			error_log('Umeng push error'.$e->getMessage(),0);
		}
	}
	
	function backSend($text,$type) {
		$info=['text'=>$text,'type'=>0];
		foreach ([0,1] as $isIos){
			if ($isIos){
				if ($type!=1)
					$this->_backSend($this->_getinstance($info,TRUE,TRUE), $type);
				if ($type!=2)
					$this->_backSend($this->_getinstance($info,TRUE,FALSE), $type);
			}else $this->_backSend($this->_getinstance($info,FALSE,NULL), $type);
		}
	}
	
	function _backSend($cla,$type) {
		if ($type==0){
			$cla->setPredefinedKeyValue("type","broadcast");
		}else {
			$cla->setPredefinedKeyValue("type","customizedcast");
			$cla->setPredefinedKeyValue("alias_type",'TYPE');
			$cla->setPredefinedKeyValue("alias",$type==2?'TEA':'STU');
		}
		try {
// 			var_dump($cla->data);
			$cla->send();
		} catch (Exception $e) {
			throw new MyException('友盟错误：'.$e->getMessage(),MyException::THIRD);
			error_log('Umeng push error'.$e->getMessage(),0);
		}
	}
	
	function _getinstance($info,$isIos,$isTea) {
		if ($isIos){
			require_once('umeng/IOSNotification.php');
			$cla=new IOSNotification();
			if ($isTea){
				$cla->setAppMasterSecret(self::IOS_SECRET_TEA);
				$cla->setPredefinedKeyValue("appkey",self::IOS_KEY_TEA);
			}else{
				$cla->setAppMasterSecret(self::IOS_SECRET_STU);
				$cla->setPredefinedKeyValue("appkey",self::IOS_KEY_STU);
// 				$cla->setPredefinedKeyValue("production_mode",FALSE);
			}
			$cla->setPredefinedKeyValue("alert",$info['text']);
			$cla->setCustomizedField("YR_TYPE",$info['type']);
		}else {
			require_once('umeng/AndroidNotification.php');
			$cla=new AndroidNotification();
			$cla->setAppMasterSecret(self::ANDROID_SECRET);
			$cla->setPredefinedKeyValue("appkey",self::ANDROID_KEY);
			$cla->setPredefinedKeyValue("title","蚁众约驾");
			$cla->setPredefinedKeyValue("ticker","蚁众约驾");
			$cla->setPredefinedKeyValue("text",$info['text']);
			$cla->setExtraField("YR_TYPE",$info['type']);
			$cla->setPredefinedKeyValue("after_open",'go_app');
			$cla->setPredefinedKeyValue("activity",'');
		}
		$cla->setPredefinedKeyValue("timestamp",strval(time()));
		return $cla;
	}
}
