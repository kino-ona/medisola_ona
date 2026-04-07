function gd_captcha_reload() {
    $('#captchaImg').removeAttr('src');
    setTimeout(function () {
        var someDate = new Date();
        someDate = someDate.getTime();
        $('#captchaImg').attr('src', '../board/captcha.php?ch=' + someDate);
    }, 1);
}

/*
function gd_add_upload() {
    uploadBoxCount = $('#uploadBox').find('.file-upload').length;
    if (uploadBoxCount > 4) {
        alert("업로드는 최대 5개만 지원합니다");
        return;
    }

    var addUploadBox = _.template(
        $("script.template").html()
    );

    $('#uploadBox:last').append(addUploadBox({idx: uploadBoxCount + 1}));
}

function uploadRemove(obj) {
    obj.closest('.file-upload').remove();
}
*/

var boardSubmitFl = true;
$(document).ready(function () {
    var params = {};
    if(typeof bdId != 'undefined') {
        params = {
            bdId : bdId,
            bdSno : bdSno,
        };
    }
    $('.js_board_layer').bind('click',function(){
        var type = $(this).data('type');
        $.ajax({
            method: "POST",
            data : params,
            cache: false,
            url: "../share/layer_"+type+"_select.php",
            success: function (data) {
                $('#popupSelectGoods').empty().append(data);
                $('#popupSelectGoods').show();
            },
            error: function (data) {
                alert(data.message);
            }
        });
    })

    $('body').on('click','.js_layer_close',function(e){
        e.stopPropagation();
        $('.js_select_confirm').off('click');
        $('#popupSelectGoods').empty();
        $('#popupSelectGoods').hide();
    })

    $('body').on('click','.js_select_remove',function(){
        $(this).closest('div.js_select_item').remove();
        $('.itemselect_comment').show();
    })

    uploadBoxCount = $('#uploadBox').find('.file_upload').length;
    $('.file_upload:last').find('label').attr('for', 'attach' + uploadBoxCount);
    $('.file_upload:last').find('input[type=file]').attr('id', 'attach' + uploadBoxCount);

    if (cfgUploadFl == 'y') {
        $('#addUploadBtn').bind('click', function () {
            gd_add_upload();
        });
    }

    $("#frmWrite").validate({
        submitHandler: function (form) {
            if (boardSubmitFl === false) {
                alert(__('처리중입니다. 잠시만 기다려주세요.'));
                return false;
            }
            if($(form).find('[name=uploadType]').val() == 'ajax') {
                $('input:file').prop('disabled', true);
            }
            if (cfgEditorFl == 'y') {
                oEditors.getById["editor"].exec("UPDATE_CONTENTS_FIELD", []);	// 에디터의 내용이 textarea에 적용됩니다.
            }
            boardSubmitFl = false;
            form.submit();
            $('iframe[name=ifrmProcess]').load(function(){
                var doc = this.contentWindow ? this.contentWindow.document : (this.contentDocument ? this.contentDocument : this.document);
                var root = doc.documentElement ? doc.documentElement : doc.body;
                var result = root.textContent ? root.textContent : root.innerText;
                result = result.replace(/(\s+)|(\s+$)|(\r)|(\n)/g, "");
                if (result.indexOf(__('저장되었습니다')) < 0 && result.indexOf('/board/list.php') < 0) { //실패 시 버튼 활성화
                    boardSubmitFl = true;
                }
            });
        },
        rules: {
            private: 'required',
            contents: {
                required: function (textarea) {
                    if (cfgEditorFl == 'y') {
                        var editorcontent = oEditors.getById[textarea.id].getIR();	// 에디터의 내용 가져오기.
                        editorcontent = editorcontent.replace(/<(?!img).*?>/ig, '').replace('&nbsp;', '');  //이미지테그제외한 테그제거
                        return editorcontent.length === 0;
                    }
                    else {
                        return textarea.value.length === 0;
                    }
                }
            },
            writerNm: 'required',
            writerPw: 'required',
            subject: 'required',
            captchaKey: 'required',
            goodsPt: 'required',
            writerEmail: {
                required: true,
                email: true
            },
        },
        messages: {
            writerNm: {
                required: __('작성자를 입력해주세요.')
            },
            writerPw: {
                required: __('비밀번호를 입력해주세요.')
            },
            subject: {
                required: __('제목을 입력해주세요.')
            },
            contents: {
                required: __('내용을 입력해주세요')
            },
            private: {
                required: __('이용약관과 개인정보 수집 및 이용에 대한 안내 모두 동의해주세요.')
            },
            goodsPt: {
                required: __('평가를 체크해주세요.')
            },
            writerEmail: {
                required: __('이메일을 입력해주세요.'),
                email: __('메일 형식이 틀렸습니다.')
            }
        }
    })

    $("#category").on("change", function(){
        var category = $(this).val();
        var bdId = $('[name="bdId"]').val();
        $.ajax({
            method: "POST",
            url: "../board/board_ps.php",
            data: {mode: 'category', bdId: bdId, 'category': category},
            dataType: 'text',
            cache: false,
            async: false,
        }).success(function (result) {
            $("#contents").val(result).focus();
        }).error(function (e) {
            alert(e.responseText);
        });
    });
});


function gd_init_file_upload() {
    var maxUploadFile = maxFileNumber;
    maxUploadFile = maxUploadFile ? maxUploadFile : 0;
    if (mode == 'modify') {
        for (var i = 0; i < prvFilePath.length; i++) {
            prevImg = gd_get_prev_img(prvFileName[i], prvFilePath[i]);
            $newItem = $("#boardAttach li.item.template").clone();
            $("#boardAttach li.item.template").before($newItem);
            $newItem.removeClass("template");
            $prvFileFace = $newItem.find('.file_face');
            prewFileFace = $prvFileFace[0];
            $prvFileFace.addClass("preview").css({
                "background-image": "url('" + prevImg + "')"
            });
        }

        $("#boardAttach li.item:not(.template)").each(function (index) {
            this.onclick = function () {
                var delFilehtml = '';
                if (confirm(__('첨부된 파일을 삭제하시겠습니까?'))) {
                    delFilehtml = '<input type="hidden" name="delFile[' + index + ']" value="y" />';
                    $('#frmWrite').append(delFilehtml);
                    this.remove();
                }
            };
        });
    }

    $('#boardAttach li.item.template').off().on("click", 'button.file_face', function () {
        var templateContainer = this.parentNode;

        // templateContainer.remove('input');
        var $container = $(templateContainer);
        var $fileFace = $container.find("button.file_face"), fileFace = $fileFace[0];
        var $fileHidden = $container.find("input.file_hidden"), fileHidden = $fileHidden[0];
        var idx = $('input[name="' + fileHidden.name + '"]').index(this);
        if ($("#boardAttach li.item:not(.template) input.file_hidden").length >= maxUploadFile) {
            alert(__('첨부파일은 최대 %1$s개 까지 업로드 가능합니다.', maxUploadFile.toString()));
            return false;
        }
        else {
            fileHidden.onchange = function () {
                var index = $("#boardAttach").find('li').index($(this).closest("li"));
                //ajax업로드 처리
                var uploadImages = [];
                gdAjaxUpload.upload(
                    {
                        formObj: $("#frmWrite"),
                        thisObj: $(this),
                        actionUrl: '../board/board_ps.php',
                        params: {bdId: $('[name=bdId]').val(), 'mode': 'ajaxUpload'},
                        onbeforeunload: function () {
                            if (uploadImages.length == 0) {
                                return false;
                            }
                            $.ajax({
                                method: "POST",
                                url: "../board/board_ps.php",
                                data: {mode: 'deleteGarbageImage', bdId: $('[name=bdId]').val(), deleteImage: uploadImages.join('^|^')},
                                cache: false,
                            }).success(function (data) {
                            }).error(function (e) {
                            });
                        },
                        successAfter: function (data) {
                            uploadImages.push(data.saveFileNm);
                        },
                        failAfter : function(data) {
                            if (data.result == 'fail' && fileHidden.name == 'upfiles[]') {
                                $('input[name="' + fileHidden.name + '"]').eq(idx).val('');
                            }
                        }
                    }
                )
                if(gdAjaxUpload.isSuccess == false){
                    return false;
                }

                var fileReader = new FileReader();
                fileName = this.files[0].name;

                fileReader.readAsDataURL(this.files[0]);
                fileReader.onload = function () {
                    previewUrl = gd_get_prev_img(fileName, this.result);
                    $(templateContainer).clone(true).appendTo($("#boardAttach"));
                    $container.removeClass("template");

                    $fileFace.addClass("preview").css({
                        "background-image": "url('" + previewUrl + "')",
                        "background-position": "center",
                        "background-size": "cover",
                        "background-repeat": "no-repeat",
                        "-webkit-background-size": "cover",
                        "-moz-background-size": "cover",
                        "-o-background-size": "cover"
                    });

                    fileFace.onclick = function (e) {
                        if (confirm(__("첨부된 파일을 삭제하시겠습니까?"))) {
                            $("input[name='uploadFileNm[" + index + "]']").remove();
                            $("input[name='saveFileNm[" + index + "]']").remove();
                            $container.remove();
                        } else {
                            e.stopPropagation();
                        }
                    };
                };

                fileReader.onerror = function () {
                    alert(__("이미지 로드 중 에러가 발생했습니다."));
                };
            };
            $fileHidden.trigger("click");
        }
    });
}

function gd_get_prev_img(fileName, imgUrl) {
    var fileExt = '';
    if (fileName.indexOf('.') > 0) {
        _fileExt = fileName.substring(fileName.lastIndexOf('.'), fileName.length).toLowerCase();
        fileExt = _fileExt.replace('.', '');
    }

    switch (fileExt) {
        case 'gif':
        case 'jpg':
        case 'bmp':
        case 'png':
        case 'jpeg':
            return imgUrl;
            break;
        case 'doc':
        case'docx':
            fileName = 'icon_file_doc.png';
            break;
        case 'ppt':
        case'pptx':
            fileName = 'icon_file_ppt.png';
            break;
        case 'pdf':
            fileName = 'icon_file_pdf.png';
            break;
        case 'xls':
        case'xlsx':
            fileName = 'icon_file_xls.png';
            break;
        case 'txt':
            fileName = 'icon_file_txt.png';
            break;
        default :
            fileName = 'icon_file_etc.png';
    }
    previewUrl = mobileSkinPath + '/img/new/' + fileName;

    return previewUrl;
}

function gd_set_add_goods(frmData) {
    $.each(frmData.info, function (key, val) {
        var selectTblTr = _.template($('#selectGoodsTblTr').html());
        var param = {goodsNo: val.goodsNo, goodsImgageSrc: val.goodsImgageSrc, goodsName: val.goodsName, goodsPrice: val.goodsPrice};
        selecttblTrHtml = selectTblTr(param);
    });
    $("#selectGoodsBox").empty();
    $("#selectGoodsBox").append(selecttblTrHtml);
    $('.itemselect_comment').hide();
}

function gd_set_add_order(frmData) {
    $.each(frmData.info, function (key, val) {
        var selectTblTr = _.template($('#selectOrderTblTr').html());
        var param = {orderNo: val.orderNo, orderGoodsNo: val.orderGoodsNo, goodsImgageSrc: val.goodsImgageSrc, goodsName: val.goodsName, optionName:val.optionName,goodsPrice:val.goodsPrice};
        selecttblTrHtml = selectTblTr(param);
    });
    $("#selectOrderBox").empty();
    $("#selectOrderBox").append(selecttblTrHtml);
    $('.itemselect_comment').hide();
}
