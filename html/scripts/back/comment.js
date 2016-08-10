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
                    str = "<div class=\"modal-header\" style=\"border:none;\">" +
                        "<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>" +
                        "<h4>发布人："+item.stu+"</h4> " +
                        "</div> " +
                        "<div class=\"modal-body\">" +
                        "<p><span>" + data.getTime(item.time) + "</span></p>" +
                        "<p>教练："+item.tea+"</p>"+
                        "<p>学员："+item.tea+"</p>"+
                        "<p>描述相符：" + item.describe + "</p> " +
                        "<p>教学质量：" + item.quality + "</p> " +
                        "<p>服务态度：" + item.attitude + "</p> " +
                         "<p>满时教学：" + item.teachTime + "</p> " +
                          "<p>正文：" + item.content + "</p> " +
                        "<ul class=\"clearfix\"> " + data.getPic(item.pics) + "</ul>"
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