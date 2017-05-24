/**
 * @description 成功弹窗
 * */
function successTip(body,title,okHide){
    $.confirm({
        body : body,
        title : title,
        okHide : okHide
    });
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
 * Description 普通编辑弹出页面
 * */
function editTip(body,header,okHide,width,height) {
    $.confirm({
        body: body,
        title: header,
        okBtn: '保存',
        cancelBtn: '取消',
        height: !height ? '350px' : height,
        width: !width ? '500px' : width,
        okHide: okHide,
    });
}

/**
 * Description 状态变更
 * */
function statusChange(url,id,status,obj){
    $.get(url,
        {id:id,status:status},
        function(data){
            obj.attr("data-status",status == 1 ? 0 : 1);
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
 * Description 全选/反选
 * */
function sel(allObj,selObj){
    if(allObj.is(':checked') == true){
        $(selObj).prop('checked',true);
    }else{
        $(selObj).prop('checked',false);
    }
}
