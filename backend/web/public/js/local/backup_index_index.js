/**
 * Description 脚本备份主机相关js文件
 */
$(document).ready(function(){
    //数据检索
    $("input[class='input-mini']").keydown(function(event){
        if(event.keyCode == 13){
            location.href = '/backup/index/index?server_ip=' +
                $("input[name='LogSearch[server_ip]']").val() +
                '&serverName=' + $("input[name='LogSearch[serverName]']").val() +
                '&archive_ip=' + $("input[name='LogSearch[archive_ip]']").val() +
                '&status=' + $("select[name='LogSearch[status]']").val();
        }
    });
    $("select[name='LogSearch[status]']").change(function(){
        location.href = '/backup/index/index?server_ip=' +
            $("input[name='LogSearch[server_ip]']").val() +
            '&serverName=' + $("input[name='LogSearch[serverName]']").val() +
            '&archive_ip=' + $("input[name='LogSearch[archive_ip]']").val() +
            '&status=' + $("select[name='LogSearch[status]']").val();
    });
});
