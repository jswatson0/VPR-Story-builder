(function (Drupal, once) {
  Drupal.behaviors.customFlexpageStoryBlocks = {
    attach(context) {
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

