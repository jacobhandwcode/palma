document.addEventListener('DOMContentLoaded', function () {
  const phoneButton = document.querySelector('.phone-button');
  const popup = document.getElementById('phonePopup');
  const overlay = document.getElementById('popupOverlay');
  const closeBtn = document.getElementById('closePopup');

  if (phoneButton && popup && overlay && closeBtn) {
    function showPopup() {
      popup.classList.add('active');
      overlay.classList.add('active');
    }

    function hidePopup() {
      popup.classList.remove('active');
      overlay.classList.remove('active');
    }

    phoneButton.addEventListener('click', function (e) {
      e.preventDefault();
      showPopup();
    });

    closeBtn.addEventListener('click', hidePopup);
    overlay.addEventListener('click', hidePopup);

    setTimeout(hidePopup, 10000);
  }

  const mobilePhoneButton = document.getElementById('mobilePhoneButton');
  const mobileBottomBar = document.getElementById('mobileBottomBar');
  const closeMobileBar = document.getElementById('closeMobileBar');
  const body = document.body;

  if (mobilePhoneButton) {
    mobilePhoneButton.addEventListener('click', function () {
      mobilePhoneButton.classList.add('hidden');
      mobileBottomBar.classList.add('active');
      body.classList.add('mobile-bar-active');
    });
  }

  if (closeMobileBar) {
    closeMobileBar.addEventListener('click', function () {
      mobileBottomBar.classList.remove('active');
      body.classList.remove('mobile-bar-active');
      mobilePhoneButton.classList.remove('hidden');
    });
  }

  const slider = document.getElementById('gallerySlider');
  const sliderImage = document.getElementById('galleryImage');
  const closeBtnG = document.getElementById('galleryClose');
  const prevBtn = document.getElementById('galleryPrev');
  const nextBtn = document.getElementById('galleryNext');
  const galleryContainers = document.querySelectorAll(
    '.gallery-page-item[data-id]'
  );

  if (
    slider &&
    sliderImage &&
    closeBtnG &&
    prevBtn &&
    nextBtn &&
    galleryContainers.length > 0
  ) {
    const galleryItems = [
      {
        id: 1,
        src: '/images/gallery-page/crownshot.webp',
        alt: 'Gallery Image 1',
      },
      { id: 2, src: '/images/gallery-page/pool.webp', alt: 'Gallery Image 2' },
      { id: 3, src: '/images/gallery-page/bath.webp', alt: 'Gallery Image 3' },
      {
        id: 4,
        src: '/images/gallery-page/a1-living.webp',
        alt: 'Gallery Image 4',
      },
      {
        id: 5,
        src: '/images/gallery-page/kitchen.webp',
        alt: 'Gallery Image 5',
      },
      { id: 6, src: '/images/gallery-page/lobby.webp', alt: 'Gallery Image 6' },
      {
        id: 7,
        src: '/images/gallery-page/sunset.webp',
        alt: 'Gallery Image 7',
      },
      { id: 8, src: '/images/gallery-page/hero.webp', alt: 'Gallery Image 8' },
      {
        id: 9,
        src: '/images/gallery-page/fitness.webp',
        alt: 'Gallery Image 9',
      },
      {
        id: 10,
        src: '/images/gallery-page/pool-trellis.webp',
        alt: 'Gallery Image 10',
      },
      {
        id: 11,
        src: '/images/gallery-page/aqua-lounge.webp',
        alt: 'Gallery Image 11',
      },
      {
        id: 12,
        src: '/images/gallery-page/reception.webp',
        alt: 'Gallery Image 12',
      },
      {
        id: 13,
        src: '/images/gallery-page/a6-living.webp',
        alt: 'Gallery Image 13',
      },
      {
        id: 14,
        src: '/images/gallery-page/b1-living.webp',
        alt: 'Gallery Image 14',
      },
      {
        id: 15,
        src: '/images/gallery-page/bedroom.webp',
        alt: 'Gallery Image 15',
      },
    ];

    let currentImageIndex = 0;

    function openGallery(imageId) {
      currentImageIndex = galleryItems.findIndex(
        (item) => item.id === parseInt(imageId)
      );
      updateSliderImage();
      slider.classList.add('gallery-page-slider--active');
      document.body.style.overflow = 'hidden';
    }

    function closeGallery() {
      slider.classList.remove('gallery-page-slider--active');
      document.body.style.overflow = '';
    }

    function updateSliderImage() {
      const currentItem = galleryItems[currentImageIndex];
      sliderImage.src = currentItem.src;
      sliderImage.alt = currentItem.alt;
    }

    function prevImage() {
      currentImageIndex =
        (currentImageIndex - 1 + galleryItems.length) % galleryItems.length;
      updateSliderImage();
    }

    function nextImage() {
      currentImageIndex = (currentImageIndex + 1) % galleryItems.length;
      updateSliderImage();
    }

    galleryContainers.forEach((container) => {
      container.addEventListener('click', (e) => {
        const imageId = container.getAttribute('data-id');
        openGallery(imageId);
      });

      container.style.cursor = 'pointer';
    });

    closeBtnG.addEventListener('click', closeGallery);
    prevBtn.addEventListener('click', prevImage);
    nextBtn.addEventListener('click', nextImage);

    slider.addEventListener('click', (e) => {
      if (
        e.target === slider ||
        e.target.classList.contains('gallery-page-slider__overlay')
      ) {
        closeGallery();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (!slider.classList.contains('gallery-page-slider--active')) return;

      switch (e.key) {
        case 'Escape':
          closeGallery();
          break;
        case 'ArrowLeft':
          prevImage();
          break;
        case 'ArrowRight':
          nextImage();
          break;
      }
    });
  }
});

// Scroll Animation Handler
class ScrollAnimations {
  constructor() {
    this.animatedElements = new Set();
    this.observer = null;
    this.init();
  }

  init() {
    // Create intersection observer
    this.observer = new IntersectionObserver(
      (entries) => this.handleIntersection(entries),
      {
        threshold: 0.15, // Trigger when 15% of element is visible
        rootMargin: '0px 0px -50px 0px', // Start animation slightly before element is fully visible
      }
    );

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () =>
        this.setupAnimations()
      );
    } else {
      this.setupAnimations();
    }
  }

  setupAnimations() {
    // Add animation classes to home page elements
    this.addAnimationClasses();

    // Observe all elements with animation classes
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      this.observer.observe(element);
    });

    // Add a slight delay to ensure elements are ready
    setTimeout(() => {
      this.checkInitiallyVisible();
    }, 100);
  }

  addAnimationClasses() {
    // Text box
    const textBox = document.querySelector('.text-box');
    if (textBox) {
      textBox.classList.add('animate-on-scroll');
    }

    // Two images
    const imgContainers = document.querySelectorAll('.two-imgs .img-container');
    imgContainers.forEach((container) => {
      container.classList.add('animate-on-scroll');
    });

    // Text box no bg
    const textBoxNoBg = document.querySelector('.text-box-no-bg');
    if (textBoxNoBg) {
      textBoxNoBg.classList.add('animate-on-scroll');
    }

    // Parallax image
    const parallaxImg = document.querySelector('.parralax-img');
    if (parallaxImg) {
      parallaxImg.classList.add('animate-on-scroll');
    }

    // Funnel cards
    const funnelCards = document.querySelectorAll('.funnel-card');
    funnelCards.forEach((card) => {
      card.classList.add('animate-on-scroll');
    });

    // Gallery
    const gallery = document.querySelector('.gallery');
    if (gallery) {
      gallery.classList.add('animate-on-scroll');
    }
  }

  handleIntersection(entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting && !this.animatedElements.has(entry.target)) {
        // Add animation class with a small delay for smoother effect
        setTimeout(() => {
          entry.target.classList.add('animate-in');
          this.animatedElements.add(entry.target);
        }, 50);

        // Unobserve the element after animation to improve performance
        this.observer.unobserve(entry.target);
      }
    });
  }

  checkInitiallyVisible() {
    // Check if any elements are already visible on page load
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      const rect = element.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight * 0.85; // If element is 85% visible

      if (isVisible && !this.animatedElements.has(element)) {
        element.classList.add('animate-in');
        this.animatedElements.add(element);
        this.observer.unobserve(element);
      }
    });
  }

  // Method to manually trigger animation for specific elements
  animateElement(element) {
    if (element && !this.animatedElements.has(element)) {
      element.classList.add('animate-in');
      this.animatedElements.add(element);
      if (this.observer) {
        this.observer.unobserve(element);
      }
    }
  }

  // Cleanup method
  destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
    this.animatedElements.clear();
  }
}

// Initialize scroll animations
let scrollAnimations;

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    scrollAnimations = new ScrollAnimations();
  });
} else {
  scrollAnimations = new ScrollAnimations();
}

// Optional: Expose to global scope for debugging
window.scrollAnimations = scrollAnimations;
