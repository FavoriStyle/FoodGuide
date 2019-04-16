const pages = {
    singleItem: document.body.classList.contains('single-ait-item'),
};

// loads only needed modules. Every module must have side effect
for(var page in pages){
    if(pages[page]) import(`./pages/${page}.js`)
}
