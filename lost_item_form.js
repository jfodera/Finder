document.addEventListener("DOMContentLoaded", function() {
    const pages = Array.from(document.querySelectorAll('#infoForm .page'));
    const nextBtns = document.querySelectorAll('.next-btn');
    const prevBtns = document.querySelectorAll('.prev-btn');

    nextBtns.forEach(button => {
    button.addEventListener('click', () => {
        changePage('next');
    });
});

prevBtns.forEach(button => {
    button.addEventListener('click', () => {
        changePage('prev');
    });
});


function changePage(btn){
    const active = document.querySelector('#infoForm .page.active');
    let index = pages.indexOf(active);
    index = pages.indexOf(active);
    pages[index].classList.remove('active');
    if(btn ==='next'){
        index ++;
    }else if(btn ==='prev'){
        index --;
    }
    pages[index].classList.add('active')
    console.log(index)
}

    
});