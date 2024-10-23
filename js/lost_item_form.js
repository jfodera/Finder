document.addEventListener("DOMContentLoaded", function() {
   //returns array of all of the pages, bc .page are descendants 
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
   //removes active tag
   pages[index].classList.remove('active');
   // increment/decrement index accordingly
   if(btn ==='next'){
       index ++;
   }else if(btn ==='prev'){
       index --;
   }
   //add active hold back 
   pages[index].classList.add('active')
   console.log(index)
}

   
});