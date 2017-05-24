/**
 * Description 权限相关脚本
 */
$(document).ready(function(){
    window.server_ip = [];

    //初始化table，加入全局变量中
    $('#servers_privilege').find('table').each(function(){
        var server_ip = $(this).attr('server_ip');
        window.server_ip[server_ip] = true;
        //最左侧服务半选状态
        $("#role-servers tbody tr input[server_ip='"+server_ip+"']").prop("indeterminate",true);

        //数据库半选状态
    });

    $('#role-servers tbody tr').click(function(){
        var server_ip = $(this).find("td:last").html();
        var server_id = $(this).find("input").val();
        var checked = $(this).find("input").is(':checked');
        if(window.server_ip[server_ip] === true){
            $('#servers_privilege table').hide();
            $("#table-" + server_id).show();
            return ;
        }else{
            window.server_ip[server_ip] = true;
        }

        $('#servers_privilege table').hide();
        $.get("/rbac/role/get-dbs",{server_ip:server_ip},function(data){
            var table = '<table id="table-' + server_id + '" server_ip = ' + server_ip + ' class="sui-table table-bordered table-zebra table-content-center"><thead><tr>' +
                '<td style="width:1px" colspan="3" class="title">' + server_ip + '-点击左侧服务列表条目，展开数据库:</td></tr>' +
            '<tr><td width="120px"><b>数据库名称</b></br><input type="text" name="search_database" placeholder="数据库筛选"></td><td><b>表</b></br><input type="text" name="search_table" placeholder="表筛选"></td></tr></thead><tbody>';
            for(var key in data) {
                    if(checked) {
                        table += '<tr><td><input type="checkbox" checked="checked" value="' + key + '" name="database[]">' + key + '</td><td>';
                    }else{
                        table += '<tr><td><input type="checkbox" value="' + key + '" name="database[]">' + key + '</td><td>';
                    }
                for(var key_table in data[key]){
                    if(data[key][key_table] != "") {
                        if(checked) {
                            table += '<span class="privilege-table">' + '<input type="checkbox" checked="checked" value="' + data[key][key_table] + '" name="tables[]">' + data[key][key_table] + '</span>';
                        }else{
                            table += '<span class="privilege-table">' + '<input type="checkbox" value="' + data[key][key_table] + '" name="tables[]">' + data[key][key_table] + '</span>';
                        }
                    }
                }
                table += '</td></tr>';
            }
            table +=   '</tbody></table>';
            $('#servers_privilege').append(table);
        },'json');
    });

    //数据库选中事件
    $('#servers_privilege').on('change','table tr td input[name="database[]"]',function(){
        sel($(this),$(this).parent().next().find('input'));
    });

    //表选择事件
    $('#servers_privilege').on('click',"table tbody tr input[name='tables[]']",function(){
        var td = $(this).parent().parent();
        var checked = false;
        var allchecked = true;
        td.find('input').each(function(i){
            if($(this).is(':checked')){
                checked = true;
            }else{
                allchecked = false;
            }
        });
        if(allchecked == true){
            td.prev().find('input').prop('indeterminate',false);
            td.prev().find('input').prop('checked',true);
        }else if(checked == true){
            td.prev().find('input').prop('checked',false);
            td.prev().find('input').prop('indeterminate',true);
        }else{
            td.prev().find('input').prop('indeterminate',false);
            td.prev().find('input').prop('checked',false);
        }

        var table = td.parent().parent().parent();
        checked = false;
        allchecked = true;
        table.find("tbody input[name='database[]']").each(function(){
            if($(this).is(':checked')){
                checked = true;
            }else{
                allchecked = false;
            }
        });
        var server_id = table.attr('id');
        server_id = server_id.split("-");server_id = server_id[1];
        var server_input = $('#role-servers').find("tbody input[value='"+server_id+"']");
        if(allchecked == true){
            server_input.prop('indeterminate',false);
            server_input.prop('checked',true);
        }else if(checked == true){
            server_input.prop('indeterminate',true);
        }else{
            server_input.prop('indeterminate',false);
            server_input.prop('checked',false);
        }
    })

    //服务器选中事件
    $('#role-servers input').on('click',function(){
        var server_id = $(this).val();
        sel($(this),$('#servers_privilege #table-' + server_id).find('input'));
    });

    //数据库修改事件
    $('#servers_privilege').on('change',"table tbody input[name='database[]']",function(){
        var table = $(this).parent().parent().parent().parent();
        var checked = false;
        var allchecked = true;
        table.find("tbody input[name='database[]']").each(function(){
            if($(this).is(':checked')){
                checked = true;
            }else{
                allchecked = false;
            }
        });
        var server_id = table.attr('id');
        server_id = server_id.split("-");server_id = server_id[1];
        var server_input = $('#role-servers').find("tbody input[value='"+server_id+"']");
        if(allchecked == true){
            server_input.prop('indeterminate',false);
            server_input.prop('checked',true);
        }else if(checked == true){
            server_input.prop('indeterminate',true);
        }else{
            server_input.prop('indeterminate',false);
            server_input.prop('checked',false);
        }
    });

    //权限筛选
    $('#servers_privilege').on('keyup','table thead input',function(){
        var database,table;
        if($(this).attr('name') == 'search_database'){
            database = $(this).val();
            table = $(this).parent().next().find('input').val();
        }else{
            database = $(this).parent().prev().find('input').val();
            table = $(this).val();
        }
        searchPrivilege($(this).parent().parent().parent().parent(),database,table);
    });

    //表单提交
    $("button[type='submit']").click(function(){
        var url = $('form').attr('action');
        var name = $('#role-name').val();
        var description = $('#role-description').val();
        var rulename = $('#role-rulename').val();
        var environment = [];
        $('#role-environment').find('input:checked').each(function(i){
            environment.push($(this).val());
        });
        var sqlshardingoperations = [];
        $('#role-sqlshardingoperations').find('input:checked').each(function(i){
            sqlshardingoperations.push($(this).val());
        });
        var sqloperations = [];
        $('#role-sqloperations').find('input:checked').each(function(i){
            sqloperations.push($(this).val());
        });
        var permissions = [];
        $('#role-permissions').find('tbody input:checked').each(function(i){
            permissions.push($(this).val());
        });
        var servers = getServersPrivilege();
        $.ajax({
            url: url,
            type : 'post',
            data : {name:name,description:description,rulename:rulename,environment:environment,sqlshardingoperations:sqlshardingoperations,sqloperations:sqloperations,permissions:permissions,servers:servers},
            success : function(data){
                location.href = '/rbac/role/index';
            },
            dataType : 'json'
        });
        return false;
    });
});

/**
 * Description 获取数据库服务列表
 * */
function getServersPrivilege(){
    var servers = {};
    $('#role-servers tbody').find('input').each(function(i){
        var server_ip = $(this).parent().next().next().html();
        if($(this).is(':checked')){
            servers[server_ip] = 'all';
        }else{
            servers[server_ip] = getSingleServersPrivilege('#table-' + $(this).val(),server_ip);
        }
    });
    return servers;
}

/**
 * Description 循环获取当个服务器下的数据库权限
 * */
function getSingleServersPrivilege(table_id,server_ip){
    var singleServer = {};
    if(window.server_ip[server_ip] === false){
        return singleServer;
    }else{
        $(table_id).find("tbody input[name='database[]']").each(function(i){
            var database = $(this).val();
            if($(this).is(':checked')){
                singleServer[database] = 'all';
            }else{
                var singleDatabase = [];
                $(this).parent().next().find('input:checked').each(function(i){
                    singleDatabase.push($(this).val());
                });
                singleServer[database] = singleDatabase;
            }
        });
        return singleServer;
    }
}

/**
 * Description 筛选方法
 * */
function searchPrivilege(object,database,table){
    object.find('tbody tr').hide();
    if(database == '' && table == ''){
        object.find('tbody tr').show();
    }else if(database != '' && table == ''){
        object.find('tbody tr').each(function(i){
            if(isShowPrivilege($(this).find('td:first'),database)){
                $(this).show();
            }
        });
    }else if(database == '' && table != ''){
        object.find('tbody tr').each(function(i){
            if(isShowPrivilege($(this).find('td:last'),table)){
                $(this).show();
            }
        });
    }else if(database != '' && table != ''){
        object.find('tbody tr').each(function(i){
            if(isShowPrivilege($(this).find('td:first'),database) && isShowPrivilege($(this).find('td:last'),table)){
                $(this).show();
            }
        });
    }
}

/**
 * Description 返回是否显示
 * */
function isShowPrivilege(obj,content){
    var show = false;
    obj.find("input").each(function(i){
        if($(this).val().indexOf(content) >= 0){
            show = true;
            return true;
        }
    });
    return show;
}
