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
                        "<button type=\"button\" class=\"close close_btn\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>" +
                        "<h4><i class='detail-btn'></i>查看详情</h4> " +
                        "</div> " +
                        "<div class='modal-parent'>"+
                        "<div class=\"modal-body judge\">" +
                        "<p><span>发布人：</span><span>"+item.stu+"</span></p>"+
                        "<p><span>时间：</span><span>" + data.getTime(item.time) + "</span></p>" +
                        "<p><span>教练：</span><span>"+item.tea+"</span></p>"+
                        "<p><span>学员：</span><span>"+item.tea+"</span></p>"+
                        "<p><span>描述相符：</span><span class='star "+des+"'></span></p>" +
                        "<p><span>教学质量：</span><span class='star "+qua+"'></span></p>" +
                        "<p><span>服务态度：</span><span class='star "+att+"'></span></p>" +
                        "<p><span>满时教学：</span><span class='star "+tea+"'></span></p>" +
                        "<p><span>正文:</span><textarea>"+item.content+"</textarea></p>" +
                        "<ul class=\"clearfix\"> " + data.getPic(item.pics) + "</ul>"+
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