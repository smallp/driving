<?php 
	class Rong{

		/**
		 * [RCsignature 生成融云签名]
		 */
		public function RCsignature() {
			
			srand((double)microtime()*1000000);
			
			$appKey = 'ik1qhw0917tjp';
			$appSecret = 'ZMkaDuksmj'; // 开发者平台分配的 App Secret。
			$randNumber = rand(); // 获取随机数。
			$timeStamp = time(); // 获取时间戳。
			$signature = sha1($appSecret.$randNumber.$timeStamp);
			
			return array(
				'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
				'Accept: application/json',
				'App-Key: '.$appKey,
				'Nonce: '.$randNumber,
				'Timestamp: '.$timeStamp,
				'Signature: '.$signature 
			);
		}
		
		private function _send($params,$url) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			// 以返回的形式接收信息
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// 设置为POST方式
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 超时设置
			// 请求头/签名
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->RCsignature());
			
			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
		}

		/**
		 * [_getToken 获取Token]
		 * @param  [Array] $userInfo [用户信息]
		 * @return [JSON]			[返回状态码]
		 */
		public function RCgetToken($userInfo){
			$params = array(
				'userId' => ($userInfo['kind']==0?'stu':'tea').$userInfo['id'],
				'name' => $userInfo['name'],
				'portraitUri' => $userInfo['avatar']
			);
			
			$response=$this->_send(http_build_query($params),'https://api.cn.ronghub.com/user/getToken.json');
			if ($response){
				$response=json_decode($response,true);
				if ($response['code']==200) return $response['token'];
				else throw new MyException('',$response['code']);
			}else throw new MyException('',0);
		}

		/**
		 * [RCrefresh 更新融云信息]
		 */
		public function RCrefresh($userId, $info){
			$params = array(
				'userId' => $userId
			);
			if (isset($info['name'])) $params['name']=$info['name'];
			else $params['portraitUri']=$info['avatar'];
			
			$response=$this->_send(http_build_query($params),'https://api.cn.ronghub.com/user/refresh.json');
			if ($response){
				$response=json_decode($response,true);
				return ($response['code']==200)?true:$response['code'];
			}else return 0;
		}
		
		function newFriend($from,$to,$text) {
			$params = array(
				'fromUserId' =>'stu1',
				'toUserId' =>($to['kind']==0?'stu':'tea').$to['id'],
				'objectName' =>'RC:ContactNtf',
				'content' =>json_encode(["operation"=>"none",
						"sourceUserId"=>$from,"targetUserId"=>$to['id'],"message"=>$text,"extra"=>""])
			);
			$response=$this->_send(http_build_query($params),'https://api.cn.ronghub.com/message/system/publish.json');
			if ($response){
				$response=json_decode($response,true);
				return ($response['code']==200)?true:$response['code'];
			}else return 0;
		}
		
		function info($from,$to) {
			$params = array(
				'fromUserId' =>($from['kind']==0?'stu':'tea').$from['id'],
				'toUserId' =>($to['kind']==0?'stu':'tea').$to['id'],
				'objectName' =>'RC:InfoNtf',
				'content' =>json_encode(["message"=>'你们已经是好友了，开始聊天吧',"extra"=>""])
			);
			$response=$this->_send(http_build_query($params),'https://api.cn.ronghub.com/message/private/publish.json');
			if ($response){
				$response=json_decode($response,true);
				return ($response['code']==200)?true:$response['code'];
			}else return 0;
		}
		
		function cmd($from,$to) {
			$params = array(
				'fromUserId' =>'stu1',
				'toUserId' =>($to['kind']==0?'stu':'tea').$to['id'],
				'objectName' =>'RC:CmdMsg',
				'content' =>json_encode(["name"=>'delPerson',"data"=>($from['kind']==0?'stu':'tea').$from['id']])
			);
			$response=$this->_send(http_build_query($params),'https://api.cn.ronghub.com/message/system/publish.json');
			if ($response){
				$response=json_decode($response,true);
				return ($response['code']==200)?true:$response['code'];
			}else return 0;
		}

		/**
		 * 创建群
		 */
		public function createGrp($gid,$name, $mem){
			$params = array(
				'groupId' =>$gid,
				'groupName' =>$name
			);
			//日狗的融云，数组不能有[]符号，所以需要特殊处理
			$params=http_build_query($params);
			foreach ($mem as $item) {
				$params.="&userId=$item";
			}
			
			$response=$this->_send($params,'https://api.cn.ronghub.com/group/create.json');
			if ($response){
				$response=json_decode($response,true);
				return $response['code']==200?true:$response;
			}else return 0;
		}

		public function quitGrp($gid,$uid){
			$params = array(
				'groupId' =>$gid,
				'userId' =>$uid
			);
			$params=http_build_query($params);
			$response=$this->_send($params,'https://api.cn.ronghub.com/group/quit.json');
			if ($response){
				$response=json_decode($response,true);
				return $response['code']==200?true:$response;
			}else return 0;
		}

		/**
		 * 加入群
		 */
		public function addToGrp($gid,$name, $mem){
			$params = array(
				'groupId' =>$gid,
				'groupName' =>$name
			);
			//日狗的融云，数组不能有[]符号，所以需要特殊处理
			$params=http_build_query($params);
			foreach ($mem as $item) {
				$params.="&userId=$item";
			}
			
			$response=$this->_send($params,'https://api.cn.ronghub.com/group/join.json');
			if ($response){
				$response=json_decode($response,true);
				return $response['code']==200?true:$response;
			}else return 0;
		}

		/**
		 * 刷新群
		 */
		public function refreshGrp($gid,$name){
			$params = array(
				'groupId' =>$gid,
				'groupName' =>$name
			);
			$params=http_build_query($params);
			
			$response=$this->_send($params,'https://api.cn.ronghub.com/group/refresh.json');
			if ($response){
				$response=json_decode($response,true);
				return $response['code']==200?true:$response;
			}else return 0;
		}
	}

?>