function doSearch() {
	var key=$('#place').val();
	if (key.length==0) PAGER.param.key=null;
	else PAGER.param.key=key;
	location.href='#1';
	PAGER.loadPage();
};
function change_status(e){
	PAGER.param.status=e.value;
	PAGER.reload(true);
}
$(document).ready(function(){
	param={
		T:$('#template').html(),
		dealData:function(data){
			for(x in data){
				switch (parseInt(data[x].status)) {
					case -1:
					data[x].status='<span class="label label-primary">待审核</span>';
						break;
					case 1:
					data[x].status='<span class="label label-success">审核通过</span>';
						break;
					case 0:
					data[x].status='<span class="label label-warning">审核失败</span>';
						break;
					default:
						break;
				}
			}
			return data;
		}
	}
	PAGER.init(param);
	var NOWID=0;
	$('#options').hide();
    $('tbody').on('click','.J-change',function(){
        $("html,body").animate({scrollTop:"1200px"}, 500);
        $.web('/back/info/place/'+$(this).attr('data-id'),'',function( item ){
		  window.data.fillForm($('#form')[0],item);
		  item.status==-1?$('#options').show():$('#options').hide();
		  NOWID=item.id;
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
	$('#success').click(function() {
		$.web('/back/info/PlaceStatus/'+NOWID,{status:1},function() {
			alert('操作成功！');
			$('#form')[0].reset();
			$('#pic').html('');
			PAGER.reload();
		},'put');
	})
	$('#cancle').click(function() {
		var reason=$('#reason').val();
		if (reason==''){
			alert('拒绝请输入原因');
			return;
		}
		$.web('/back/info/PlaceStatus/'+NOWID,{status:0,reason:reason},function() {
			alert('操作成功！');
			$('#form')[0].reset();
			$('#pic').html('');
			$('#options').hide();
			PAGER.reload();
		},'put');
	})
});