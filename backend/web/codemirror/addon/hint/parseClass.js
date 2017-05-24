/**
 * Created by user on 2016/12/13.
 */
var parseClass = function(text,searchCur){
    var text = text,searchCur = searchCur;
    var result = [];
    var subQuery = [];
    var subReplace = '@sub';

    /**
     * @description 开始执行
     * */
    this.execute = function(){
        //初始化
        var root = [];
        root["sql"] = text;
        root["indexLeft"] = -1;
        root["indexRight"] = parseInt(text.length-1);
        subQuery.push(root);
        //解析所有子查询
        parseSubQuery();
    }

    /**
     * @description 获取解析结果
     * */
    this.getResult = function(){
        return subQuery;
    }

    /**
     * @description 根据括号解析数据，解析出所有子查询
     * */
    function parseSubQuery(){
        var indexLeft = getAllIndex(text,"(",[]);
        var indexRight = getAllIndex(text,")",[]);
        var index = {},parentIndex;
        for(var i in indexLeft){
            index[indexLeft[i]] = "left";
        }
        for(var j in indexRight){
            index[indexRight[j]] = "right";
        }
        for(var key in index){
            parentIndex = getParentIndex(key,index);
            setValue(parseInt(key),parseInt(parentIndex),index[key]);
        }
        subQuery = optimizerSubQuery(subQuery,-2);
    }

    /**
     * @descriptoin 根据索引给subQuery变量赋值
     * */
    function getParentIndex(indexNow,index){
        var tmp = [];
        for(var key in index){
            if(key == indexNow) return tmp.length == 0 ? -1 : tmp[tmp.length-1];
            if(index[key] == 'left') {
                tmp.push(key);
            }else{
                tmp.pop();
            }
        }
    }

    /**
     * @description 设置subQuery值
     * */
    function setValue(indexNow,parentIndex,type){
        if(type == 'left'){
            subQuery = setValueLeft(subQuery,indexNow,parentIndex);
        }else{
            subQuery = setValueRight(subQuery,indexNow,parentIndex);
        }
    }

    function setValueLeft(tmpSubQuery,indexNow,parentIndex){
        var tmpSingerSub = [];
        for(var key in tmpSubQuery){
            if(tmpSubQuery[key]['indexLeft'] == parentIndex){
                tmpSubQuery[key]['child'] = typeof(tmpSubQuery[key]['child']) == 'undefined' ? [] : tmpSubQuery[key]['child'];
                tmpSingerSub['indexLeft'] = indexNow;
                tmpSubQuery[key]['child'].push(tmpSingerSub);
                break;
            }else{
                if(typeof(tmpSubQuery[key]['child']) != 'undefined') {
                    tmpSubQuery[key]['child'] = setValueLeft(tmpSubQuery[key]['child'],indexNow,parentIndex);
                }
            }
        }
        return tmpSubQuery;
    }

    function setValueRight(tmpSubQuery,indexNow,parentIndex){
        for(var key in tmpSubQuery){
            if(tmpSubQuery[key]['indexLeft'] == parentIndex){
                tmpSubQuery[key]['indexRight'] = indexNow;
                tmpSubQuery[key]['sql'] = text.substring(parentIndex+1,indexNow);
                break;
            }else{
                if(typeof(tmpSubQuery[key]['child']) != 'undefined') {
                    tmpSubQuery[key]['child'] = setValueRight(tmpSubQuery[key]['child'],indexNow,parentIndex);
                }
            }
        }
        return tmpSubQuery;
    }

    /**
     * @description 递归修改subQueryTmp的值
     * */
    function modifyLeft(obj,search,indexRight,parentIndexLeft){
        for(var key in obj){
            if(obj[key].indexLeft == search){
                obj[key].indexRight = indexRight;
                obj[key].sql = text.substr(obj.indexLeft,obj.indexRight);
            }else{
                if(typeof(obj.child) != "undefined"){
                        var childObj = modifyLeft(obj[key].child,search,indexRight,obj[key].indexLeft);
                        obj.child = childObj[0];
                        parentIndexLeft = childObj[1];
                }
            }
        }
        return [obj,parentIndexLeft];
    }

    /**
     * @description 优化子查询，1.无又括号则默认到最后 2.若存在子查询，则用@sub替换 3.判断是否为子查询，若不是则打上标签
     * */
    function optimizerSubQuery(tmpSubQuery,parentIndexLeft){
        var leftPos = 0, rightPos = 0;
        for(var key in tmpSubQuery){
            if(typeof(tmpSubQuery[key]['indexRight']) == 'undefined'){
                tmpSubQuery[key]['indexRight'] = parseInt(text.length-1);
                tmpSubQuery[key]['sql'] = text.substring(parseInt(tmpSubQuery[key]['indexLeft']+1),parseInt(tmpSubQuery[key]['indexRight']));
            }
            if(typeof(tmpSubQuery[key]['child']) != 'undefined'){
                var sql = '';
                for(var i in tmpSubQuery[key]['child']){
                    if(typeof(tmpSubQuery[key]['child'][i]['indexRight']) == 'undefined'){
                        tmpSubQuery[key]['child'][i]['indexRight'] = parseInt(text.length-1);
                        tmpSubQuery[key]['child'][i]['sql'] = text.substring(parseInt(tmpSubQuery[key]['child'][i]['indexLeft']+1),parseInt(tmpSubQuery[key]['child'][i]['indexRight']));
                    }
                    //if(isSubQuery(tmpSubQuery[key]['child'][i]['sql'])) {
                        leftPos = i > 0 ? (tmpSubQuery[key]['child'][i - 1]['indexRight'] - tmpSubQuery[key]['indexLeft']) : 0;
                        rightPos = i > 0 ? tmpSubQuery[key]['child'][i]['indexLeft'] - tmpSubQuery[key]['child'][i - 1]['indexRight'] - 1 : tmpSubQuery[key]['child'][i]['indexLeft'] - tmpSubQuery[key]['indexLeft'] - 1;
                        sql += tmpSubQuery[key]['sql'].substr(leftPos, rightPos) + subReplace;
                    //}else{
                    //    sql += tmpSubQuery[key]['sql'].substring(tmpSubQuery[key]['indexLeft'], tmpSubQuery[key]['indexRight']);
                    //}
                }
                tmpSubQuery[key]['sql'] = sql + tmpSubQuery[key]['sql'].substring((tmpSubQuery[key]['child'][i]['indexRight']-tmpSubQuery[key]['indexLeft']),tmpSubQuery[key]['sql'].length);
                tmpSubQuery[key]['child'] = optimizerSubQuery(tmpSubQuery[key]['child'],tmpSubQuery[key]['indexLeft']);
            }
            tmpSubQuery[key]['parent'] = parentIndexLeft;
        }
        return tmpSubQuery;
    }

    /**
     * @description 判断是否为子查询
     * */
    function isSubQuery(sqlString){
        var firstWord = /[\s\t]*(\w+)/i.exec(sqlString);
        if(firstWord == null){return false;}else{firstWord = firstWord[1]}
        var operatorKeywords = ['select','update','delete','drop','alter','truncate','insert'];
        return (operatorKeywords.indexOf(firstWord) >= 0) ? true : false;
    }
};
