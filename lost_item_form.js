const pages = Array.from(document.querySelector('form .page'));
const nextBtn = document.querySelectorAll('form .next-btn');
const prevBtn = document.querySelectorAll('form .prev-Btn');
const form = document.querySelectorAll('form');


nextBtn.forEach(button=>{
    button.addEventListener('click', () => {
        changePage('next');
    })
})

prevBtn.forEach(button=>{
    button.addEventListener('click', () => {
        changePage('prev');
    })
})

function changePage(btn){
    let index = 0;
    const active = documenet.querySelector('form .page.active');
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