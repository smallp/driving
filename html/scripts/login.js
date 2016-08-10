$(function () {
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
});