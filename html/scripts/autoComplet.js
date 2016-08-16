$(document).ready(function(){
	$('.autoComplet').on('keyup',function(){
		var key=this.value;
		var parent=$(this).parent()[0];
		if (key.length==0){
			var id=$(this).attr('id');
			$('#'+id+'Id').val('0');
			$('.completeList').html(' ');
			return;
		}
		$.web('/back/user/userid',{key:key},function(item){
			var str = '';
			for( var i  = 0,len=item.length;i<len;i++ ){
				str += '<li data-id="'+item[i].id+'">'+item[i].name+'</li>';
			}
			$('.completeList',parent).html('').append( str);
		},'get');
	})

	$(document).on('click','.completeList li',function(){
		var target=$(this).parent().attr('data-target');
		$(this).parent().html('');
		$('#'+target).val($(this).text());
		$('#'+target+'Id').val($(this).attr('data-id'));
	});
	$('form').delegate('input','focus',function(){
		$(this).parent().parent().parent().siblings().find('.completeList').html("");
	});
});