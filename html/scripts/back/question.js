function doSearch() {
    var key = $('#key').val();
    if (key.length == 0) delete PAGER.param.key;
    else PAGER.param.key = key;
    location.href = '#1';
    PAGER.loadPage();
};
var question = (function () {
    return {
        init: function () {
            var self = this;
            var page = 0;
            $(document).on('click', '#deleteBtn', function () { //删除按钮赋值
                $('#deleteSub').attr('data-id', $(this).attr('data-id'));
            });
            $("#deleteSub").click(function(){
            	var idArr=[];
                idArr.push($('#deleteSub').attr('data-id'));
                self.getDelete(idArr,'','delete');
            });
            $(document).on('click', '#detailBtn', function () { //查看详情
                self.getDetail($(this).attr('data-id'), '/back/question/question/', 'GET');
            });

            $(document).on('click', '#changeBtn', function () { //修改操作
                self.changeData($(this).attr('data-id'), '/back/info/school/', 'put');
            });

            $(document).on('change', '#checkAll', function () {
                if ($(this).is(':checked')) {
                    $(this).parent().parent().parent().next().find('input[type=checkbox]').prop('checked', 'checked');
                } else {
                    $(this).parent().parent().parent().next().find('input[type=checkbox]').attr('checked', false);
                }
            }); //批量删除

            $(document).on('click', '#addSub', function () { //添加操作
            	$('#title').html('添加题目');
                $('#tm_content').html('');
                $('#tm_analy').html('');
                $('#tm')[0].reset();
                $('#subTm').data('id',0);
            });
            $('#all').on('click', function () {
            	var ids=[];
                $('tbody').find('input[type=checkbox]').each(function () {
                    if ($(this).is(':checked')) {
                        ids.push($(this).val());
                    }
                });
                if (ids.length === 0) {
                	alert('请至少选择一个题目!');
                    return false;
                }
                self.getDelete(ids,'','delete');
            });
            
        },
        getDelete: function (data, url, method) { //删除请求
            $.ajax({
                url: location.pathname,
                method: method,
                data: {id:data},
                dataType: 'JSON',
                success: function (suc) {
                	alert("删除成功！！！");
                    $('#delete').modal('hide');
                    PAGER.loadPage();
                }
            });
        },
        getDetail: function (data, url, method) { //查看详情
        	$('#tm')[0].reset();
        	$('#title').html('修改题目');
        	$('#subTm').attr('data-id',data);
            $.ajax({
                url: url + data,
                dataType: 'JSON',
                method: method,
                success: function (item) {
                    $('#tm_content').html(item.content);
                    $('#tm_analy').html(item.analy);
                    $('#kind').val(item.kind);
                    $('#type').val(item.type);
                    var newo=JSON.parse(item.option);
                    $('#opt1').val(newo[0]);
                    $('#opt2').val(newo[1]);                  
                    $('#opt3').val(newo[2]);                   
                    $('#opt4').val(newo[3]);
                    for (var answer=1;answer<=8;answer*=2) {
                		$('.answer[value='+answer+']').prop('checked',(item.answer&answer)>0);
                    }
        			$('#loadPic').attr('src',item.pics);
                }
            });
        }
    }
}());
$(function(){
	param={
        T:'<tr><td><input type="checkbox" value={id}></td><td>{id}</td><td>{content}</td><td>{type}</td><td>{kind}</td>'+
        '<td><button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addQue" id="detailBtn" data-id={id}><i class="detail-btn"></i>修改</button></td>'+
        '<td><button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#delete" id="deleteBtn" data-id={id}><i class="delete-btn"></i>删除</button></td></tr>',
        dealData:function(data){
            for(x in data){
                data[x].type=data[x].type==0?'科目一':'科目四';
            }
            return data;
        }
    }
    PAGER.init(param);
    question.init();
    $('#subTm').on('click',function(){
        var answer = 0;
        var option = []
        $('#checkAnswer').find('input[type=checkbox]').each(function(){
            if($(this).is(':checked')){
                answer += new Number($(this).val());
            }
        });
        $('#checkAnswer').find('input[type=text]').each(function(){
            option.push($(this).val());
        });

        $('#pics').val($('#picUrl').val());
        $('#content').val($('#tm_content').val());
        $('#analy').val($('#tm_analy').val());
        $('#answer').val(answer);
        $('#option').val(JSON.stringify(option));
        var url,method,id=$('#subTm').data('id');
        if(id==0){//添加操作
        	url='';
        	method='post';
        }else{
        	url='/back/question/question/'+id;
        	method='put';
        }
        $.web(url,$('#tm').serialize(),function(item){
            alert('提交成功！');
            $('#addQue').modal('hide');
            PAGER.loadPage();
        });
    });
})
