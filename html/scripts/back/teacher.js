$(function(){
	param={
		T:'<tr><td>{id}</td><td>{tel}</td><td>{name}</td><td>{realname}</td><td>{school}</td><td>{grade}</td>'+//<td>{kind}</td>
            '<td>{money}</td><td>{addrTime}</td><td>{status}</td>'+
            '<td><button class="btn btn-primary btn-sm js-mod" data-target="#option" data-toggle="modal" data-id="{id}">修改星级</button> '+
            '<button class="btn btn-primary btn-sm js-detail" data-id="{id}" data-toggle="modal" data-target="#detail">详细信息</button> '+
            '<button class="btn btn-primary btn-sm js-froze" data-id="{id}" data-toggle="modal" data-target="#sure">{option}</button>'+'</td></tr>',
    	dealData:function(data){
    		var kind='';
    		for(x in data){
                data[x].option=data[x].status==1?'冻结':'恢复';
    			data[x].status=data[x].status==1?'正常':'冻结';
    			kind='';
    			if (data[x].kind%2==1){
    				kind+='科目二 ';
    				data[x].kind-=1;
    			}
    			if (data[x].kind%4==2){
    				kind+='科目三 ';
    			}
    			if (data[x].kind>=4) kind+='陪练陪驾 ';
    			data[x].kind=kind;
                data[x].grade=data[x].grade+'星';
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
    $('#btnok').on('click',function(){
        var id=$(this).attr('data-id');
        $('.close').trigger('click');
        $.web('/back/user/froze/'+id,{status:$('#status').html()=='冻结'?2:1},function () {
            PAGER.loadPage();
        },'get');
    });
    $('#btnsub').on('click',function () {
        var id=$(this).attr('data-id');
        $.web('/back/user/level/'+id,{price:$('#price').val(),grade:$('input[name]:checked').val()},function () {
            $('.close').trigger('click');
            PAGER.loadPage();
        },'put')
    });
    $('tbody').on('click','.js-mod',function () {
        var id=$(this).attr('data-id');
        $('#btnsub').attr('data-id',id);
        $.web('/back/user/teacher/'+id,'',function (data) {
            $('input[name=grade]').attr('checked',false);
            $('input[value='+data.grade+']').attr('checked',true);
            $('#price').val(data.price);
        },'get');
    });
    $('tbody').on('click','.js-detail',function () {
        var id=$(this).data('id');
        var url=location.pathname+'/'+id;
        $.web(url,'',function(e){
        	e.addrTime=window.data.getTime(e.addrTime);
        	var kind='';
        	if (e.kind%2==1){
				kind+='科目二 ';
				e.kind-=1;
			}
			if (e.kind%4==2){
				kind+='科目三 ';
			}
			if (e.kind>=4) kind+='陪练陪驾 ';
			e.kind=kind;
        	var tpl=$('#detail_tpl').html();
        	var res=tpl.replace(/{\w+?}/g, function (word) {
                var key=word.substr(1,word.length-2);
                return e[key];
            });
        	$('#detail_body').html(res);
        },'get');
    });
});
function doSearch(){
	var key=$('#key').val();
	if (key.length==0) PAGER.param.key=null;
	else PAGER.param.key=key;
	location.href='#1';
	PAGER.loadPage();
}