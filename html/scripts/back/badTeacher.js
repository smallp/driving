$(function(){
	param={
		T:'<tr><td>{id}</td><td>{tel}</td><td>{name}</td><td>{realname}</td><td>{school}</td><td>{kind}</td>'+//
            '<td>{num}</td><td>{regTime}</td><td>{status}</td>'+
            '<td><button class="btn btn-primary btn-sm js-detail" data-id="{id}" data-toggle="modal" data-target="#yc_teaBox">详细信息</button> '+
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
        var id=$(this).attr('data-id');
        $('.close').trigger('click');
        $.web('/back/user/froze/'+id,{status:$('#status').html()=='冻结'?2:1},function () {
            alert('操作成功');
            $('#sure').modal('hide');
            PAGER.loadPage();
        },'get');
    });
    //详细信息按钮点击
    $('tbody').on('click','.js-detail',function () {
        $('#yc_teaInfo').html("");
        $('.right_stu').html("");
        var id=$(this).data('id');
        var url=location.pathname+'/'+id;
        $.web(url,'',function(data){
            // 调用渲染方法
            showBadteacher(data);
        },'get');
    });
    // 如果当前是由异常提醒跳转
    if(location.search!==""){
        $('#yc_teaBox').modal('show');
        $('#yc_teaInfo').html("");
        $('.right_stu').html("");
        var str=location.search;
        var id=(str.split('='))[1];
        $('#btnSure').attr('data-id',id);
        $.web(location.pathname+'/'+id,'',function(data){
            // 调用渲染方法
            showBadteacher(data);
        },'get');
    } 
    $('#btnSure').on('click',function(){
        var id=$(this).attr('data-id');
        $.web('/back/user/froze/'+id,{status:2},function () {
            alert('操作成功');
            $('#yc_teaBox').modal('hide');
            PAGER.loadPage();
        },'get');
    })
});
// 渲染异常教练模态框
function showBadteacher(data) {
    // body...
    // 渲染订单信息
    var stu=data.order;
    var stuStr="";
    $.each(stu,function(idx,item){
        stuStr+="<table><tr>"+
                    "<td>学员姓名</td>"+
                    "<td>同伴姓名</td>"+
                    "<td>训练场地</td>"+
                    "<td>预约时间</td>"+
                    "<td>训练费用</td>"+
                "</tr>"+
                "<tr>"+
                    "<td>"+item.stu+"</td>"+
                    "<td>"+(item.partner==null?'无':item.partner)+"</td>"+
                    "<td>"+item.info[0].place+"</td>"+
                    "<td>"+item.info[0].date+"<br>"+item.info[0].time+"</td>"+
                    "<td>"+item.price+"</td>"+
                "</tr>"+
                "<tr>"+
                    "<td>取消<br>原因</td>"+
                    "<td colspan=4>"+item.reason+"</td>"+
                "</tr></table>";
    });
    $('.right_stu').html('').append(stuStr);
    // 渲染教练信息
    var tea=data.people;
    var addrTime=timeDeal(tea.addrTime);
    var kind="";
    if (tea.kind%2==1){
            kind+='科目二 ';
            tea.kind-=1;
        }
    if (tea.kind%4==2){
        kind+='科目三 ';
    }
    if (tea.kind>=4){
        kind+='陪练陪驾 ';
    }
    tea.kind=kind;
    var teaStr="<tr>"+
                "<td>真实姓名</td>"+
                "<td>"+tea.realname+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>昵称</td>"+
                "<td>"+tea.name+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>联系方式</td>"+
                "<td>"+tea.phone+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>教导类型</td>"+
                "<td>"+(tea.zjType=="1"?"C1":tea.zjType=="2"?"C2":tea.zjType=="3"?"B2":"")+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>教导科目</td>"+
                "<td>"+tea.kind+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>所属驾校</td>"+
                "<td>"+tea.school+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>账户状态</td>"+
                "<td>"+(tea.status==1?'正常':'冻结')+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>账户余额</td>"+
                "<td>"+tea.money+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>取消数量</td>"+
                "<td>"+stu.length+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>注册时间</td>"+
                "<td>"+tea.regTime+"</td>"+
            "</tr>"+
            "<tr>"+
                "<td>登录时间</td>"+
                "<td>"+addrTime+"</td>"+
            "</tr>";
    $('#yc_teaInfo').html('').append(teaStr);
}
// 时间处理
function timeDeal(timeStamp) {
    var a=(new Date(Number(timeStamp)*1000)).toLocaleString();
    var time=new Date(Number(timeStamp)*1000);
    var year=time.getFullYear();
    var month=time.getMonth()+1;
    month=month<10?'0'+month:month;
    var day=time.getDate();
    day=day<10?'0'+day:day;
    var hour=time.getHours();
    hour=hour<10?'0'+hour:hour;
    var min=time.getMinutes();
    min=min<10?'0'+min:min;
    var sec=time.getSeconds();
    sec=sec<10?'0'+sec:sec;
    var gsTime=year+"-"+month+"-"+day+" "+hour+":"+min+":"+sec;
    return gsTime;
}