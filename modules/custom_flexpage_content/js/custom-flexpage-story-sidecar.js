(function (Drupal, once) {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // Plays a muted background video, ignoring the rejection browsers raise
  // when autoplay is blocked or the element is paused mid-load.
  const play = (video) => {
    const result = video.play();
    if (result && typeof result.catch === 'function') {
      result.catch(() => {});
    }
  };

  Drupal.behaviors.customFlexpageStorySidecar = {
    attach(context) {
      once('vs-sidecar', '[data-vs-sidecar]', context).forEach((section) => {
        if (typeof window.IntersectionObserver === 'undefined') {
          return;
        }

        const videos = Array.from(section.querySelectorAll('[data-vs-sidecar-video]'));
        const syncVideos = (shouldPlay) => {
          videos.forEach((video) => {
            if (shouldPlay(video) && !prefersReducedMotion.matches) {
              play(video);
            }
            else {
              video.pause();
            }
          });
        };

        if (section.dataset.vsSidecar === 'release') {
          // Each slide's media scrolls with its own row; play videos only
          // while they're on screen.
          const visible = new Set();
          const observer = new window.IntersectionObserver((entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                visible.add(entry.target);
              }
              else {
                visible.delete(entry.target);
              }
            });
            syncVideos((video) => visible.has(video));
          }, { threshold: 0.25 });
          videos.forEach((video) => observer.observe(video));
          return;
        }

        // Crossfade: the slide whose text crosses the middle of the viewport
        // picks the media layer. A slide without media keeps showing the
        // previous slide's media.
        const layers = Array.from(section.querySelectorAll('[data-vs-sidecar-layer]'));
        const slides = Array.from(section.querySelectorAll('[data-vs-sidecar-slide]'));
        if (!layers.length || !slides.length) {
          return;
        }

        let sectionVisible = false;
        let activeLayer = layers.find((layer) => layer.classList.contains('is-active')) || layers[0];

        const syncActiveVideo = () => {
          syncVideos((video) => sectionVisible && activeLayer.contains(video));
        };

        const activate = (slideIndex) => {
          const layer = layers.filter((l) => Number(l.dataset.vsSidecarLayer) <= slideIndex).pop() || layers[0];
          if (layer !== activeLayer) {
            activeLayer.classList.remove('is-active');
            activeLayer.setAttribute('aria-hidden', 'true');
            layer.classList.add('is-active');
            layer.removeAttribute('aria-hidden');
            activeLayer = layer;
          }
          syncActiveVideo();
        };

        const slideObserver = new window.IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              activate(Number(entry.target.dataset.vsSidecarSlide));
            }
          });
        }, { rootMargin: '-50% 0px -50% 0px' });
        slides.forEach((slide) => slideObserver.observe(slide));

        const sectionObserver = new window.IntersectionObserver((entries) => {
          sectionVisible = entries[0].isIntersecting;
          syncActiveVideo();
        });
        sectionObserver.observe(section);
      });
    },
  };
})(Drupal, once);
