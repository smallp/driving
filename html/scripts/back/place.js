function doSearch() {
	var key=$('#place').val();
	if (key.length==0) PAGER.param.key=null;
	else PAGER.param.key=key;
	location.href='#1';
	PAGER.loadPage();
};
$(document).ready(function(){
	param={
		T:$('#template').html(),
		dealData:function(data){
			for(x in data){
				data[x].status=data[x].status==1?'显示':'隐藏';
			}
			return data;
		}
	}
	PAGER.init(param);
	$('tbody').on('click','.btnhide',function(){
		var id=$(this).attr('data-id');
		id=[id];
		$.web('',{id:id},function(){alert('操作成功！');PAGER.loadPage()},'delete');
	});
    $('#checkAll').on('change', function () {
        if ($(this).is(':checked')) {
            $('tbody').find('input[type=checkbox]').prop('checked',true);
        } else {
            $('tbody').find('input[type=checkbox]').prop('checked', false);
        }
    });
    $('#all').on('click',function(){
    	var arr=[];
    	$('tbody').find('input:checked').each(function() {
			arr.push($(this).attr('data-id'));
		});
    	$.web('',{id:arr},function(){alert('操作成功！');PAGER.loadPage()},'delete');
    });
    $('tbody').on('click','.J-change',function(){
        $("html,body").animate({scrollTop:"1200px"}, 500);
        $.web('/back/info/place/'+$(this).attr('data-id'),'',function( item ){
          window.data.fillForm($('#form')[0],item);
	      $('#pic').html(function(){
	          var str = "";
	          for( var i = 0,len = item.pics.length;i<len;i++ ){
	              str += '<div style="display: block;float:left;width:150px;"><img src="'+item.pics[i].url+'" alt="" style="width:150px;height:100px;"><p style="text-align: center;width:150px;">'+item.pics[i].intro+'</p><a class="btn btn-primary js-pic-del">删除</a></div>';
	          }
	          return str;
	      });
          $('#new_lng').val(item.lng);
          $('#new_lat').val(item.lat);
          $('#lng').val(item.lng);
          $('#lat').val(item.lat);
          map.clearOverlays();  //清除标注
          var point = new BMap.Point(item.lng, item.lat);
          map.centerAndZoom(point, 12);
          var marker = new BMap.Marker(point);  // 创建标注
          map.addOverlay(marker);               // 将标注添加到地图中
	    },'get');
    });
    $('#addSub').on('click',function(){
        $('#pic').append('<div style="display: block;float:left;width:150px;"><img src="'+$('#picUrl').val()+'" alt="" style="width:150px;height:100px;"><p style="text-align: center;width:150px;">'+$('#picDes').val()+'</p><a class="btn btn-primary js-pic-del">删除</a></div>');
    });
    $('#pic').on('click','.js-pic-del',function(){
    	$(this).parent().remove();
    });
    $('#subData').on('click',function(){
    	var arr=[];
    	$('#pic').find('div').each(function(){
    		var obj=$(this);
    		var src=obj.find('img')[0].src;
    		var intro=obj.find('p').html();
    		arr.push({url:src,intro:intro});
    	});
    	$('#pics').val(JSON.stringify(arr));
    	var id=$('#id').val();
    	if (id==''){
    		$.web('',$('#form').serialize(),function(){
    			alert('添加成功！');
    		});
    	}else{
    		$.web('/back/info/place/'+id,$('#form').serialize(),function(){
    			alert('修改成功！');
    		},'put');
    	}
    });
    $('#cancle').on('click',function(){
        $('#form')[0].reset();
        $('#pic').html('');
    });
});