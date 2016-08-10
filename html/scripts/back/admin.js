var admin = (function() {
    var passwordForm = $('#passwordForm');
    var changeForm = $('#changeForm');
    var addForm = $('#addForm');
    return {
        init: function() {
            var self = this;

            $('tbody').on('click','.js-del',function(){
            	var id=$(this).attr('data-id');
            	$('#btnDel').attr('data-id',id);
            });
            $('tbody').on('click','.js-pwd',function(){
            	var id=$(this).attr('data-id');
            	$('#subPass').attr('data-id',id);
            });
            $('tbody').on('click','.js-mod',function(){
            	var id=$(this).attr('data-id');
            	$.get(location.href+'/'+id,'',function(date){
            		var form=changeForm[0];
            		$('input[type=checkbox]',form).attr('checked',false)
            		for (x in date)
            			form[x].value=date[x];
            		for (x in date['pri'])
            			$('input[value='+date['pri'][x]+']',form)[0].checked=true;
            	})
            	$('#subChange').attr('data-id',id);
            });
            /*
             * 那一串提交
             */
            $('#subPass').on('click', function() {
            	var pwd=$('#pass1').val();
            	if (pwd.length==0){
            		alert('密码为空！');
            		return;
            	}
            	if (pwd!=$('#pass2').val()){
            		alert('输入两次密码不一致！');
            		return;
            	}
            	var id=$(this).attr('data-id');
            	$.web('/back/sa/password/'+id,{password:pwd},function(){
                	$('.close').trigger('click');
                },'put');
            });
            $('#subChange').on('click', function() {
            	var id=$(this).attr('data-id');
            	var pri=self.getPri(changeForm);
            	$('#cpri').val(JSON.stringify(pri));
                $.web(location.href+'/'+id,changeForm.serialize(),function(){
                	location.reload();
                },'put');
            });
            $('#subAdd').on('click', function() {
            	var pwd=$('#addpass1').val();
            	if (pwd.length==0){
            		alert('密码为空！');
            		return;
            	}
            	if (pwd!=$('#addpass2').val()){
            		alert('输入两次密码不一致！');
            		return;
            	}
            	var pri=self.getPri(addForm);
            	$('#pri').val(JSON.stringify(pri));
                $.web("", addForm.serialize(),function(){
                	location.reload();
                });
            });
            $('#btnDel').on('click', function() {
            	var id=$(this).attr('data-id');
                $.web(location.href+'/'+id,'',function(){
                	location.reload();
                },'delete');
            });
        },
        getPri: function(form) {//获取权限
            var arr = [];
            form.find('input[type=checkbox]').each(function() {
                if ($(this).is(':checked')) {
                    arr.push($(this).val());;
                }
            });
            return arr;
        }

    }
}());

$(document).ready(function() {
    admin.init();
});
