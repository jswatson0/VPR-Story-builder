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
      document.querySelector('form.layout-builder') !== null;
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
    const backgroundScrollBehavior = hero.dataset.vsBackgroundScrollBehavior || 'parallax';
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
    const scrollZoomDelta = 0.14;
    const targetBgScale = Math.min(1.28, configuredBgScale + scrollZoomDelta);

    gsap.set(bg, { y: 0, scale: configuredBgScale, force3D: true });
    gsap.set(content, { y: 0, force3D: true });

    // Load-in animation: use bgLayer scale+fade while scroll tweens move bg.
    // This avoids competing transform tweens on the same element at init.
    gsap.fromTo(
      bgLayer,
      backgroundScrollBehavior === 'parallax' ? { autoAlpha: 0, scale: 1.05 } : { autoAlpha: 0 },
      {
        autoAlpha: 1,
        ...(backgroundScrollBehavior === 'parallax' ? { scale: 1 } : {}),
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
    const motionStart = 'top bottom';
    const motionEnd = 'bottom top';

    if (backgroundScrollBehavior === 'parallax') {
      gsap.to(bg, {
        // Move upward only (negative Y) so the image stays locked to the top edge
        // while revealing more of the scene as the user scrolls.
        y: () => -buildScrollValues().bgOffset,
        scale: targetBgScale,
        ease: 'none',
        scrollTrigger: {
          trigger: hero,
          start: motionStart,
          end: motionEnd,
          scrub: 1.35,
          invalidateOnRefresh: true,
        },
      });
    }

    const textTargetScale = textZoomEffect === 'in' ? 1.1 : textZoomEffect === 'out' ? 0.9 : 1;
    if (textTargetScale !== 1) {
      gsap.to(content, {
        scale: textTargetScale,
        ease: 'none',
        scrollTrigger: {
          trigger: hero,
          start: motionStart,
          end: motionEnd,
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

      once('vs-bg-video-parallax', '[data-vs-bg-video]', context).forEach((section) => {
        const content = section.querySelector('.visual-story__content');
        if (!content) {
          return;
        }

        if (isLayoutBuilderContext() || !hasGsap) {
          return;
        }

        if (prefersReducedMotion.matches || window.innerWidth <= 900) {
          return;
        }

        const { gsap, ScrollTrigger } = window;
        gsap.registerPlugin(ScrollTrigger);

        const pinDistance = Math.round(window.innerHeight * 0.85);

        // Content starts below its natural position and faded out.
        gsap.set(content, { y: '40vh', autoAlpha: 0 });

        // Pin the section (video locks in place) for the scroll travel distance.
        ScrollTrigger.create({
          trigger: section,
          pin: true,
          pinSpacing: true,
          start: 'top top',
          end: `+=${pinDistance}`,
          invalidateOnRefresh: true,
        });

        // Scrub content up into its configured position as the user scrolls.
        gsap.to(content, {
          y: 0,
          autoAlpha: 1,
          ease: 'none',
          scrollTrigger: {
            trigger: section,
            start: 'top top',
            end: `+=${pinDistance}`,
            scrub: 1.2,
            invalidateOnRefresh: true,
          },
        });

        window.addEventListener('resize', () => ScrollTrigger.refresh());
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

      once('custom-flexpage-detail-selector', '[data-vs-detail-selector]', context).forEach((section) => {
        const triggers = Array.from(section.querySelectorAll('[data-vs-detail-trigger]'));
        const panels = Array.from(section.querySelectorAll('[data-vs-detail-panel]'));
        if (!triggers.length || !panels.length) {
          return;
        }

        const activatePanel = (targetId) => {
          triggers.forEach((trigger) => {
            const isActive = trigger.dataset.vsTarget === targetId;
            trigger.classList.toggle('is-active', isActive);
            trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
            trigger.setAttribute('aria-expanded', isActive ? 'true' : 'false');
          });

          panels.forEach((panel) => {
            const isActive = panel.dataset.vsDetailPanel === targetId;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
          });
        };

        activatePanel('default');

        triggers.forEach((trigger) => {
          trigger.addEventListener('click', () => {
            const targetId = trigger.dataset.vsTarget || 'default';
            const isAlreadyActive = trigger.classList.contains('is-active');
            activatePanel(isAlreadyActive ? 'default' : targetId);
          });
        });
      });

      // ── Story: Title Section ──────────────────────────────────────────
      once('vs-title-section', '[data-vs-title]', context).forEach((section) => {
        if (isLayoutBuilderContext()) {
          return;
        }

        const bgEl = section.querySelector('[data-vs-title-bg]');
        const bgMedia = bgEl ? bgEl.querySelector('.vs-title__bg-media') : null;
        const textBlock = section.querySelector('[data-vs-title-textblock]');
        const bgScrollEffect = section.dataset.vsBgScroll || 'none';
        const textScrollEffect = section.dataset.vsTextScroll || 'none';
        const textPerLine = section.dataset.vsTextPerLine === '1';

        // Entrance fade-in (always, even without GSAP fallback via CSS).
        if (!hasGsap) {
          return;
        }

        const { gsap, ScrollTrigger } = window;
        gsap.registerPlugin(ScrollTrigger);

        if (prefersReducedMotion.matches || window.innerWidth <= 900) {
          return;
        }

        // Background scroll effects.
        if (bgMedia && bgScrollEffect !== 'none') {
          if (bgScrollEffect === 'parallax') {
            // The bg-media is CSS-positioned at height:130%, top:-15% giving
            // 15% slack on each side. We travel ±12% of the layer height so
            // there is always a 3% safety margin — the container background
            // is never exposed at either edge.
            const getTravelPx = () => Math.round((bgEl ? bgEl.offsetHeight : section.offsetHeight) * 0.12);
            gsap.fromTo(bgMedia,
              { y: () => getTravelPx() },
              {
                y: () => -getTravelPx(),
                ease: 'none',
                scrollTrigger: {
                  trigger: section,
                  start: 'top bottom',
                  end: 'bottom top',
                  scrub: true,
                  invalidateOnRefresh: true,
                },
              }
            );
          }
          else if (bgScrollEffect === 'zoom-in') {
            // Image already extends beyond container via CSS, so scale
            // from a slightly zoomed baseline to avoid any edge reveal.
            gsap.fromTo(bgMedia,
              { scale: 1.05 },
              {
                scale: 1.22,
                ease: 'none',
                scrollTrigger: {
                  trigger: section,
                  start: 'top bottom',
                  end: 'bottom top',
                  scrub: true,
                  invalidateOnRefresh: true,
                },
              }
            );
          }
        }

        // Text scroll-out effects: fire as section scrolls up and out.
        if (textScrollEffect !== 'none' && textBlock) {
          const buildProps = (effect) => {
            if (effect === 'fade') {
              return { autoAlpha: 0 };
            }
            if (effect === 'blur') {
              return { autoAlpha: 0.1, filter: 'blur(10px)' };
            }
            if (effect === 'zoom') {
              return { scale: 0.84, autoAlpha: 0.1 };
            }
            return {};
          };

          const scrollEnd = () => `+=${Math.round(section.offsetHeight * 0.55)}`;

          if (textPerLine) {
            const lines = Array.from(textBlock.children);
            if (lines.length > 0) {
              const tl = gsap.timeline({
                scrollTrigger: {
                  trigger: section,
                  start: 'top top',
                  end: scrollEnd,
                  scrub: 1.2,
                  invalidateOnRefresh: true,
                },
              });
              lines.forEach((line, i) => {
                const position = (i / Math.max(lines.length, 1)) * 0.6;
                tl.to(line, { ...buildProps(textScrollEffect), ease: 'none' }, position);
              });
            }
          }
          else {
            gsap.to(textBlock, {
              ...buildProps(textScrollEffect),
              ease: 'none',
              scrollTrigger: {
                trigger: section,
                start: 'top top',
                end: scrollEnd,
                scrub: 1.2,
                invalidateOnRefresh: true,
              },
            });
          }
        }

        window.addEventListener('resize', () => ScrollTrigger.refresh());
      });

      once('vs-reveal-section', '[data-vs-reveal]', context).forEach((section) => {
        if (isLayoutBuilderContext() || !hasGsap) {
          return;
        }

        const panels = Array.from(section.querySelectorAll('[data-vs-reveal-panel]'));
        if (panels.length < 2) {
          return;
        }

        const { gsap, ScrollTrigger } = window;
        gsap.registerPlugin(ScrollTrigger);

        if (prefersReducedMotion.matches) {
          return;
        }

        const durationMap = { short: 800, medium: 1600, long: 3000 };
        const perTransition = durationMap[section.dataset.vsRevealDuration] || 800;
        const totalDistance = (panels.length - 1) * perTransition;
        const numTransitions = panels.length - 1;
        const isPageTurn = section.hasAttribute('data-vs-page-turn');

        // fromTo with explicit from values so invalidate() can never
        // re-read a stale in-progress position as the new "from".
        const tl = gsap.timeline({ defaults: { ease: 'none' } });

        if (isPageTurn) {
          // Page-turn: outgoing panels fold away toward the left spine (rotateY 0→-90),
          // revealing the panel beneath. Stack so the first panel is on top.
          panels.forEach((panel, i) => {
            gsap.set(panel, { zIndex: panels.length - i, force3D: true });
          });

          panels.forEach((panel, i) => {
            if (i === panels.length - 1) {
              return; // Last panel has nothing beneath it — stays put.
            }

            // Inject the fold corner: a single fixed-size div scaled from 0 at
            // the top-right corner so GSAP never has to wrestle with percentage
            // children of a zero-sized container.
            const fold = document.createElement('div');
            fold.className = 'visual-story__page-fold';
            panel.appendChild(fold);
            gsap.set(fold, { scaleX: 0, scaleY: 0, transformOrigin: '100% 0%' });

            const pos = i / numTransitions;
            const dur = 1 / numTransitions;
            const peak = dur * 0.28;

            // Corner lifts as the page begins to turn, then retreats as the
            // full-panel rotation takes over.
            tl.to(fold, { scaleX: 1, scaleY: 1, ease: 'power2.out', duration: peak }, pos);
            tl.to(fold, { scaleX: 0, scaleY: 0, ease: 'power2.in', duration: dur - peak }, pos + peak);

            tl.fromTo(
              panel,
              { rotateY: 0 },
              { rotateY: -90, duration: dur },
              pos,
            );
          });
        }
        else {
          const getOffscreen = (panel) => {
            const dir = panel.dataset.vsRevealDirection || 'from-right';
            const w = section.offsetWidth;
            const h = section.offsetHeight;
            return ({
              'from-right':  { x: w,  y: 0 },
              'from-left':   { x: -w, y: 0 },
              'from-bottom': { x: 0,  y: h },
              'from-top':    { x: 0,  y: -h },
            })[dir] || { x: w, y: 0 };
          };

          // Place panels off-screen via GSAP so it owns the transforms.
          panels.forEach((panel, i) => {
            if (i === 0) {
              return;
            }
            const off = getOffscreen(panel);
            gsap.set(panel, { x: off.x, y: off.y, force3D: true });
          });

          panels.forEach((panel, i) => {
            if (i === 0) {
              return;
            }
            const off = getOffscreen(panel);
            tl.fromTo(
              panel,
              { x: off.x, y: off.y },
              { x: 0, y: 0, duration: 1 / numTransitions },
              (i - 1) / numTransitions,
            );
          });
        }

        ScrollTrigger.create({
          trigger: section,
          pin: true,
          anticipatePin: 1,
          start: 'top top',
          end: `+=${totalDistance}`,
          scrub: true,
          animation: tl,
        });

        window.addEventListener('resize', () => {
          ScrollTrigger.refresh();
        });
      });

      once('vs-text-section-fade', '[data-vs-text-section-fade]', context).forEach((section) => {
        if (isLayoutBuilderContext() || prefersReducedMotion.matches) {
          return;
        }

        if (typeof window.IntersectionObserver === 'undefined') {
          return;
        }

        section.classList.add('will-fade');

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
          threshold: 0.1,
        });

        observer.observe(section);
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
