$(document).ready(function(){
    var url=location.pathname.trimRight('/')+'/';
    $('tbody').on('click', '.js-del', function() {
        var id = $(this).attr('data-id');
        $.web(url+id,'',function() {
            PAGER.loadPage();
        },'delete');
    });
    $('tbody').on('click', '.js-mod', function() {
        var id = $(this).attr('data-id');
        $.web(url+id,'',function(data) {
            PAGER.loadPage();
        },'get');
    });
});
