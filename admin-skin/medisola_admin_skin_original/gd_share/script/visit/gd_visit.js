// 파라미터 Parsing
// 절대로 gd_visit.js 파일 이름 변경하지 말 것 (해당 파일 이름으로 script 탐색)
var VISITPARAM = (function () {
    var script = document.getElementsByTagName('script');
    for (const idx in script) {
        if (typeof script[idx].src !== 'undefined' && script[idx].src.indexOf('gd_visit') !== -1) {
            var param = script[idx].src
                .replace(/^[^\?]+\?/, '')
                .replace(/#.+$/, '')
                .split('&');
            var queries = {}
                , query;
            while(param.length){
                query = param.shift().split('=');
                queries[query[0]] = query[1];
            }
            break;
        }
    }
    return queries;
})();
(function() {
    try {
        const VISITPARAMREQUEST = VISITPARAM.requestData;
        const VISITPARAMDATA = JSON.parse(decodeURIComponent(VISITPARAMREQUEST));
        const VISITURL = decodeURIComponent(VISITPARAM.requestUrl);
        const VISITURLPARAM = {
            "base_time": VISITPARAMDATA.base_time,
            "mall_id": VISITPARAMDATA.mall_id,
            "user_id": VISITPARAMDATA.user_id,
            "refer": VISITPARAMDATA.refer,
            "uri": VISITPARAMDATA.uri,
            "domain": VISITPARAMDATA.domain,
            "country": VISITPARAMDATA.country,
            "solution": VISITPARAMDATA.solution,
        };

        $.ajax({
            url : VISITURL,
            type : "POST",
            headers: {'Content-Type': 'application/json; charset=UTF-8'},
            data : JSON.stringify(VISITURLPARAM),
            timeout: 5000,
            complete:function(){
            },
            success : function(data){
                //console.log('success');
            },
            error : function(request, status, error){
                console.log(error);
            },
            fail : function() {
                //console.log('fail');
            }
        });
    } catch(e) {
    }
})();