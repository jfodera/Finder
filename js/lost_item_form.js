const pages = Array.from(document.querySelector('form .page'));
const nextBtn = document.querySelectorAll('form .next-btn');
const prevBtn = document.querySelectorAll('form .prev-Btn');
const form = document.querySelectorAll('form');
console.log(pages)

nextBtn.forEach(button=>{
    button.addEventListener('click', (e) => {
        changeStep('next');
    })
})

function changeStep(btn){
    let index = 0;
    const active = documenet.querySelector('form .page.active');
    index = pages.indexOf
}