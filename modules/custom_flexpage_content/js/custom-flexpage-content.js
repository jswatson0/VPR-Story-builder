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

      once('custom-flexpage-scrollpoints', '[data-vs-scrollpoints]', context).forEach((wrapper) => {
        const pointButtons = wrapper.querySelectorAll('[data-vs-point]');
        const pointPanels = wrapper.querySelectorAll('[data-vs-panel]');

        const activatePoint = (index) => {
          pointButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.vsPoint === index);
          });
          pointPanels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.vsPanel === index);
          });
        };

        pointButtons.forEach((button) => {
          button.addEventListener('click', () => activatePoint(button.dataset.vsPoint));
        });

        if (pointButtons.length) {
          activatePoint(pointButtons[0].dataset.vsPoint);
        }
      });
    },
  };
})(Drupal, once);
