$(document).ready(function(){
	param={
		T:$('#template').html(),
		target:'data'
	}
	PAGER.init(param);
    $('#time').click(function(){
        var key=$('#type').val();
        var user=$('#studentId');
        if (key==0){
			delete PAGER.param.type;
			key=user.val();
			if (key==0) delete PAGER.param.uid;
			else PAGER.param.uid=key;
		}else{
			PAGER.param.type=key;
			key=user.val();
			if (key!=0){
				delete PAGER.param.uid;
				user.val(0);
				$('#student').val('');
			}
		}
    });
	$('.js-time').click(function(){
		var obj=$(this);
		obj.addClass('active').siblings().removeClass('active');
		PAGER.param.time=obj.data('data');
		PAGER.loadPage();
	});
});