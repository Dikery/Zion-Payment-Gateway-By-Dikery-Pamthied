// ui.js - shared reveal and tilt interactions
(function(){
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUI);
  } else {
    initUI();
  }

  function initUI(){
    try { setupReveals(); } catch(e) {}
    try { setupTilt(); } catch(e) {}
  }

  function setupReveals(){
    const els = Array.from(document.querySelectorAll('[data-reveal], .reveal'));
    if (!els.length) return;

    // Stagger on initial load
    els.forEach((el, i) => {
      const delay = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--reveal-delay-step')) || 0.06;
      el.style.animationDelay = (i * delay) + 's';
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });

    els.forEach(el => observer.observe(el));
  }

  function setupTilt(){
    const tiltEls = Array.from(document.querySelectorAll('[data-tilt], .tilt'));
    if (!tiltEls.length) return;

    tiltEls.forEach(el => {
      el.addEventListener('mousemove', (e) => {
        const rect = el.getBoundingClientRect();
        const x = e.clientX - rect.left; // x position within the element.
        const y = e.clientY - rect.top;  // y position within the element.
        const px = (x / rect.width) - 0.5;
        const py = (y / rect.height) - 0.5;
        const rot = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--tilt-rotate')) || 2.5;
        const tx = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--tilt-translate')) || 6;
        el.style.transform = `translateY(${tx}px) rotateX(${ -py * rot * 2 }deg) rotateY(${ px * rot * 2 }deg)`;
      });
      el.addEventListener('mouseleave', () => {
        el.style.transform = '';
      });
    });
  }
})();


