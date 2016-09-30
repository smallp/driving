$(function(){
	param={
		T:'<tr><td>{id}</td><td>{tel}</td><td>{name}</td><td>{realname}</td><td>{school}</td><td>{kind}</td>'+//
            '<td>{num}</td><td>{regTime}</td><td>{status}</td>'+
            '<td><button class="btn btn-primary btn-sm js-detail" data-id="{id}" data-toggle="modal" data-target="#detail">详细信息</button> '+
            '<button class="btn btn-primary btn-sm js-froze" data-id="{id}" data-toggle="modal" data-target="#sure">{option}</button>'+'</td></tr>',
    	target:'badTeacher_table',
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
       			// data[x].addrTime=window.data.getTime(data[x].addrTime);
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
        console.log(this);
        var id=$(this).attr('data-id');
        $('.close').trigger('click');
        $.web('/back/user/froze/'+id,{status:$('#status').html()=='冻结'?2:1},function () {
            PAGER.loadPage();
        },'get');
    });
    $('tbody').on('click','.js-detail',function () {
        var id=$(this).data('id');
        var url=location.pathname+'/'+id;
        $.web(url,'',function(e){
        	e.addrTime=e.addrTime>0?window.data.getTime(e.addrTime):'';
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
    // 如果当前是由异常提醒跳转
    if(location.search!==""){
        $('#yc_teaBox').modal('show');
        var str=location.search;
        var id=(str.split('='))[1];
        $('#btnSure').attr('data-id',id);
        $('.yc_teaInfo')
        $.web(location.pathname+'/'+id,'',function(data){
            console.log(data);
            // var str="<tr>"+
            //             "<td>真实姓名</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>昵称</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>联系方式</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>教导类型</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>教导科目</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>所属驾校</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>账户状态</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>账户余额</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>取消数量</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>注册时间</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>"+
            //         "<tr>"+
            //             "<td>登录时间</td>"+
            //             "<td>"++"</td>"+
            //         "</tr>";
            // $('.yc_teaInfo').html(str);
        })
    }
    $('#btnSure').on('click',function(){
        console.log(this);
        var id=$(this).attr('data-id');
        // $('.close').trigger('click');
        $.web('/back/user/froze/'+id,{status:2},function () {
            alert('操作成功');
            $('#yc_teaBox').modal('hide');
            PAGER.loadPage();
        },'get');
    })
});
