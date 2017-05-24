/**
 * Created by user on 2017/1/16.
 */
$(document).ready(function(){
    //添加内容
    $('#content-add').click(function(){
        if(verifyContent()){
            var type = $("input[name='type']:checked").val();
            var cycle = $('#month>span').html()+"/"+$('#week>span').html()+"/"+$('#day>span').html()+"/"+$('#hour>span').html()+"/"+$('#minute>span').html();
            var retention_time = $('#retention_num').val() + "/" + $('#retention_unit>span').html();
            var content = "<tr>" +
                "<td>"+type+"</td>" +
                "<td>"+cycle+"</td>" +
                "<td>"+retention_time+"</td>" +
                '<td>' +
                '<a href="#" style="cursor:pointer" title="删除"><i class="sui-icon icon-touch-garbage"></i></a>&nbsp;&nbsp;&nbsp;' +
                '<a href="#" style="cursor:pointer" title="编辑"><i class="sui-icon icon-touch-todo"></i></a>' +
                '</td>' +
                "</tr>";
            $('#backup_content tbody').append(content);
        }
    });

    //删除内容
    $('#backup_content').on('click','.icon-touch-garbage',function(){
        $(this).parent().parent().parent().remove();
    });

    //修改内容
    $('#backup_content').on('click','.icon-touch-todo',function(){
        var current_tr = $(this).parent().parent().parent();
        var type = current_tr.find("td:first-child").html();
        var crycle = current_tr.find("td:nth-child(2)").html();
        var remain = current_tr.find("td:nth-child(3)").html();
        //设置备份类型
        $("input[value='"+type+"']").trigger("click");

        //设置备份周期
        crycle = crycle.split("/");
        $('#month').next().find("li").each(function(i){
            var month = $(this).find("a").html();
            if( month == crycle[0]){
                $(this).find("a").trigger("click");
                return false;
            }
        });
        $('#week').next().find("li").each(function(i){
            var month = $(this).find("a").html();
            if( month == crycle[1]){
                $(this).find("a").trigger("click");
                return false;
            }
        });
        $('#day').next().find("li").each(function(i){
            var month = $(this).find("a").html();
            if( month == crycle[2]){
                $(this).find("a").trigger("click");
                return false;
            }
        });
        $('#hour').next().find("li").each(function(i){
            var month = $(this).find("a").html();
            if( month == crycle[3]){
                $(this).find("a").trigger("click");
                return false;
            }
        });
        $('#minute').next().find("li").each(function(i){
            var month = $(this).find("a").html();
            if( month == crycle[4]){
                $(this).find("a").trigger("click");
                return false;
            }
        })

        //设置保留期限
        remain = remain.split("/");
        $('#retention_num').val(remain[0]);
        $('#retention_unit').next().find("li").each(function(i){
            var month = $(this).find("a").html();
            if( month == remain[1]){
                $(this).find("a").trigger("click");
                return false;
            }
        })
        current_tr.remove();
    });

    //下拉选择器
    $(".sui-dropdown ul li a").click(function(){
        $(this).parent().siblings().removeClass('active');
        $(this).parent().addClass('active');
        $(this).parent().parent().prev().html('<i class="caret"></i><span>'+$(this).html()+'</span>');
    });

    //添加备份策略按钮
    $('#save').click(function(){
        $('.msg-error').remove();
        if($("#name").val() == ''){
            errorRemind($("#name").parent(),'请填写备份策略名称');
            return false;
        }
        var strategy_content = {};
        $('#backup_content tbody tr').each(function(i){
            strategy_content[i] = {};
            strategy_content[i].type = $(this).find("td:first").html();
            strategy_content[i].cycle = $(this).find("td:first").next().html();
            strategy_content[i].retention_time = $(this).find("td:first").next().next().html();
        });
        var id = $('#id').val();
        var url = id == undefined ? '/backup/strategy/add' : '/backup/strategy/update';
        $.post(
            url,
            {name:$('#name').val(),strategy_content:strategy_content,id:id},
            function(data){
                if(data.status == 1){
                    successTip(data.msg,"添加成功",function(){location.href = '/backup/strategy/index'});
                }else{
                    errorTip(data.msg);
                }
            },'json'
        );
        return false;
    });

    /**
     * Description 添加内容时判断内容的合法性
     * */
    function verifyContent(){
        $('.msg-error').remove();
        var back = true;
        if($("#retention_num").val() == ''){
            errorRemind($("#retention_num").parent(),'请填写保留期限');
            back = false;
        }
        if($("#javascript_file").val() == ''){
            errorRemind($("#javascript_file").parent(),'请填写备份服务器脚本文件');
            back = false;
        }
        if($("#log_path").val() == ''){
            errorRemind($("#log_path").parent(),'请填写日志生成路径');
            back = false;
        }
        return back;
    }

    /**
     * Description 错误提醒
     * */
    function errorRemind(obj,msg){
        var content = '<div class="sui-msg msg-error msg-clear help-block"><div class="msg-con">'+msg+'</div><s class="msg-icon"></s></div>';
        obj.append(content);
    }
});