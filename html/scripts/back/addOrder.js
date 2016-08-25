$(function(){
	$('#cancle').on('click',function(){
		$('input').val('');
	});
	PAGER.dealData=function(data){
		var res=[];
		for(x in data){
			if (data[x].price==-1) continue;
			data[x].time=parseInt(data[x].time);
			data[x].timeStr=data[x].time+':00-'+(data[x].time+1)+':00 ';
			data[x].price+='学车币';
			res.push(data[x]);
		}
		return res;
	};
	PAGER.T=$('#item').html();
	$(document).on('click','.completeList li',function(){
		var target=$(this).parent().attr('data-target');
		$('#'+target+'Name').html($(this).text());
		if (target=='teacher'){
			var id=$(this).attr('data-id');
			PAGER.url='/back/order/availTime/'+id;
			PAGER.loadPage();
		}
	});

	$('#reset').on('click',function(){
		$('#time').empty();
		$('input').val(0);
		$('.autoComplet').val('');
		$('.name').html('');
	});
	
	$('#time').on('click','.js-time',function(){
		var obj=$(this);
		var target=obj.parent().parent();
		if (target.find('select').length!=0) return;
		var param={};
		param.id=$('#teacherId').val();
		param.date=obj.data('date');
		param.time=obj.data('time');
		$.web('/back/order/avaliPlace',param,function(d){
			var str='<select class="place">';
			if (d.length==0) str+='<option value="0">无</option>';
			else for (x in d) {
				str+='<option value="'+d[x].id+'">'+d[x].name+'</option>';
			}
			str+='</select>';
			target.find('.place-holder').html(str)
		},'get');
	});

	$('#btnSub').on('click',function(){
		if ($('#partnerId').val()==0){
			$('#partnerName').html('');
		}
		if ($('#teacherId').val()==0||$('#studentId').val()==0){
			alert('请完善信息！');
			return false;
		}
		var arr=[];
		var info=[];
		$('#time').find('input:checked').each(function(){
			var obj=$(this).parent().parent();
			var str=obj.find('.date').html()+'&nbsp;'+obj.find('.time').html()+'&nbsp;'+obj.find('.price').html()+'&nbsp';
			var item=obj.find('input').data();
			item.place=obj.find('select').val();
			str+=' '+obj.find('option[value='+item.place+']').html();
			arr.push(str);
			info.push(item);
		});
		if (info.length==0){
			alert('请选择一个时段！');
			return false;
		}
		$('#info').val(JSON.stringify(info));
		var str='';
		for ( x in arr) {
			str+='<li>'+arr[x]+'</li>';
		}
		$('#times').html(str);
	});

	$('#btnSure').on('click',function(){
		$.web('/back/order/order',$('form').serialize(),function(d){
			alert('预约成功！');
			$('#sure').modal('hide');
		},'post');
	});
});