$(function () {
	//检查当前浏览器内核
	console.log(navigator.appName);
	var navigatorName = "Microsoft Internet Explorer"; 
	if( navigator.appName == navigatorName ){ 
		$('.tishi').css('display','block');
		$('#sub').attr({"disabled":"disabled"});
	}else{
		
	}
    var $user = $('#user');
    var $password = $('#password');
    var $btn = $('#sub');
    $( document ).ajaxError(function(event, res) {
		try{
			res=JSON.parse(res.responseText);
			alert(res.info);
		}catch(e){
			alert('服务器错误！');
		}
	});
    $btn.on('click', function (e) {
        //    阻止默认事件
        e.preventDefault();
        if (check($user, $password)) {
            $.ajax({
                url: '',
                method: 'post',
                data: {user: $user.val(), password: $password.val()},
                dataType: 'json',
                success: function (data) {
                    window.location = data.info;
                }
            });
        }
    });
  	//兼容IE 支持trim方法
    String.prototype.trim = function () {
		return this .replace(/^\s\s*/, '' ).replace(/\s\s*$/, '' );
	}
    //检查表单值知否为空
    function check(login, pass) {
        if (login.val().trim().length === 0) {
            login.focus();
            return false;
        }
        if (pass.val().trim().length === 0) {
            pass.focus();
            return false;
        }
        return true;
    }
    if (window.PIE) {
    	
        $('.rounded').each(function() {
            PIE.attach(this);
            console.log("aa");
        });
    }
});