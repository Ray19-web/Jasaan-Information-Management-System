document.addEventListener('DOMContentLoaded', () => {

  
  const jasaanCards = document.querySelectorAll('.jasaan-why-card');

  const jasaanObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('is-visible');
        }, index * 150);
      }
    });
  }, { threshold: 0.2 });

  jasaanCards.forEach(card => jasaanObserver.observe(card));

  const whyBtn = document.querySelector('.jt-hero-btn-primary');
  const historyBtn = document.querySelector('.jt-history-btn');

  const whySection = document.getElementById('jasaan-why-section');
  const historySection = document.getElementById('jt-heritage-section');

  function safeScroll(target) {
    const targetPosition = target.offsetTop - 80; 
    window.scrollTo({
      top: targetPosition,
      behavior: 'smooth'
    });
  }

  function addClickAnimation(button) {
    button.classList.add('btn-clicked');
    setTimeout(() => {
      button.classList.remove('btn-clicked');
    }, 300);
  }

  if (whyBtn && whySection) {
    whyBtn.addEventListener('click', (e) => {
      e.preventDefault();
      addClickAnimation(whyBtn);
      safeScroll(whySection);
    });
  }

  if (historyBtn && historySection) {
    historyBtn.addEventListener('click', (e) => {
      e.preventDefault();
      addClickAnimation(historyBtn);
      safeScroll(historySection);
    });
  }
});