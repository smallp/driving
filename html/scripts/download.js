function download(url){
    var begin=$('#begin').val();
    var end=$('#end').val();
    if (begin.length==0||end.length==0){
        alert('请设定时间！');
        return;
    }
    url='/back/export/'+url+'?begin='+begin.substr(0,10)+'&end='+end.substr(0,10);
    window.open(url,'_blank');
}