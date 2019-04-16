import CommentsWidget from 'https://cdn.jsdelivr.net/gh/FavoriStyle/AllBoooms-APIAssets@3/dist/comments/widget.js'

const appId = 'lSgmGGAVrVta3X9xeO3D',
    currentPostIdRegex = /^postid-(\d+)$/;

function currentPostId(){
    for(var i = 0; i < document.body.classList.length - 1; i++){
        var a = currentPostIdRegex.exec(document.body.classList[i]);
        if(a && a[1]) return a[1]
    }
}

const postId = currentPostId();

if(postId) document.getElementById('item-right-actions-panel').appendChild(new CommentsWidget(appId, 'single-ait-item-' + postId, {
    // widget settings
}))
