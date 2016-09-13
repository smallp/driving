$(document).ready(function(){
	param={
		T:$('#template').html(),
		target:'data',
    	dealData:function(data){
    		var status={'3':'待处理','4':'已处理'};
//    		var kind={'1':'科目二','2':'科目三','4':'陪练陪驾'};
    		for(x in data){
//  			data[x].partner=data[x].partner?data[x].partner:'';
    			data[x].place=data[x].place?data[x].place:'';
    			data[x].oprator=data[x].oprator?data[x].oprator:'';
        		if (data[x].status!=4)
        			data[x].option='<button  class="btn btn-danger btn-sm getDetail" data-toggle="modal" data-target="#shensu_detail" data-id="'+data[x].logId+'"><i class="cacel-btn"></i>处理</button>';
        		else data[x].option='';
        		data[x].status=status[data[x].status];
        		data[x].orderTime=window.data.orderTime(data[x].orderTime);
//      		data[x].dealTime=(data[x].dealTime==0)?'':window.data.getTime(data[x].dealTime);
        		if(data[x].dealTime!==0){
        			var dtime=window.data.getTime(data[x].dealTime);
        			var idx=dtime.indexOf(" ");
        			var dyear=dtime.substring(0,idx);
        			var driqi=dtime.substring(idx+1);
        			data[x].dealTime=dyear+"</br>"+driqi;
        		}else{
        			data[x].dealTime="";
        		}
        		var time=data[x].time;
        		var year=time.slice(0,10);
        		var riqi=time.substring(11);
        		data[x].time=year+"</br>"+riqi;
    		}
    		return data;
    	}
	}
	PAGER.init(param);
	$('#data').delegate('.getDetail','click',function(){
		if($(this).text()=="详情"){
			$('#shensu_detail h4').text("申诉详情");
			$('.cancelInfo').html('');
			$('.sure').attr('data-id','').attr('data-dismiss','modal');
		}else{
			console.log($(this).attr('data-id'));
			$('.sure').attr('data-id',$(this).attr('data-id')).attr('data-dismiss','');
			$('#shensu_detail h4').text("处理申诉");
			$('.cancelInfo').html('').append(function(){
				var str="<li class='cancelLi'><i><img src='/images/tuihuan.png'/></i><div class='cancel'>" +
	            "<p>请输入需要退还金额：<input type='text' id='stu'></p>" +
	            "<p style='padding-left:202px'>教练所得金额：<input type='text' id='tea'></p>"+
	            "</div></li>";
	            return str;
			});
		}
		var tds=$(this).parent().prevAll();
		var str="";
		for(var i=tds.length-1;i>=0;i--){
			if(i==10){
				str+="<tr><td>申诉者</td>";
			}else if(i==9){
				str+="<tr><td>申述时间</td>";
			}else if(i==8){
				str+="<tr><td>申述地点</td>";
			}else if(i==7){
				str+="<tr><td>学员</td>";
			}else if(i==6){
				str+="<tr><td>教练</td>";
			}else if(i==5){
				str+="<tr><td>预约时间</td>";
			}else if(i==4){
				str+="<tr><td>预约场地</td>";
			}else if(i==3){
				str+="<tr><td>实际支付</td>";
			}else if(i==2){
				str+="<tr><td>费用</td>";
			}else if(i==1){
				str+="<tr><td>处理时间</td>";
			}else if(i==0){
				str+="<tr><td>处理人</td>";
			}
			str+="<td>"+$(tds[i]).text()+"</td></tr>";
			
		}
//		console.log(str);
		$('.shensu').html(str);
	});
	$('#data').delegate('.addr','mouseover',function(){
		$('.allInfo').css('display','block').html($('.addr').html());
	});
	$('#data').delegate('.addr','mouseout',function(){
		$('.allInfo').css('display','none').html($('.addr').html());
	});
});
