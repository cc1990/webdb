//加载表格插件信息

function load_function(){
    //right_menu();
    dbclick_dbinfo();
    mousedown_change();

    //隐藏左侧菜单栏
    $(".spread").on('click',function(){
        spread_click( $(this) );
    });

    resizable( $(".sqlbox") );

    
}

function data_table(){
    var resultbox_height = reckon_resultbox_height();

    $('.result-content table.display').DataTable({
        //"scrollY": resultbox_height,
        "scrollX": true, //横向滚动条
        "scrollCollapse": "true",
        "jQueryUI": true,
        //"searching": false,
        "dom": 'rt<"bottom"ilfp<"clear">>',
        "bAutoWidth": true, //自适应宽度
        "columnDefs": [
            { "width": "20%", "targets": 0 }
        ],
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
    $('.db-name').contextMenu('dbMenu', {
        bindings: {
            'refresh-db': function(t) {
                open_db($(t));
            },
        }
    });
    $('.tb-name').contextMenu('tbMenu',{
        bindings: {
            'info-table': function (t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var type = $(t).attr("type");
                var projectName = $('#Project').val();
                var environment = $("#environment").val();
                get_table_info(projectName, environment, dbname, tbname, type);
            },
            'open-table': function (t) {
                var dbname = $(t).attr("data-dbname");
                var sqlinfo = "";
                create_tab(dbname, '', sqlinfo);
            },
            'select-table': function (t) {
                var dbname = $(t).attr("data-dbname");
                var tbname = $(t).attr("data-tbname");
                var sqlinfo = "SELECT * FROM " + tbname + ";";
                create_tab(dbname, tbname, sqlinfo);
            }
        }
    })
}

//获取表信息
function get_table_info( projectName, environment, dbname, tbname ,type){
    var url = "/index/sharding/get-table-info?projectName="+projectName+"&environment="+environment+"&db_name="+dbname+"&tb_name="+tbname+"&type="+type;
    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        success: function(data){
            if( data.status == 0 ){
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }else{
                var create_sql = data.data.create_sql;
                var list = data.data.list;

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
                        content: "<pre style=''>"+create_sql+"</pre>"
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
        create_tab(dbname, tbname, sqlinfo);
    });
}

//左键或右键点击是给予背景颜色
function mousedown_change(){
    $(".db-name").mousedown(function(){
        $(".lihover").removeClass('lihover');
        $(this).addClass("lihover");
    });
}

//选项卡切换
$("#tabbox li").on('click', function(){
    change_tab( $(this) );
});

//关闭选项卡
$("#tabbox .nav-title i").on('click', function(){
    close_tab( $(this) );
});

$(".resultnav li").on('click', function(){
    //change_result( $(this) );
});

//计算结果集div的高度
function reckon_resultbox_height(){
    var body_height = $(window).height();//可视区域高度
    /*var nav_height = $(".sui-navbar").height(); //导航栏高度
    var footer_height = $("footer").height(); //底部栏高度
    var tabbox_height = $("#tabbox").height();
    var navbox_height = $("#pagebox .navbox").height();
    var sqlbox_height = $("#pagebox .sqlbox").height();
    var form_height = navbox_height+sqlbox_height;
    var resultnav_height = $("#pagebox .resultnav").height();*/
    //console.log("body: "+body_height +"， 导航栏高度："+nav_height+"， 底部高度："+footer_height+"， 标签页高度："+tabbox_height+"， 表单高度："+form_height+"， 结果集高度："+resultnav_height+"， 剩余高度："+resultbox_height);
    //var resultbox_height = body_height-nav_height-footer_height-tabbox_height-form_height-resultnav_height;
    var resultbox_height = body_height-479;
    //alert(resultbox_height);
    
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


///双击时创建新的标签
//function dbclick_dbinfo(){
//    $(".dbinfo").dblclick(function(){
//        var dbname = $(this).attr("data-dbname");
//        var sqlinfo = "";
//        create_tab(dbname, sqlinfo);
//    });
//}

//双击打开数据库
function dbclick_dbinfo(){
    $('.db-name').dblclick(function(){
        if( $(this).next().length == 0  ){
            open_db( $(this) );
        }else{
            tbname_list_hide( $(this) );
        }
    })
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

//打开数据库
function open_db( o ){
    var projectName = $('#Project').val();
    var environment = $('#environment').val();
    var database = o.parent().attr("data-dbname");
    var url = "/index/sharding/get-config?projectName="+projectName+"&environment="+environment+"&database="+database;
    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        success: function(data){
            if( data.status == 0 ){
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            }else{
                o.next().remove();
                var tb_tpl = "<ul class='tblist'>";
                var list = data.data;
                for (var key in list.sharding) {
                    tb_tpl += "<li class='tb-name' style='color:#3A87AD' type='sharding' data-tbname='"+list.sharding[key]+"' data-dbname='"+database+"'><span class='tb-name-title'><i class='iconfont'>&#xe602;</i>"+list.sharding[key]+"</span></li>";
                }
                for (var i = 0; i < list.common.length; i++) {
                    tb_tpl += "<li class='tb-name' type='common' data-tbname='"+list.common[i]+"' data-dbname='"+database+"'><span class='tb-name-title'><i class='iconfont'>&#xe602;</i>"+list.common[i]+"</span></li>";
                }
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
    }
    
}

//创建新的标签
function create_tab( dbname, tbname, sqlinfo )
{
    if( $("#tabbox li").length >= 6 ){
        layer.msg("最多创建5个标签页，请关闭其他标签页", {time: 3000, icon:5, shade: 0.6,shadeClose: true});
        return false;
    }
    var project = $("#Project option:selected").val();
    var environment = $("#environment option:selected").val();
    dbname = dbname||'';
    sqlinfo = sqlinfo||'';
    if( project == '' ){
        title = 'SQL窗口';
    }else{
        title = project + "." + environment + "." + dbname + "." + tbname;
    }
    
    var tab_title = "<li class='active'><span class='nav-title'><span class='title' title='"+title+"'>"+title+"</span><i class='iconfont' title='关闭'>&#xe615;</i></span></li>";
    $("#tabbox li").removeClass("active");
    $("#tabbox ul").append(tab_title);

    create_page();

    //开始赋值
    $("#pagebox .pagebox-list:last input[name='Project']").val(project);
    $("#pagebox .pagebox-list:last input[name='environment']").val(environment);
    $("#pagebox .pagebox-list:last input[name='DBName']").val(dbname);
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

    $(".resultbox").height( reckon_resultbox_height() );

    resizable($("#pagebox .pagebox-list .sqlbox"));
    
    codemirror_high(sqlinfo);
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
    $("#pagebox .pagebox-list:first input[name='batch']").attr("checked", false);
    $("#pagebox .pagebox-list:first input[name='batch_notes']").val("");
    $("#pagebox .pagebox-list:first .sqlinfo").text("");
    $("#pagebox .pagebox-list:first .resultnav ul").html('<li class="active"><span class="resultnav-title">消息</span></li>');
    $("#pagebox .pagebox-list:first .resultbox").html('<div class="result-info-box"></div');
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
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").html("");
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").remove();
    var textarea = $("#pagebox .pagebox-list:eq("+index1+") textarea");
    var value = window.editor[textarea.attr('id')].getValue();
    textarea.val(value);

    var url = "/index/sharding/execute";
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: $("#pagebox .pagebox-list:eq("+index1+") form").serialize(),
        success: function(data){
            //console.log(data);
            $("#pagebox .pagebox-list:eq("+index1+") #submit").html('执行SQL');
            $("#pagebox .pagebox-list:eq("+index1+") #submit").removeClass('disabled');

            if (data.code == 0) {
                layer.msg(data.msg, {time: 3000, icon:5, shade: 0.6,shadeClose: true});
            } else {
                var content = data.content;
                var x = 1;
                var resultbox_height = reckon_resize_resultbox_height();//计算表格高度
                $.each( content, function(i, item){
                    var info_tpl = "";var result_tpl = "";var pre = /\<+\s*([a-z]+)\s*/;
                    info_tpl += "<div class='result-info'>"
                            + "<p>【执行SQL：（"+i+"）】</p>"
                            + "<p>"+item.sql+"</p>"
                            + "<p>"+item.msg+"</p>";
                    //显示执行结果
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").append(info_tpl);
                    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").show();

                    if ( item.excute_result != '' && item.excute_result != undefined ) {
                        //显示结果集列
                        var resultnav_tpl = "<li><span class='resultnav-title'>结果集"+x+"</span></li>";
                        $("#pagebox .pagebox-list:eq("+index1+") .resultnav ul").append(resultnav_tpl);

                        result_tpl += "<div class='result-content' style=\"width:100%;height:"+resultbox_height+"px;\"><div id='table_"+x+"' class='tablebox' style=\"width:100%;height:"+resultbox_height+"px;display:block;\"></div></div>";

                        $("#pagebox .pagebox-list:eq("+index1+") .resultbox").append(result_tpl);
                        easyui_datagrid( $("#pagebox .pagebox-list:eq("+index1+") .resultbox #table_"+x), item.excute_result);

                        x++;
                    }
                    var content_count = $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").index();
                    if( content_count >= 1 ){
                        $("#pagebox .pagebox-list:eq("+index1+") .resultnav li").removeClass("active");
                        $("#pagebox .pagebox-list:eq("+index1+") .resultnav li:eq(1)").addClass("active");
                        
                        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").hide();
                        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").hide();
                        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content:first").show();
                    }
                } );
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

    var url = "/index/sharding/sql-verify";
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: $("#pagebox .pagebox-list:eq("+index1+") form").serialize(),
        success: function(data){
            console.log(data);
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