import console from './components/console.js'

const parts = {
    allboomsCommentsWidget: () => document.body.classList.contains('single-ait-item'),
    topMenuNormalize: () => true,
};

function loader(){
    // loads only needed modules. Every module must have side effect
    // spawns only after dom is ready
    for(var part in parts){
        if(parts[part]()) import(`./parts/${part}.js`).catch(e => { console.error(e) })
    }
}

if (document.readyState !== "loading") loader(); else document.addEventListener('DOMContentLoaded', loader);
