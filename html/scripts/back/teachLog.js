$(document).ready(function(){
    param={
        T:$('#template').html(),
        target:'data',
        dealData:function(data){
            for(x in data){
                if(data[x].content!=""){
                    data[x]['content_float']='class="content-float"';
                    data[x]['real_content']='class="real-content"';
                }
            }
            return data;
        }
    }
    PAGER.init(param);
    $('#download').on('click',function () {
        var param=PAGER.param;
        window.open('downTeachLog',$.param(param),'_blank');
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