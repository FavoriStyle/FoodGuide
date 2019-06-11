import API from './api.js'

export default {
    log(...args){
        window.console.log(...args)
    },
    warn(...args){
        window.console.warn(...args)
    },
    error(...args){
        window.console.warn('На странице произошла восстановимая ошибка. Нашим специалистам уже отправлено уведомление, проблема скоро будет решена');
        return API.FG_log_err(args.length === 1 && args[0].stack ? args[0] : args.join(' '))
    },
}
