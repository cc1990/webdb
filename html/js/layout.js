//加载表格插件信息
(function(){
    $('#table_list.display').DataTable({
        //"scrollY": true,
        //"scrollX": true,
        "scrollCollapse": "true",
        //"searching": false,
        "dom": 'rt<"bottom"ilfp<"clear">>',
        "bAutoWidth": true,

        "pagingType": "full_numbers",
        "language": {
            "lengthMenu": "显示 _MENU_ 项结果",
            "zeroRecords": "没有找到记录",
            "info": "显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",
            "infoEmpty": "无记录",
            "infoFiltered": "(从 _MAX_ 条记录过滤)",

            "paginate": {
                "first":    "首页",
                "previous": "上一页",
                "next":     "下一页",
                "last":     "末页"
            }
        }
    } );

    //使用col插件实现表格头宽度拖拽
    $(".table").colResizable({
        liveDrag:true, 
        //gripInnerHtml:"<div class='grip'></div>", 
        draggingClass:"dragging", 
        resizeMode:'fit'
    });
})();


//右键菜单
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
            var sqlinfo = "SELECT * FROM " + tbname + ";";
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
            var dbname = $(t).attr("data-dbname");
            var tbname = $(t).attr("data-tbname");
            var sqlinfo = "SHOW CREATE TABLE " + tbname + ";";
            alert(sqlinfo);
        }
    }
});

//右键菜单
$('.db-name').contextMenu('dbMenu', {
    bindings: {
        'open': function(t) {
            alert('Trigger was '+t.id+'\nAction was Open');
        },
        'email': function(t) {
            alert('Trigger was '+t.id+'\nAction was Email');
        },
        'save': function(t) {
            alert('Trigger was '+t.id+'\nAction was Save');
        },
        'delete': function(t) {
            alert('Trigger was '+t.id+'\nAction was Delete');
        }
    }
});



//左键或右键点击是给予背景颜色
(function(){
    $(".db-name").mousedown(function(){
        $(".lihover").removeClass('lihover');
        $(this).addClass("lihover");
    });
    $(".tb-name").mousedown(function(){
        $(".lihover").removeClass('lihover');
        $(this).addClass("lihover");
    });
})();

//选项卡切换
$(".tabbox li").on('click', function(){
    change_tab( $(this) );
});

//关闭选项卡
$(".tabbox .nav-title i").on('click', function(){
    close_tab( $(this) );
});

//双击库名是隐藏表
$(".db-name").dblclick(function(){

    tbname_list_hide( $(this) );
});

$(".resultnav li").on('click', function(){
    change_result( $(this) );
})

//隐藏表列
function tbname_list_hide( o )
{
    if ( o.next().is(":hidden") ) {
        o.next().show();
    }else{
        o.next().hide();
    }
    
}

function change_tab( o )
{
    $(".tabbox li").removeClass("active");
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

function close_tab( o )
{
    var index = o.parent().parent().index();

    if( o.parent().parent().is(".active") ){
        $(".tabbox ul li:first").addClass("active");
        $("#pagebox .pagebox-index").show();
    }
    o.parent().parent().remove();
    if( $("#pagebox").children(".pagebox-list").length > 1 ){
        $("#pagebox .pagebox-list:eq("+(index-1)+")").remove();
    }else{
        clear_result();
    }
    
}

//创建新的标签
//function create_tab( title, dbname='', tbname='', sqlinfo='' )
function create_tab( title, dbname, tbname, sqlinfo )
{
    dbname = dbname||'';
    tbname = tbname||'';
    sqlinfo = sqlinfo||'';
    if( title == '' ){
        title = 'SQL窗口';
    }
    var tab_title = "<li class='active'><span class='nav-title'>"+title+"<i class='iconfont' title='关闭'>&#xe615;</i></span></li>";
    $(".tabbox li").removeClass("active");
    $(".tabbox ul").append(tab_title);

    create_page();

    //开始赋值
    $("#pagebox .pagebox-list:last input[name='DBHost']").val($("input[name='server_host'] :selected").val());
    $("#pagebox .pagebox-list:last input[name='DBName']").val(dbname);
    $("#pagebox .pagebox-list:last input[name='tbname']").val(tbname);
    $("#pagebox .pagebox-list:last .sqlinfo").html(sqlinfo);

    $(".tabbox").undelegate("li", "click").delegate("li", "click", function(){
        change_tab( $(this) );
    });
    $(".tabbox").undelegate(".nav-title i", "click").delegate(".nav-title i", "click", function(){
        close_tab( $(this) );
    });
    $("#pagebox").undelegate(".resultnav li", "click").delegate(".resultnav li", "click", function(){
        change_result( $(this) );
    });

}

//创建新的SQL窗口
function create_page()
{
    $("#pagebox .pagebox-index").hide();
    if( $(".tabbox .active").index() == 1 ){
        $("#pagebox .pagebox-list:first").show();
    }else {
        var pagebox_list = $("#pagebox .pagebox-list:first").html();

        $("#pagebox .pagebox-list").hide();
        $("#pagebox").append("<div class='pagebox-list'>" + pagebox_list + "</div>");

        $("#pagebox .pagebox-list:last input[name='batch']").attr("checked", false);
        $("#pagebox .pagebox-list:last input[name='batch_notes']").val("");
        $("#pagebox .pagebox-list:last .sqlinfo").html("");
        $("#pagebox .pagebox-list:last .resultnav ul").html('<li class="active"><span class="resultnav-title">消息</span></li>');
        $("#pagebox .pagebox-list:last .resultbox").html('<div class="result-info-box"></div');
    }
}

/**
 * 创建查询结果集
 * @param  {[int]} index [在第几个标签页窗口创建]
 * @return {[type]}       [description]
 */
function create_result(index){

}


function change_result( o ){
    var index = $(".tabbox .active").index();
    var index1 = index-1;

    $("#pagebox .pagebox-list:eq("+index1+") .resultnav li").removeClass('active');
    o.addClass('active');
    var index_ = $("#pagebox .pagebox-list:eq("+index1+") .resultnav .active").index();
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content").hide();
    $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").hide();

    if( index_ == 0){
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content:last").hide();
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-info-box").show();
    }{
        $("#pagebox .pagebox-list:eq("+index1+") .resultbox .result-content:eq("+(index_-1)+")").show();
    }
}

function clear_result(){
    $("#pagebox .pagebox-list:first").hide();
    $("#pagebox .pagebox-list:first input[name='batch']").attr("checked", false);
    $("#pagebox .pagebox-list:first input[name='batch_notes']").val("");
    $("#pagebox .pagebox-list:first .sqlinfo").html("");
    $("#pagebox .pagebox-list:first .resultnav ul").html('<li class="active"><span class="resultnav-title">消息</span></li>');
    $("#pagebox .pagebox-list:first .resultbox").html('<div class="result-info-box"></div');
}