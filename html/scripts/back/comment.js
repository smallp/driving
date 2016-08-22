$(document).ready(function () {
	param={
		T:$('#template').html(),
		target:'data',
		dealData:function(data){
			for(x in data){
	    		data[x].time=window.data.getTime(data[x].time);
			}
			return data;
		}
	}
	PAGER.init(param);
	$('#data').on('click', '.js-detail', function () {
        $.ajax({
            url: '/back/order/comment/' + $(this).attr('data-id'),
            dataType: 'JSON',
            method: 'GET',
            success: function (item) {
                $('#detailContent').html(' ').append(function () {
                    var str = "";
                    var des,qua,att,tea;
                    console.log(item.describe,item.quality,item.attitude,item.teachTime);
                    des=item.describe==1?"oneStar":item.describe==2?"twoStar":item.describe==3?"threeStar":item.describe==4?"fourStar":item.describe==5?"fiveStar":"star";
                    qua=item.quality==1?"oneStar":item.quality==2?"twoStar":item.quality==3?"threeStar":item.quality==4?"fourStar":item.quality==5?"fiveStar":"star";
                    att=item.attitude==1?"oneStar":item.attitude==2?"twoStar":item.attitude==3?"threeStar":item.attitude==4?"fourStar":item.attitude==5?"fiveStar":"star";
                    tea=item.teachTime==1?"oneStar":item.teachTime==2?"twoStar":item.teachTime==3?"threeStar":item.teachTime==4?"fourStar":item.teachTime==5?"fiveStar":"star";
                     str = "<div class=\"modal-header modal-header-css\" style=\"border:none;\">" +
                        "<button type=\"button\" class=\"close_btn\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>" +
                        "<h4><i class='detail-btn'></i>查看详情</h4> " +
                        "</div>"+
                        "<div class='modal-parent'>"+
                        "<div class=\"modal-body judge\">" +
                        "<table class='tableStyle'>"+
	                        "<tr><td>发布人</td><td>"+item.stu+"</td></tr>"+
	                        "<tr><td>时间</td><td>" + data.getTime(item.time) + "</td></tr>" +
	                        "<tr><td>教练</td><td>"+item.tea+"</td></tr>"+
	                        "<tr><td>学员</td><td>"+item.tea+"</td></tr>"+
	                        "<tr><td>描述相符</td><td><span class='star "+des+"'></span></td></tr>" +
	                        "<tr><td>教学质量</td><td><span class='star "+qua+"'></td></tr>" +
	                        "<tr><td>服务态度</td><td><span class='star "+att+"'></td></tr>" +
	                        "<tr><td>满时教学</td><td><span class='star "+tea+"'></td></tr>" +
	                        "<tr><td>评价内容</td><td>"+item.content+"</td></tr>" +
	                        "<tr><td>相关图片</td><td>"+data.getPic(item.pics)+"</td></tr>" +
                        "</table>"+
                        "</div>"+
                        "<div class='modal-footer modal-footer-css'>"+
                		"<button type='button' class='btn btn-primary' data-dismiss='modal'>确定</button>"+
                		"<button type='button' class='btn btn-danger' data-dismiss='modal'>取消</button>"+
            			"</div>"+
            			"</div>";
                    console.log(item.describe);
                    return str;
                })
            }
        });
  });
    $('#data').on('click', '.js-del', function () { //删除
    	var id=$(this).data('id');
    	$('#btnDel').data('id',id);
    });
    $('#btnDel').on('click',function(){
    	var id=[$(this).data('id')];
    	$.web('',{id:id},function(){alert('操作成功！');$('#delete').modal('hide');PAGER.loadPage();},'delete');
    });
    
    $('#checkAll').on('change', function () {
        if ($(this).is(':checked')) {
            $('#data').find('input[type=checkbox]').prop('checked',true);
        } else {
            $('#data').find('input[type=checkbox]').prop('checked', false);
        }
    });
    $('#all').on('click',function(){
    	var arr=[];
    	$('#data').find('input:checked').each(function() {
			arr.push($(this).attr('data-id'));
		});
    	$.web('',{id:arr},function(){alert('操作成功！');PAGER.loadPage()},'delete');
    });
    $('#time').on('click',function(){
		var key=$('#studentId').val();
		if (key==0)
			delete PAGER.param.stu;
		else PAGER.param.stu=key;
		key=$('#teacherId').val();
		if (key==0)
			delete PAGER.param.tea;
		else PAGER.param.tea=key;
    });
});