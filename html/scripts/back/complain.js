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
        			data[x].option='<button   class="btn btn-danger btn-sm shensu_chuli" data-toggle="modal" data-target="#shensu_chuli" data-id="'+data[x].logId+'"><i class="cacel-btn"></i>处理</button>';
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
	//订单详情
	$('#data').delegate('.shensu_detail','click',function(){
		var url = '/back/order/order/';
        doOperation( $(this).attr('data-id'),url,'get','get');
	});
	function doOperation( data,url,method,type){
        var _url= url+data;
            data = {};
//      var _url;
//      if( method != 'delete'){
//          _url = url+data;
//          data = {};
//      }
        $.web(_url,data,function(item){
            if( method == 'get' ){
                getContent(item,type);
            } 
//          else{
//             alert('操作成功！');
//             $('#delete').modal('hide');
//             PAGER.loadPage();
//          }
        },method);
    }
	function getContent( data,way ){
        var partner=(data.partner==null?"无":data.partner);
        $('.shensu').html('').append(function(){
            var str='<tr>'+
                		'<td>学员姓名</td>'+
                		'<td>'+data.stu+'</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>学员电话</td>'+
                		'<td>'+data.stuTel+'</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>教练</td>'+
                		'<td>'+data.tea+'</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>教练电话</td>'+
                		'<td>'+data.teaTel+'</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>拼教练同伴</td>'+
                		'<td>'+partner+'</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>原价</td>'+
                		'<td><i class="price">'+data.price+'</i>元</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>实际支付</td>'+
                		'<td><i class="price">'+data.realPrice+'</i>元</td>'+
                	'</tr>'+
                	'<tr>'+
                		'<td>时段与地点</td>'+
                		'<td>'+
                			'<table>'+getTimes(data.info)+'</table>'
                		'</td>'+
                	'</tr>';
            return str;
        });

    }
	function getTimes( time ){
        var str = '';
        for( var i = 0, len = time.length;i<len;i++ ){
        	time[i].time=parseInt(time[i].time);
			str +='<tr>'+
					'<td>'+time[i].date+'</td>'+
					'<td>'+(time[i].time)+':00-'+(parseInt(time[i].time)+1)+':00 '+'</td>'+
					'<td>'+time[i].place+'</td>'+
					'<td><i class="price">'+time[i].price+'</i>元</td>'+
					'</tr>';
        }
        return str;
    }
	//处理申诉
	$('#data').delegate('.shensu_chuli','click',function(){
		$('.sure').attr('data-id',$(this).attr('data-id')).attr('data-dismiss','');
		var tds=$(this).parent().prevAll();
		var str="";
		for(var i=tds.length-1;i>=2;i--){
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
			}
//			else if(i==1){
//				str+="<tr><td>处理时间</td>";
//			}else if(i==0){
//				str+="<tr><td>处理人</td>";
//			}
			str+="<td>"+$(tds[i]).text()+"</td></tr>";
			
		}
		$('.chuli').html(str);
	});

	$('#data').delegate('.addr','mouseover',function(){
		$('.allInfo').css('display','block').html($('.addr').html());
	});
	$('#data').delegate('.addr','mouseout',function(){
		$('.allInfo').css('display','none').html($('.addr').html());
	});
});
