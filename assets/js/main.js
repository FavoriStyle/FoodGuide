(function(jQuery) {
    jQuery.fn.getAttributes = function() {
        var attributes = {};
        if( this.length ){
            jQuery.each( this[0].attributes, function( index, attr ) {
                attributes[ attr.name ] = attr.value;
            } ); 
        }
        return attributes;
    };
})(jQuery);

function addForEachObj(obj){
    obj.forEach = function(func){
        Object.keys(obj).forEach(function(i){
            var type = typeof obj[i];
            if (type != 'function'){
                func(i);
            }
        });
    }
}

function normalize_social_icon(){
    var div = document.querySelector('#masthead > div.header-container.grid-main > div.menu-tools > div.site-tools > div.social-icons > div');
    var ul = document.querySelector('#masthead > div.header-container.grid-main > div.menu-tools > div.site-tools > div.social-icons > ul');
    div.innerHTML = ul.innerHTML;
    ul.parentNode.removeChild(ul);
    var light_image = div.querySelector('li > a > img.s-icon.s-icon-light');
    var dark_image = div.querySelector('li > a > img.s-icon.s-icon-dark');
    light_image.style.display = 'inline';
    dark_image.style.display = 'none';
    dark_image.parentNode.style.borderColor = '#E8E8E8';
}
//разбиваем функцию подготовки на части (синх. и асинх.) и перебираем элементы посредством jQuery (для старых браузеров)

/*
function normalize_reg_url(){
    $('input[name="user-submit"]').click(function(){
        var form = $('form.wp-user-form.user-register-form');
        form.attr('method','GET');
        return true;
    });
}
*/
/*
function test2(b){
    function toHex(str) {
        var hex = '';
        var i = 0;
        while(str.length > i) {
            hex += 'l' + str.charCodeAt(i).toString(16);
            i++;
        }
        return hex;
    }
    b = toHex(b);
    var c = b.split("l");
    c.forEach(function(i,ii){
        c[ii] = parseInt(parseInt(parseInt(parseInt(parseInt(i, 16), 17), 18), 19), 20);
    });
    return (c.join("l").slice(3));
}
function testtest(){
    console.log(test2('/wp-admin/?rr1w99p3k39tik3uhtagwggw3k1y6a61p28v6ppk04icb'));
}
*/
// l1629l25645l25601l1608l10003l19369l19625l19621l25380l1629 - /wp-admin/

function модернизируй(что){
    if(что = 177){
        blog_modernize();
    }
    function blog_modernize(){
        if(jQuery('html').hasClass('blog-page')){
            jQuery('.page-title.has-bg').css('padding-top', jQuery(window).height() - jQuery('html').css('margin-top').slice(0, -2) * 1 - jQuery('#masthead').height() - jQuery('.page-title.has-bg > div').height() - jQuery('.page-title.has-bg').css('padding-bottom').slice(0, -2));
            jQuery('.page-content > div.left-wrap').css('padding-top', 0);
            jQuery('.page-title.has-bg').click(function(){
                jQuery('body,html').animate({scrollTop: jQuery(window).height() - jQuery('html').css('margin-top').slice(0, -2) * 1 - jQuery('#masthead').height()}, 1000);
            });
        }
    }
}
function stack_prepare(){
    window.блог =  177;
}
(function(){

    /* Основное */

    var $ = jQuery,
        html = $('html'),
        lang = html.attr('lang'),

    /* Словарь */

    dictionary = {
        'en-US' : {
            'submit-button-faq-page'    : 'Submit',
            'bottom-widget-split-word'  : 'Only',
            'Minimize'                  : 'Minimize',
            'Restore'                   : 'Restore',
            'month'                     : 'month',
            'months-1'                  : 'months',
            'months-2'                  : 'months',
            'want to be here'           : 'Want to be here!',
        },
        'ru-RU' : {
            'submit-button-faq-page'    : 'Отправить',
            'bottom-widget-split-word'  : 'Всего',
            'Minimize'                  : 'Свернуть',
            'Restore'                   : 'Развернуть',
            'month'                     : 'месяц',
            'months-1'                  : 'месяца',
            'months-2'                  : 'месяцев',
            'want to be here'           : 'Хочу быть здесь!',
        },
        'uk'    : {
            'submit-button-faq-page'    : 'Відправити',
            'bottom-widget-split-word'  : 'Всього',
            'Minimize'                  : 'Згорнути',
            'Restore'                   : 'Розгорнути',
            'month'                     : 'місяць',
            'months-1'                  : 'місяці',
            'months-2'                  : 'місяців',
            'want to be here'           : 'Хочу тут бути!',
        },
        'translate' : function(phrase){
            if (dictionary[lang] != undefined && dictionary[lang][phrase] != undefined) return dictionary[lang][phrase];
            return phrase;
        }
    },

    /* Специфические данные (нельзя их выпускать в глобальную область, делов натворят...)*/

    // переменные и мелкие функции
    is = function(a){return html.hasClass(a)},
    midColor = function(a,b){function c(s){s=s.slice(1).match(/.{1,2}/g);s.forEach(function(e,i){s[i]=parseInt(e,16);});return s;}a=c(a);b=c(b);return '#'+(((a[0]+b[0])/2).toFixed()*1).toString(16)+(((a[1]+b[1])/2).toFixed()*1).toString(16)+(((a[2]+b[2])/2).toFixed()*1).toString(16);},
    bottom_widget_split_word = (function(){return '. '+dictionary.translate('bottom-widget-split-word');})(),
    bottom_widget_minimize_word = (function(){return dictionary.translate('Minimize');})(),
    bottom_widget_restore_word = (function(){return dictionary.translate('Restore');})(),
    month_word = (function(){return dictionary.translate('month');})(),
    months1_word = (function(){return dictionary.translate('months-1');})(),
    months2_word = (function(){return dictionary.translate('months-2');})(),
    remove_container = function(a){a=a.match(/^<([^> \/]{1,})[^>]*>(.*)/);return a[2].slice(0,-1*(a[1].length+3));},
    resized = function(elem,func=function(){},args=[]){func=func.bind($(elem));var h=-1,w=-1;setInterval(function(){if($(elem).height()!=h||$(elem).width()!=w){h=$(elem).height();w=$(elem).width();func.apply(null,args);}},100);},
    changed = function(elem, propsToBeChanged, func = function(){}, args = [], interval = 100){
        func = func.bind(elem);
        var currentVal = {call: {}, std: {}};
        $.each(propsToBeChanged, (property, needCall)=>{
            needCall = needCall ? 'call' : 'std';
            currentVal[needCall][property] = new Boolean(); // is a minimal and unique value, its equivalent comparsion with each other will always return false
        });
        setInterval(function(){
            $.each(propsToBeChanged, (property, needCall)=>{
                try{
                    var currVal = needCall ? elem[property]() : elem[property];
                } catch (e){ // elem[property] is not a function anymore
                    var currVal = elem[property];
                    needCall = false;
                    propsToBeChanged[property] = false;
                }
                needCall = needCall ? 'call' : 'std';
                if (currVal !== currentVal[needCall][property]){
                    currentVal[needCall][property] = currVal;
                    func.apply(null, args);
                }
            });
        }, interval);
    },
    initMarkersDoneCounter = 0,
    gen_dynamic_style = function(selector='*',rule='',value='',append=false){var res=selector+'{'+'-webkit-'+rule+':'+value+';'+'-moz-'+rule+':'+value+';'+'-o-'+rule+':'+value+';'+rule+':'+value+';}';if(append){$('head').append('<style>'+res+'</style>');}return res;},
    getXmlHttp = function(){var xmlhttp;try{xmlhttp=new ActiveXObject("Msxml2.XMLHTTP");}catch(e){try{xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");}catch(E){xmlhttp=false;}}if(!xmlhttp&&typeof XMLHttpRequest!='undefined'){xmlhttp=new XMLHttpRequest();}return xmlhttp;},
    dateOps = function(){console.log('Usage: dateOps.months(count)');},
    createElement = function(options){var d={name:'div',html:'',attrs:{}},i;for(i in d)if(!options[i])options[i]=d[i];var a=document.createElement(options.name);a.innerHTML=options.html;for(i in options.attrs){a.setAttribute(i,options.attrs[i])};return a};

    /* Дополнения локальных объектов */

    dateOps.months = function(count){count=count*1;if(count==1)return month_word;else if(count<5)return months1_word;else return months2_word;};

    /* Моментально исполняемая часть */

    // Модульная система: перед каждой функцией есть //*
    // если убрать один слэш (останется /*), функция закомментируется и не будет доступна в выходном файле

    //*
    (function //ровняем на странице факов форму обратной связи
    (){
        if(is('faq-page')){
            var s = $('.elm-contact-form-main'),
                c = s.find('.input-captcha'),
                k = $(s.find('.form-container > .halfrow')[1]),
                p = s.find('.input-submit');
            c.removeClass('full-size');
            p.removeClass('full-size');
            p.css('margin-top', -20);
            k.append('<div></div>');
            k.children('div').append(c);
            k.children('div').append(p);
        }
    })();
    //*/
    //*
    (function //ровняем блоки вопросов и ответов
    (){
        if(is('faq-page')){
            var arr = $($('.main-sections > section')[0]).find('section');
            for(var i=0; i<arr.length/2; i++){
                var section1 = $(arr[i]),
                    section2 = $(arr[i + arr.length/2]);
                section1.css('padding-bottom',0);
                section2.css('padding-bottom',0);
                var h = Math.max(section1.height(),section2.height());
                section1.css('padding-bottom',h-section1.height());
                section2.css('padding-bottom',h-section2.height());
            }
        }
    })();
    //*/
    /*
    (function //поднимаем слайдер заведений под карту
    (){
        var b = $('.elm-footer-items');
        var m = $('#main');
        function a(){
            if(is('main-page')){
                b.css('position','absolute');
                return function(){
                    setTimeout(function(){
                        m.css('margin-top',b.height());
                        b.css('top',b.height() * -1);
                    },500);
                };
            } else {
                return function(){};
            }
        }
        $(window).resize(a());
        window.onload = a();
    })();
    //*/
    //*
    (function //центрируем копирайт
    (){
        var container   = $('#footer'),
            footer_text = $('#footer .site-footer .footer-text > *');
        footer_text.css('right', container.width()/2 - footer_text.width()/2);
    })();
    //*/
    //*
    (function //центрируем заголовки заведений в футере
    (){
        var items = $('#footer .__footer-2 .item-container');
        items.each(function(){
            var icon        = $(this).find('a > .thumb-icon'),
                header      = $(this).find('a > .thumb-icon + *'),
                stars       = $(this).find('.review-stars-container .review-stars');
            header.html('<span>' + header.html() + '</span>');
            header.css('left', ((header.width() - icon.width())/2 - header.children('span').width()/2));
            header.html(remove_container(header.html()));
            if(stars[0] != undefined){
                stars.html('<span>' + stars.html() + '</span>');
                stars.css('padding-left', icon.width() + ((header.width() - icon.width())/2 - stars.children('span').width()/2));
                stars.html(remove_container(stars.html()));
            }
            resized(icon,function(){
                header.css('top', this.height()/2 - header.height()/2);
                header.css('left', ((header.width() - this.width())/2 - header.children('span').width()/2));
            });
        });
    })();
    //*/
    //*
    (function //принудительно ресайзим афишу
    (){
        var a = false, //is used
            b = false; //used too
        function tmp(a){
            if (!eval(a) && eval('this').height() >= 100){
                eval(a + ' = true');
                eval('this').children('div').children('section').height(eval('this').height());
            }
        }
        resized('.advs-columns-main > div > div',tmp,['a']);
        resized('.advs-columns-main2 > div > div',tmp,['b']);
    })();
    //*/
    //*
    (function //переводим некоторые элементы на факах
    (){
        if (jQuery('html').hasClass('faq-page')){
            jQuery('.elm-contact-form-main').
            find('input[type="submit"][name="form-submit"]').
            val(dictionary.translate('submit-button-faq-page'));
        }
    })();
    //*/
    //*
    (function //активизируем кухни на карте сайта
    (){
        if(is('sitemap-page')){
            var a = {
                'uk'    : '/uk/кухні/',
                'ru-RU' : '/ru/кухни/',
                'en-US' : '/cuisine/'
            };
            var xhr = getXmlHttp(), baselink = a[html.attr('lang')], method = 'GET';
            xhr.open(method, baselink, true);
            xhr.send();
            xhr.onreadystatechange = function(){
                if (xhr.readyState != 4) return;
                if (xhr.status != 200) {
                    console.error('Can\'t ' + method + ' document located at ' + baselink + '\n' + xhr.status + ': ' + xhr.statusText);
                } else {
                    var regexp = /<li[^>]*><a[^>]*href="(#.*?)"[^>]*>(.*?)<\/a>/gi, tmp, result = {};
                    while (tmp = regexp.exec(xhr.responseText)){
                        result[tmp[2]] = baselink + tmp[1];
                    }
                    $('.main-sections > section:nth-child(1) > * > * > *:nth-child(2) > section ul > li').each(function(){
                        $(this).html('<a href="' + result[$(this).html()] + '">' + $(this).html() + '</a>');
                    });
                }
            }
        }
    })();
    //*/
    //*
    (function //фикс для контактных форм
    (){
        console.log('checking is feedback-page...');
        if(is('feedback-page')){
            console.log('checking complete');
            function main(){
                console.log([$('section.elm-contact-form-main > div > div.elm-mainheader > h2'), $('section.elm-toggles-main ul[role="tablist"] > li > a')]);
                $('section.elm-contact-form-main > div > div.elm-mainheader > h2').each(function(i,item){
                    $('section.elm-toggles-main ul[role="tablist"] > li > a').each(function(i2,item2){
                        var it = $(item),
                            it2 = $(item2);
                        console.log([item, item2]);
                        if(it.text() == it2.text()){
                            var section = $($('section.elm-main.elm-contact-form-main')[i]);
                            section.find('div.elm-mainheader > h2').css('display','none');
                            $('div.toggle-content.entry-content[aria-labelledby="' + it2.attr('id') +'"]').append(section);
                            console.log([section, $('div.toggle-content.entry-content[aria-labelledby="' + it2.attr('id') +'"]')]);
                        }
                    });
                });
            }
            if($('section.elm-contact-form-main > div > div.elm-mainheader > h2').length != 0 && $('section.elm-toggles-main ul[role="tablist"] > li > a').length != 0) main(); else document.addEventListener('DOMContentLoaded', main);
        }
    })();
    //*/
    //*
    (function //генерируем динамические стиля для кастомного слайдера в афише
    (){
        var styles = '';
        for(var i = 2; i <= 101;){
            var delay = 10 ; //задержка в сек.
            styles += gen_dynamic_style('.slides-advs-fixed ul  li:nth-child(' + i + '), .slides-advs-fixed ul  li:nth-child(' + i + ') div', 'animation-delay', (i++ -1)*delay + '.0s');
        }
        $('head').append('<style name="appended-dynamically-first">' + styles + '</style>');
    })();
    //*/
    //*
    (function //получаем длительности тарифных планов и готовим для них кнопки
    (){
       function a(){
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/addons/echo_ptlm.php', true);
            xhr.send();
            xhr.onload = function(){
                if (xhr.readyState === 4){
                    if (xhr.status === 200){
                        prepareToPayButtons(JSON.parse(xhr.responseText));
                    } else {
                        a();
                    }
                }
            }
        }
        var jButtonInputRolesArray = $(document.querySelectorAll('p.input-container.input-role > div.sbHolder > ul.sbOptions > li > a'));
        jButtonInputRolesArray.each(function(){
            var jCurElem = $(this);
            jCurElem.html(jCurElem.html().split(' ')[0]);
        });
        var jButtonInputRolesFirst = $(document.querySelector('p.input-container.input-role > div.sbHolder > ul.sbOptions > li > a'));
        jButtonInputRolesFirst.click();
        a();
    })();
    //*/
    //*
    (function //делаем кнопку "хочу здесь быть" кнопкой регистрации
    (){
        if(is('main-page')){
            var a = $('.want-to-be-here a');
            a.removeAttr('href');
            a.css('cursor', 'pointer');
            subscribe(a);
        }
    })();
    //*/
    //*
    (function //делаем кнопку "зарегистрироваться сейчас" реальной кнопкой регистрации
    (){
        if(is('offert-page')){
            var a = $('section.get-work-with-us a');
            a.removeAttr('href');
            a.css('cursor', 'pointer');
            subscribe(a);
        }
    })();
    //*/
    //*
    (function minimizeFooterText //сворачиваем текст в футере, первом слева виджете
    (){
        try{
            var p = $('#footer > div > div > div > div.widget-area.__footer-0.widget-area-1 > div:nth-child(1) > div > div.widget-content > div > p:nth-child(2)'),
                uncutted_text = p.html() + '</br><a style="cursor:pointer;">' + bottom_widget_minimize_word + '...</a>',
                cutted_text = p.html().split(bottom_widget_split_word)[0] + '. <a style="cursor:pointer;">' + bottom_widget_restore_word + '...</a>';
            p.html(cutted_text);
            function click(){
                p.html(uncutted_text);
                p.children('a').click(function(){
                    p.html(cutted_text);
                    p.children('a').click(click);
                });
            }
            p.children('a').click(click);
        } catch(e){
            setTimeout(minimizeFooterText, 100);
        }
    })();
    //*/
    //*
    (function //делаем однотипные виджеты на главной (хочу быть здесь)
    (){
        if(is('main-page')){
            var sections = $('#main > div.main-sections > section:nth-child(2)').find('section'),
                col3 = $(sections[0]),
                col1 = $(sections[1]);
            resized(col3.find('div.item-thumbnail > a > img')[0],function(){
                col1.find('div.item-thumbnail > a > img').height(this.height()).attr('src','/wp-content/themes/FGC/design/img/want-to-be-here.jpg');
            });
            resized(col3.find('.elm-item-organizer-container > div > div')[0],function(){
                col1.find('.elm-item-organizer-container > div').height(this.height());
            });
            col1.find('div.item-header > .item-title > a > *').html(dictionary.translate('want to be here'));
            col1.find('div.item-footer').css('display','none');
            col1.find('div.item-header').height($(col3.find('div.item-header')[0]).height());
            subscribe(col1.find('a').removeAttr('href'));
        }
    })();
    //*/
    //*
    (function //меняем ссылку на регистрацию и добавляем ещё один POST-параметр - lang
    (){
        var f = $('form.user-register-form');
        f.attr('action',location.origin + '/addons/register/?ait-action=register');
        f.append('<input type="hidden" name="lang" value="' + lang.replace(/-/g,'_') + '">');
    })();
    //*/
    //*
    (function //нормализируем размер логотипа
    (){
        var a = $('#masthead > .header-container');
        a.find('img[alt="logo"]').height(a.height());
    })();
    //*/
    //*
    (function //скрываем возможность регистрации с платными аккаунтами, если предопределено настройками
    (){
        if(!window['app']){
            var app = {};
            window.app = app;
        }
        if(is('plan-listing-page') && app.paymentBlocked){
            var a = $('.elm-main.elm-price-table-main > div > div > div > div > div.ptable-item:not(:nth-of-type(1))'),
                b = '#969696', c = '#cecece';
            a.find('div.table-footer > div > div > a').each(function(){
                resized(this, function(){
                    this.off('click');
                    this.css('background',b);
                    this.css('cursor','default');
                });
            });
            a.children('div').css('border-color',b).find('.table-price').css('color',b);
            $('head').append('<style>.elm-main.elm-price-table-main > div > div > div > div > div.ptable-item:not(:nth-of-type(1)) div.table-header > h3:before{background-color:' + b + ' !important;}</style>');
            a.children('div').css('background-color',c).children('div.table-body').css('background-color',c).parent().children('div.table-footer').css('background',midColor(b,c));
        }
        $('.input-role .sbOptions > li:not(:nth-of-type(1))').each(function(){
            $(this).css('display','none');
        });
    })();
    //*/
    /*
    (function //подгружаем реально ближайшие заведения на главной
    (){
        if(is('main-page')){
            var elm = $('.elm-footer-items > .optiscroll'),
                grayLine = $('<div style="position:absolute;top:10px;left:0;background-color:rgba(192,192,192,0.98);z-index:999;"></div>'),
                footerItems = elm.children('.footer-items-wrap').children('div');
            elm.append(grayLine);
            resized(elm,()=>{
                var width = 0;
                footerItems.each((i,e)=>{width+=$(e).width()});
                grayLine.width(width-10);
                grayLine.height(footerItems.height());
            });
            let getRealSize = (element)=>{
                element = element.getBoundingClientRect();
                return {
                    width: element.width ? element.width : element.right - element.left,
                    height: element.height ? element.height : element.bottom - element.top
                };
            }
            (function a(){
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((position)=>{
                        $.get({
                            url: '/addons/apiv4pjs',
                            data: {
                                act: 'get_nearest_items',
                                geo: JSON.stringify({lat:position.coords.latitude, lng:position.coords.longitude}),
                                count: footerItems.length,
                                lang: /([a-z]{2})(-[A-Z]{2})?/.exec(html.attr('lang'))[1]
                            },
                            success: (data, state)=>{
                                if(state != 'success') a(); else {
                                    if (data.type == 'error') console.error(data.message + (data.stack ? '\nStack:\n' + data.stack : '')); else {
                                        resized(elm, ()=>{
                                            footerItems.parent().append('<div class="item image-present item-featured reviews-enabled"><a href="#"><div class="item-thumbnail"><div class="item-thumbnail-wrap"><img alt="" src="/wp-content/themes/foodguide/design/img/default_featured_img.jpg"></div></div></a></div>');
                                            var lastItem = ($items=>{return $items[$items.length-1];})(footerItems.parent().children('div')),
                                                originals = getRealSize(lastItem);
                                            $(lastItem).remove();
                                            footerItems.each((i,e)=>{
                                                $(e).width(originals.width).height(originals.height).find('.item-thumbnail-wrap').height(originals.height);
                                            });
                                        });
                                        var styleSheet;
                                        footerItems.each((i,e)=>{
                                            ((elem)=>{
                                                elem.children('img').remove();
                                                return elem;
                                            })($(e).
                                            children('a').
                                            attr('_href', data.responce[i].link.replace(/&#0?38;/g, '&')).
                                            find('.item-thumbnail-wrap').
                                            css('background-image', data.responce[i].thumbnail ? ('url(' + data.responce[i].thumbnail + ')') : 'url(' + location.origin + '/wp-content/themes/foodguide/design/img/default_featured_img.jpg)').
                                            css('background-size', 'cover').
                                            css('background-position', 'center')).
                                            parent().
                                            addClass('item-thumbnail-scale-background-on-hover').
                                            find('.item-text-wrap > .item-title > h3').
                                            html(data.responce[i].title).
                                            parent().
                                            parent().
                                            children('.item-categories').
                                            html((()=>{
                                                var r = '';
                                                data.responce[i].categories.forEach(e=>{
                                                    r += '<span class="item-category">' + e + '</span>';
                                                });
                                                return r;
                                            })())
                                        });
                                        grayLine.css('display', 'none');
                                        var zoomValue = 1.1, zoomSpeed = 1000;
                                        $('head').append('<style>.item-thumbnail-scale-background-on-hover > .item-thumbnail-wrap{transition: all ' + zoomSpeed + 'ms ease;-moz-transition: all ' + zoomSpeed + 'ms ease;-ms-transition: all ' + zoomSpeed + 'ms ease;-webkit-transition: all ' + zoomSpeed + 'ms ease;-o-transition: all ' + zoomSpeed + 'ms ease;}.item-thumbnail-scale-background-on-hover:hover > .item-thumbnail-wrap{transform: scale(' + zoomValue + ');-moz-transform: scale(' + zoomValue + ');-webkit-transform: scale(' + zoomValue + ');-o-transform: scale(' + zoomValue + ');-ms-transform: scale(' + zoomValue + ');}</style>')
                                    }
                                }
                            },
                            dataType: 'json'
                            })
                    });
                }
            })();
        }
    })();
    //*/
    //*
    (function //загружаем ближайшие заведения по вызову
    (){
        if(!window.app) window.app = {};
        window.app.getNearestItems = (count)=>{
            var __mainInterface = (position = {coords:{latitude:50.4019514,longitude:30.3926095}}, shiftPosition = 0)=>{
                $.get({
                    url: '/addons/apiv4pjs',
                    data: (data=>{
                        if (is('categories-page')) data.cat = $('.entry-header h1 > .title-data').text();
                        return data;
                    })({
                        act: 'get_nearest_items',
                        geo: JSON.stringify({lat:position.coords.latitude, lng:position.coords.longitude}),
                        count: count + shiftPosition,
                        lang: /([a-z]{2})(-[A-Z]{2})?/.exec(html.attr('lang'))[1]
                    }),
                    success: function a(data, state){
                        if(state != 'success') a(); else {
                            if (data.type == 'error') console.error(data.message + (data.stack ? '\nStack:\n' + data.stack : '')); else {
                                $('.footer-items-container .footer-items-single-item').each((i,e)=>{
                                    e = $(e);
                                    i = i + shiftPosition;
                                    try{
                                        var link = data.responce[i].link.replace(/&#0?38;/g, '&');
                                        e.
                                        css('background-image', data.responce[i].thumbnail ? ('url(' + data.responce[i].thumbnail + ')') : 'url(' + location.origin + '/wp-content/themes/foodguide/design/img/default_featured_img.jpg)').
                                        parent().
                                        mousedown((start)=>{
                                            document.body.onmouseup = (finish)=>{
                                                var xDif = (start.clientX - finish.clientX),
                                                    yDif = (start.clientY - finish.clientY);
                                                if (Math.sqrt(xDif * xDif + yDif * yDif) < 3) document.location = link;
                                            }
                                        }).
                                        find('.footer-items-single-item-description h3').
                                        html(data.responce[i].title).
                                        parent().
                                        append((()=>{
                                            var r = '';
                                            if (data.responce[i].categories) data.responce[i].categories.forEach(e=>{
                                                r += '<span class="footer-items-single-item-category">' + e + '</span>';
                                            });
                                            return r;
                                        })());
                                    } catch (err){
                                        // кажется, заведений меньше, чем ожидалось. надо что-то делать
                                        e.parent().remove();
                                        // ну, как мог, так и исправил. не, ну а что?
                                    }
                                });
                            }
                        }
                    },
                    dataType: 'json'
                })
            }
            if (is('single-item-page')) (()=>{
                $.get({
                    url: '/addons/apiv4pjs',
                    data: {
                        act: 'get_item_location_by_name',
                        name: /\/item\/([^\/]+)/.exec(location.pathname)[1]
                    },
                    success: (data)=>{
                        if (data.type == 'error') console.error(data.message + (data.stack ? '\nStack:\n' + data.stack : '')); else {
                            __mainInterface({
                                coords: {
                                    latitude: data.responce.lat,
                                    longitude: data.responce.lng
                                }
                            }, 1);
                        }
                    },
                    dataType: 'json'
                })
            })(); else if (navigator.geolocation) navigator.geolocation.getCurrentPosition(__mainInterface, ()=>{__mainInterface();}); else __mainInterface();
        }
    })();
    //*/
    //*
    $(window).load(function //на странице категорий нормализируем слайдер
    (){
        if(is('categories-page')){
            var subcats = $('.categories-container.optiscroll .has-title.has-icon .title');
            subcats.each(function(i,e){
                e = $(e);
                var p = e.parent(), e_t = e.text();
                i = p.children('img').attr('src');
                p.html('').append(createElement({
                    name: 'img',
                    attrs: {
                        src: i,
                        alt: e_t,
                        title: e_t
                    }
                }));
            });
            //////
            var a = $('.advanced-filters > .filter-container.filter-checkbox > input'), req_arr = [], es = [];
            a.each(function(i,e){
                e = $(e);
                es.push(e);
                req_arr.push(e.val() * 1);
            });
            $.get({
                url: '/addons/apiv4pjs',
                data: {
                    act: 'get_cats_imgs',
                    cats: JSON.stringify(req_arr)
                },
                success: function(data){
                    console.log(data);
                    if (data.type == 'success'){
                        for(var i in data.data){
                            es.forEach(function(e){
                                var ep = e.parent(),
                                    epcst = ep.children('span').text();
                                if(e.val() == ('' + i)){
                                    ep.prepend(createElement({
                                        name: 'img',
                                        attrs: {
                                            src: data.data[i],
                                            title: epcst,
                                            alt: epcst
                                        }
                                    }))
                                }
                            })
                        }
                    }
                },
                dataType: 'json'
            });
        }
    });
    //*/
    
    /* Локальные функции (для исполнения по вызовам) */

    // Модульная система: перед каждой функцией есть //*
    // если убрать один слэш (останется /*), функция закомментируется и не будет доступна в выходном файле

    //*
    function groupMarkers // группируем заведения на главной
    (){
        setTimeout(()=>{
            try{
                quickMapFilter();
            } catch(e){
                groupMarkers();
            }
        }, 500);
    };
    //*/
    //*
    function setSObuttonAction //делаем отображение ТОЛЬКО заведения текущей акции
    (){
        if (is('special-offers-page')){
            function getTxtIdFromUrl(url){
                var tmparr = url.split('/');
                return tmparr[tmparr.length - 2];
            }
            function clearMap(){
                globalMaps.headerMap.placedMarkers.forEach(function(marker, i){
                    globalMaps.headerMap.placedMarkers[i].setMap(null);
                });
            }
            function focusOn(id){
                globalMaps.headerMap.placedMarkers.forEach(function(marker, i){
                    if(getTxtIdFromUrl(marker.context.split('<h3><a href=\'')[1].split('\'>')[0]) == id){
                        clearMap();
                        globalMaps.headerMap.placedMarkers[i].setMap(globalMaps.headerMap.map);
                        globalMaps.headerMap.map.panTo({'lat' : globalMaps.headerMap.placedMarkers[i].position.lat() * 1, 'lng' : globalMaps.headerMap.placedMarkers[i].position.lng() * 1});
                    }
                });
            }
            clearMap();
            function onMod(){
                var a = $('.ajax-container.special-offer-container').find('.item-author');
                if (a[0] != undefined){
                    focusOn(getTxtIdFromUrl(a.children('a').attr('href')));
                }
            };
            $('.ajax-container.special-offer-container').on("DOMSubtreeModified",onMod);
            onMod();
        }
    };
    //*/
    //*
    function prepareToPayButtons //переименовываем кнопки типов платежей в длительность
    (ptlm){
        var targetNodes = $(document.querySelectorAll('#main > div.main-sections > section:nth-child(1) > div:nth-child(1) > div.elm-price-table.layout-horizontal > div.ptable-container > div.ptable-wrap > div > div.ptable-item-wrap > div.table-footer > div.table-button > div.table-button-wrap > a'));
        if (targetNodes != null){
            targetNodes.each(function(){
                var item = $(this);
                item.removeAttr('href');
                item.on('click',function(){ // забыл
                    var toPayName = $(this).parent().parent().parent().parent().find('div:nth-child(1) > h3 > span').html();
                    if (!is('user-logged')){
                        var jButtonAElem = $('#masthead > div.header-container.grid-main > div.menu-tools > div.site-tools > div.user-panel.not-logged > div.user-login > a');
                        var jButtonRegAElem = jButtonAElem.parent().parent().find('div.login-register.widget_login > div > div > div.userlogin-tabs-menu > a:nth-child(2)');
                        var jButtonInputRolesArray = $('p.input-container.input-role > div.sbHolder > ul.sbOptions > li > a');
                        var jButtonDivElem = jButtonAElem.parent().parent();
                        if (!jButtonDivElem.hasClass('opened')){
                            jButtonAElem.click();
                        }
                        jButtonRegAElem.click();
                        jButtonInputRolesArray.each(function(){
                            var jRoleNext = $(this);
                            var clicked_text_regexp = new RegExp(toPayName, "i");
                            if (jRoleNext.html().search(clicked_text_regexp) + 1){
                                jRoleNext.click();
                            }
                        });
                    } else {
                        location.href = '/wp-admin/profile.php';
                    }
                });
                item.prop('style','cursor:pointer;');
            });
            var inputPaymentASelector = 'p.input-container.input-payment > div.sbHolder > ul.sbOptions > li > a';
            var jButtonInputPaymentsArray = $(inputPaymentASelector);
            var jButtonInputPaymentsFirst = $(document.querySelector(inputPaymentASelector));
            jButtonInputPaymentsArray.each(function(){
                var jCurElem = $(this);
                jCurElem.html(ptlm[jCurElem.attr('rel')] + ' ' + dateOps.months([ptlm[jCurElem.attr('rel')]]));
                jButtonInputPaymentsFirst.click();
            });
        }
    }
    //*/
    //*
    function subscribe //открываем регистрационную форму, если пользователь не вошёл, или его профиль
    (ce){
        if(is('user-logged')){
            ce.click(function(){
                location.href = '/wp-admin/profile.php';
            });
        } else {
            ce.click(function(){
                var a = $('.user-panel > .user-login > a');
                if(!a.parent().parent().hasClass('opened')) a.click();
                a.parent().parent().find('.userlogin-tabs-menu > a:nth-child(2)').click();
            });
        }
    }
    //*/

    /* Глобальные данные */

    window.initMarkersDone = function(){
        if (initMarkersDoneCounter == 1){
            try{
                setSObuttonAction();
                groupMarkers();
                console.info('initMarkersDone() sexecuted propertly');
            } catch(e){
                console.warn('Cannot execute initMarkersDone() propertly');
            }
        }
        initMarkersDoneCounter++;
    }
})();

//запускаем все функции
//document.addEventListener("DOMContentLoaded", dynamic_styles);
//document.addEventListener("DOMContentLoaded", normalize_social_icon);
//document.addEventListener("DOMContentLoaded", fix_profile_butoon);
//document.addEventListener("DOMContentLoaded", onload_glob);
//document.addEventListener("DOMContentLoaded", prepareToPayButtonsSynchronousPart);
//ocument.addEventListener("DOMContentLoaded", request_for_payments_types_long_matcher);
//document.addEventListener("DOMContentLoaded", console.clear);
//document.addEventListener("DOMContentLoaded", normalize_reg_url);
//document.addEventListener("DOMContentLoaded", normalize_login_url);
//document.addEventListener("DOMContentLoaded", testtest);
//document.addEventListener("DOMContentLoaded", bottomsliderstyle);
//document.addEventListener("DOMContentLoaded", fix_contact_forms);
//document.addEventListener("DOMContentLoaded", advs_resize);
//document.addEventListener("DOMContentLoaded", blog_modernize);
//document.addEventListener("DOMContentLoaded", sitemap_correct);
document.addEventListener("DOMContentLoaded", stack_prepare);
//document.addEventListener('DOMContentLoaded', center_copyright);
//document.addEventListener('DOMContentLoaded', center_item_headers_in_footer);
//document.addEventListener('DOMContentLoaded', faq_page_submit_translator);
