/**
 * Description 脚本备份主机相关js文件
 */
$(document).ready(function(){
    //更新备份服务器
    $('#update_host').on('click',function(){
        $.get("/backup/host/get",{},function(data){
            if(data.status == 1){
                var body = '';
                body += '<div class="back_checkbox">';
                body += '<div class="sel_all"><input type="checkbox" id="sel_all">全选/反选</div>';
                var localHostList = eval($('#localHostList').val());
                for(var key in data.data){
                    if(localHostList.indexOf(data.data[key]['serverIp']) == -1) {
                        body += '<div class="back_input"><input type="checkbox" value="' + data.data[key]['serverIp'] + '/' + data.data[key]['serverName'] + '" name="data[]">' + data.data[key]['serverIp'] + '</div>';
                    }else{
                        body += '<div class="back_input"><input type="checkbox" checked="checked" value="' + data.data[key]['serverIp'] + '/' + data.data[key]['serverName'] + '"  name="data[]">' + data.data[key]['serverIp'] + '</div>';
                    }
                }
                body += '</div>';
                successTip(body,'选择备份主机',update);
            }else{
                errorTip(data.msg);
            }
        },'json')
    });

    //全选/反选事件
    $(document).on('click','#sel_all',function(){
        sel($(this),$("input[name='data[]']"));
    });

    //开启关闭禁用状态
    $("img[event-bind='status_change']").on('click',function(){
        statusChange("/backup/strategy/status",$(this).attr('data-id'),$(this).attr('data-status'),$(this))
    });

    //数据检索
    $("input[class='form-control']").keydown(function(event){
        if(event.keyCode == 13){
            location.href = '/backup/strategy/index?name=' +
                $("input[name='StrategySearch[name]']").val() +
                '&status=' + $("select[name='StrategySearch[status]']").val();
        }
    });

    //数据检索
    $("select[name='StrategySearch[status]']").change(function(){
        location.href = '/backup/strategy/index?name=' +
            $("input[name='StrategySearch[name]']").val() +
            '&status=' + $("select[name='StrategySearch[status]']").val();
    });

    //删除备份
    $("a[name='delete']").click(function(){
        if(confirm("确认删除?")){
            $.get($(this).attr('href'),
                {},
                function(data){
                    if(data.status == 1){
                        successTip(data.msg,'删除成功',function(){location.reload()});
                    }else{
                        errorTip(data.msg);
                    }
                },'json'
            )
        }
        return false;
    });

    //关联备份主机
    $("a[name='relationHost']").click(function(){
        var strategy_id = $(this).attr('data-id');
        $.get(
            $(this).attr('href'),
            {},
            function(data){
                if(data.status == 1){
                    var body = '';
                    body += '<div class="back_checkbox">';
                    body += '<div class="sel_all"><input type="checkbox" id="sel_all">全选/反选</div>';
                    for(var key in data.data){
                        if(data.data[key][1] == true) {
                            body += '<div class="back_input"><input type="checkbox" checked="checked" value="' + key +'" name="data[]">' + data.data[key][0].serverIp + '</div>';
                        }else{
                            body += '<div class="back_input"><input type="checkbox" value="' + key +'" name="data[]">' + data.data[key][0].serverIp + '</div>';
                        }
                    }
                    body += '</div>';
                    body += '<input type="hidden" id="strategy_id" value="'+strategy_id+'">';
                    successTip(body,'关联备份主机',relationHost)
                }else{
                    errorTip(data.msg);
                }
            },'json'
        )
        return false;
    });
});

/**
 * Description 数据更新
 * */
function update(){
    var backupHost = [];
    $("input[name='data[]']").each(function(){
        if($(this).is(':checked')){
            backupHost.push($(this).val())
        }
    });
    $.get("/backup/host/update",{backupHost:backupHost.join("-")},function(data){
        if(data.status == 1){
            successTip(data.msg,'成功提示')
        }else{
            errorTip(data.msg);
        }
    },'json')
    return true;
}

/**
 * Description 关联备份主机
 * */
function relationHost(){
    var strategy_id = $('#strategy_id').val();
    var hostList = [];
    $("input[name='data[]']:checked").each(function(i){
        hostList.push($(this).val());
    });
    $.get("/backup/strategy/relation",{strategy_id:strategy_id,hostList:hostList},function(data){
        if(data.status == 1){
            successTip(data.msg,'关联成功');
        }else{
            errorTip(data.msg);
        }
    },'json')
}
