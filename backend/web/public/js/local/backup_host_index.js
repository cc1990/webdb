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
                        body += '<div class="back_input"><input type="checkbox" value="' + data.data[key]['serverIp'] + '|' + data.data[key]['serverName'] + '|' + data.data[key]['backupDir'] + '" name="data[]">' + data.data[key]['serverIp'] + '</div>';
                    }else{
                        body += '<div class="back_input"><input type="checkbox" checked="checked" value="' + data.data[key]['serverIp'] + '|' + data.data[key]['serverName'] + '|' + data.data[key]['backupDir']  + '"  name="data[]">' + data.data[key]['serverIp'] + '</div>';
                    }
                }
                body += '</div>';
                successTip(body,'选择备份主机',update);
            }else{
                errorTip(data.msg);
            }
        },'json')
    });

    //关联备份策略
    $("a[name='relationStrategy']").click(function(){
        var host_id = $(this).attr('data-id');
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
                            body += '<div class="back_input"><input type="checkbox" checked="checked" value="' + key +'" name="data[]">' + data.data[key][0].name + '</div>';
                        }else{
                            body += '<div class="back_input"><input type="checkbox" value="' + key +'" name="data[]">' + data.data[key][0].name + '</div>';
                        }
                    }
                    body += '</div>';
                    body += '<input type="hidden" id="host_id" value="'+host_id+'">';
                    successTip(body,'关联备份策略',relationStrategy)
                }else{
                    errorTip(data.msg);
                }
            },'json'
        )
        return false;
    });

    //全选/反选事件
    $(document).on('click','#sel_all',function(){
        sel($(this),$("input[name='data[]']"));
    });

    //开启关闭禁用状态
    $("img[event-bind='status_change']").on('click',function(){
        statusChange("/backup/host/status",$(this).attr('data-id'),$(this).attr('data-status'),$(this));
    });

    //数据检索
    $("input[class='form-control']").keydown(function(event){
        if(event.keyCode == 13){
            location.href = '/backup/host/index?serverIp=' +
                $("input[name='HostSearch[serverIp]']").val() +
                '&serverName=' + $("input[name='HostSearch[serverName]']").val() +
                '&status=' + $("select[name='HostSearch[status]']").val();
        }
    });

    //数据检索
    $("select[name='HostSearch[status]']").change(function(){
        location.href = '/backup/host/index?serverIp=' +
            $("input[name='HostSearch[serverIp]']").val() +
            '&serverName=' + $("input[name='HostSearch[serverName]']").val() +
            '&status=' + $("select[name='HostSearch[status]']").val();
    });

    //编辑主机信息
    $("a[name='save']").click(function(){
        var body = '<div class="sui-form projects-form">';
        body += '<div class="form-group field-projects-name"><label class="control-label">主机名称:</label><input type="text" value="'+$(this).attr("data-serverName")+'" id="serverName" class="input-info input-xlarge"></div>'
        body += '<div class="form-group field-projects-name"><label class="control-label">备份分区:</label><input type="text" value="'+$(this).attr("data-disk")+'" id="disk" class="input-info input-xlarge"></div>'
        body += '<div class="form-group field-projects-name"><label class="control-label">脚本文件:</label><input type="text" value="'+$(this).attr("data-scriptFile")+'" id="scriptFile" class="input-info input-xlarge"></div>'
        body += '<div class="form-group field-projects-name"><label class="control-label">备份目录:</label><input type="text" value="'+$(this).attr("data-backupPath")+'" id="backupPath" class="input-info input-xlarge"></div>'
        body += '<div class="form-group field-projects-name"><label class="control-label">日志目录:</label><input type="text" value="'+$(this).attr("data-logPath")+'" id="logPath" class="input-info input-xlarge"></div>'
        body += '<div class="form-group field-projects-name"><label class="control-label">归档服务器IP:</label><input type="text" value="'+$(this).attr("data-archiveIp")+'" id="archiveIp" class="input-info input-xlarge"></div>'
        body += '<div class="form-group field-projects-name"><label class="control-label">归档服务器路径:</label><input type="text" value="'+$(this).attr("data-archivePath")+'" id="archivePath" class="input-info input-xlarge"></div>'
        body += '</div>';
        body += '<input type="hidden" id="hostId" value="'+$(this).attr("data-id")+'" />';
        var title = '编辑主机-' + $(this).attr('data-serverIp');
        editTip(body,title,hostSave);
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
            successTip(data.msg,'成功提示',function(){location.reload();})
        }else{
            errorTip(data.msg);
        }
    },'json')
    return true;
}

/**
 * Description 关联备份策略
 * */
function relationStrategy(){
    var host_id = $('#host_id').val();
    var strategyList = [];
    $("input[name='data[]']:checked").each(function(i){
        strategyList.push($(this).val());
    });
    $.get("/backup/host/relation",{host_id:host_id,strategyList:strategyList},function(data){
        if(data.status == 1){
            successTip(data.msg,'关联成功');
        }else{
            errorTip(data.msg);
        }
    },'json')
}

/**
 * Description 保存主机信息
 * */
function hostSave(){
    $.get(
        '/backup/host/save',
        {
            id:$('#hostId').val(),
            serverName:$('#serverName').val(),
            disk:$('#disk').val(),
            scriptFile:$('#scriptFile').val(),
            backupPath:$('#backupPath').val(),
            logPath:$('#logPath').val(),
            archiveIp:$('#archiveIp').val(),
            archivePath:$('#archivePath').val()
        },
        function(data){
            if(data.status == 1){
                successTip(data.msg,'成功提醒',function(){location.reload()});
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    );
}
