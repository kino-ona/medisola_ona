'use strict';
if (!window.console) window.console = {
    log: function() {}
};
var NetError = function($code, $command, $message) {
    this.code = $code;
    this.command = $command;
    this.message = $message
};
var Net = function() {
	this.xhr = null;
    this.load = __load;
    this.loading = __loading;
    this.success = null;
    this.fail = null;
    this.start = null;
    this.end = null;
	this.cancel = null;

    this.context = this;

    function __loading() {
        return xhr ? true : false
    }

    function __start() {
        if (this.start) {
            this.start.call(this.context)
        }
    }

    function __complete($command, $result) {
        if (this.success) {
            this.success.call(this.context, $command, $result)
        }
    }

    function __error($err) {
        if (this.fail) {
            this.fail.call(this.context, $err.code, $err.message)
        }
    }

    function __end() {
        if (this.end) {
            this.end.call(this.context)
        }
    }
	
	function __cancel() {
        if (this.cancel) {
            this.cancel.call(this.context)
        }
    }

    function __load($type, $command, $params, $options, $dataType) {
        if (!_.isString($command) || $.trim($command).length === 0) {
            alert('Wrong command. command=' + $command);
            return
        }
        if ($options) {
            if ($options.start) this.start = $options.start;
            if ($options.success) this.success = $options.success;
            if ($options.fail) this.fail = $options.fail;
            if ($options.end) this.end = $options.end;
            if ($options.cancel) this.cancel = $options.cancel
			if ($options.context) this.context = $options.context
        }
        var url = '/app' + $command;
        var setting = {
            type: (_.isNull($type) ? 'GET' : $type),
            url: url,
            dataType: 'json',
//			cache: true,
            context: this,
            beforeSend : function(xhr) {
                xhr.overrideMimeType('text/html; charset=EUC-KR')
                __start.call(this)
            },
            success: function($json) {
                if (_.isUndefined($json) || _.isNull($json)) {// || _.isUndefined($json.code)
                    __error.call(this, new NetError(-9999999, null, 'Unknown error'))
                } else {
                    __complete.call(this, $json)
                }
            },
            error: function($jqXHR, $textStatus, $errorThrown) {
                if ($errorThrown === 'abort') {
					__cancel.call(this)
				} else {
                    __error.call(this, new NetError(-1000000, $command, 'A ' + $textStatus + ' has occurred. '))
                }
            },
            complete: function() {
                __end.call(this)
            }
        };
        if (_.isString($dataType)) {
            setting.dataType = $dataType;//'xml','json','script','html'
        }
        if (!$params) $params = {};
        if ($params instanceof FormData) {
            setting.contentType = false;
            setting.processData = false;
            $params.append('timezoneOffset', new Date().getTimezoneOffset())
        } else {
            $params.timezoneOffset = new Date().getTimezoneOffset()
        }
        setting.data = $params;
        this.xhr = $.ajax(setting)
    }
};
Net.post = function($command, $params, $options, $dataType) {
    var net = new Net();
    setTimeout(function() {
        net.load('POST', $command, $params, $options, $dataType)
    }, 1);
    return net
};
Net.get = function($command, $params, $options, $dataType) {
    var net = new Net();
    setTimeout(function() {
        net.load('GET', $command, $params, $options, $dataType)
    }, 1);
    return net
};
Net.ajax = function($command, $params, $options, $dataType) {
    var net = new Net();
    setTimeout(function() {
        net.load(null, $command, $params, $options, $dataType)
    }, 1);
    return net
};
Net.stop = function($net) {
	setTimeout(function() {
        if(!_.isUndefined($net.xhr) && $net.xhr.readyState != 4) {
			$net.xhr.abort();
		}
    }, 1);
};
var Validate = {
    regExpEmail: /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
    regExpPassword: /^[0-9a-zA-Z!@#$%^&*()?+-_~=\/]{8,20}$/,
    regExpPosPassword: /^[0-9]{4}$/,
    regExpHomepage: /^((http(s?))\:\/\/)([0-9a-zA-Z\-]+\.)+[a-zA-Z]{2,6}(\:[0-9]+)?(\/\S*)?$/,
    regExpInteger: /^[+|-]?[0-9]+/,
    regExpUserName: /^[a-zA-Z°¡-Èþ]{2,10}/,
    regExpDomaeName: /^[a-zA-Z°¡-Èþ0-9]{1,20}/,
    regExpSomaeName: /^[a-zA-Z°¡-Èþ0-9]{1,10}/,
    regExpMobile: /^\d{2,3}-\d{3,4}-\d{4}$/,
    regExpPhone: /^\d{2,3}-\d{3,4}-\d{4}$/,
    regExpKorean: /^[°¡-Èþ+$/,regExpKoreanAndSpace:/^[°¡-Èþ]+$/,
    regExpKoreanEnglishAndNumber: /^[a-zA-Z°¡-Èþ0-9]{1,20}$/,
    regExpEnglish: /^[a-zA-Z]+$/,
    regExpEnglishAndSpace: /^[a-zA-Z\s]+$/,
    regExpNumber: /^[0-9]+$/,
    regExpEnglishAndNumber: /^[a-zA-Z0-9]+$/,
    regExpJumin: /^(?:[0-9]{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[1,2][0-9]|3[0,1]))-[1-4][0-9]{6}$/,
    filter: function($regExp, $val) {
        return $regExp.test($val)
    },
    email: function($val) {
        return this.regExpEmail.test($val)
    },
    password: function($val) {
        return this.regExpPassword.test($val)
    },
    posPassword: function($val) {
        return this.regExpPosPassword.test($val)
    },
    homepage: function($val) {
        return this.regExpHomepage.test($val)
    },
    integer: function($val) {
        return this.regExpInteger.test($val)
    },
    userName: function($val) {
        return this.regExpUserName.test($val)
    },
    domaeName: function($val) {
        return this.regExpDomaeName.test($val)
    },
    somaeName: function($val) {
        return this.regExpSomaeName.test($val)
    },
    mobile: function($val) {
        return this.regExpMobile.test($val)
    },
    phone: function($val) {
        return this.regExpPhone.test($val)
    },
    korean: function($val) {
        return this.regExpKorean.test($val)
    },
    koreanAndSpace: function($val) {
        return this.regExpKoreanAndSpace.test($val)
    },
    koreanEnglishAndNumber: function($val) {
        return this.regExpKoreanEnglishAndNumber.test($val)
    },
    english: function($val) {
        return this.regExpEnglish.test($val)
    },
    englishAndSpace: function($val) {
        return this.regExpEnglishAndSpace.test($val)
    },
    englishAndNumber: function($val) {
        return this.regExpEnglishAndNumber.test($val)
    },
    number: function($val) {
        return this.regExpNumber.test($val)
    },
    jumin: function($val) {
        return this.regExpJumin.test($val)
    }
};

function onlyNumber() {
    var key = window.event ? event.keyCode : event.which;
    if ((event.shiftKey == false) && ((key > 47 && key < 58) || (key > 95 && key < 106) || key == 35 || key == 36 || key == 37 || key == 39 || key == 8 || key == 46)) {
        return true
    } else {
        return false
    }
}

function numberFormat(x) {
    var parts = x.toString().split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".")
}