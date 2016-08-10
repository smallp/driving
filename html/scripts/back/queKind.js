function del(id) {
	$.web('/back/question/kind/'+id,'',function(){alert('删除成功！');location.reload();},'delete');
}
//修改专题
function change(id){
	
}
//添加专题
$('#addSub').click(function(){
	var subName=$('#subName').val();
	var subClass=$('#subClass').val();
	var subNum=$('#subNum').val();	
	console.log(subName);
});
