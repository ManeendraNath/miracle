// Back-to-top button and the header's shadow, both shown once the page has scrolled.
(function () {
    var button = document.querySelector('.mw-to-top');
    var header = document.querySelector('.mw-sticky');
    function update() {
        var y = window.scrollY;
        button.hidden = y < 400;
        header.classList.toggle('mw-scrolled', y > 0);
    }
    button.addEventListener('click', function () {
        var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({top: 0, behavior: calm ? 'auto' : 'smooth'});
        // The button is about to hide: keyboard users carry on from the top of the page.
        header.querySelector('.navbar-brand').focus({preventScroll: true});
    });
    window.addEventListener('scroll', update, {passive: true});
    update();
})();
