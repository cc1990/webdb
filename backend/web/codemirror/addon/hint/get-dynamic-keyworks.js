/**
 * Created by user on 2016/12/6.
 */
    var path = document.currentScript.src;
    path = path.replace("get-dynamic-keyworks.js","");
    loadScript(path + "grammarParseClass.js");
    loadScript(path + "parseClass.js");
    $(document).ready(function(){
        //全局变量
        //window.firstWord = ["insert","select","drop","update","desc","describe","show","create","truncate"];    //文本框每行首个字母，若在则表示为sql首行
        window.operationCur = "@cur";
        window.explain = "@explain";
        window.spechars = ['~','!','@','#','$','%','^','&','*','(',')',',',';','{','}','[',']',':','"',"'",'<','>','?','/','\\','|',' ','=','+'];
        window.now = [];
        window.sqlString = '';
    });

    /**
     * @description 获取动态关键字
     * @param object editor codemirror实例
     * @param object keyworks 初始化关键字
     * */
    function getDynamicKeywords(editor,oldKeyworks){
        var cur = editor.getCursor();
        var token = editor.getTokenAt(cur);
        var text = getText(editor,cur,token);

        //获取当前操作的主机，数据库和表
        getStatus(editor.getTextArea());

        //过滤特殊字符
        if(filterSpechars(text,token.string)) return returnKey(oldKeyworks);

        //解析sql语法
        var parseArr = parseSqlString(text,token,oldKeyworks);

        var newKeyWords = getKeywords(parseArr,oldKeyworks);
        return newKeyWords;
        //return returnKey(oldKeyworks,newKeyWords);
    }

    /**
     * @description 给当前操作的TAB标签赋值服务器和数据库和表
     * */
    function getStatus(content){
        var id = $(content).attr("id");
        var parent = $('#'+id).parent().parent();
        window.now['host'] = parent.find("input[name='DBHost']").val();
        window.now['database'] = parent.find("input[name='DBName']").val();
        window.now['table'] = parent.find("input[name='tbname']").val();
    }

    /**
     * @description 解析sql语句,将string语句按照一定规则解析成一个多维数组
     * @param text
     * */
    function parseSqlString(text,token,commonKeywords){
        if(text.indexOf(window.operationCur) == -1)   return [];
        if(text == '' || text == undefined || text == false) return [];
        //将sql语句所有子查询进行解析
        var resolveClass = new parseClass(text,window.operationCur);
        resolveClass.execute();
        var subQueryTree = resolveClass.getResult();

        //解析sql语法，返回结果
        var singleResolveClass = new grammarParseClass(subQueryTree,token,commonKeywords);
        singleResolveClass.execute();
        var grammarParseResult = singleResolveClass.getResult();

        return grammarParseResult;
    }

    /**
     * @description 获取库和表的keywords
     * */
    function getKeywords(parseArr,oldKeywords){
        var keywords = {},parseArrSort = [];
        var hostNow = window.now['host'];
        var databasesNow = window.now['database'];
        //parseArrSort['columns'] = parseArr['columns'];parseArrSort['tables'] = parseArr['tables'];
        //parseArrSort['databases'] = parseArr['databases'];parseArrSort['keywords'] = parseArr['keywords'];
        if(typeof (parseArr['columns']) != 'undefined') parseArrSort['columns'] = parseArr['columns'];
        if(typeof (parseArr['tables']) != 'undefined') parseArrSort['tables'] = parseArr['tables'];
        if(typeof (parseArr['databases']) != 'undefined') parseArrSort['databases'] = parseArr['databases'];
        if(typeof (parseArr['keywords']) != 'undefined') parseArrSort['keywords'] = parseArr['keywords'];
        parseArr = parseArrSort;
        window.databases = typeof(window.databases) == "undefined" ? [] : window.databases;
        window.databases[hostNow] = typeof(window.databases[hostNow]) == "undefined" ? [] : window.databases[hostNow];
        window.databases[hostNow][databasesNow] = typeof(window.databases[hostNow][databasesNow]) == "undefined" ? [] : window.databases[hostNow][databasesNow];

        for(var key in parseArr){
            if(key == "keywords"){
                if(parseArr['keywords'] == "all"){
                    for(var i in oldKeywords){keywords[i] = {"type":"keywords","displayText":i,"text":i};}
                }
            }
            if(key == "databases"){
                if(parseArr[key] == 'all'){
                    for(var i in window.databases[hostNow]["databases"]){keywords[window.databases[hostNow]["databases"][i]] = {"type":"database","displayText":window.databases[hostNow]["databases"][i],"text":window.databases[hostNow]["databases"][i]};}
                }else{
                    if(typeof(parseArr[key]) == "object"){
                        for(var i in parseArr[key]){keywords[parseArr[key][i]] = {"type":"database","displayText":parseArr[key][i],"text":parseArr[key][i]};}
                    }
                }
            }
            if(key == "tables"){
                var tmpKeywords = [],tmpDatabase = '',tmpTable = '';
                for(var i in parseArr[key]){
                    tmpKeywords = i.split(".");
                    tmpDatabase = tmpKeywords.length == 2 ? tmpKeywords[0] : databasesNow;
                    tmpDatabase = tmpDatabase == "@now" ? databasesNow : tmpDatabase;
                    tmpTable = tmpKeywords.length == 2 ? tmpKeywords[1] : tmpKeywords[0];
                    if(parseArr[key][i] != ''){
                        keywords[parseArr[key][i]] = {"type":"table","displayText":parseArr[key][i],"text":parseArr[key][i]};
                    }
                    if(tmpTable == '*'){
                        for(var j in window.databases[hostNow][databasesNow]["tables"]){
                            keywords[window.databases[hostNow][databasesNow]["tables"][j]] = {"type":"table","displayText":window.databases[hostNow][databasesNow]["tables"][j],"text":window.databases[hostNow][databasesNow]["tables"][j]};
                        }
                    }else{
                        keywords[tmpTable] = {"type":"table","displayText":tmpTable,"text":tmpTable};
                    }
                }
                //for (var i in window.databases[hostNow][databasesNow]) {
                //    keywords[i] = true;
                //}
            }
            if(key == "columns"){
                var tmpKeywords = [],tmpDatabase = '',tmpTable = '',tmpColumns = [];
                for(var i in parseArr[key]){
                    tmpKeywords = parseArr[key][i].split(".");
                    tmpDatabase = tmpKeywords.length == 3 ? tmpKeywords[0] : databasesNow;
                    tmpTable = tmpKeywords[tmpKeywords.length-2];
                    tmpTable = tmpTable == "*" ? window.now['table'] : tmpTable;
                    tmpColumns = tmpKeywords[tmpKeywords.length-1];
                    if(tmpColumns == '*'){
                        for(var j in window.databases[hostNow][tmpDatabase][tmpTable]){
                            keywords[window.databases[hostNow][tmpDatabase][tmpTable][j]] = {"type":"column","displayText":window.databases[hostNow][tmpDatabase][tmpTable][j],"text":window.databases[hostNow][tmpDatabase][tmpTable][j]};
                        }
                    }else{
                        keywords[tmpColumns] = {"type":"column","displayText":tmpColumns,"text":tmpColumns};
                    }
                }
            }
        }
        return keywords;
    }

    /**
     * @description 获取text完整内容
     * @param edit 编辑器
     * @param cur 光标所在位置
     * @param token 编辑字符对象
     * */
    function getText(edit,cur,token){
        var text = getAllText(edit,cur,token);
        text = text.replace(/(")([^"]*)(")/g,window.explain);//替换引号内的内容为[$explain]
        text = text.replace(/(')([^']*)(')/g,window.explain);//替换引号内的内容为[$explain]
        text = text.replace(/".*]/i,window.explain);
        text = text.replace(/'.*]/i,window.explain);
        text = getSingleSql(text);  //获取单条sql语句
        return text;
    }

    /**
     * @description 获取全部内容,替换操作字符为[$cur],替换换行符为空格
     * */
    function getAllText(edit,cur,token){
        var content = "",line,operationCur = window.operationCur;
        for(var i = 0; i<edit.lineCount(); i++){
            if(cur.line == i) {
                line = edit.getLine(i);
                if(token.string.substr(0,1) == '"' || token.string.substr(0,1) == "'") operationCur = token.string.substr(0,1) + operationCur;
                if(token.string.substr(-1) == '"' || token.string.substr(-1) == "'") operationCur = operationCur + token.string.substr(-1) ;
                content += line.substr(0, token.start) + operationCur + line.substring(token.end, line.length) + " ";
            }else{
                content += edit.getLine(i) + " ";
            }
        }
        return content;
    }

    /**
     * @description 获取单条sql语句,以分号隔离
     * @param string 全部sql语句
     * */
    function getSingleSql(text){
        var curIndex = text.indexOf(window.operationCur),indexBetween;
        var index = getAllIndex(text,";",[]);
        if(curIndex == -1){
            return index.length == 0 ? text : text.substr(index[index.length]);
        }else{
            indexBetween = getIndexBetween(index,curIndex,text.length,0);
            text = text.substring(indexBetween['start'],indexBetween['end']);
            if(text.indexOf(";") == 0)  text = text.substr(1);
            return text.replace(";","");
        }
    }

    /**
     * @description 在数组中查找给定值间隔最小的左边和右边的值
     * @param index 数组
     * @param search 检索的数字
     * @param max 检索最大值
     * @param min 检索最小值
     * */
    function getIndexBetween(index,search,max,min){
        var start = min,end = max,indexBetween = [];
        index = index.sort();
        for(var i in index){
            if(search >= index[i]) start = index[i];
            if(search <= index[i] && end == max) end = index[i];
        }
        indexBetween['start'] = start;
        indexBetween['end'] = end;
        indexBetween['index'] = search;
        return indexBetween;
    }

    /**
     * @description 获取所有index
     * @param text 文本
     * @param str 检索字符
     * @param index
     * */
    function getAllIndex(text,str,index){
        if((pos = text.indexOf(str)) == -1){
            return index.sort();
        }else{
            index.push(pos);
            text = text.substr(0,pos) + " " + text.substr(pos+1);
            return getAllIndex(text,str,index)
        }
    }

    /**
     * @description 返回拼接后的keywords
     * @param oldKeywords sql关键字
     * @param newKeywords 字段关键字
     * */
    function returnKey(oldKeyworks,newKeywords){
        var returnKeys = JSON.stringify(oldKeyworks);
        returnKeys = JSON.parse(returnKeys);
        for(var key in newKeywords){
            if(newKeywords[key] == true){
                returnKeys[key] = newKeywords[key];
            }
        }
        return returnKeys;
    }

    /**
     * @description 过滤操作字符为特殊字符
     * @param text 操作的文本
     * @param string 当前操作字符
     * */
    function filterSpechars(text,string){
        if(text.indexOf(window.operationCur) == -1)
            return true;
        for(var i in window.spechars){
            if(string.indexOf(window.spechars[i]) >= 0)
                return true;
        }
        return false;
    }

    /**
     * @description 过滤数据库
     * */
    function filterDatabase(){
        var ip = {},databases = [];
        $("#DBHost option").each(function(i){
            ip[$(this).attr('server_id')] = $(this).val();
        });
        $("#DBName li").each(function(i){
            var server_id = $(this).attr("class");
            server_id = server_id.split(" ");
            server_id = server_id[1].split("_");server_id = server_id[1];
            var dbname = $(this).find("div").attr("data-dbname");
            if(typeof(databases[ip[server_id]]) == 'undefined') databases[ip[server_id]] = [];
            databases[ip[server_id]].push(dbname);
        });
        for(var i in window.databases){
            for(var j in window.databases[i]){
                if(databases[i].indexOf(j) == -1){
                    delete window.databases[i][j];
                }
            }
        }
    }

    /**
     * @description 引入外部文件
     * */
    function loadScript(url) {
        // Adding the script tag to the head as suggested before
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;

        // Fire the loading
        document.body.appendChild(script);
    }

    /**
     * @description 修改选中的值
     * */
    function modifySelectVal(completion,token){
        if(token.string.charAt(0) == "`"){
            if(typeof(completion) == 'object') {
                completion.text = completion.text + "`";
            }else{
                completion = completion + "`";
            }
        }
        return completion;
    }
