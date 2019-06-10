export default e => {
    return new Proxy(e.style, {
        get(s, p){
            const v = s[p] || getComputedStyle(e)[p];
            if(typeof v !== 'string') return v;
            if(v.slice(-2) === 'px') return parseInt(v);
            return v
        },
        set(s, p, v){
            if(typeof v === 'number') v += 'px';
            s[p] = v
        }
    })
}
