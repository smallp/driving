$(document).ready(function(){
	param={
		T:$('template').html(),
		target:'data',
		dealData:function(data){
			$('#total').html(parseFloat(data.stat.total));
			$('#real').html(parseFloat(data.stat.realMoney));
			$('#virtual').html(parseFloat(data.stat.virtualMoney));
			data=data.data;
			for (x in data){
				data[x].realMoney=parseFloat(data[x].realMoney);
				data[x].virtualMoney=parseFloat(data[x].virtualMoney);
				data[x].total=parseFloat(data[x].total);
			}
			return data;
		}
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
	$('#download').click(function () {
		var param={};
		param.type=$('#type').val();
		var time=0;
		if ('time' in PAGER.param)
			time=PAGER.param.time;
		param.time=time;
		var begin=$('#begin').val();
    	var end=$('#end').val();
    	if (begin==''||end==''){
    		alert('请指定时间区间！');
			return false;
    	}else{
    		param.begin=begin.substr(0,10);
    		param.end=end.substr(0,10);
    	}
		window.open('/back/export/tongji?'+$.param(param),'_blank');
	});
	$('.js-time').click(function(){
		var obj=$(this);
		obj.addClass('active').siblings().removeClass('active');
		PAGER.param.time=obj.data('data');
		PAGER.loadPage();
	});
});