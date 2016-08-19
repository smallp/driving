var seeds = (function () {
    return {
        init: function () {
            var self = this;
            $(document).on('click', '.js-del', function () {
                $('#subBtn').data('id',$(this).data('id'));
            });
            $(document).on('click', '#detailBtn', function () {
                $.ajax({
                    url: '/back/user/seeds/' + $(this).attr('data-id'),
                    dataType: 'JSON',
                    method: 'GET',
                    success: function (item) {
                        console.log(item);
                        $('#detailContent').html(' ').append(function () {
                            var str = "";
//                          str = "<div class=\"modal-header modal-header-css\" style=\"border:none;\">" +
//                              "<button type=\"button\" class=\"close close_btn\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>" +
//                              "<h4>" + item.name + "</h4> " +
//                              "</div> " +
////                              "<div class='modal-parent'>"+
//                              "<div class=\"modal-body modal-body-css\">" +
//                              "<p><span>" + data.getTime(item.time) + "</span> <span><i class='dianzan'></i>点赞(" + item.praise + ")</span></p>" +
//                              "<p>" + item.content + "</p> " +
//                              "<ul class=\"clearfix\"> " + data.getPic(item.pics) + "</ul>"
							str = "<div class=\"modal-header modal-header-css\" style=\"border:none;\">" +
                                "<button type=\"button\" class=\"close close_btn\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>" +
                                "<h4><i class='detail-btn'></i>驾友圈详情</h4> " +
                                "</div> " +
                                "<div class='modal-parent'>"+
                                "<div class=\"modal-body\">" +
                                "<p>"+item.name+"</p>"+
                                "<p><span>" + data.getTime(item.time) + "</span> <span><i class='dianzan'></i>点赞(" + item.praise + ")</span></p>" +
                                "<p>" + item.content + "</p> " +
                                "<ul class=\"clearfix\"> " + data.getPic(item.pics) + "</ul>"
                            return str;
                        })
                    }
                });
            });

            $(document).on('click', '#subBtn', function () { //删除
                self.deleteData([$(this).data('id')], '/back/user/seed', 'delete');
            });

            $(document).on('change', '#checkAll', function () {
                if ($(this).is(':checked')) {
                    $(this).parent().parent().parent().next().find('input[type=checkbox]').prop('checked', 'checked');
                } else {
                    $(this).parent().parent().parent().next().find('input[type=checkbox]').attr('checked', false);
                }
            });

            $('#all').on('click', function () {
            	var all=[];
            	$('#data').find('input:checked').each(function() {
					all.push($(this).data('id'));
				});
            	if (all.length==0) return;
                self.deleteData(all, '/back/user/seed', 'delete');
            });

            $('#time').on('click', function () {
            	var key=$('#userId').val();
        		if (key==0) delete PAGER.param.user;
        		else PAGER.param.user=key;
        		key=$('#content').val();
        		if (key.length==0) delete PAGER.param.key;
        		else PAGER.param.key=key;
            });
        },
        deleteData: function (data, url, method) {
            var self = this;
            $('#seeds').find('input[type=checkbox]').each(function () {
                if ($(this).is(':checked')) {
                    if (!isNaN($(this).val())) {
                        data.push($(this).val());
                    }
                }
            });
            if (data.length === 0) {
                return false;
            } else {
                $.ajax({
                    url: url,
                    method: method,
                    data: {
                        id: data
                    },
                    dataType: "JSON",
                    success: function () {
                    	alert('操作成功！');
                    	PAGER.init(param);
                        $('#close').trigger('click');
                    }
                });
            }
        }
    }
}());

$(document).ready(function () {
	param={
		T:$('#template').html(),
		dealData:function(data){
			for(x in data){
				data[x].time=window.data.getTime(data[x].time);
			}
			return data;
		},
		target:'data'
	}
	PAGER.init(param);
    seeds.init();
});