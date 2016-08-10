$(document).ready(function() {
    $('.js-pic').on('change', function() {
        var self=$(this);
        var tar=self.attr('data-target');
        var pic=self.attr('data-pic');
        $('#picInfo').html('图片上传中，请稍候...');
        $.ajax({
            url: '/back/back/qiniu',
            method: 'get',
            data: {},
            dataType: 'json',
            success: function(item) {
                var token = item.token;
                var formData = new FormData();
                formData.append('file',self[0].files[0]);
                formData.append('token', token);
                $.ajax({
                    url: 'http://upload.qiniu.com/',
                    type: 'post',
                    cache: false,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(item) {
                    	$('#picInfo').html('');
                        src = 'http://7xtdgv.com2.z0.glb.qiniucdn.com/' + item.key;
                        $('#'+pic).attr('src', src).css({
                            'width': '300px'
                        }, {
                            'height': '200px'
                        });
                        $('#'+tar).val(src);
                        alert('图片上传成功！');
                    },
                    error: function(err) {
                        alert('上传出错！');
                    }
                });
            },
            error: function(err) {
                alert(err.info);
            }
        });
    });
});