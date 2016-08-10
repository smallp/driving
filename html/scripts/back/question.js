function doSearch() {
    var key = $('#key').val();
    if (key.length == 0) PAGER.param.key = null;
    else PAGER.param.key = key;
    location.href = '#1';
    PAGER.loadPage();
};

var question = (function () {
    window.search = "";
    window.page; //判断是否重新加载页码
    var allId = [];
    return {
        init: function () {
            var self = this;
            var page = 0;

            $(document).on('click', '#deleteBtn', function () { //删除按钮赋值
                $('#deleteSub').attr('data-id', $(this).attr('data-id'));
            });

            $(document).on('click', '#detailBtn', function () { //查看详情
                self.getDetail($(this).attr('data-id'), '/back/question/question/', 'GET');
            });

            $(document).on('click', '#changeBtn', function () { //修改操作
                self.changeData($(this).attr('data-id'), '/back/info/school/', 'put');
            });

            $(document).on('click', '#deleteSub', function () { //删除复制id
                allId.push($(this).attr('data-id'));
                self.getDelete(allId, ' /back/question/question/', 'delete');

            });

            $(document).on('change', '#checkAll', function () {
                if ($(this).is(':checked')) {
                    $(this).parent().parent().parent().next().find('input[type=checkbox]').prop('checked', 'checked');
                } else {
                    $(this).parent().parent().parent().next().find('input[type=checkbox]').attr('checked', false);
                }
            }); //批量删除

            $(document).on('click', '#addSub', function () { //添加操作
                var data = {}
                self.addData(data, '/back/info/school', 'post');
            });
            $('#all').on('click', function () {
                $('#tm').find('input[type=checkbox]').each(function () {
                    if ($(this).is(':checked')) {
                        if (!isNaN($(this).val())) {
                            allId.push($(this).val());
                        }
                    }
                });
                if (allId.length === 0) {
                    return false;
                }
                $.ajax({
                    url: '/back/question/question',
                    method: 'delete',
                    data: {id: allId},
                    dataType: "JSON",
                    success: function () {
                        self.getData(page, window.content);
                    },
                    error: function (err) {
                        console.log(err);
                    }
                });
                allId = [];
            });
        },
        addData: function (data, url, method) {

        },
        changeData: function (data, url, method) { //修改数据
            var self = this;
            $.ajax({
                url: url + data,
                data: {},
                method: method,
                dataType: 'JSON',
                success: function (item) {

                },
                error: function (err) {

                }
            });
        },
        getDelete: function (data, url, method) { //删除请求
            var self = this;
            $.ajax({
                url: url + data,
                method: method,
                data: {},
                dataType: 'JSON',
                success: function (suc) {
                    self.getData(0, "");
                    $('#close').trigger('click');
                    allId = [];
                },
                error: function (err) {
                    $('#close').trigger('click');
                    allId = [];
                    alert(err);
                }
            });
        },
        getDetail: function (data, url, method) { //查看详情
            $.ajax({
                url: url + data,
                dataType: 'JSON',
                method: method,
                success: function (item) {
                    $('#detailContent').html(' ').append(function () {
                        var str;
                        str = "<p>" + item.id + "." + item.name + "</p>" +
                            "<p>" + item.content + "</p>";
                        return str;
                    });
                },
                error: function (err) {
                    alert(err.responseText);
                }
            });
        }
    }
}());