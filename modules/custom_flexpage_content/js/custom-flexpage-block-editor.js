(function (Drupal, once) {
  Drupal.behaviors.customFlexpageHeroBlockEditor = {
    attach(context) {
      once('custom-flexpage-hero-block-editor', '[data-vs-hero-block-form]', context).forEach((form) => {
        const resetScroll = () => {
          form.scrollTop = 0;
          const dialogContent = form.closest('.ui-dialog-content');
          if (dialogContent) {
            dialogContent.scrollTop = 0;
          }
          const offCanvasMain = form.closest('.ui-dialog-off-canvas, .dialog-off-canvas-main-canvas');
          if (offCanvasMain) {
            offCanvasMain.scrollTop = 0;
          }
        };

        // Wait for dialog layout/widgets to settle before forcing initial top.
        requestAnimationFrame(() => {
          requestAnimationFrame(resetScroll);
        });
      });
    },
  };
})(Drupal, once);
