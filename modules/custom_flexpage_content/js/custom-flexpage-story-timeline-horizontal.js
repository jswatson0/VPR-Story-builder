(function (Drupal, once) {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // Canvas width as a multiple of the viewport width, per zoom step.
  const ZOOM_LEVELS = [1, 1.5, 2, 3, 4, 6, 8, 12];
  const MAX_ROWS = 3;
  const MARKER_GAP = 8;
  const MIN_TICK_SPACING = 80;
  const TICK_STEPS = [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000];

  // Horizontal timeline: one slide at a time, with a zoomable time axis of
  // marker buttons below. Without JS every slide stays visible and stacked.
  Drupal.behaviors.customFlexpageStoryTimelineHorizontal = {
    attach(context) {
      once('vs-timeline-h', '[data-vs-timeline-h]', context).forEach((timeline) => {
        const slides = Array.from(timeline.querySelectorAll('[data-vs-timeline-h-slide]'));
        const markers = Array.from(timeline.querySelectorAll('[data-vs-timeline-h-marker]'));
        const prev = timeline.querySelector('[data-vs-timeline-h-prev]');
        const next = timeline.querySelector('[data-vs-timeline-h-next]');
        const viewport = timeline.querySelector('[data-vs-timeline-h-viewport]');
        const canvas = timeline.querySelector('[data-vs-timeline-h-canvas]');
        const axis = timeline.querySelector('[data-vs-timeline-h-axis]');
        const zoomButtons = Array.from(timeline.querySelectorAll('[data-vs-timeline-h-zoom]'));
        if (!slides.length || slides.length !== markers.length || !viewport || !canvas) {
          return;
        }

        const items = markers.map((button) => ({
          button,
          li: button.parentElement,
          position: parseFloat(button.parentElement.dataset.vsTimelineHPosition) || 0,
        }));
        const start = parseInt(timeline.dataset.vsTimelineHStart, 10);
        const end = parseInt(timeline.dataset.vsTimelineHEnd, 10);
        const hasYears = !Number.isNaN(start) && !Number.isNaN(end) && end > start;

        let activeIndex = -1;
        let zoom = -1;

        const behavior = () => (prefersReducedMotion.matches ? 'auto' : 'smooth');

        const setArrow = (button, index) => {
          if (!button) {
            return;
          }
          const target = markers[index];
          button.hidden = !target;
          if (!target) {
            return;
          }
          const year = target.dataset.year || '';
          const title = target.dataset.title || '';
          button.querySelector('[data-vs-timeline-h-arrow-year]').textContent = year;
          button.querySelector('[data-vs-timeline-h-arrow-title]').textContent = title;
          button.setAttribute('aria-label', Drupal.t('@direction: @label', {
            '@direction': button === prev ? Drupal.t('Previous') : Drupal.t('Next'),
            '@label': [year, title].filter(Boolean).join(' – ') || Drupal.t('milestone @n', { '@n': index + 1 }),
          }));
        };

        const centerMarker = (index, instant) => {
          const li = items[index].li;
          const left = li.offsetLeft + li.offsetWidth / 2 - viewport.clientWidth / 2;
          viewport.scrollTo({ left: Math.max(0, left), behavior: instant ? 'auto' : behavior() });
        };

        const goTo = (index, { instant = false } = {}) => {
          const target = Math.max(0, Math.min(slides.length - 1, index));
          if (target === activeIndex) {
            return;
          }
          const previous = slides[activeIndex];
          if (previous) {
            // Stop any video that was started on the slide being left.
            previous.querySelectorAll('iframe').forEach((iframe) => {
              iframe.dispatchEvent(new CustomEvent('vs-timeline-video-stop'));
            });
          }
          activeIndex = target;
          slides.forEach((slide, i) => {
            const isActive = i === target;
            slide.classList.toggle('is-active', isActive);
            slide.inert = !isActive;
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
          });
          markers.forEach((marker, i) => {
            const isActive = i === target;
            marker.classList.toggle('is-active', isActive);
            if (isActive) {
              marker.setAttribute('aria-current', 'step');
            }
            else {
              marker.removeAttribute('aria-current');
            }
          });
          setArrow(prev, target - 1);
          setArrow(next, target + 1);
          centerMarker(target, instant);
        };

        // Packs markers into up to MAX_ROWS rows, left to right. A marker
        // with no free row is nudged right of the row that frees up first.
        // Returns how many markers had to be nudged.
        const layoutMarkers = () => {
          canvas.style.minWidth = '';
          const width = canvas.clientWidth;
          const markerWidth = items[0].li.offsetWidth;
          const padding = 16;
          const span = Math.max(0, width - markerWidth - padding * 2);
          const rowEnds = [];
          let nudged = 0;
          const order = items
            .map((item, i) => ({ item, left: padding + item.position * span, i }))
            .sort((a, b) => a.left - b.left || a.i - b.i);

          for (const { item, left: dateLeft } of order) {
            let left = dateLeft;
            let row = rowEnds.findIndex((rowEnd) => rowEnd + MARKER_GAP <= left);
            if (row === -1) {
              if (rowEnds.length < MAX_ROWS) {
                row = rowEnds.length;
              }
              else {
                row = rowEnds.indexOf(Math.min(...rowEnds));
                left = rowEnds[row] + MARKER_GAP;
                nudged++;
              }
            }
            rowEnds[row] = left + markerWidth;
            item.li.style.left = `${left}px`;
            item.li.style.setProperty('--vs-timeline-row', row);
          }
          canvas.style.minWidth = `max(100%, ${Math.max(...rowEnds) + padding}px)`;
          timeline.style.setProperty('--vs-timeline-rows', Math.max(1, rowEnds.length));
          drawAxis(padding, span);
          return nudged;
        };

        const drawAxis = (padding, span) => {
          if (!axis) {
            return;
          }
          axis.textContent = '';
          if (!hasYears) {
            return;
          }
          const range = end - start;
          const pxPerYear = span / range;
          const step = TICK_STEPS.find((s) => s * pxPerYear >= MIN_TICK_SPACING) || TICK_STEPS[TICK_STEPS.length - 1];
          for (let year = Math.ceil(start / step) * step; year <= end; year += step) {
            const tick = document.createElement('span');
            tick.className = 'visual-story__timeline-tick';
            tick.style.left = `${padding + (year - start) * pxPerYear}px`;
            tick.textContent = year;
            axis.appendChild(tick);
          }
        };

        const setZoom = (level) => {
          zoom = Math.max(0, Math.min(ZOOM_LEVELS.length - 1, level));
          canvas.style.width = `${ZOOM_LEVELS[zoom] * 100}%`;
          layoutMarkers();
          zoomButtons.forEach((button) => {
            const direction = parseInt(button.dataset.vsTimelineHZoom, 10);
            button.disabled = direction > 0 ? zoom === ZOOM_LEVELS.length - 1 : zoom === 0;
          });
          if (activeIndex >= 0) {
            centerMarker(activeIndex, true);
          }
        };

        // Start at the widest view where no more than a quarter of the
        // markers are nudged off their dates (zooming in until none are can
        // make dense timelines enormously wide).
        const autoZoom = () => {
          const tolerance = Math.floor(items.length / 4);
          let level = 0;
          while (level < ZOOM_LEVELS.length - 1) {
            canvas.style.width = `${ZOOM_LEVELS[level] * 100}%`;
            if (layoutMarkers() <= tolerance) {
              break;
            }
            level++;
          }
          setZoom(level);
        };

        markers.forEach((marker, i) => {
          marker.addEventListener('click', () => goTo(i));
        });
        if (prev) {
          prev.addEventListener('click', () => goTo(activeIndex - 1));
        }
        if (next) {
          next.addEventListener('click', () => goTo(activeIndex + 1));
        }
        zoomButtons.forEach((button) => {
          button.addEventListener('click', () => setZoom(zoom + parseInt(button.dataset.vsTimelineHZoom, 10)));
        });

        timeline.addEventListener('keydown', (event) => {
          if (event.target.closest('input, textarea, select, iframe')) {
            return;
          }
          if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            const focusMarker = event.target.closest('[data-vs-timeline-h-marker]');
            goTo(activeIndex + (event.key === 'ArrowLeft' ? -1 : 1));
            if (focusMarker) {
              markers[activeIndex].focus({ preventScroll: true });
            }
          }
        });

        // Horizontal swipe on the slide area.
        const stage = slides[0].parentElement;
        let swipeX = null;
        let swipeY = null;
        stage.addEventListener('pointerdown', (event) => {
          if (event.pointerType === 'mouse') {
            return;
          }
          swipeX = event.clientX;
          swipeY = event.clientY;
        });
        stage.addEventListener('pointerup', (event) => {
          if (swipeX === null) {
            return;
          }
          const dx = event.clientX - swipeX;
          const dy = event.clientY - swipeY;
          swipeX = null;
          if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
            goTo(activeIndex + (dx < 0 ? 1 : -1));
          }
        });
        stage.addEventListener('pointercancel', () => {
          swipeX = null;
        });

        // Drag the axis with a mouse; touch and trackpads scroll natively.
        let dragX = null;
        let dragScroll = 0;
        let dragged = false;
        viewport.addEventListener('pointerdown', (event) => {
          if (event.pointerType !== 'mouse' || event.button !== 0) {
            return;
          }
          dragX = event.clientX;
          dragScroll = viewport.scrollLeft;
          dragged = false;
        });
        window.addEventListener('pointermove', (event) => {
          if (dragX === null) {
            return;
          }
          const dx = event.clientX - dragX;
          if (Math.abs(dx) > 4) {
            dragged = true;
            viewport.classList.add('is-dragging');
          }
          viewport.scrollLeft = dragScroll - dx;
        });
        window.addEventListener('pointerup', () => {
          dragX = null;
          viewport.classList.remove('is-dragging');
        });
        // Swallow the click that ends a drag so it doesn't select a marker.
        viewport.addEventListener('click', (event) => {
          if (dragged) {
            event.stopPropagation();
            event.preventDefault();
            dragged = false;
          }
        }, true);

        let resizeFrame = 0;
        let lastWidth = viewport.clientWidth;
        window.addEventListener('resize', () => {
          if (resizeFrame || viewport.clientWidth === lastWidth) {
            return;
          }
          resizeFrame = window.requestAnimationFrame(() => {
            resizeFrame = 0;
            lastWidth = viewport.clientWidth;
            setZoom(zoom);
          });
        });

        timeline.classList.add('is-ready');
        autoZoom();
        goTo(0, { instant: true });
      });
    },
  };
})(Drupal, once);
