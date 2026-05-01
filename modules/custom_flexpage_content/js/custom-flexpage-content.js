(function (Drupal, once) {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const hasGsap = typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined';

  const supportsParallax = () => {
    return !prefersReducedMotion.matches && window.innerWidth > 900;
  };

  const isLayoutBuilderContext = () => {
    return document.body.classList.contains('layout-builder') ||
      document.body.classList.contains('layout-builder-enabled') ||
      document.body.classList.contains('path-admin') ||
      document.querySelector('.layout-builder') !== null;
  };

  const clearHeroTransforms = (bg, content) => {
    bg.style.transform = '';
    content.style.transform = '';
  };

  const flagGsapIssue = (hero, message, errorObj) => {
    hero.classList.add('visual-story--gsap-error');
    if (!hero.querySelector('.visual-story__gsap-warning')) {
      const warning = document.createElement('p');
      warning.className = 'visual-story__gsap-warning';
      warning.textContent = message;
      hero.prepend(warning);
    }
    // Always fail loudly so this is obvious during QA.
    if (errorObj) {
      // eslint-disable-next-line no-console
      console.error(`[custom_flexpage_content] ${message}`, errorObj);
      return;
    }
    // eslint-disable-next-line no-console
    console.error(`[custom_flexpage_content] ${message}`);
  };

  const clampOffset = (value) => Math.max(-120, Math.min(120, value));

  const cssLengthToPx = (rawValue, fallbackPx) => {
    if (!rawValue) {
      return fallbackPx;
    }
    const value = String(rawValue).trim();
    const parsed = parseFloat(value);
    if (!Number.isFinite(parsed)) {
      return fallbackPx;
    }
    if (value.endsWith('vh')) {
      return (parsed / 100) * window.innerHeight;
    }
    if (value.endsWith('rem')) {
      const rootFontSize = parseFloat(window.getComputedStyle(document.documentElement).fontSize) || 16;
      return parsed * rootFontSize;
    }
    if (value.endsWith('px')) {
      return parsed;
    }
    return parsed;
  };

  const getClassicDepthMotionValues = (hero, bgLayer, content) => {
    const heroStyles = window.getComputedStyle(hero);
    const configuredLayerHeight = cssLengthToPx(
      heroStyles.getPropertyValue('--vs-hero-layer-height'),
      bgLayer.offsetHeight
    );
    const layerHeight = Math.max(1, configuredLayerHeight);
    const heroScrollRange = Math.max(1, hero.offsetHeight - layerHeight);
    const contentInsetBottom = parseFloat(window.getComputedStyle(content).paddingBottom) || 0;
    const maxContentShift = Math.max(0, layerHeight - content.offsetHeight - contentInsetBottom);
    const configuredScale = parseFloat(heroStyles.getPropertyValue('--vs-bg-scale')) || 1.06;
    // Safe travel so transformed image never exposes container background.
    const safeBgTravel = Math.max(0, ((configuredScale - 1) * layerHeight) / 2 - 2);
    const requestedBgTravel = clampOffset(heroScrollRange * 0.5);
    return {
      heroScrollRange,
      maxContentShift: clampOffset(maxContentShift),
      bgOffset: Math.min(requestedBgTravel, safeBgTravel),
      fgFastOffset: clampOffset(Math.min(maxContentShift, heroScrollRange * 1.1)),
    };
  };

  const getConfiguredBgScale = (hero) => {
    const heroStyles = window.getComputedStyle(hero);
    return parseFloat(heroStyles.getPropertyValue('--vs-bg-scale')) || 1.06;
  };

  const getReleaseFactors = (hero) => {
    // Keep background motion running almost the full section length so it
    // doesn't feel like it starts/stops abruptly.
    if (hero.classList.contains('visual-story--height-tall')) {
      return { contentReleaseFactor: 0.96, bgReleaseFactor: 0.98 };
    }
    if (hero.classList.contains('visual-story--height-screen')) {
      return { contentReleaseFactor: 0.97, bgReleaseFactor: 0.99 };
    }
    return { contentReleaseFactor: 0.95, bgReleaseFactor: 0.97 };
  };

  const attachGsapClassicDepthHero = (hero, bg, bgLayer, content) => {
    const { gsap, ScrollTrigger } = window;
    gsap.registerPlugin(ScrollTrigger);
    const foregroundBehavior = hero.dataset.vsForegroundBehavior || 'pinned_then_release';
    const foregroundTravelVh = parseFloat(hero.dataset.vsForegroundPinVh || '60');
    const foregroundTravelFactor = Math.max(0.35, Math.min(1, foregroundTravelVh / 100));
    const textZoomEffect = hero.dataset.vsTextZoomEffect || 'out';

    const setStaticState = () => {
      if (!supportsParallax()) {
        clearHeroTransforms(bg, content);
      }
    };
    setStaticState();

    // Reduced motion: no parallax, only simple fade.
    if (prefersReducedMotion.matches) {
      gsap.fromTo(
        [bg, content],
        { autoAlpha: 0 },
        { autoAlpha: 1, duration: 0.45, ease: 'power1.out', clearProps: 'opacity,visibility' }
      );
      return;
    }

    if (!supportsParallax()) {
      return;
    }

    // Set a stable baseline before creating scroll-linked tweens to avoid
    // first-paint "jump" when ScrollTrigger initializes.
    const configuredBgScale = getConfiguredBgScale(hero);
    const scrollZoomDelta = 0.1;
    const targetBgScale = Math.min(1.28, configuredBgScale + scrollZoomDelta);

    gsap.set(bg, { y: 0, scale: configuredBgScale, force3D: true });
    gsap.set(content, { y: 0, force3D: true });

    // Load-in animation: use bgLayer scale+fade while scroll tweens move bg.
    // This avoids competing transform tweens on the same element at init.
    gsap.fromTo(
      bgLayer,
      { autoAlpha: 0, scale: 1.05 },
      {
        autoAlpha: 1,
        scale: 1,
        duration: 0.9,
        ease: 'power2.out',
      },
    );
    gsap.fromTo(
      content,
      { autoAlpha: 0 },
      {
        autoAlpha: 1,
        duration: 0.75,
        delay: 0.08,
        ease: 'power2.out',
        clearProps: 'opacity,visibility',
      },
    );

    const buildScrollValues = () => getClassicDepthMotionValues(hero, bgLayer, content);
    const { contentReleaseFactor, bgReleaseFactor } = getReleaseFactors(hero);

    gsap.to(bg, {
      // Move upward only (negative Y) so the image stays locked to the top edge
      // while revealing more of the scene as the user scrolls.
      y: () => -buildScrollValues().bgOffset,
      scale: targetBgScale,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: () => `+=${Math.round(buildScrollValues().heroScrollRange * bgReleaseFactor)}`,
        scrub: 1.35,
        invalidateOnRefresh: true,
      },
    });

    const textTargetScale = textZoomEffect === 'in' ? 1.04 : textZoomEffect === 'out' ? 0.96 : 1;
    if (textTargetScale !== 1) {
      gsap.to(content, {
        scale: textTargetScale,
        ease: 'none',
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: () => `+=${Math.round(buildScrollValues().heroScrollRange)}`,
          scrub: 1.35,
          invalidateOnRefresh: true,
        },
      });
    }

    // Keep copy position stable for the default behavior to avoid vertical
    // jumps. Only apply vertical translation in explicit faster-than-scroll mode.
    if (foregroundBehavior === 'faster_than_scroll') {
      gsap.to(content, {
        y: () => clampOffset(buildScrollValues().fgFastOffset * foregroundTravelFactor),
        ease: 'none',
        scrollTrigger: {
          trigger: hero,
          start: 'top top',
          end: () => `+=${Math.round(buildScrollValues().heroScrollRange * contentReleaseFactor)}`,
          scrub: 1.35,
          invalidateOnRefresh: true,
        },
      });
    }

    window.addEventListener('resize', () => {
      setStaticState();
      ScrollTrigger.refresh();
    });
  };

  Drupal.behaviors.customFlexpageStoryBlocks = {
    attach(context) {
      once('custom-flexpage-hero-classic-depth', '[data-vs-classic-depth-hero]', context).forEach((hero) => {
        const bg = hero.querySelector('[data-vs-classic-bg]');
        const bgLayer = hero.querySelector('.visual-story__bg-layer');
        const content = hero.querySelector('[data-vs-classic-content]');

        if (!bg || !bgLayer || !content) {
          return;
        }

        if (isLayoutBuilderContext()) {
          clearHeroTransforms(bg, content);
          return;
        }

        if (!hasGsap) {
          flagGsapIssue(hero, 'GSAP/ScrollTrigger missing: classic depth hero did not initialize.');
          return;
        }

        try {
          attachGsapClassicDepthHero(hero, bg, bgLayer, content);
        }
        catch (error) {
          flagGsapIssue(hero, 'GSAP ScrollTrigger failed during classic depth hero initialization.', error);
        }
      });

      once('custom-flexpage-text-media-reveal', '[data-vs-text-media-reveal]', context).forEach((section) => {
        if (prefersReducedMotion.matches) {
          section.classList.add('is-visible');
          return;
        }

        if (typeof window.IntersectionObserver === 'undefined') {
          section.classList.add('is-visible');
          return;
        }

        const rootFontSize = parseFloat(window.getComputedStyle(document.documentElement).fontSize) || 16;
        const revealOffsetPx = Math.round(rootFontSize * 7);

        const observer = new window.IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) {
              return;
            }
            section.classList.add('is-visible');
            observer.unobserve(section);
          });
        }, {
          root: null,
          threshold: 0.12,
          // Delay reveal until the section is scrolled in by roughly 7rem.
          rootMargin: `0px 0px -${revealOffsetPx}px 0px`,
        });

        observer.observe(section);
      });

      once('custom-flexpage-text-media-scrollable', '[data-vs-text-media]', context).forEach((section) => {
        const text = section.querySelector('.visual-story__text');
        const media = section.querySelector('.visual-story__media');
        const bodyWrap = section.querySelector('[data-vs-text-media-body-wrap]');
        const body = section.querySelector('[data-vs-text-media-body]');

        if (!text || !media || !bodyWrap || !body) {
          return;
        }

        const setScrollCues = () => {
          const maxScrollTop = Math.max(1, body.scrollHeight - body.clientHeight);
          const topAlpha = body.scrollTop > 4 ? 1 : 0;
          const bottomAlpha = body.scrollTop < (maxScrollTop - 4) ? 1 : 0;

          if (hasGsap) {
            window.gsap.to(bodyWrap, {
              '--vs-scroll-top-alpha': topAlpha,
              '--vs-scroll-bottom-alpha': bottomAlpha,
              duration: 0.3,
              ease: 'power1.out',
              overwrite: true,
            });
            return;
          }

          bodyWrap.style.setProperty('--vs-scroll-top-alpha', String(topAlpha));
          bodyWrap.style.setProperty('--vs-scroll-bottom-alpha', String(bottomAlpha));
        };

        const clearTextScrollState = () => {
          body.style.maxHeight = 'none';
          body.style.overflowY = 'visible';
          body.scrollTop = 0;
          bodyWrap.style.setProperty('--vs-scroll-top-alpha', '0');
          bodyWrap.style.setProperty('--vs-scroll-bottom-alpha', '0');
        };

        let textScrollTrigger = null;
        const killTextScrollTrigger = () => {
          if (textScrollTrigger) {
            textScrollTrigger.kill();
            textScrollTrigger = null;
          }
        };

        const updateScrollableBody = () => {
          killTextScrollTrigger();
          clearTextScrollState();

          if (window.innerWidth <= 900 || prefersReducedMotion.matches || !hasGsap) {
            return;
          }

          const textStyles = window.getComputedStyle(text);
          const textPaddingTop = parseFloat(textStyles.paddingTop) || 0;
          const textPaddingBottom = parseFloat(textStyles.paddingBottom) || 0;
          const nonBodyHeight = text.offsetHeight - body.offsetHeight;
          const mediaHeight = media.getBoundingClientRect().height;
          const availableBodyHeight = Math.max(
            180,
            Math.round(mediaHeight - nonBodyHeight - textPaddingTop - textPaddingBottom)
          );

          const hiddenTextDistance = Math.max(0, body.scrollHeight - availableBodyHeight);
          if (hiddenTextDistance <= 2) {
            return;
          }

          body.style.maxHeight = `${availableBodyHeight}px`;
          body.style.overflowY = 'hidden';
          setScrollCues();

          textScrollTrigger = window.ScrollTrigger.create({
            trigger: section,
            start: 'bottom bottom',
            end: `+=${Math.round(hiddenTextDistance + 64)}`,
            scrub: 1.15,
            invalidateOnRefresh: true,
            onUpdate: (self) => {
              body.scrollTop = self.progress * hiddenTextDistance;
              setScrollCues();
            },
          });
        };

        window.addEventListener('resize', updateScrollableBody);
        updateScrollableBody();
      });

      once('custom-flexpage-story-timeline', '[data-vs-story-timeline]', context).forEach((section) => {
        const items = section.querySelectorAll('[data-vs-timeline-item]');
        const progressFill = section.querySelector('[data-vs-timeline-progress]');

        if (!items.length || !progressFill) {
          return;
        }

        if (prefersReducedMotion.matches || !hasGsap) {
          items.forEach((item) => item.classList.add('is-active'));
          progressFill.style.height = '100%';
          return;
        }

        const { ScrollTrigger } = window;

        items.forEach((item) => {
          ScrollTrigger.create({
            trigger: item,
            start: 'top 72%',
            end: 'bottom 38%',
            onEnter: () => item.classList.add('is-active'),
            onEnterBack: () => item.classList.add('is-active'),
            onLeave: () => item.classList.remove('is-active'),
            onLeaveBack: () => item.classList.remove('is-active'),
          });
        });

        ScrollTrigger.create({
          trigger: section,
          start: 'top 70%',
          end: 'bottom 35%',
          scrub: 1,
          invalidateOnRefresh: true,
          onUpdate: (self) => {
            progressFill.style.height = `${(self.progress * 100).toFixed(2)}%`;
          },
        });
      });

    },
  };
})(Drupal, once);
