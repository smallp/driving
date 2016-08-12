function del(id) {
	$.web('/back/question/kind/'+id,'',function(e){
		alert('删除成功！');
		location.reload();
	},'delete');
}

$(function () {
	$('#addSub').click(function(){
		var id=$(this).data('id');
		var methon=id==0?'post':'put';
		$.web('/back/question/kind/'+id,$('form').serialize(),function (e) {
			alert('提交成功！');
			location.reload();
		},methon);
	});

	$('#add').click(function(){
		$('#addSub').data('id',0);
		$('#title').html('添加题目');
		$('form')[0].reset();
	});
	$('.js-mod').click(function(){
		var obj=$(this);
		var id=obj.data('id');
		obj=obj.parent().parent();
		$('#detail').modal('show');
		$('#addSub').data('id',id);
		$('#title').html('修改专题');
		$('#name').val(obj.find('.name').html());
		$('#type').val(obj.find('.type').html()=='科目一'?0:1);
	});
})
