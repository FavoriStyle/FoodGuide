const apiDomain = 'api.foodguide.in.ua';

export default new Proxy({}, {
    get(_, act){
        return async data => {
            const r = await fetch(`https://${apiDomain}/?act=${encodeURIComponent(act)}&params=${encodeURIComponent(JSON.stringify(data))}&token=1`);
            return JSON.parse(await r.text())
        }
    },
    set(){
        return true
    }
})
