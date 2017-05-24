/**
 * Created by user on 2016/11/24.
 */
/**
 * @description js初始化
 * */
$(document).ready(function(){
    $("#righter_tab .pagination a").click(function(){
        var href = $(this).attr('href');
        href += '&type=righter';
        location.href = href;
        return false;
    });

    $("#template_list option[value!='0']").hide();
    var host = $('#DBHost').val();
    $("#template_list option[data-host='"+host+"']").show();

    //服务器切换
    $('#DBHost').change(function(){
        var host = $('#DBHost').val();
        $('#data_database').val('*');
        $('#data_table').val('*');
        $('#where').attr('disabled',true);
        $("#template_list option[value!='0']").hide();
        $("#template_list option[data-host='"+host+"']").show();
    });
    //数据库选择
    $('#sel_database').click(function(){
        var host = $('#DBHost').val();
        show_database(host);
        return false;
    });
    //表格选择
    $('#sel_table').click(function(){
        var host = $('#DBHost').val();
        var databases = $('#data_database').val();
        var databases_arr = databases.split(",");
        if(databases == '*' || databases_arr.length > 1){
            errorTip("表格选择只针对单数据库操作！");
        }else{
            show_table(host,databases);
        }
        return false;
    });
    //全选/反选
    $('body').on('click','.sel_all input',function(){
        if($(this).is(':checked') == true){
            $("input[name='data[]']").prop('checked',true);
        }else{
            $("input[name='data[]']").prop('checked',false);
        }
    });

    //反选
    $('body').on('click',"input[name='data[]']",function(){
        var sel_all = true;
        $("input[name='data[]']").each(function(i){
            if($(this).is(':checked') == true){

            }else{
                sel_all = false;
            }
        });
        if(sel_all == true){
            $('.sel_all input').prop('checked',true);
        }else{
            $('.sel_all input').prop('checked',false);
        }
    });

    //保存按钮
    $('#save,#backup').click(function(){
        var databases = $('#data_database').val();
        var tables = $('#data_table').val();
        var where = $('#where').val();
        var job_number = $('#job_number').val();
        var host = $('#DBHost').val();
        var type = $(this).attr('id');
        if(databases == ''){
            errorTip('请选择备份数据库');
        }
        $.post(
            '/index/back/save',
            {host:host,databases:databases,tables:tables,job_number:job_number,where:where,type:type},
            function(data){
                if(data.status == 1){
                    successTip(data.msg,function(){location.reload();});
                }else{
                    errorTip(data.msg);
                }
            },
        'json');
    });

    //设为模版
    $("a[data-bind='template']").click(function(){
        var body = '<div class="sui-form projects-form"><div class="form-group field-projects-name"><label class="control-label">请输入模版名称: </label><input class="input-info input-xlarge" id="template_name" value="" type="text"></div></div>';
        body += '<input type="hidden" value="' + $(this).attr('data-id') + '" id="template_id">';
        $.confirm({
            body: body,
            title: "模版设置",
            okBtn: '保存',
            cancelBtn: '取消',
            okHide: save_template,
        });
        return false;
    });

    //选择模版
    $("#template_list").change(function(){
        if($(this).val() == 0){
            $('#data_database').val('*');
            $('#data_table').val('*');
            $('#where').val('');
            $('#job_number').val('');
            $('#where').attr('disabled',true);
        }else{
            $('#data_database').val($("#template_list option:checked").attr("data-databases"));
            $('#data_table').val($("#template_list option:checked").attr("data-tables"));
            $('#where').val($("#template_list option:checked").attr("data-where"));
            $('#job_number').val($("#template_list option:checked").attr("data-job_number"));
            $('#where').attr('disabled',false);
        }
    });

    //根据记录数据备份
    $("a[event-bind='backup']").click(function(){
        var id = $(this).attr('data-id');
        $.get("/index/back/back",{id:id},function(data){
            errorTip(data.msg);
        },'json')
    });

    //备份方式初始化
    $("#type").change(function(){
        var type = $(this).val();
        $("#backup_add,#righter_add").hide();
        $("#"+type+"_add").show();
        $("#tab_righter,#tab_backup").removeClass("active");
        $("#tab_"+type).addClass("active");
        $("#righter_tab,#backup_tab").hide();
        $("#"+type+"_tab").show();
    });

    //标签切换
    $(".tab_table ul li a").click(function(){
        var id = $(this).attr("href");
        $(this).parent().parent().find("li").removeClass('active');
        $(this).parent().addClass('active');
        $(".tab_table table").hide();
        $(id).show();
        return false;
    });

    //数据订正按钮
    $("#righter_backup").click(function(){
        if($("#outfile_sql").val() == ''){
            errorTip("请填写订正语句");
            return false;
        }
        righter_save($("#outfile_sql").val());
    });

    //数据订正检索
    $("#righter_tab input[class='form-control']").keyup(function(event){
        if(event.keyCode == 13){
            location.href = "/index/back?host="+$('#DBHost').val()+"&type="+$('#type').val()+"&sql="+$("#outfile_sql").val()+"&righterSearchHost="+$(this).val();
        }
    });

    //数据备份检索
    $("#backup_tab input[class='form-control']").keyup(function(event){
        if(event.keyCode == 13){
            location.href = "/index/back?host="+$('#DBHost').val()+"&type="+$('#type').val()+"&sql="+$("#outfile_sql").val()+
                "&righterSearchHost="+$("#righter_tab input[name='RighterSearch[host]']").val() +
                "&databases=" + $("#backup_tab input[name='BackupSearch[databases]']").val() +
                "&table=" + $("#backup_tab input[name='BackupSearch[table]']").val() +
                "&where=" + $("#backup_tab input[name='BackupSearch[where]']").val() +
                "&job_number=" + $("#backup_tab input[name='BackupSearch[job_number]']").val() +
                "&backupSearchHost=" + $("#backup_tab input[name='BackupSearch[host]']").val();
        }
    });
});

/**
 * @description 数据库展示
 * */
function show_database(host){
    $.get("/index/back/get-databases",
        {host:host},
        function(data){
            if(data.status == 1){
                dataTip(data.data,host + "-备份数据库选择",save_database,$('#data_database').val());
            }else{
                errorTip(data.msg);
            }
        }
    ,'json')
}

/**
 * @description 表展示
 * */
function show_table(host,database){
    $.get("/index/back/get-tables",
        {host:host,database:database},
        function(data){
            if(data.status == 1){
                dataTip(data.data,host + "-" + database + "-备份表选择",save_table,$('#data_table').val());
            }else{
                errorTip(data.msg);
            }
        }
    ,'json')
}

/**
 * @description 数据库保存
 * */
function save_database(){
    if($(".sel_all input").is(':checked') == true){
        $('#data_table').val('*');
        $('#where').attr('disabled',true);
        $('#data_database').val('*');
    }else{
        var data = [];
        $("input[name='data[]']").each(function(i){
            if($(this).is(':checked') == true){
                data.push($(this).val());
            }
        });
        if(data.length > 1){
            $('#data_table').val('*');
            $('#where').attr('disabled',true);
        }
        $('#data_database').val(data.join(","));
    }
}

/**
 * @description 数据库保存
 * */
function save_table(){
    if($(".sel_all input").is(':checked') == true){
        $('#data_table').val('*');
        if($("input[name='data[]']").length == 1){
            $('#where').attr('disabled',false);
        }else{
            $('#where').attr('disabled',true);
        }
    }else{
        var data = [];
        $("input[name='data[]']").each(function(i){
            if($(this).is(':checked') == true){
                data.push($(this).val());
            }
        });
        if(data.length == 1){
            $('#where').attr('disabled',false);
        }else{
            $('#where').attr('disabled',true);
        }
        $('#data_table').val(data.join(","));
    }
}

/**
 * @description 数据订正
 * @param str outfile_sql订正语句
 * @param str outfile_name订正文件名称
 * */
function righter_save(outfile_sql){
    var ip = $('#DBHost').val();
    $.get(
        "/index/back/righter",
        {outfile_sql:outfile_sql,ip:ip},
        function(data){
            if(data.status == 1){
                successTip(data.msg,function(){location.href = '/index/back?type=righter&host='+ip+'&sql='+outfile_sql});
            }else{
                errorTip(data.msg);
            }
        },'json'
    )
}

/**
 * @description 保存模版
 * */
function save_template(){
    var id = $('#template_id').val();
    var template_name = $('#template_name').val();
    if(id == ''){
        errorTip("请选择记录条目");
        return false;
    }
    if(template_name == ''){
        errorTip("请输入模版名称");
        return false;
    }
    $.get("/index/back/template",{id:id,template_name:template_name},
        function(data){
            if(data.status == 1){
                successTip(data.msg,function(){location.reload()})
            }else{
                errorTip(data.msg);
            }
        },'json'
    );
}

/**
 * @description 数据展示
 * */
function dataTip(data,title,okHide,init){
    var init_arr = init.split(",");
    var body = '<div class="back_checkbox">';
    if(init == '*') {
        body += '<div class="sel_all"><input type="checkbox" id="sel_all" checked="checked">全选/反选</div>';
    }else{
        body += '<div class="sel_all"><input type="checkbox" id="sel_all">全选/反选</div>';
    }
    for(var i in data){
        if(init == '*' || $.inArray(data[i],init_arr) >= 0) {
            body += '<div class="back_input"><input type="checkbox" name="data[]" value="' + data[i] + '" checked="checked">' + data[i] + '</div>';
        }else{
            body += '<div class="back_input"><input type="checkbox" name="data[]" value="' + data[i] + '">' + data[i] + '</div>';
        }
    }
    body += '</div>';
    $.confirm({
        body: body,
        title: title,
        okBtn: '保存',
        cancelBtn: '取消',
        height: '350px',
        width:  '500px',
        okHide: okHide,
    });
}