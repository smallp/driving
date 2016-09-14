$(document).ready(function(){
	param={
		T:$('#template').html(),
		target:'data',
    	dealData:function(data){
    		var status={'5':'待处理','6':'已处理'};
//    		var kind={'1':'科目二','2':'科目三','4':'陪练陪驾'};
    		for(x in data){
    			data[x].place=data[x].place?data[x].place:'';
    			data[x].oprator=data[x].oprator?data[x].oprator:'';
        		if (data[x].status==5)
        			data[x].option='<button class="btn btn-danger btn-sm js-del" data-toggle="modal" data-target="#shensu_chuli" data-id="'+data[x].logId+'"><i class="cacel-btn"></i>处理</button>';
        		else data[x].option='';
        		data[x].status=status[data[x].status];
        		data[x].orderTime=window.data.orderTime(data[x].orderTime);
				(data[x].place=="")&&(data[x].paddress="");
				(data[x].partner==null)&&(data[x].partner="");
        		if(data[x].dealTime!=0){
        			var dtime=window.data.getTime(data[x].dealTime);
        			var idx=dtime.indexOf(" ");
        			var dyear=dtime.substring(0,idx);
        			var driqi=dtime.substring(idx+1);
        			data[x].dealTime=dyear+"<br>"+driqi;
        		}else{
        			data[x].dealTime="";
        		}
        		var time=data[x].time;
        		var year=time.slice(0,10);
        		var riqi=time.substring(11);
        		data[x].time=year+"<br>"+riqi;
    		}
    		return data;
    	}
	}
	PAGER.init(param);
	//订单详情
	$('#data').on('click','.shensu_detail',function(){
		var url = '/back/order/order/';
        doOperation( $(this).attr('data-id'),url,'get','get');
	});
	//处理申诉
	var clickNum=0,$map;//创建点击次数以及map的html;
	$('#data').on('click','.js-del',function(){
		var newMap;//创建地图实例变量
		var id=$(this).attr('data-id');
		$('#sure').attr('data-id',id);
		var data=PAGER.getRow('logId',id);
		var str="";
		str="<tr><td>申诉者</td><td>"+data.upName+"</td></tr>"+
			"<tr><td>申述时间</td><td>"+data.time.replace('<br>',' ')+"</td></tr>"+
			"<tr><td>申述地点</td><td>"+data.address+"</td></tr>"+
			"<tr><td>场地名称</td><td>"+data.place+"</td></tr>"+
			"<tr><td>场地地点</td><td>"+data.paddress+"</td></tr>"+
			"<tr><td>地图显示</td><td><div id='map'></div></td></tr>"+
			"<tr><td>学员</td><td>"+data.stu+"</td></tr>"+
			"<tr><td>教练</td><td>"+data.tea+"</td></tr>"+
			"<tr><td>拼教练同伴</td><td>"+data.partner+"</td></tr>"+
			"<tr><td>预约时间</td><td>"+data.orderTime+"</td></tr>"+
			"<tr><td>费用</td><td>"+data.priceTea+"</td></tr>"+
			"<tr><td>实际支付</td><td>"+data.price+"</td></tr>";
		$('.chuli').html(str);
		if(clickNum==0){
			newMap=new BMap.Map("map");//创建地图实例
			mapShow(newMap,data.place,data.lng,data.lat,data.plng,data.plat);
			$map=$('#map');//保存创建的地图；
		}else{
			$('#map').html($map);
		}
		clickNum++;
	});
	//地图	
	function mapShow(map,place,pointSX,pointSY,pointCX,pointCY){
		if(place==""){
			pointCX=null;
			pointCY=null;
		}
		//展示地图
		var point=new BMap.Point(pointSX,pointSY);//创建点坐标
		map.centerAndZoom(point,18);//初始化地图
		//map放在table中无法居中。需要做地图偏移，数值为像素
		map.panBy(220,100);
		map.enableScrollWheelZoom(true);//允许鼠标滚动缩放
		//申诉点
		var marker1=new BMap.Marker(point);//创建标注
		map.addOverlay(marker1);//添加标注
//		marker1.setAnimation(BMAP_ANIMATION_BOUNCE);//标注跳动
		var label = new BMap.Label("申诉点",{offset:new BMap.Size(20,-10)});
		marker1.setLabel(label);
		//场地点
		if(pointCX!==null&&pointCY!==null){
			var marker2=new BMap.Marker(new BMap.Point(pointCX,pointCY));//创建标注
			map.addOverlay(marker2);//添加标注
//			marker2.setAnimation(BMAP_ANIMATION_BOUNCE);//标注跳动
			var label = new BMap.Label("场地点",{offset:new BMap.Size(20,-10)});
			marker2.setLabel(label);
		}
	}
	//地址展示
	$('#data').on('mouseover','.addr',function(){
		$('.allInfo').css('display','block').html($('.addr').html());
	});
	$('#data').on('mouseout','.addr',function(){
		$('.allInfo').css('display','none').html($('.addr').html());
	});
	
	$('#sure').click(function(){
		var tea=$('#tea').val(),stu=$('#stu').val();
		if (tea.length==0||stu.length==0||isNaN(tea)||isNaN(stu)){
			alert('请检查参数！');
			return false;
		}
		$.web('/back/order/ComplainDeal/'+$(this).data('id'),{tea:tea,stu:stu},function(){
			$('#tea').val('');
			$('#stu').val('');
			$('#shensu_chuli').modal('hide');
			PAGER.loadPage();
			alert('处理完成');
		});
	});
	
});
function doOperation( data,url,method,type){
    var _url= url+data;
        data = {};
    $.web(_url,data,function(item){
        if( method == 'get' ){
            getContent(item,type);
        } 
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
