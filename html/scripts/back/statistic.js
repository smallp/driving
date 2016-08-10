var sta = (function() {

    return {
        init: function() {
            $(document).on('click', '.fa', function() {
                var $parentEle = $(this).parent().parent().parent();
                if ($parentEle.hasClass('panel-primary')) {
                    $parentEle.find('.fa').each(function() {
                        $(this).css({ 'background': '#1abc9c', 'color': '#fff' });
                    });
                    $(this).css({ 'background': '#fff', 'color': '#1abc9c' });
                }

                if ($parentEle.hasClass('panel-danger')) {
                    $parentEle.find('.fa').each(function() {
                        $(this).css({ 'background': '#f29489', 'color': '#fff' });
                    });
                    $(this).css({ 'background': '#fff', 'color': '#f29489' });
                }

                if ($parentEle.hasClass('panel-info')) {
                    $parentEle.find('.fa').each(function() {
                        $(this).css({ 'background': '#89c7f1', 'color': '#fff' });
                    });
                    $(this).css({ 'background': '#fff', 'color': '#89c7f1' });
                }
            });
        }
    }
}());

$(document).ready(function() {
    sta.init();
});
