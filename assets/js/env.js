const html = document.getElementsByTagName('html')[0],
    body = document.getElementsByTagName('body')[0],
    jsoToUrle = obj => {
        var res = '', i;
        for(i in obj) res += `${encodeURIComponent(i)}=${encodeURIComponent(obj[i])}&`;
        return res.slice(0, -1)
    },
    http = new (class HTTP{
        /**
         * Gets contents from url
         * @param {String} url
         * @return {Promise<String>}
         */
        get(url){
            var xhr = new XMLHttpRequest();
            return new Promise((resolve, reject) => {
                xhr.open('GET', url, true);
                xhr.onreadystatechange = () => {
                    if (xhr.readyState != 4) return;
                    if (xhr.status != 200) reject(new Error(`Cannot get requested url. Error ${xhr.status}: ${xhr.statusText}`)); else resolve(xhr.responseText);
                };
                xhr.send()
            })
        }
        /**
         * Sends POST to url
         * @param {String} url
         * @param {Object} data
         * @return {Promise<String>}
         */
        post(url, data){
            var xhr = new XMLHttpRequest();
            return new Promise((resolve, reject) => {
                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = () => {
                    if (xhr.readyState != 4) return;
                    if (xhr.status != 200) reject(new Error(`Cannot post to requested url. Error ${xhr.status}: ${xhr.statusText}`)); else resolve(xhr.responseText);
                };
                xhr.send(jsoToUrle(data));
            })
        }
    })(),
    gogsAPI = new Proxy({}, {
        get(target, act){
            return data => {
                return new Promise((resolve, reject) => {
                    http.get(`https://allbooms.com:3008/?act=${encodeURIComponent(act)}&params=${encodeURIComponent(JSON.stringify(data))}&token=1`).then(result => {
                        resolve(JSON.parse(result))
                    }).catch(reject)
                })
            }
        },
        set(){
            return true
        }
    });
/**
 * Checks if html or body matches selector
 * @param {String} selector Selector to check
 * @return {Boolean}
 */
function is(selector){
    function match(el){
        el.matches = (el.matches || el.matchesSelector || (() => {return false}))
        return selector => {return el.matches(selector)}
    }
    try {
        return match(body)(selector) || match(html)(selector)
    } catch(e){
        return false;
    }
}
/**
 * Checks if html or body matches all the selectors
 * @param {Array<String>} selectors Array of selectors to check
 * @return {Boolean}
 */
module.exports = {
    html,
    body,
    is,
    isAll: selectors => {
        var res = true;
        selectors.forEach(sel => {res = res && is(sel)});
        return res;
    },
    isOneOf: selectors => {
        var res = false;
        selectors.forEach(sel => {res = res || is(sel)});
        return res;
    },
    /**
     * jQ-like selecting i-face
     * @param {String} selector
     * @return {NodeListOf<HTMLElement>}
     */
    $: selector => {
        return document.querySelectorAll(selector)
    }, Cookies: new (class Cookies{
        /**
         * @typedef {Object} Options
         * @property {Number|Date} expires Interpreted differently, depending on the type: The number is the number of seconds before the expiration. For example, expires: 3600 - cookie for an hour. An object of type Date is the expiration date. If expires in the past, the cookie will be deleted. If expires is absent or 0, the cookie will be set as session and will disappear when the browser is closed.
         * @property {String} path Cookie path
         * @property {String} domain Cookie domain
         * @property {Boolean} secure Whether to forward only over a secure connection
         */
        constructor(){
            /**
             * Gets selected cookie
             * @param {String} name
             * @return {void}
             */
            this.get = name => {
                var matches = document.cookie.match(new RegExp(
                    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
                ));
                return matches ? decodeURIComponent(matches[1]) : undefined;
            }
            /**
             * Sets selected cookie
             * @param {String} name
             * @param {String} value
             * @param {Options} options
             * @return {void}
             */
            this.set = (name, value, options) => {
                options = options || {};
                var expires = options.expires;
                if (typeof expires == "number" && expires) {
                    var d = new Date();
                    d.setTime(d.getTime() + expires * 1000);
                    expires = options.expires = d;
                }
                if (expires && expires.toUTCString) {
                    options.expires = expires.toUTCString();
                }
                value = encodeURIComponent(value);
                var updatedCookie = name + "=" + value;
                for (var propName in options){
                    updatedCookie += "; " + propName;
                    var propValue = options[propName];
                    if (propValue !== true) {
                        updatedCookie += "=" + propValue;
                    }
                }
                document.cookie = updatedCookie;
            }
            /**
             * Delete selected cookie
             * @param {String} name
             * @return {void}
             */
            this.del = name => {
                this.set(name, "", {
                    expires: -1
                })
            }
        }
    })(),
    http,
    apiv4pjs: new Proxy({}, {
        get(target, act){
            return data => {
                return new Promise((resolve, reject) => {
                    var targetURI = `${location.origin}/addons/apiv4pjs?act=${encodeURIComponent(act)}&${jsoToUrle(data)}`;
                    http.get(targetURI).then(result => {
                        try{
                            resolve(JSON.parse(result))
                        } catch(e){
                            reject(new SyntaxError(`Document at ${targetURI} is not in JSON format`))
                        }
                    }).catch(reject)
                })
            }
        },
        set(){
            return true
        }
    }),
    _: ({name, attrs, html}) => {
        var elem = document.createElement(name || 'div');
        for(var i in (attrs || {})) elem.setAttribute(i, attrs[i]);
        elem.innerHTML = html || '';
        return elem;
    },
    gogsAPI,
    console: new (class Console{
        log(...args){
            window.console.log(...args)
        }
        warn(...args){
            window.console.warn(...args)
        }
        async err(e){
            window.console.log('На странице произошла восстановимая ошибка. Нашим специалистам уже отправлено уведомление, проблема скоро будет решена');
            return await gogsAPI.FG_log_err({
                stack: `На странице ${window.location.href} произошла следующая ошибка:\`\`\`${e.stack || e}\`\`\``
            })
        }
        error(e){
            return this.err(e)
        }
    }),
    GET: (() => {
        var res = {}, tmp;
        window.location.search.substr(1).split("&").forEach(item => {
            tmp = item.split("=");
            res[decodeURIComponent(tmp.shift())] = decodeURIComponent(tmp.join('=')) || null;
        });
        return res
    })(),
}
