import css from '../components/css.js'

function fixTopMenu(){
    let userPanel = document.querySelector('.menu-tools .user-panel'),
        menuPanel = document.querySelector('.menu-tools .menu-container');
    (function onResize(){
        let top = userPanel.getBoundingClientRect().top;
        if (window.innerWidth > 1200 && top != 32 && top != 0 && top != 37.5){ // bad pos
            menuPanel.querySelectorAll('li > a').forEach(e => {
                const style = css(e);
                style.transitionDuration = '0s';
                if (style.paddingLeft > 1){
                    style.paddingLeft -= 1;
                    style.paddingRight -= 1;
                } else {
                    style.fontSize -= 0.5;
                }
                style.transitionDuration = '';
            });
            setTimeout(onResize, 100);
        }
    })()
}

if (document.readyState === 'complete') fixTopMenu(); else document.addEventListener('load', fixTopMenu);
