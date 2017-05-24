//加载表格插件信息
(function(){
    //data_table();
    right_menu();

    //隐藏左侧菜单栏
    $(".spread").on('click',function(){
        spread_click( $(this) );
    });

    resizable( $(".sqlbox") );

    sqlBoxKeyDown();

})();

function data_table(){
    var resultbox_height = reckon_resultbox_height();

    $('.result-content table.display').DataTable({
        //"scrollY": resultbox_height,
        "scrollX": true,
        "scrollCollapse": "true",
        "jQueryUI": true,
        //"searching": false,
        "dom": 'rt<"bottom"ilfp<"clear">>',
        //"bAutoWidth": true,
        "bAutoWidth": false,
        "iDisplayLength":50,

        "pagingType": "full_numbers",
        "language": {
            "lengthMenu": "显示 _MENU_ 项",
            "zeroRecords": "没有找到记录",
            "info": "共 _TOTAL_ 项",
            "infoEmpty": "无记录",
            "infoFiltered": "(从 _MAX_ 条记录过滤)",

            "paginate": {
                "first":    "<<",
                "previous": "<",
                "next":     ">",
                "last":     ">>"
            }
        }
    } );

    $(".dataTables_scrollBody").height(resultbox_height-70);
    //colresizable();
}

function colresizable(){
    //使用col插件实现表格头宽度拖拽
    $(".result-content table").colResizable({
        liveDrag:true, 
        //gripInnerHtml:"<div class='grip'></div>", 
        draggingClass:"dragging", 
        resizeMode:'fit'
    });
}

//右键菜单
function right_menu(){
    $('.tb-name').contextMenu('tbMenu', {
        bindings: {
            'open-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'select-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "SELECT * FROM " + tbname + ";";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'create-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "#新建表模板示例\nCREATE TABLE IF NOT EXISTS newtable(\nid bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '表自增主键ID',\ncreate_time datetime NOT NULL DEFAULT current_timestamp COMMENT '创建时间',\nupdate_time datetime NOT NULL DEFAULT current_timestamp COMMENT '修改时间',\ncreate_person varchar(20) NOT NULL DEFAULT 'system' COMMENT '创建人',\nupdate_person varchar(20) NOT NULL DEFAULT 'system' COMMENT '修改人',\nPRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '建表模板示例';";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'alter-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "SELECT * FROM " + tbname + ";";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'drop-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "DROP TABLE IF EXSITS " + tbname + ";";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'truncate-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "TRUNCATE TABLE " + tbname + ";";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'rename-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "RENAME TABLE " + tbname + " TO new_tbl_name;";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'refresh-table': function(t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "SELECT * FROM " + tbname + ";";
                create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
            },
            'info-table': function(t) {
                var server_ip = $("#DBHost option:selected").val();
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                get_table_info( server_ip, dbname, tbname );
            }
        }
    });
}
//右键菜单
$('.db-name').contextMenu('dbMenu', {
    bindings: {
        'refresh-db': function(t) {
            //var dbname = $(t).attr("data-dbname");
            open_db( $(t) );
        },
        'create-tb': function(t) {
            var dbname = $(t).attr("data-dbname");
            var tbname = '';
            var sqlinfo = "#新建表模板示例\nCREATE TABLE IF NOT EXISTS newtable(\nid bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '表自增主键ID',\ncreate_time datetime NOT NULL DEFAULT current_timestamp COMMENT '创建时间',\nupdate_time datetime NOT NULL DEFAULT current_timestamp COMMENT '修改时间',\ncreate_person varchar(20) NOT NULL DEFAULT 'system' COMMENT '创建人',\nupdate_person varchar(20) NOT NULL DEFAULT 'system' COMMENT '修改人',\nPRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '建表模板示例';";
            create_tab(dbname, dbname, tbname, sqlinfo);
        }
    }
});



//左键或右键点击是给予背景颜色
(function(){
    $(".db-name").mousedown(function(){
        $(".lihover").removeClass('lihover');
        $(this).addClass("lihover");
    });
    
})();

//选项卡切换
$("#tabbox li").on('click', function(){
    change_tab( $(this) );
});

//关闭选项卡
$("#tabbox .nav-title i").on('click', function(){
    close_tab( $(this) );
});

//双击库名是隐藏表
$(".db-name").dblclick(function(){
    if( $(this).next().length == 0  ){
        open_db( $(this) );
    }else{
        tbname_list_hide( $(this) );
    }
});

$(".resultnav li").on('click', function(){
    //change_result( $(this) );
});

//计算结果集div的高度
function reckon_resultbox_height(){
    var body_height = $(window).height();//可视区域高度
    var nav_height = $(".sui-navbar").height(); //导航栏高度
    var footer_height = $("footer").height(); //底部栏高度
    var tabbox_height = $("#tabbox").height();
    var navbox_height = 37;
    var sqlbox_height = $("#pagebox .sqlbox").height();
    var form_height = navbox_height+sqlbox_height;
    var resultnav_height = $("#pagebox .resultnav").height();
    var resultbox_height = body_height-nav_height-footer_height-tabbox_height-form_height-resultnav_height;
 
    return resultbox_height-10;
}

//计算结果集div的高度
function reckon_resize_resultbox_height(){
    var body_height = $(window).height();//可视区域高度
    var nav_height = $(".sui-navbar").height(); //导航栏高度
    var footer_height = $("footer").height(); //底部栏高度
    var tabbox_height = $("#tabbox").height();
    var navbox_height = 37;
    var sqlbox_height_ = $("#pagebox .sqlbox").height();

    var index = $("#tabbox .active").index()-1;
    if( index >= 0 ){
        var sqlbox_height = $("#pagebox .pagebox-list:eq("+index+") .sqlinfo").attr('height');
        if( sqlbox_height == '' || sqlbox_height == undefined ){
            sqlbox_height = sqlbox_height_;
        }
    }

    var form_height = navbox_height+sqlbox_height;
    var resultnav_height = $("#pagebox .resultnav").height();
    var resultbox_height = body_height-nav_height-footer_height-tabbox_height-form_height-resultnav_height;
    //console.log("body: "+body_height +"， 导航栏高度："+nav_height+"， 底部高度："+footer_height+"， 标签页高度："+tabbox_height+"， 表单栏高度："+navbox_height+"，SQL框："+sqlbox_height+"， 结果集高度："+resultnav_height+"， 剩余高度："+resultbox_height);
    
    return resultbox_height-10;
}

function reckon_resultbox_width(){
    var body_width = $(window).width();
    return body_width-331;
}

//鼠标点击表名的时候给予背景颜色
function mousedown_click(){
    $(".tb-name").mousedown(function(){
        $(".lihover").removeClass('lihover');
        $(this).addClass("lihover");
    });
}

///双击时创建新的标签
function dbclick_tbname(){
    $(".tb-name").dblclick(function(){
        var dbname = $(this).attr("data-dbname");
        var tbname = $(this).attr("data-tbname");
        var sqlinfo = "";
        create_tab(dbname+"."+tbname, dbname, tbname, sqlinfo);
    });
}

/**
 * Description 获取打开窗口对应的数据
 * */
function addDatabase(host,database,table){
    window.databases = typeof(window.databases) == "undefined" ? [] : window.databases;
    //数据库信息
    if(typeof(window.databases) == "undefined" || typeof(window.databases[host]) == "undefined" || typeof(window.databases[host][database]) == "undefined" || typeof(window.databases[host][database][table]) == "undefined") {
        $.ajax({
            url: "/index/redis/get-keyword?host="+host+"&database="+database+"&table="+table,
            type: 'get',
            cache: false,
            success: function (data) {
                window.databases = data;
                window.databases[host] = typeof(window.databases[host]) == "undefined" ? [] : window.databases[host];
                window.databases[host]["databases"] = data[host].databases;
                window.databases[host][database] = typeof(window.databases[host][database]) == "undefined" ? [] : window.databases[host][database];
                window.databases[host][database][table] = data[host][database][table];
                window.databases[host][database]["tables"] = data[host][database].tables;
                //filterDatabase();
            },
            dataType: 'json'
        });
    }
}

//隐藏表列
function tbname_list_hide( o )
{
    if ( o.next().is(":hidden") ) {
        o.next().show();
        o.children().children("i").css('color', "#28a3ef");
    }else{
        o.next().hide();        
        o.children().children("i").css('color', "#333333");
    }
    
}

//切换选项卡
function change_tab( o )
{
    $("#tabbox li").removeClass("active");
    o.addClass("active");

    //模块的切换
    var index = o.index();
    $("#pagebox .pagebox-list").hide();

    if( index == 0 ){
        $("#pagebox .pagebox-index").show();
    }else{
        var index_ = index-1;
        $("#pagebox .pagebox-index").hide();
    
        var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
        if( index_ >= 0 ){
            var index1 = $("#pagebox .pagebox-list:eq("+index_+") .resultnav .active").index()-1;
            console.log(resultbox_height);
            if( index1 >= 0 ){
                //$("#pagebox .pagebox-list:eq("+index_+") .resultbox .result-content:eq("+index1+") .tablebox").datagrid('resize',{width:'100%'});
            }
        }

        $("#pagebox .pagebox-list:eq("+index_+")").show();
    }
}

//关闭选项卡
function close_tab( o )
{
    var index = o.parent().parent().index();
    if( o.parent().parent().is(".active") ){
        $("#tabbox ul li:eq(0)").addClass("active");
        $("#pagebox .pagebox-index").show();
    }
    
    o.parent().parent().remove();
    if( $("#tabbox ul").children("li").length > 1 ){
        $("#pagebox .pagebox-list:eq("+(index-1)+")").remove();
    }else{
        clear_result();
        $("#pagebox .pagebox-list:eq(0)").hide();
    }
    
}

//创建新的标签
//function create_tab( title, dbname='', tbname='', sqlinfo='' )
function create_tab( title, dbname, tbname, sqlinfo )
{
    if( $("#tabbox li").length >= 6 ){
        layer.msg("最多创建5个标签页，请关闭其他标签页", {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        return false;
    }
    dbname = dbname||'';
    tbname = tbname||'';
    sqlinfo = sqlinfo||'';
    if( title == '' ){
        title = 'SQL窗口';
    }
    var tab_title = "<li class='active'><span class='nav-title'><span class='title' title='"+title+"'>"+title+"</span><i class='iconfont' title='关闭'>&#xe615;</i></span></li>";
    $("#tabbox li").removeClass("active");
    $("#tabbox ul").append(tab_title);

    create_page();

    //开始赋值
    $("#pagebox .pagebox-list:last input[name='DBHost']").val($("#DBHost option:selected").val());
    $("#pagebox .pagebox-list:last input[name='DBName']").val(dbname);
    $("#pagebox .pagebox-list:last input[name='tbname']").val(tbname);
    $("#pagebox .pagebox-list:last select[name='project']").val('');
    $("#pagebox .pagebox-list:last button").val('');
    $("#pagebox .pagebox-list:last button").html('<i class="caret"></i>请选择项目');
    $("#pagebox .pagebox-list:last input").val();
    $("#pagebox .pagebox-list:last ul li").removeClass('hide');
    $("#pagebox .pagebox-list:last .sqlinfo").text(sqlinfo);

    $("#tabbox").undelegate("li", "click").delegate("li", "click", function(){
        change_tab( $(this) );
    });
    $("#tabbox .nav-title i").off('click');
    $("#tabbox .nav-title i").on("click", function(){
        close_tab( $(this) );
    });
    $("#pagebox").undelegate(".resultnav li", "click").delegate(".resultnav li", "click", function(){
        change_result( $(this) );
    });
    $("#pagebox").undelegate("#submit", "click").delegate("#submit", "click", function(){
        form_submit();
    });
    $("#pagebox").undelegate("#sql_verify", "click").delegate("#sql_verify", "click", function(){
        sql_verify( $(this) );
    });

    $(".resultbox").height( reckon_resize_resultbox_height() );

    resizable($("#pagebox .pagebox-list .sqlbox"));
    
    codemirror_high(sqlinfo);
    addDatabase($("#DBHost option:selected").val(),dbname,tbname);
    var length = $("#pagebox .pagebox-list:last .CodeMirror").length;
    //console.log(length);
    if( length > 1 ){
        $("#pagebox .pagebox-list:last .CodeMirror:last").remove();
    }
    
}

//创建新的SQL窗口
function create_page()
{
    $("#pagebox .pagebox-index").hide();
    if( $("#tabbox .active").index() == 1 ){
        $("#pagebox .pagebox-list:first").show();
    }else {
        var pagebox_list = $("#pagebox .pagebox-list:first").html();

        $("#pagebox .pagebox-list").hide();
        $("#pagebox").append("<div class='pagebox-list'>" + pagebox_list + "</div>");

        $("#pagebox .pagebox-list:last input[name='batch']").attr("checked", false);
        $("#pagebox .pagebox-list:last input[name='batch_notes']").val("");
        $("#pagebox .pagebox-list:last .sqlinfo").text("");
        $("#pagebox .pagebox-list:last .resultnav ul").html('<li class="active"><span class="resultnav-title">消息</span></li>');
        $("#pagebox .pagebox-list:last .resultbox").html('<div class="result-info-box"></div');
    }
}

//计算tab 的宽度
function tab_width(){

}

//代码高亮
function codemirror_high(sqlinfo)
{
    //获取日期与时间
    var myDate = new Date();
    var hours = myDate.getHours();
    var minutes = myDate.getMinutes();
    var seconds = myDate.getSeconds();
    var id = hours+minutes+seconds;

    $("#pagebox .pagebox-list:last .sqlinfo").attr('id', "sql_"+id);

    var mime = 'text/x-mysql';
    // get mime type
    if (window.location.href.indexOf('mime=') > -1) {
        mime = window.location.href.substr(window.location.href.indexOf('mime=') + 5);
    }
    window.editor["sql_"+id] = CodeMirror.fromTextArea(document.getElementById("sql_"+id), {
        lineNumbers : true,
        mode : mime,
        lineSeparator : "\n",
        indentUnit : 4,
        lineWrapping : false,
        lineNumberFormatter : function(line){
            return line;
        },
        theme : "mysql",
        inputStyle : "contenteditable",
        showCursorWhenSelecting: false,
    });
    window.editor["sql_"+id].setValue(sqlinfo);
    window.editor["sql_"+id].on('change',function(){
        window.editor["sql_"+id].showHint();
    });
}

/**
 * 创建查询结果集
 * @param  {[int]} index [在第几个标签页窗口创建]
 * @return {[type]}       [description]
 */
function create_result(index){

}

/**
 * 切换查询结果集
 * @param  {[type]} o [description]
 * @return {[type]}   [description]
 */
function change_result( o ){
    var index = $("#tabbox .active").index();
    var index1 = index-1;
    $("#pagebox .pagebox-list:eq("+index1+") .resultnav li").removeClass('active');
    o.addClass('active');
    var index_ = $("#pagebox .pagebox-list:eq("+index1+") .resultnav .active").index();
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").hide();
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").hide();

    var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
    if( index_ == 0){
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").css("height", resultbox_height);
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").show();
    }else{
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content:eq("+(index_-1)+")").show();
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content:eq("+(index_-1)+") .tablebox").datagrid('resize',{width:'100%', height: resultbox_height});
    }
}

//清除执行的结果内容
function clear_result(){
    $("#pagebox .pagebox-list:first").hide();
    $("#pagebox .pagebox-list:first #project").prop('selectedIndex', 0);
    $("#pagebox .pagebox-list:first input[name='batch']").attr("checked", false);
    $("#pagebox .pagebox-list:first input[name='batch_notes']").val("");
    $("#pagebox .pagebox-list:first .sqlinfo").text("");
    $("#pagebox .pagebox-list:first .resultnav ul").html('<li class="active"><span class="resultnav-title">消息</span></li>');
    $("#pagebox .pagebox-list:first .resultbox").html('<div class="result-info-box"></div');
}

//打开数据库
function open_db( o ){
    var dbhost = $("#DBHost option:selected").val();
    var dbname = o.attr("data-dbname");
    var url = "/index/default/get-table-list?server_ip="+dbhost+"&db_name="+dbname;
    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        success: function(data){
            if( data.code == 0 ){
                console.log(data.msg);
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                //alert(data.msg);
            }else{
                o.next().remove();
                var tb_tpl = "<ul class='tblist'>";
                var list = data;
                for (var i = 0; i < list.length; i++) {
                    tb_tpl += "<li class='tb-name' data-tbname='"+list[i]+"' data-dbname='"+dbname+"'><span class='tb-name-title'><i class='iconfont'>&#xe602;</i>"+list[i]+"</span></li>";
                };
                tb_tpl += "</ul>";
                o.after(tb_tpl);

                right_menu(); // 右键菜单
                mousedown_click();
                dbclick_tbname();
                o.children().children("i").css('color', "#28a3ef");
            }
        },
        error: function(e){
            //console.log(e.responseText);
            if(e.responseText.indexOf('loginhtml') != -1) {
                window.location.href= "/site/login";
                return;
            }
        }
    });
}

//获取表信息
function get_table_info( server_ip, dbname, tbname ){
    var url = "/index/default/get-table-info?server_ip="+server_ip+"&db_name="+dbname+"&tb_name="+tbname;
    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        success: function(data){
            if( data.code == 0 ){
                console.log(data.msg);
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                //alert(data.msg);
            }else{
                //console.log(data.content);
                var info = data.content;
                var list = info.list;

                var table = "<table class='table-info'><thead><tr><th width='30px'></th><th width='120px'>属性名</th><th>属性值</th></tr></thead><tbody>";
                $.each( list, function(i, item){
                    table += "<tr><td>"+(i+1)+"</td><td>"+item.name+"</td><td>"+item.value+"</td></tr>";
                } );

                table += "</tbody></table>";
                layer.tab({
                    area: ['600px', '500px'],
                    tab: [{
                        title: '基本属性', 
                        content: table
                    }, {
                        title: '创建语句', 
                        content: "<pre style=''>"+info.create_sql+"</pre>"
                    }]
                    });
            }
        },
        error: function(e){
            //console.log(e.responseText);
            if(e.responseText.indexOf('loginhtml') != -1) {
                window.location.href= "/site/login";
                return;
            }
            layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        }
    });
}

function ping(server_ip){
    $("#DBHost").next(".status").html("");
    var url = "/index/default/ping?server_ip="+server_ip;
    var back = 0;
    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        async: false,
        success: function( data ){
            back = data;
            if( data.status == 0 ){
                $("#DBHost").next(".status").html("<i class='iconfont' style='color:#34c360'>&#xe663;</i>");
                $("#DBHost").next(".status").attr('title', "服务器通讯正常！");
            }else{
                //layer.msg("服务器通讯失败，请检查服务器是否正常运行！", {time: 3000, icon:5, shade: 0.6,shadeClose: true});
                $("#DBHost").next(".status").html("<i class='iconfont' style='color:#e8351f'>&#xe655;</i>");
                $("#DBHost").next(".status").attr('title', "服务器通讯失败，请检查服务器是否正常运行！");
            }
        }
    });
    return back;
}

//提交表单
function form_submit(){
    var index = $("#tabbox .active").index();
    var index1 = index-1;

    if( $("#pagebox .pagebox-list:eq("+index1+") #submit").css('background-color') == 'rgb(243, 243, 243)' ){
        alert('正在运行，请等待执行结果！');return false;
    }

    $("#pagebox .pagebox-list:eq("+index1+") #submit").html('执行中');
    $("#pagebox .pagebox-list:eq("+index1+") #submit").addClass('disabled');

    $("#pagebox .pagebox-list:eq("+index1+") .resultnav ul").html("<li class='active'><span class='resultnav-title'>消息</span></li>");
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox").html("<div class='result-info-box'></div>");
    var textarea = $("#pagebox .pagebox-list:eq("+index1+") textarea");
    var value = window.editor[textarea.attr('id')].getValue();
    textarea.val(value);

    var url = "/index/default/execute";
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: $("#pagebox .pagebox-list:eq("+index1+") form").serialize(),
        success: function(data){
            $("#pagebox .pagebox-list:eq("+index1+") #submit").html('执行SQL');
            $("#pagebox .pagebox-list:eq("+index1+") #submit").removeClass('disabled');

            if (data.code == 0) {
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            } else {
                var content = data.content;
                var x = 1;
                var resultbox_width = $(".result-info-box").width();//计算表格高度
                var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
                //console.log(resultbox_height);
                $.each( content, function(i, item){
                    var info_tpl = "";var result_tpl = "";var pre = /\<+\s*([a-z]+)\s*/;
                    info_tpl += "<div class='result-info'>"
                            + "<p>【执行SQL：（"+i+"）】</p>"
                            + "<p>"+item.sql+"</p>"
                            + "<p>"+item.msg+"</p>";
                    //显示执行结果
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").append(info_tpl);
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").css("height", resultbox_height);
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").show();

                    if ( item.excute_result != '' && item.excute_result != undefined ) {
                        //显示结果集列
                        var resultnav_tpl = "<li><span class='resultnav-title' >结果集"+x+"</span></li>";
                        
                        $("#pagebox .pagebox-list:eq("+index1+") .resultnav ul").append(resultnav_tpl);
                        result_tpl += "<div class='result-content' style=\"width:100%;height:"+resultbox_height+"px;\"><div id='table_"+x+"' class='tablebox' style=\"width:100%;height:"+resultbox_height+"px;display:block;\"></div></div>";

                        $("#pagebox .pagebox-list:eq("+index1+") .resultbox").append(result_tpl);
                        easyui_datagrid( $("#pagebox .pagebox-list:eq("+index1+") .resultbox #table_"+x), item.excute_result);

                        x++;
                    }
                } );
                var content_count = $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").index();
                if( content_count >= 1 ){
                    $("#pagebox .pagebox-list:eq("+index1+") .resultnav li").removeClass("active");
                    $("#pagebox .pagebox-list:eq("+index1+") .resultnav li:eq(1)").addClass("active");
                    
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").hide();
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").hide();
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content:first").show();
                }
            }
        },
        error: function(e){
            $("#pagebox .pagebox-list:eq("+index1+") #submit").html('执行SQL');
            $("#pagebox .pagebox-list:eq("+index1+") #submit").removeClass('disabled');
            //console.log("error："+e.responseText);
            if(e.responseText.indexOf('loginhtml') != -1) {
                window.location.href= "/site/login";
                return;
            }
            layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        }
    });
}

function easyui_datagrid( o, data){
    var pre = /\<+\s*([a-z]+)\s*/;
    var tpl = "[[";
    $.each( data, function(h, v){
        if( h == 0 ){
            $.each( v, function( k, vo ){
                tpl += "{field:'"+k+"', align:\"center\", title:\""+k+"\",width:80,formatter:function(value,row,index){if( value == null ){ return 'null'; }else{ return value; }}},";
            });
        }
    } );
    tpl += "]]";
    o.datagrid({ 
        rownumbers:true, 
        pagination:true, 
        data:data.slice(0, 50),
        columns: eval(tpl),
        pageSize:50,
        pageList:[20,50,100,300],
        striped:true,
        singleSelect:true,
        autoRowHeight:false,
    }); 

    var pager = o.datagrid("getPager"); 
    pager.pagination({ 
        total:data.length, 
        onSelectPage:function (pageNo, pageSize) { 
            var start = (pageNo - 1) * pageSize; 
            var end = start + pageSize; 
            o.datagrid("loadData", data.slice(start, end)); 
                pager.pagination('refresh', { 
                total:data.length, 
                pageNumber:pageNo 
            }); 
        } 
    });
    
}

//检测sql的合法性和影响范围
function sql_verify( o ){
    var index = $("#tabbox .active").index();
    var index1 = index-1;

    o.html('正在检测中');
    o.addClass('disabled');

    $("#pagebox .pagebox-list:eq("+index1+") .resultnav ul").html("<li class='active'><span class='resultnav-title'>消息</span></li>");
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").html("");
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").remove();
    var textarea = $("#pagebox .pagebox-list:eq("+index1+") textarea");
    var value = window.editor[textarea.attr('id')].getValue();
    textarea.val(value);

    var url = "/index/default/sql-verify";
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: $("#pagebox .pagebox-list:eq("+index1+") form").serialize(),
        success: function(data){
            //console.log(data);
            o.html('SQL检测');
            o.removeClass('disabled');
            
            if (data.code == 0) {
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            } else {
                var content = data.content;
                var x = 1;
                var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
                $.each( content, function(i, item){
                    var info_tpl = "";var result_tpl = "";
                    info_tpl += "<div class='result-info'>"
                            + "<p>【执行SQL：（"+i+"）】</p>"
                            + "<p>"+item.sql+"</p>"
                            + "<p>"+item.msg+"</p>";
                    //显示执行结果
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").append(info_tpl);
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").show();
                } );
            }
        },
        error: function(e){
            o.html('SQL检测');
            o.removeClass('disabled');
            //console.log("error："+e.responseText);
            if(e.responseText.indexOf('loginhtml') != -1) {
                window.location.href= "/site/login";
                return;
            }
            layer.msg(e.responseText, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        }
    });
}

/**
 * @description 检索项目
 * */
function search_project(search) {
    $.get("/index/default/get-project",
        {search: search},
        function (data) {
            var content = '<li id="search_project"><input type="text" placeholder="项目检索" class="input-default" value=""></li>';
            for (var key in data.data) {
                content += '<li role="presentation"><a role="menuitem" tabindex="-1" href="#" data-pro_id="' + data.data[key].pro_id + '">' + data.data[key].name + '</a></li>';
            }
            $('#select_project ul').html(content);
            $('#search_project input').focus().val(data.search);
        },
        'json'
    )
}

/**
 * 点击伸展或收缩按钮时触发事件
 * 
 * @return {[type]}   [description]
 */
function spread_click( o ){
    var spread_width = $(o).width();
    var tree_width = $(".tree").width()+1;

    if( $(".tree").is(":hidden") ){
        //如果左侧菜单栏是隐藏状态
        $(".tree").show();
        o.css("margin-left", tree_width);
        $(".right").css("margin-left", spread_width+tree_width);
        $(".spread .img").css("background-position", "top left");
        $(".spread").attr("title", "隐藏");
    }else{
        //如果左侧菜单栏是显示状态
        $(".tree").hide();
        o.css("margin-left", "0px");
        $(".right").css("margin-left", spread_width);
        $(".spread .img").css("background-position", "top right");
        $(".spread").attr("title", "显示");
    }
    
    var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
    var index = $("#tabbox .active").index()-1;
    if( index >= 0 ){
        var index1 = $("#pagebox .pagebox-list:eq("+index+") .resultnav .active").index()-1;
        if( index1 >= 0 ){
            $("#pagebox .pagebox-list:eq("+index+") .resultbox .result-content:eq("+index1+") .tablebox").datagrid('resize',{width:'100%', height: resultbox_height});
        }
    }
}

function resizable( o ){

    o.resizable({
        minHeight: 100, //最小高度
        maxHeight: 500, // 最大高度
        edge: 5,
        handles: 's',
        onResize: function( e ) {
            var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
            var index = $("#tabbox .active").index()-1;
            if( index >= 0 ){
                //console.log(resultbox_height);
                var index1 = $("#pagebox .pagebox-list:eq("+index+") .resultnav .active").index()-1;
                $("#pagebox .pagebox-list:eq("+index+") .resultbox .result-info-box").css('height', resultbox_height);
                if( index1 >= 0 ){
                    $("#pagebox .pagebox-list:eq("+index+") .resultbox .result-content:eq("+index1+") .tablebox").datagrid('resize',{width:'100%', height: resultbox_height});
                }
            }
        }
    });
}

function sqlBoxKeyDown(){
    $(document).keydown(function(e){
        if( e.ctrlKey  == true && e.keyCode == 82 ){
            var index = $("#tabbox .active").index();
            var index1 = index-1;
            $("#pagebox input").focus();
            form_submit();
            return false; // 截取返回false就不会刷新网页了
        }
    });
}