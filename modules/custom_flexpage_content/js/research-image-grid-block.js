(function (Drupal, once) {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  Drupal.behaviors.researchImageGridBlock = {
    attach(context) {
      once('research-image-grid-block', '[data-image-grid-block]', context).forEach((gridWrapper) => {
        const modalElement = gridWrapper.querySelector('[data-image-grid-modal]');
        const modalTitle = gridWrapper.querySelector('[data-image-grid-modal-title]');
        const modalImage = gridWrapper.querySelector('[data-image-grid-modal-image]');
        const modalDescription = gridWrapper.querySelector('[data-image-grid-modal-description]');
        const tileButtons = gridWrapper.querySelectorAll('.mosaic-item-button');
        const hasGsap = typeof window.gsap !== 'undefined';

        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
          return;
        }

        const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalElement);

        if (hasGsap && !prefersReducedMotion.matches && tileButtons.length > 0) {
          window.gsap.fromTo(
            tileButtons,
            { autoAlpha: 0, y: 10 },
            {
              autoAlpha: 1,
              y: 0,
              duration: 0.45,
              ease: 'power2.out',
              stagger: 0.05,
              clearProps: 'opacity,transform',
            },
          );

          tileButtons.forEach((trigger) => {
            trigger.addEventListener('mouseenter', () => {
              window.gsap.to(trigger, { scale: 1.015, duration: 0.16, ease: 'power2.out' });
            });

            trigger.addEventListener('mouseleave', () => {
              window.gsap.to(trigger, { scale: 1, duration: 0.16, ease: 'power2.out' });
            });

            trigger.addEventListener('focusin', () => {
              window.gsap.to(trigger, { scale: 1.015, duration: 0.16, ease: 'power2.out' });
            });

            trigger.addEventListener('focusout', () => {
              window.gsap.to(trigger, { scale: 1, duration: 0.16, ease: 'power2.out' });
            });
          });
        }

        gridWrapper.querySelectorAll('.mosaic-item-button').forEach((trigger) => {
          trigger.addEventListener('click', () => {
            const imageSrc = trigger.getAttribute('data-image-grid-img') || '';
            const title = trigger.getAttribute('data-image-grid-title') || '';
            const descriptionSource = trigger.nextElementSibling;
            const descriptionHtml = (
              descriptionSource &&
              descriptionSource.matches('[data-image-grid-desc-source]')
            ) ? descriptionSource.innerHTML : '';

            if (modalTitle) {
              modalTitle.textContent = title;
            }
            if (modalImage) {
              modalImage.setAttribute('src', imageSrc);
              modalImage.setAttribute('alt', title);
            }
            if (modalDescription) {
              modalDescription.innerHTML = descriptionHtml;
            }

            modalInstance.show();
          });
        });

        if (hasGsap && !prefersReducedMotion.matches) {
          modalElement.addEventListener('show.bs.modal', () => {
            const animatedNodes = [modalImage, modalDescription].filter(Boolean);
            if (animatedNodes.length === 0) {
              return;
            }

            window.gsap.set(animatedNodes, { autoAlpha: 0 });
          });

          modalElement.addEventListener('shown.bs.modal', () => {
            const animatedNodes = [modalImage, modalDescription].filter(Boolean);
            if (animatedNodes.length === 0) {
              return;
            }

            window.gsap.to(
              animatedNodes,
              {
                autoAlpha: 1,
                duration: 0.32,
                ease: 'power2.out',
                stagger: 0.06,
                clearProps: 'opacity',
              },
            );
          });
        }
      });
    },
  };
})(Drupal, once);
