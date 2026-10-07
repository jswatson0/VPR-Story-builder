(function (Drupal, once) {
  // Keeps the "Card N" labels in the Card Carousel block form in sync with
  // the row order after tabledrag moves a card (by mouse or keyboard).
  Drupal.behaviors.customFlexpageCardCarouselEditor = {
    attach(context) {
      once('vs-card-carousel-editor', '[data-vs-card-carousel-cards]', context).forEach((table) => {
        const tbody = table.tBodies[0];
        if (!tbody) {
          return;
        }

        const renumber = () => {
          Array.from(tbody.rows).forEach((row, index) => {
            const label = row.querySelector('[data-vs-card-num]');
            if (label) {
              label.textContent = String(index + 1);
            }
          });
        };

        new MutationObserver(renumber).observe(tbody, { childList: true });
      });
    },
  };
})(Drupal, once);
