$(document).ready(function(){
    var content = $('#detailContent');
    var cancelContent = $('#cancelContent');
    var url = '/back/order/order/';
    $(document).on('click','#getDetail',function(){
        var url = '/back/order/order/';
        doOperation( $(this).attr('data-id'),url,'get','get');
    });
    $(document).on('click','#getCancel',function(){
        $('#cancelBtn').attr('data-id',$(this).attr('data-id'))
        doOperation( $(this).attr('data-id'),url,'get','delete');
    });
    $(document).on('click','#cancelBtn',function(){
        var id=$(this).attr('data-id');
        var data = {
            tea:$('#tea').val(),
            stu:$('#stu').val()
        }
        doOperation( data,url+id,'delete','delete' );
    });

    function doOperation( data,url,method,type){
        var _url;
        if( method != 'delete'){
            _url = url+data;
            data = {};
        }else{
            _url = url;
        }
        $.web(_url,data,function(item){
            if( method == 'get' ){
                getContent(item,type);
            } else{
               alert('操作成功！');
               $('.close').trigger('click');
               PAGER.loadPage();
            }
        },method);
    }

    function getContent( data,way ){
        var target;
        if( way != 'delete' ){
            target = content;
        }else{
            target = cancelContent;
        }
        target.find('table').html(' ').append(function(){
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
        target.find('.cancelInfo').html('').append(function(){
        	var cancle="";
        	if( way == 'delete' ){
        		$('.price').css('color','#e94c3b');
				cancle += "<li class='cancelLi'><i><img src='/images/tuihuan.png'/></i><div class='cancel'>" +
	            "<p>请输入需要退还金额：<input type='text' id='stu'></p>" +
	            "<p>&nbsp;&nbsp;&nbsp;教练所得金额：<input type='text' id='tea'></p>"+
	                "<div class='btn-group clearfix cancelorder'><button class='btn  btn-primary' style='margin-right:50px;border-radius: 4px;' id='cancelBtn' data-id="+data.id+">确定</button>"+
	                " <button type=\"button\" class=\"btn btn-danger \" style='border-radius: 4px;' data-dismiss=\"modal\">取消</button></div>"+
	            "</div></li>"
            }
        	return cancle;
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
});
