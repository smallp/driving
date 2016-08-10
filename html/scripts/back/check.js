$(function() {
	param = {
	    T: $('#template').html()
	}
	PAGER.init(param);
	$('tbody').on('click', '.js-pass', function() {
	    var id = $(this).attr('data-id');
	    $.web('/back/user/status/'+id, {
	        status: 1
	    }, function() {
	        alert('操作成功！');
	        PAGER.loadPage();
	    }, 'put');
	});
});
