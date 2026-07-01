(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var SCALE = 0.60;

    // Collapse GSAP pin-spacers so the actual block section is visible at the top.
    // GSAP sets height via inline style; we override with !important via setProperty.
    document.querySelectorAll('.vs-docs__example-inner .pin-spacer').forEach(function (spacer) {
      spacer.style.setProperty('height', 'auto', 'important');
      spacer.style.setProperty('min-height', '0', 'important');
      spacer.style.setProperty('padding-bottom', '0', 'important');
      spacer.style.setProperty('position', 'static', 'important');
    });

    // transform: scale() shrinks visual size but not layout size. Since the
    // container uses overflow:hidden (BFC), negative margin-bottom won't collapse it.
    // Instead, directly set the container height to the scaled visual height.
    document.querySelectorAll('.vs-docs__example').forEach(function (container) {
      var inner = container.querySelector('.vs-docs__example-inner');
      if (!inner) return;
      var naturalHeight = inner.offsetHeight;
      var visualHeight = Math.round(naturalHeight * SCALE);
      var maxH = 500;
      container.style.height = Math.min(maxH, visualHeight) + 'px';
    });
  });
}());
