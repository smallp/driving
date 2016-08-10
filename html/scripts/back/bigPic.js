$(function() {
    $(document).on('click','.js-bigImg',function() {
        var imgSrc = $(this).attr('src'); //取得img的src
        var str = '<img src="' + src + '" alt="原图" />';
        $('#orgPic').html(str);
    });
});
