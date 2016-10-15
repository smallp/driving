 $(document).ready(function() {
    var naver = $('#naver');
    var pageName = location.href.indexOf('/b');
    pageName = location.href.substr(pageName);
    var thisPage = $('a[href="' + pageName + '"]');
    thisPage.parent().addClass('active');
    thisPage.parent().find('a').addClass('acline');
    thisPage.parent().parent().show();
    // 显示异常消息提示
    $('.oclock').click(function(){
        var $div=$('.mes_content');
        if($div.css('display')=='block'){
            $div.slideUp();
        }else{
            $div.slideDown();
        }
    });
    if($('.yc_total').html()!=='0'){
        $('.yc_total').addClass('yc_y').removeClass('yc_w');
    }else{
        $('.yc_total').addClass('yc_w').removeClass('yc_y');
    }
});
