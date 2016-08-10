$(document).ready(function(){
	param={
		T:$('#template').html(),
		target:'data',
    	dealData:function(data){
    		var status=['待支付','等待同伴支付','等待学车','等待评价','已评价','已过期','已取消'];
    		var kind={'1':'科目二','2':'科目三','4':'陪练陪驾'};
    		for(x in data){
    			data[x].kind=kind[data[x].kind];
    			data[x].partner=data[x].partner?data[x].partner:'';
        		if (data[x].status==2)
        			data[x].option='<button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#delete" data-id="'+data[x].id+'" id="getCancel"><i class="cacel-btn"></i>取消订单</button>';
        		else data[x].option='';
        		data[x].status=status[parseInt(data[x].status)];
    		}
    		return data;
    	}
	}
	PAGER.init(param);
	$('#time').on('click',function(){
		var key=$('#studentId').val();
		if (key==0) delete PAGER.param.stu;
		else PAGER.param.stu=key;
		key=$('#teacherId').val();
		if (key==0) delete PAGER.param.tea;
		else PAGER.param.tea=key;
    });
});