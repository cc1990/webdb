/**
 * Created by user on 2016/10/31.
 */
/**
 * @description 获取数据库用户信息
 * */
function getUserList(ip,url){
    if(url == null)  url = '/servers/user/get-user-list';
    $.get(url,{ip:ip,server_id:$('#DBHost option:selected').attr('server_id')},function(data){
        if(data.status == 1){
            var content = '';
            for(var key in data.data){
                content += '<tr><td>' + data.data[key].User + '</td><td>' + data.data[key].Host + '</td><td>';
                content += '<a href="#" onclick="getPrivilege(\'' + data.data[key].Host + '\',\'' + data.data[key].User + '\',\'' + ip + '\',\'*\',3,\''+ ip + '-'+data.data[key].User+'用户权限查看\',\'ck\',\'*\')">查看</a> ';
                if(data.rule_list[data.data[key].User + "@" + data.data[key].Host] != true) {
                    content += '<a href="javascript:void(0)" onclick="getPrivilege(\'' + data.data[key].Host + '\',\'' + data.data[key].User + '\',\'' + ip + '\',\'*\',3,\'' + ip + '-' + data.data[key].User + '用户授权\',\'edit\',\'*\')" >授权</a> ';
                }
                content += '<a href="/servers/database/index?ip='+ip+'&user='+data.data[key].User+'&host='+data.data[key].Host+'&server_id=' + $('#DBHost option:selected').attr('server_id') + '">数据库授权</a></td>';
                content += '<td><a href="javascript:void(0)" event-bind="user_ck" data-ip="'+ip+'" data-host="'+data.data[key].Host+'" data-user="'+data.data[key].User+'">授权语句查看</a> ';
                if(data.rule_list[data.data[key].User + "@" + data.data[key].Host] != true) {
                    content += '<a href="javascript:void(0)" event-bind="user_edit" data-ip="' + ip + '" data-host="' + data.data[key].Host + '" data-user="' + data.data[key].User + '">编辑</a> ';
                    content += '<a href="javascript:void(0)" event-bind="user_delete" data-ip="' + ip + '" data-host="' + data.data[key].Host + '" data-user="' + data.data[key].User + '">删除</a></td></tr> ';
                }
            }
            content += '<tr><td colspan="4">'+data.pageHtml+'</td></tr>';

            $('#dbList tbody').html(content);
            //加载分页点击事件
            $('.pagination a').off('click');
            $('.pagination a').on('click',function(){
                url = $(this).attr("href");
                getUserList(ip,url);
                return false;
            });

            //加载用户查看编辑事件
            $("a[event-bind='user_edit']").off('click');
            $("a[event-bind='user_edit']").on('click',function(){
                userEdit($(this).attr('data-ip'),$(this).attr('data-host'),$(this).attr('data-user'),'modify');
            });

            //加载用户删除事件
            $("a[event-bind='user_delete']").off('click');
            $("a[event-bind='user_delete']").on('click',function(){
                if(confirm("确认删除？")){
                    userDelete($(this).attr('data-ip'),$(this).attr('data-user'),$(this).attr('data-host'));
                }
            });

            //加载用户权限查看事件
            $("a[event-bind='user_ck']").off('click');
            $("a[event-bind='user_ck']").on('click',function(){
                userCk($(this).attr('data-ip'),$(this).attr('data-host'),$(this).attr('data-user'));
            });
        }else{
            errorTip(data.msg);
        }
    },'json');
}

/**
 * @description 错误弹窗
 * @param body 页面数据
 * */
function errorTip(body){
    $.confirm({
        body    :   body,
        title   :   '错误提醒',
        okBtn   :   '确认',
        cancelBtn   :   false,
        timeout :   5000,
    });
}

/**
 * @description 加载弹窗
 * @param body 弹窗内容
 * @param header 头
 * */
function privilegeTip(body,header,ck,okHide,width,height){
    if(ck == 'ck') {
        $.confirm({
            body: body,
            title: header,
            height: !height ? '350px' : height,
            width:  !width ? '500px' : width,
            okHide: okHide,
        });
    }else{
        $.confirm({
            body: body,
            title: header,
            okBtn: '保存',
            cancelBtn: '取消',
            height: !height ? '350px' : height,
            width:  !width ? '500px' : width,
            okHide: okHide,
        });
    }
}

/**
 * @description 成功弹窗
 * */
function successTip(body,okHide){
    $.confirm({
        body : body,
        title : '成功提醒',
        okHide : okHide
    });
}

/**
 * @description 权限编辑及查看
 * @param serverId 服务器ID
 * @param database 操作数据库
 * @param type 操作类型 1-数据库层面权限操作 2-表层面权限操作
 * */
function getPrivilege(host,user,ip,database,type,header,ck,table){
    $.get("/servers/privilege/get-privilege",
        {database:database,ip:ip,type:type,host:host,user:user,table:table},
        function(data){
            if(data.status == 1){
                var body = '<table class="sui-table table-bordered table-zebra"><thead>';
                if(ck == 'ck'){
                    body += '<tr></th><th style="width:150px">权限</th><th>说明</th></tr>';
                }else{
                    body += '<tr><th style="width:30px"><input class="check_all" name="" type="checkbox"></th><th style="width:150px">权限</th><th>说明</th></tr>';
                }
                var privilege = data.privilege;

                for(var key in privilege){
                    if(ck == 'ck'){
                        if(privilege[key][1]) {
                            body += '<tr><td>' + key + '</td><td>' + privilege[key][0] + '</td></tr>';
                        }
                    }else {
                        if (privilege[key][1]) {
                            body += '<tr><td><input name="privilege_list[]" value="' + key + '" type="checkbox" checked="checked"></td><td>' + key + '</td><td>' + privilege[key][0] + '</td></tr>';
                        } else {
                            body += '<tr><td><input name="privilege_list[]" value="' + key + '" type="checkbox"></td><td>' + key + '</td><td>' + privilege[key][0] + '</td></tr>';
                        }
                    }
                }
                body += '<input type="hidden" id="database" value="' + database + '">';
                body += '<input type="hidden" id="type" value="' + type + '">';
                body += '<input type="hidden" id="table" value="' + table + '">';
                body += '<input type="hidden" id="host" value="' + host + '">';
                body += '<input type="hidden" id="user" value="' + user + '">';
                body += '</tbody></table>';
                privilegeTip(body,header,ck,privilegeSave);

                //全选操作
                $("input[class='check_all']").on('click',function(){
                    if($(this).is(':checked') == true){
                        $("input[name='privilege_list[]']").prop('checked',true);
                    }else{
                        $("input[name='privilege_list[]']").prop('checked',false);
                    }
                });
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    )
}

/**
 * @description 权限保存
 * */
function privilegeSave(){
    var ip = $('#DBHost').val();
    var privilege = new Array();
    var name;
    var privilege_str = '';
    var database = $('#database').val();
    var type = $('#type').val();
    var table = $('#table').val();
    var host = $('#host').val();
    var user = $('#user').val();
    $("input[name='privilege_list[]']").each(function(i){
        name = $(this).val();
        privilege[name] = $(this).is(':checked');
    });
    for(var key in privilege){
        privilege_str += key+"=>"+privilege[key]+";";
    }
    $.post("/servers/privilege/save",
        {ip:ip,privilege:privilege_str,database:database,type:type,table:table,host:host,user:user},
        function(data){
            if(data.status == 1){
                errorTip(data.msg);
            }else{
                errorTip(data.msg);
            }
        },'json');
}

/**
 * @description 用户编辑
 * @param ip 服务器地址
 * @param host 用户主机
 * @param user 用户名
 * */
function userEdit(ip,host,user,style){
    var body = '';
    var header;
    body += '<div class="sui-form projects-form">';
    body += '<div class="form-group field-projects-name"><label class="control-label">用户名:</label><input type="text" class="input-info input-xlarge" id="user" value="'+user+'"></div>';
    body += '<div class="form-group field-projects-name"><label class="control-label">主机:</label><input type="text" class="input-info input-xlarge" id="host" value="'+host+'"></div>';
    body += '<div class="form-group field-projects-name"><label class="control-label">密码:</label><input type="password" class="input-info input-xlarge" id="password"></div>';
    body += '<div class="form-group field-projects-name"><label class="control-label">确认密码:</label><input type="password" class="input-info input-xlarge" id="rePassword"></div>';
    body += '<input type="hidden" id="ip" value="'+ip+'">';
    body += '<input type="hidden" id="oldUser" value="'+user+'">';
    body += '<input type="hidden" id="oldHost" value="'+host+'">';
    body += '<input type="hidden" id="style" value="'+style+'">';
    body += '</div>';
    if(style == 'add'){
        header = ip+"-用户新增";
    }else {
        header = ip + "-" + user + "@" + host + "编辑";
    }
    privilegeTip(body, header, 'edit', userSave);
}

/**
 * @description 保存修改
 * */
function userSave(){
    var ip = $('#ip').val();
    var host = $('#host').val();
    var user = $('#user').val();
    var password = $('#password').val();
    var rePassword = $('#rePassword').val();
    var oldUser = $('#oldUser').val();
    var oldHost = $('#oldHost').val();
    var style = $('#style').val();
    if(ip == '' || ip == undefined || ip == false){
        errorTip("请选择服务器！");
        return false;
    }
    if(user == '' || user == undefined || user == false){
        errorTip("用户不可为空！");
        return false;
    }
    if(host == '' || host == undefined || host == false){
        errorTip("主机不可为空！");
        return false;
    }
    if(password != '' && rePassword != password){
        errorTip("两次密码输入不一致！");
        return false;
    }
    if(style == 'add'){
        if(password == '' || password == undefined || password == false){
            errorTip("主机不可为空！");
            return false;
        }
        if(rePassword == '' || rePassword == undefined || rePassword == false){
            errorTip("主机不可为空！");
            return false;
        }
    }

    $.get('/servers/user/'+style,
        {ip:ip,user:user,host:host,password:password,oldUser:oldUser,oldHost:oldHost},
        function(data){
            if(data.status == 0) {
                errorTip(data.msg);
            }else if(data.status == 1){
                getUserList(ip);
                errorTip(data.msg);
            }
        },
        'json'
    );
}

/**
 * @description 用户权限列表查看
 * @param ip 服务器地址
 * @param host 用户主机
 * @param user 用户名
 * */
function userCk(ip,host,user){
    var body = '<table class="sui-table table-bordered table-zebra"><thead>';
    var header = ip + '-' + user + '@' + host + '权限查看';
    body += '<tr><th style="width:35px">序号</th><th>授权语句</th></tr></thead>';
    body += '<tbody>';
    $.get('/servers/privilege/get',
        {ip:ip,host:host,user:user},
        function(data){
            if(data.status == 1){
                for(var key in data.data) {
                    for(var key2 in data.data[key]){
                        body += '<tr><td>' + (parseInt(key)+1) + '</td><td class="consolas">' + data.data[key][key2] + '</td></tr>';
                    }
                }
                body += '</body></table>';
                privilegeTip(body,header,'ck');
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    )
}

/**
 * @description 用户删除
 * */
function userDelete(ip,user,host){
    $.get('/servers/user/delete',
        {ip:ip,host:host,user:user},
        function(data){
            if(data.status == 1){
                getUserList(ip);
            }
            errorTip(data.msg);
        },
        'json'
    )
}

/**
 * @description 新增规则
 * */
function ruleAdd(){
    var user = $('#user').val();
    var server_id = $('#server_id').val();
    var host = $('#host').val();
    if(server_id == '' || server_id == undefined || server_id == false){
        errorTip("请选择服务器");
        return false;
    }
    if(user == '' || user == undefined || user == false){
        errorTip("禁用用户名称不可为空");
        return false;
    }
    if(host == '' || host == undefined || host == false){
        errorTip("禁用主机不可为空");
        return false;
    }
    $.get("/servers/rule/add",
        {user:user,server_id:server_id,host:host},
        function(data){
            if(data.status == 1){
                successTip(data.msg,function(){location.href = '/servers/rule/index?server_id=' + server_id});
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    )
}

/**
 * @description DDL操作新增规则
 * */
function DdlruleAdd(){
    var database = $('#database').val();
    var table = $('#table').val();
    if(database == '' || database == undefined || database == false){
        errorTip("数据库名称不可为空");
        return false;
    }
    if(table == '' || table == undefined || table == false){
        errorTip("表格名称不可为空");
        return false;
    }
    $.get("/logs/rule/create",
        {database:database,table:table},
        function(data){
            if(data.status == 1){
                successTip(data.msg,function(){location.href = '/logs/rule/index';});
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    )
}

/**
 * @description 状态变更
 * @param id 服务器id
 * @param obj 当前事件
 * */
function statusChange(id,status,obj){
    $.get("/servers/rule/status",
        {id:id,status:status},
        function(data){
            if(data.status == 1){
                obj.attr("src","/public/images/" + data.data + ".png");
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    )
}

/**
 * @description 状态变更
 * @param id 服务器id
 * @param obj 当前事件
 * */
function DdlStatusChange(id,status,obj){
    $.get("/logs/rule/status",
        {id:id,status:status},
        function(data){
            if(data.status == 1){
                obj.attr("src","/public/images/" + data.data + ".png");
            }else{
                errorTip(data.msg);
            }
        },
        'json'
    )
}

/**
 * @description 保存修改
 * */
function DdlRuleSave(){
    var database = $('#database').val();
    var table = $('#table').val();
    var id = $('#id').val();
    if(id == '' || id == undefined || id == false){
        errorTip("请选择修改规则！");
        return false;
    }
    if(database == '' || database == undefined || database == false){
        errorTip("数据库名称不可为空！");
        return false;
    }
    if(table == '' || table == undefined || table == false){
        errorTip("表名称不可为空！");
        return false;
    }

    $.get('/logs/rule/update',
        {database:database,table:table,id:id},
        function(data){
            if(data.status == 0) {
                errorTip(data.msg);
            }else if(data.status == 1){
                successTip(data.msg,function(){
                    location.href = '/logs/rule/index';
                });
            }
        },
        'json'
    );
}

