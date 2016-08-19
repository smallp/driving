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
        target.find('ul').html(' ').append(function(){
            var str = " <li><span>学员姓名:</span><i>&nbsp;"+data.stu+"</i></li> " +
                "<li><span>学员电话:</span> <i>"+data.stuTel+" </i></li> " +
                "<li><span>教练:</span> <i>"+data.tea+" </i></li>" +
                "<li><span>教练电话:</span> <i>"+data.teaTel+" </i></li> " +
                "<li><span>原价:</span> <i> "+data.price+"</i></li>"+
                "<li><span>实际支付:</span> <i> "+data.realPrice+"</i></li>"+
                "<li><span>时段与地点:</span>"+
	                "<ul class='time_place'>"+
	                "<li><i class='contenTime'>"+getTimes(data.info)+" </i></li>"+
	                "</ul>"+
                "</li> ";
            if( way == 'delete' ){
//              str += "<li class='cancelLi'><div class='cancel'>" +
//                  "<p> <i class=\"tuihuan\"></i>请输入需要退还金额：<input type='text' id='stu'></p>" +
//                  "<p style='padding-left:106px;'>教练所得金额：<input type='text' id='tea'></p>"+
//                      "<div class='btn-group clearfix'><button class='btn btn-sm btn-primary' style='margin-right:152px;border-radius: 4px;' id='cancelBtn' data-id="+data.id+">确定</button>"+
//                      " <button type=\"button\" class=\"btn btn-danger btn-sm\" style='border-radius: 4px;' data-dismiss=\"modal\">取消</button></div>"+
//                  "</div></li>"
					str += "<li class='cancelLi'><i><img src='/images/tuihuan.png'/></i><div class='cancel'>" +
                    "<p>请输入需要退还金额：<input type='text' id='stu'></p>" +
                    "<p>&nbsp;&nbsp;&nbsp;教练所得金额：<input type='text' id='tea'></p>"+
                        "<div class='btn-group clearfix cancelorder'><button class='btn  btn-primary' style='margin-right:50px;border-radius: 4px;' id='cancelBtn' data-id="+data.id+">确定</button>"+
                        " <button type=\"button\" class=\"btn btn-danger \" style='border-radius: 4px;' data-dismiss=\"modal\">取消</button></div>"+
                    "</div></li>"
            }
            return str;
        });

    }

    function getTimes( time ){
        var str = '';
        for( var i = 0, len = time.length;i<len;i++ ){
        	time[i].time=parseInt(time[i].time);
//          str += time[i].date+"&nbsp;&nbsp;&nbsp;&nbsp;"+time[i].place+"&nbsp;&nbsp;&nbsp;&nbsp;"+time[i].price+"元<br />";
			str += time[i].date+'&nbsp;'+(time[i].time)+':00-'+(time[i].time+1)+':00 '+"&nbsp;&nbsp;&nbsp;&nbsp;"+time[i].place+"&nbsp;&nbsp;&nbsp;&nbsp;"+time[i].price+"元<br />";
        }
        return str;
    }
});
