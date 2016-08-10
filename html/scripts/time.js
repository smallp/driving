$(function(){
    $('.form_datetime').datetimepicker({
        language:  'zh-CN',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        startView: 2,
        minView: 2,
        forceParse: 0,
        format:'yyyy-mm-dd'
    });
    $('#time').on('click',function(){
    	var begin=$('#begin').val();
    	var end=$('#end').val();
    	if (begin==''||end==''){
    		delete PAGER.param.begin;
    		delete PAGER.param.end;
    	}else{
    		PAGER.param.begin=begin;
    		PAGER.param.end=end;
    	}
		location.href='#1';
    	PAGER.loadPage();
    });
});