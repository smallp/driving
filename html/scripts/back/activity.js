$(document).ready(function() {
    $('#subBtn').on('click',function(e){
    	var data=[];var rate=0;
    	$('.option').each(function(){
    		rate+=parseInt($('.rate',this).val());
    		data.push({
    			rate:$('.rate',this).val(),
    			text:$('.text',this).val(),
    			coins:$('.coins',this).val()
			});
    	});
    	if (rate!=100){
    		alert('概率和不等于100！请检查数据');
    		e.stopImmediatePropagation();
    		return false;
    	}
    	$('#option').val(JSON.stringify(data));
    });
});