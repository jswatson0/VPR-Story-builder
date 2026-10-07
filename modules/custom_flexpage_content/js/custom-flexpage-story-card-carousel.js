(function (Drupal, once) {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // Swiping is native CSS scroll-snap; this wires the arrows and dots to the
  // track and keeps the active card/dot in sync with the scroll position.
  Drupal.behaviors.customFlexpageStoryCardCarousel = {
    attach(context) {
      once('vs-card-carousel', '[data-vs-card-carousel]', context).forEach((carousel) => {
        const track = carousel.querySelector('[data-vs-carousel-track]');
        const cards = Array.from(carousel.querySelectorAll('[data-vs-carousel-card]'));
        const dots = Array.from(carousel.querySelectorAll('[data-vs-carousel-dot]'));
        const prev = carousel.querySelector('[data-vs-carousel-prev]');
        const next = carousel.querySelector('[data-vs-carousel-next]');
        const loop = carousel.hasAttribute('data-vs-carousel-loop');
        if (!track || !cards.length) {
          return;
        }

        let activeIndex = -1;
        // Set while a button-driven scroll is in flight so intermediate scroll
        // positions don't overwrite the card the visitor asked for.
        let targetIndex = null;
        let settleTimer = 0;
        let frame = 0;

        const setActive = (index) => {
          if (index === activeIndex) {
            return;
          }
          activeIndex = index;
          cards.forEach((card, i) => {
            card.classList.toggle('is-active', i === index);
          });
          dots.forEach((dot, i) => {
            const isActive = i === index;
            dot.classList.toggle('is-active', isActive);
            if (isActive) {
              dot.setAttribute('aria-current', 'true');
            }
            else {
              dot.removeAttribute('aria-current');
            }
          });
          if (prev) {
            prev.disabled = !loop && index === 0;
          }
          if (next) {
            next.disabled = !loop && index === cards.length - 1;
          }
        };

        // Cards are positioned relative to the track (position: relative), so
        // offsetLeft is directly comparable to scrollLeft.
        const nearestIndex = () => {
          const center = track.scrollLeft + track.clientWidth / 2;
          let nearest = 0;
          let nearestDistance = Infinity;
          cards.forEach((card, i) => {
            const distance = Math.abs(card.offsetLeft + card.offsetWidth / 2 - center);
            if (distance < nearestDistance) {
              nearest = i;
              nearestDistance = distance;
            }
          });
          return nearest;
        };

        const settle = () => {
          window.clearTimeout(settleTimer);
          settleTimer = window.setTimeout(() => {
            targetIndex = null;
            setActive(nearestIndex());
          }, 150);
        };

        const goTo = (index, instant = false) => {
          const count = cards.length;
          const target = loop ? (index + count) % count : Math.max(0, Math.min(count - 1, index));
          const card = cards[target];
          track.scrollTo({
            left: card.offsetLeft + card.offsetWidth / 2 - track.clientWidth / 2,
            // 'auto' would defer to the track's CSS scroll-behavior: smooth.
            behavior: instant || prefersReducedMotion.matches ? 'instant' : 'smooth',
          });
          targetIndex = target;
          setActive(target);
          settle();
        };

        track.addEventListener('scroll', () => {
          if (targetIndex !== null) {
            settle();
            return;
          }
          window.cancelAnimationFrame(frame);
          frame = window.requestAnimationFrame(() => setActive(nearestIndex()));
        }, { passive: true });

        if (prev) {
          prev.addEventListener('click', () => goTo((targetIndex ?? activeIndex) - 1));
        }
        if (next) {
          next.addEventListener('click', () => goTo((targetIndex ?? activeIndex) + 1));
        }
        dots.forEach((dot) => {
          dot.addEventListener('click', () => goTo(parseInt(dot.dataset.vsCarouselDot, 10) || 0));
        });

        track.addEventListener('keydown', (event) => {
          if (event.target !== track) {
            return;
          }
          if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            goTo((targetIndex ?? activeIndex) + (event.key === 'ArrowLeft' ? -1 : 1));
          }
        });

        // Peek padding depends on the container width, so re-center the active
        // card whenever the track is resized.
        if (typeof ResizeObserver !== 'undefined') {
          let lastWidth = track.clientWidth;
          new ResizeObserver(() => {
            if (track.clientWidth !== lastWidth && activeIndex >= 0) {
              lastWidth = track.clientWidth;
              goTo(activeIndex, true);
            }
          }).observe(track);
        }

        setActive(nearestIndex());
        carousel.setAttribute('data-vs-carousel-ready', '');
      });
    },
  };
})(Drupal, once);
