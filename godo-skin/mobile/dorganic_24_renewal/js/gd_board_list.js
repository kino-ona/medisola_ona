/**
 * Created by LeeNamJu on 2015-11-03.
 */

var passwordListLayer = {
    element: $('.js_list_password_layer'),
    btnEl: $('.js_list_password_layer').find('.js_submit'),
    inputEl: $('.js_list_password_layer').find('input[name=writerPw]'),
    show: function () {
        this.element.removeClass('dn');
        $('#layerDim').removeClass('dn');
        //$('html').addClass('oh-space');
        //$('#scroll-left, #scroll-right').addClass('dim');
        $('.cite_layer .wrap div .text').focus();
    },
    close: function () {
        $('.cite_layer .close').trigger('click');
    }
}
