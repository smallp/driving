$(document).ready(function () {
	param={
		T:
			'<tr><td><input type="checkbox" data-id="{id}"></td><td>{id}</td><td class="tel">{tel}</td><td class="user">{name}</td>'+
            '<td>{money}</td><td>{level}</td><td>{addrTime}</td><td>{status}</td>'+
            '<td><button class="btn btn-primary btn-sm js-froze" data-id="{id}" data-toggle="modal" data-target="#sure">{option}</button> '+
            '<button class="btn btn-primary btn-sm js-detail" data-id="{id}" data-toggle="modal" data-target="#detail">详细信息</button> '+
            '<button class="btn btn-primary btn-sm js-money" data-id="{id}" data-toggle="modal" data-target="#addMoney">充值</button></td></tr>',
       	dealData:function(data){
       		for(x in data){
                data[x].option=data[x].status==1?'冻结':'恢复';
       			data[x].status=data[x].status==1?'正常':'冻结';
       			data[x].level='科目'+data[x].level;
       			data[x].addrTime=window.data.getTime(data[x].addrTime);
       		}
       		return data;
       	}
	}
	PAGER.init(param);
    $('tbody').on('click','.js-froze',function () {
        var obj=$(this);
        $('#btnok').attr('data-id',obj.attr('data-id'));
        $('#status').html(obj.html());
    });
    $('tbody').on('click','.js-money',function () {
    	$(".userInfo").html('<li><span>用户名</span><span>手机号码</span></li><li><span id="user"></span><span id="tel"></span></li>');
        var obj=$(this);
        $('#btnmoney').attr('data-id',obj.attr('data-id'));
        var tr=obj.parent().parent();
        $('#tel').html($('.tel',tr).html());
        $('#user').html($('.user',tr).html());
    });
    $('#all_recharge').click(function(){
    	$(".userInfo").html('<li><span>用户名</span><span>手机号码</span></li>');
    	var chxs=$('tbody').find('input:checked');
    	$(chxs).each(function(idx,item){
    		var str="";
    		var id=item.dataset.id;
    		var tr=$('tbody').find('input[data-id='+id+']').parent().parent();
    		str+="<li><span>"+$('.user',tr).html()+"</span><span>"+$('.tel',tr).html()+"</li>";
    		console.log(str);
    		$('.userInfo').append(str);
    	});
    });
    $('tbody').on('click','.js-detail',function () {
        var id=$(this).data('id');
        var url=location.pathname+'/'+id;
        $.web(url,'',function(e){
        	e.addrTime=window.data.getTime(e.addrTime);
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
        $.web('/back/user/student',$('form').serialize(),function () {
            alert('添加成功！初始密码123456');
            $('form')[0].reset();
            $('.close').trigger('click');
        },'post');
    });
    $('#btnmoney').on('click',function(){
    	var id=[$(this).attr('data-id')];
    	$('#ids').val(JSON.stringify(id));
        $.web('/back/user/money',$('#charge').serialize(),function () {
            alert('充值成功！');
            PAGER.loadPage();
            $('.close').trigger('click');
        },'put');
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