function doSearch() {
	var key=$('#key').val();
	if (key.length==0) PAGER.param.key=null;
	else PAGER.param.key=key;
	location.href='#1';
	PAGER.loadPage();
};
$(document).ready(function() {
    param = {
        T: '<tr><td>{name}</td><td>{address}</td><td>{place}</td><td>{car}</td><td>{teacher}</td>' +
            '<td><button class="btn btn-sm btn-primary js-edit" data-toggle="modal" data-target="#addSchool" data-id="{id}"><i class="detail-btn"></i>修改</button></td>' +
            '<td><button class="btn btn-sm btn-danger js-del" data-toggle="modal" data-target="#del" data-id="{id}"><i class="delete-btn"></i>删除</button></td></tr>'
    }
    PAGER.init(param);
    $('tbody').on('click','.js-del',function(){
    	$('#btnDel').data('id',$(this).data('id'));
    });
    $('tbody').on('click','.js-edit',function(){
    	var id=$(this).data('id');
    	$('#btnSub').data('id',id);
    	$.web('/back/info/school/'+id,'',function(info){
    		window.data.fillForm($('form')[0],info);
    	},'get');
    });
    $('#btnAdd').on('click',function(){
    	$('form')[0].reset();
    	$('#btnSub').data('id',0);
    });
    $('#btnDel').on('click',function(){
    	var id=$(this).data('id');
    	$.web('/back/info/school/'+id,'',function(){
    		alert('删除成功！');
    		$('#del').modal('hide');
    		PAGER.loadPage();
    	},'delete');
    });
    $('#btnSub').on('click',function(){
    	var id=$(this).data('id');
    	if (id==0){
    		$.web('',$('form').serialize(),function(){
        		alert('添加成功！');
        		$('#addSchool').modal('hide');
        		PAGER.loadPage();
        	});
    	}else{
    		$.web('/back/info/school/'+id,$('form').serialize(),function(){
        		alert('修改成功！');
        		$('#addSchool').modal('hide');
        		PAGER.loadPage();
        	},'put');
    	}
    });
});