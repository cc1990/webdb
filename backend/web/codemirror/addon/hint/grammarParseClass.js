/**
 * Created by user on 2016/12/16.
 */
var grammarParseClass = function(subQueryTree,token,commonKeywords){
    var operatorKeywords = ['select','update','delete','drop','alter','truncate','insert'];
    var subQueryTree = subQueryTree;
    var sqlString = '',oriSqlString = '',parentSqlString = [];
    var token = token;
    var grammar = [];
    var template = {
        "getTables" : {
            "select" : /(from|left join|right join|inner join|full join|natural join|join)[\s\t]+([A-Za-z0-9.`_]+)[\s\t]*(?:as){0,1}[\s\t]*([A-Za-z0-9`_]*)/gi,
            "update" : /(update|left join|right join|inner join|full join|natural join|join)[\s\t]+([A-Za-z0-9.`_]+)[\s\t]*(?:as){0,1}[\s\t]*([A-Za-z0-9`_]*)/gi,
            "delete" : /(from|left join|right join|inner join|full join|natural join|join)[\s\t]+([A-Za-z0-9.`_]+)[\s\t]*(?:as){0,1}[\s\t]*([A-Za-z0-9`_]*)/gi,
            "insert" : /(insert into|left join|right join|inner join|full join|natural join|join)[\s\t]+([A-Za-z0-9.`_]+)[\s\t]*(?:as){0,1}[\s\t]*([A-Za-z0-9`_]*)/gi,
            "alter" : /(delete)[\s\t]+(\w+)[\s\t]*(\w*)/gi,
            "from" : /(from)[\s\t]+([\w.]+)[\s\t]*(\w*)/gi,
        }
    };
    var config = {
        "defaultKeywords" : 'all',
        "defaultDatabases" : 'all',
        "defaultTables" : {"*":""},
    }

    /**
     * @description 执行函数
     * */
    this.execute = function(){
        grammar['keywords'] = config.defaultKeywords;
        grammar['databases'] = config.defaultDatabases;
        grammar['tables'] = config.defaultTables;
        grammar['columns'] = [];
        setSqlString(subQueryTree);
        var regexp = new RegExp(window.operationCur,"i");
        oriSqlString = sqlString.replace(regexp,token.string);
        grammarParse(sqlString);
        getParentTable();
    }

    this.getTables = function(string){return getTables(string)};

    /**
     * @description 语法解析器
     * */
    function grammarParse(){
        var firstWord = /[\s\t]*(\w+)/i.exec(sqlString);
        if(firstWord == null){return grammar;}else{firstWord = firstWord[1]}
        if(token.string.indexOf(".") >= 0){
            return getOnlyDbTable();
        }
        if(token.string.indexOf("`") == 0){
            if(sqlString.indexOf("."+window.operationCur) >= 0){
                return getOnlyDbTable();
            }
        }
        if(operatorKeywords.indexOf(firstWord) >= 0){
            return typeof(eval(firstWord + "Parse")) === 'function' ? eval(firstWord + "Parse()") : commonParse();
        }else{
            return grammar;
        }
    }

    /**
     * @description 获取继承过来的表及别名
     * */
    function getParentTable(){
        var tables;
        for(var i in parentSqlString){
            tables = getTables(parentSqlString[i]);
            for(var key in tables){
                grammar['tables'][key] = tables[key];
            }
        }
    }

    /**
     * @description 设置单条操作语句
     * */
    function setSqlString(tmpQueryTree){
        if(typeof(tmpQueryTree) == "string"){
            sqlString = tmpQueryTree.toLowerCase();
            parentSqlString = [];
        }else{
            for(var i in tmpQueryTree){
                if(tmpQueryTree[i]['sql'].indexOf(window.operationCur) >= 0){
                    sqlString = tmpQueryTree[i]['sql'].toLowerCase();
                    break;
                }
                if(typeof(tmpQueryTree[i]['child']) != 'undefined'){
                    parentSqlString[tmpQueryTree[i]['indexLeft']] = tmpQueryTree[i]['sql'];
                    setSqlString(tmpQueryTree[i]['child']);
                }
            }
        }
    }

    /**
     * @description 返回结果
     * */
    this.getResult = function(){
        return grammar;
    }

    /**
     * @description 获取相应的表格
     * */
    function getTables(string){
        var firstWord = /[\s\t]*(\w+)/i.exec(sqlString);
        firstWord = firstWord[1].toLowerCase();
        var regexp = template.getTables[firstWord];
        if(regexp == '' || regexp == undefined || regexp == false) return [];
        var result = [],tables = [];
        do{
            result = regexp.exec(string);
            if(result != null){
                if(typeof(commonKeywords[result[2].toLowerCase()]) == 'undefined') {
                    tables[result[2].replace(/`/g,'')] = result[3].replace(/`/g,'');
                }
            }
        }while(result != null);
        return Object.keys(tables).length == 0 ? config.defaultTables : tables;
    }


    /**
     * @description select语句语法解析
     * */
    function selectParse(){
        grammar['tables'] = getTables(sqlString);
        grammar['tables']["@now.*"] = '';
        var column = [];
        for(var key in grammar['tables']){
            if(key.substr(0,4) != '@now')   column.push(key+".*");
        }
        grammar['columns'] = column;
    }

    /**
     * @description select语句语法解析
     * */
    function updateParse(){
        grammar['tables'] = getTables(sqlString);
        var column = [];
        for(var key in grammar['tables']){
            column.push(key+".*");
        }
        grammar['columns'] = column;
    }

    /**
     * @description select语句语法解析
     * */
    function deleteParse(){
        grammar['tables'] = getTables(sqlString);
        var result = getTables(sqlString),column = [];
        for(var key in result){
            column.push(key+".*");
        }
        grammar['columns'] = column;
    }

    /**
     * @description select语句语法解析
     * */
    function insertParse(){
        grammar['tables'] = getTables(sqlString);
        var result = getTables(sqlString),column = [];
        for(var key in result){
            column.push(key+".*");
        }
        grammar['columns'] = column;
    }

    /**
     * @description 公共解析
     * */
    function commonParse(){
        return true;
    }

    /**
     * @description 若存在.则只取库和表
     * */
    function getOnlyDbTable(){
        var str = "[a-zA-Z0-9._`]+"+window.operationCur;
        var regexp = new RegExp(str,'i');
        var result = regexp.exec(sqlString);
        var table = [],tableName = '';
        result = result[0].replace(new RegExp(window.operationCur,'i'),"").replace(/`/g,"");
        result = result.charAt(result.length-1) == '.' ? result.substr(0,result.length-1) : result;
        result = result.split(".");
        if(result.length == 0){
            grammar['keywords'] = "all";
            delete grammar['databases'];delete grammar['tables'];delete grammar['columns'];
        }else if(result.length == 1){
            delete grammar['keywords'];delete grammar['databases'];
            //查看是否存在别名，若存在转成正式名称
            tableName = aliasToNormal(result[0],sqlString,parentSqlString);
            table[tableName+".*"] = '';
            grammar['tables'] = table;grammar['columns'].push([result[0]]+".*");
            grammar['columns'] = [tableName+".*"];
        }else if(result.length == 2){
            delete grammar['keywords'];delete grammar['databases'];delete grammar['tables'];
            grammar['columns'].push(result[0]+"."+result[1]+"."+"*");
        }else{
            grammar['keywords'] = "all";
            delete grammar['databases']; delete grammar['tables'];delete grammar['columns'];
        }
        return grammar;
    }

    /**
     * @description 查看是否存在别名，若存在则转换成正式名称
     * */
    function aliasToNormal(tableName,string,parentString){
        var result = getTables(string);
        for(var key in result){
            if(result[key] == tableName){
                return key;
            }
        }
        for(var key in parentString){
            result = getTables(parentString[key]);
            for(var key in result){
                if(result[key] == tableName){
                    return key;
                }
            }
        }
        return tableName;
    }
}