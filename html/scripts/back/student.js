$(document).ready(function () {
	param={
		T:
			'<tr><td><input type="checkbox" data-id="{id}"></td><td>{id}</td><td class="tel">{tel}</td><td class="user">{name}</td>'+
            '<td class="price">{money}</td><td class="price">{frozenMoney}</td><td>{level}</td><td>{regTime}</td><td>{status}</td>'+
            '<td><button class="btn btn-primary btn-sm js-froze" data-id="{id}" data-toggle="modal" data-target="#sure">{option}</button> '+
            '<button class="btn btn-primary btn-sm js-detail" data-id="{id}" data-toggle="modal" data-target="#detail">详细信息</button> '+
            '<button class="btn btn-primary btn-sm js-limit" data-id="{id}" data-toggle="modal" data-target="#modLimit">修改预约限制</button> '+
            '<button class="btn btn-primary btn-sm js-money" data-id="{id}" data-toggle="modal" data-target="#addMoney">充值</button></td></tr>',
       	dealData:function(data){
       		for(x in data){
                data[x].option=data[x].status==1?'冻结':'恢复';
       			data[x].status=data[x].status==1?'正常':'冻结';
       			data[x].level='科目'+data[x].level;
       			// data[x].addrTime=window.data.getTime(data[x].addrTime);
       		}
       		return data;
       	},
           target:'tdata'
	}
	PAGER.init(param);
    $('tbody').on('click','.js-froze',function () {
        var obj=$(this);
        $('#btnok').attr('data-id',obj.attr('data-id'));
        $('#status').html(obj.html());
    });
    $('tbody').on('click','.js-money',function () {
    	$('#btnmoney').data('method','one');
    	$(".userInfo").html('<tr><td width="126px">用户名</td><td width="173px">手机号码</td></tr><tr><td id="user"></td><td id="tel"></td></tr>');
        var obj=$(this);
        $('#btnmoney').attr('data-id',obj.attr('data-id'));
        var tr=obj.parent().parent();
        $('#tel').html($('.tel',tr).html());
        $('#user').html($('.user',tr).html());
    });
    $('tbody').on('click','.js-limit',function () {
        var id=$(this).attr('data-id');
        $('#btnlimit').attr('data-id',id);
        var data=PAGER.getRow('id',id);
        $('#ltel').html(data.tel);
        $('#luser').html(data.name);
		$('#limit').val('');
    });
    $('#all_recharge').click(function(){
    	$('#btnmoney').data('method','all');
    	$(".userInfo").html('<tr><td width="126px">用户名</td><td width="173px">手机号码</td></tr>');
    	var chxs=$('tbody').find('input:checked');
    	$(chxs).each(function(idx,item){
    		var str="";
    		var id=item.dataset.id;
    		var tr=$('tbody').find('input[data-id='+id+']').parent().parent();
    		str+="<tr><td>"+$('.user',tr).html()+"</td><td>"+$('.tel',tr).html()+"</td></tr>";
    		console.log(str);
    		$('.userInfo').append(str);
    	});
    });
    $('tbody').on('click','.js-detail',function () {
        var id=$(this).data('id');
        var url=location.pathname+'/'+id;
        $.web(url,'',function(e){
        	e.addrTime=e.addrTime>0?window.data.getTime(e.addrTime):'';
        	var tpl=$('#detail_tpl').html();
        	var res=tpl.replace(/{\w+?}/g, function (word) {
                var key=word.substr(1,word.length-2);
                return e[key];
            });
        	$('#detail_body').html(res);
        },'get');
    });
    $('#btnok').on('click',function(){
        var id=$(this).attr('data-id');
        $('.close').trigger('click');
        $.web('/back/user/froze/'+id,{status:$('#status').html()=='冻结'?2:1},function () {
            PAGER.loadPage();
        },'get');
    });
    $('#btnadd').on('click',function(){
        $.web('/back/user/student',$('#addForm').serialize(),function () {
            alert('添加成功！初始密码123456');
            $('#addForm')[0].reset();
            $('.close').trigger('click');
        },'post');
    });
    $('#btnlimit').on('click',function(){
        $.web('/back/user/limit/'+$(this).data('id'),{limit:$('#limit').val()},function () {
            alert('操作成功！');
            $('.close').trigger('click');
        },'put');
    });
    $('#btnmoney').on('click',function(){
    	console.log($(this).data('method'));
    	if($(this).data('method')=='one'){//单个充值
    		var id=[$(this).attr('data-id')];
	    	$('#ids').val(JSON.stringify(id));
	    	var data="id="+id+"&"+$('#charge').serialize();
	    	console.log(data);
	        $.web('/back/user/money',data,function () {
	            alert('操作成功！');
	            PAGER.loadPage();
				$('#charge')[0].reset();
	            $('.close').trigger('click');
	        },'put');
    	}else{//批量充值
    		var chxs=$('tbody').find('input:checked');
	    	var idArr=[];
    		$(chxs).each(function(idx,item){
	    		idArr.push(item.dataset.id);
	    	});
	    	var ids=JSON.stringify(idArr);
	    	var data="id="+ids+'&'+$('#charge').serialize();
	    	$.web('/back/user/money',data,function(){
	    		alert('批量操作成功');
	    		PAGER.loadPage();
				$('#charge')[0].reset();
	            $('.close').trigger('click');
	    	},'put')
    	}
    });
});
$(document).on('change', '#checkAll', function () {
    if ($(this).is(':checked')) {
        $(this).parent().parent().parent().next().find('input[type=checkbox]').prop('checked', 'checked');
    } else {
        $(this).parent().parent().parent().next().find('input[type=checkbox]').attr('checked', false);
    }
});
function doSearch(){
	var key=$('#key').val();
	if (key.length==0) PAGER.param.key=null;
	else PAGER.param.key=key;
	location.href='#1';
	PAGER.loadPage();
}
