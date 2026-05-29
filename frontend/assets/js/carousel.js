const slides = document.querySelectorAll('.jt-hero-slide');
const dots = document.querySelectorAll('.jt-hero-dot');

let current = 0;
let interval;

function showSlide(index) {
    slides.forEach((s, i) => {
        s.classList.toggle('jt-hero-active', i === index);
    });

    dots.forEach((d, i) => {
        d.classList.toggle('jt-hero-active', i === index);
    });

    
    const text = document.querySelector('.jt-hero-typing');
    text.style.animation = 'none';
    text.offsetHeight;
    text.style.animation = null;

    current = index;
}

function nextSlide() {
    let next = (current + 1) % slides.length;
    showSlide(next);
}

dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
        clearInterval(interval);
        showSlide(i);
        start();
    });
});

function start() {
    interval = setInterval(nextSlide, 12000);
}

start();