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

class ScrollAnimations {
  constructor() {
    this.animatedElements = new Set();
    this.observer = null;
    this.init();
  }

  init() {
    this.observer = new IntersectionObserver(
      (entries) => this.handleIntersection(entries),
      {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px',
      }
    );

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () =>
        this.setupAnimations()
      );
    } else {
      this.setupAnimations();
    }
  }

  setupAnimations() {
    this.addAnimationClasses();

    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      this.observer.observe(element);
    });

    setTimeout(() => {
      this.checkInitiallyVisible();
    }, 100);
  }

  addAnimationClasses() {
    const textBox = document.querySelector('.text-box');
    if (textBox) {
      textBox.classList.add('animate-on-scroll');
    }

    const imgContainers = document.querySelectorAll('.two-imgs .img-container');
    imgContainers.forEach((container) => {
      container.classList.add('animate-on-scroll');
    });

    const textBoxNoBg = document.querySelector('.text-box-no-bg');
    if (textBoxNoBg) {
      textBoxNoBg.classList.add('animate-on-scroll');
    }

    const parallaxImg = document.querySelector('.parralax-img');
    if (parallaxImg) {
      parallaxImg.classList.add('animate-on-scroll');
    }

    const funnelCards = document.querySelectorAll('.funnel-card');
    funnelCards.forEach((card) => {
      card.classList.add('animate-on-scroll');
    });

    const gallery = document.querySelector('.gallery');
    if (gallery) {
      gallery.classList.add('animate-on-scroll');
    }
  }

  handleIntersection(entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting && !this.animatedElements.has(entry.target)) {
        setTimeout(() => {
          entry.target.classList.add('animate-in');
          this.animatedElements.add(entry.target);
        }, 50);

        this.observer.unobserve(entry.target);
      }
    });
  }

  checkInitiallyVisible() {
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      const rect = element.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight * 0.85;

      if (isVisible && !this.animatedElements.has(element)) {
        element.classList.add('animate-in');
        this.animatedElements.add(element);
        this.observer.unobserve(element);
      }
    });
  }

  animateElement(element) {
    if (element && !this.animatedElements.has(element)) {
      element.classList.add('animate-in');
      this.animatedElements.add(element);
      if (this.observer) {
        this.observer.unobserve(element);
      }
    }
  }

  destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
    this.animatedElements.clear();
  }
}

let scrollAnimations;

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    scrollAnimations = new ScrollAnimations();
  });
} else {
  scrollAnimations = new ScrollAnimations();
}

window.scrollAnimations = scrollAnimations;

class AmenitiesScrollAnimations {
  constructor() {
    this.animatedElements = new Set();
    this.observer = null;
    this.init();
  }

  init() {
    this.observer = new IntersectionObserver(
      (entries) => this.handleIntersection(entries),
      {
        threshold: 0.1,
        rootMargin: '0px 0px -80px 0px',
      }
    );

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () =>
        this.setupAnimations()
      );
    } else {
      this.setupAnimations();
    }
  }

  setupAnimations() {
    this.addAnimationClasses();

    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      this.observer.observe(element);
    });

    setTimeout(() => {
      this.checkInitiallyVisible();
    }, 100);
  }

  addAnimationClasses() {
    const textBoxNoBg = document.querySelector('.text-box.no-bg');
    if (textBoxNoBg) {
      textBoxNoBg.classList.add('animate-on-scroll');
    }

    const parallaxImg = document.querySelector('.parralax-img');
    if (parallaxImg) {
      parallaxImg.classList.add('animate-on-scroll');
    }

    const imgTextSections = document.querySelectorAll('.img-txt');
    imgTextSections.forEach((section) => {
      section.classList.add('animate-on-scroll');
    });

    const dualGallery = document.querySelector('.dual-gallery');
    if (dualGallery) {
      dualGallery.classList.add('animate-on-scroll');
    }

    const beachClubSection = document.querySelector('.beach-club-section');
    if (beachClubSection) {
      beachClubSection.classList.add('animate-on-scroll');
    }
  }

  handleIntersection(entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting && !this.animatedElements.has(entry.target)) {
        let delay = 50;

        if (entry.target.classList.contains('dual-gallery')) {
          delay = 100;
        } else if (entry.target.classList.contains('beach-club-section')) {
          delay = 150;
        }

        setTimeout(() => {
          entry.target.classList.add('animate-in');
          this.animatedElements.add(entry.target);

          if (entry.target.classList.contains('beach-club-section')) {
            this.animateBeachClubChildren(entry.target);
          }
        }, delay);

        this.observer.unobserve(entry.target);
      }
    });
  }

  animateBeachClubChildren(beachClubElement) {
    const contentText = beachClubElement.querySelector('.content-text');
    const mainImage = beachClubElement.querySelector('.main-image');

    if (contentText && mainImage) {
      setTimeout(() => {
        contentText.style.opacity = '1';
        contentText.style.transform = 'translateX(0)';
      }, 100);

      setTimeout(() => {
        mainImage.style.opacity = '1';
        mainImage.style.transform = 'translateX(0) scale(1)';
      }, 300);
    }

    const gridItems = beachClubElement.querySelectorAll('.grid-item');
    gridItems.forEach((item, index) => {
      setTimeout(() => {
        item.style.opacity = '1';
        item.style.transform = 'translateY(0) scale(1)';
      }, 500 + index * 100);
    });

    const bottomText = beachClubElement.querySelector('.bottom-text');
    if (bottomText) {
      setTimeout(() => {
        bottomText.style.opacity = '1';
        bottomText.style.transform = 'translateY(0)';
      }, 800);
    }
  }

  checkInitiallyVisible() {
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      const rect = element.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight * 0.9;

      if (isVisible && !this.animatedElements.has(element)) {
        element.classList.add('animate-in');
        this.animatedElements.add(element);

        if (element.classList.contains('beach-club-section')) {
          this.animateBeachClubChildren(element);
        }

        this.observer.unobserve(element);
      }
    });
  }

  animateElement(element) {
    if (element && !this.animatedElements.has(element)) {
      element.classList.add('animate-in');
      this.animatedElements.add(element);

      if (element.classList.contains('beach-club-section')) {
        this.animateBeachClubChildren(element);
      }

      if (this.observer) {
        this.observer.unobserve(element);
      }
    }
  }

  destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
    this.animatedElements.clear();
  }
}

let amenitiesScrollAnimations;

document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    amenitiesScrollAnimations = new AmenitiesScrollAnimations();
  }, 100);
});

window.amenitiesScrollAnimations = amenitiesScrollAnimations;

class ResidencesScrollAnimations {
  constructor() {
    this.animatedElements = new Set();
    this.observer = null;
    this.init();
  }

  init() {
    this.observer = new IntersectionObserver(
      (entries) => this.handleIntersection(entries),
      {
        threshold: 0.1,
        rootMargin: '0px 0px -60px 0px',
      }
    );

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () =>
        this.setupAnimations()
      );
    } else {
      this.setupAnimations();
    }
  }

  setupAnimations() {
    this.addAnimationClasses();

    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      this.observer.observe(element);
    });

    setTimeout(() => {
      this.checkInitiallyVisible();
    }, 100);
  }

  addAnimationClasses() {
    const textBoxNoBg = document.querySelector('.text-box.no-bg');
    if (textBoxNoBg) {
      textBoxNoBg.classList.add('animate-on-scroll');
    }

    const overlappedSection = document.querySelector(
      '.overlapped-image-section'
    );
    if (overlappedSection) {
      overlappedSection.classList.add('animate-on-scroll');
    }

    const imgTextSections = document.querySelectorAll('.img-txt');
    imgTextSections.forEach((section) => {
      section.classList.add('animate-on-scroll');
    });

    const fullViewSection = document.querySelector('.full-view-img-text');
    if (fullViewSection) {
      fullViewSection.classList.add('animate-on-scroll');
    }

    const zoomSection = document.querySelector('.zoom-scroll-section');
    if (zoomSection) {
      zoomSection.classList.add('animate-on-scroll');
    }

    const floorPlansContainer = document.querySelector(
      '.floor-plans-container'
    );
    if (floorPlansContainer) {
      floorPlansContainer.classList.add('animate-on-scroll');

      const floorButtons =
        floorPlansContainer.querySelectorAll('.floor-button');
      floorButtons.forEach((button) => {
        button.classList.add('animate-on-scroll');
      });
    }
  }

  handleIntersection(entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting && !this.animatedElements.has(entry.target)) {
        let delay = 50;

        if (entry.target.classList.contains('overlapped-image-section')) {
          delay = 100;
        } else if (entry.target.classList.contains('img-txt')) {
          delay = 80;
        } else if (entry.target.classList.contains('floor-plans-container')) {
          delay = 150;
        } else if (entry.target.classList.contains('floor-button')) {
          delay = 30;
        }

        setTimeout(() => {
          entry.target.classList.add('animate-in');
          this.animatedElements.add(entry.target);

          if (
            entry.target.classList.contains('img-txt') &&
            entry.target.classList.contains('has-gallery')
          ) {
            this.animateGalleryElements(entry.target);
          } else if (entry.target.classList.contains('floor-plans-container')) {
            this.animateFloorPlanElements(entry.target);
          }
        }, delay);

        this.observer.unobserve(entry.target);
      }
    });
  }

  animateFloorPlanElements(floorPlanElement) {
    const welcomeText = floorPlanElement.querySelector('.welcome-home');
    if (welcomeText) {
      setTimeout(() => {
        welcomeText.style.opacity = '1';
        welcomeText.style.transform = 'translateY(0) scale(1)';
      }, 200);
    }

    const leftColumn = floorPlanElement.querySelector('.left-column');
    if (leftColumn) {
      setTimeout(() => {
        leftColumn.style.opacity = '1';
        leftColumn.style.transform = 'translateX(0)';
      }, 400);
    }

    const floorPlanImage = floorPlanElement.querySelector('.floor-plan-image');
    if (floorPlanImage) {
      setTimeout(() => {
        floorPlanImage.style.opacity = '1';
        floorPlanImage.style.transform = 'translateX(0) scale(1)';
      }, 600);
    }

    const floorButtons = floorPlanElement.querySelectorAll('.floor-button');
    floorButtons.forEach((button, index) => {
      if (!this.animatedElements.has(button)) {
        setTimeout(() => {
          button.classList.add('animate-in');
          this.animatedElements.add(button);
        }, 800 + index * 100);
      }
    });
  }

  checkInitiallyVisible() {
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach((element) => {
      const rect = element.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight * 0.9;

      if (isVisible && !this.animatedElements.has(element)) {
        element.classList.add('animate-in');
        this.animatedElements.add(element);

        if (
          element.classList.contains('img-txt') &&
          element.classList.contains('has-gallery')
        ) {
          this.animateGalleryElements(element);
        } else if (element.classList.contains('floor-plans-container')) {
          this.animateFloorPlanElements(element);
        }

        this.observer.unobserve(element);
      }
    });
  }

  animateElement(element) {
    if (element && !this.animatedElements.has(element)) {
      element.classList.add('animate-in');
      this.animatedElements.add(element);

      if (
        element.classList.contains('img-txt') &&
        element.classList.contains('has-gallery')
      ) {
        this.animateGalleryElements(element);
      } else if (element.classList.contains('floor-plans-container')) {
        this.animateFloorPlanElements(element);
      }

      if (this.observer) {
        this.observer.unobserve(element);
      }
    }
  }

  destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
    this.animatedElements.clear();
  }
}

let residencesScrollAnimations;

document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    residencesScrollAnimations = new ResidencesScrollAnimations();
  }, 150);
});

window.residencesScrollAnimations = residencesScrollAnimations;

class NeighborhoodScrollAnimations {
  constructor() {
    this.animatedElements = new Set();
    this.observer = null;
    this.init();
  }

  init() {
    this.observer = new IntersectionObserver(
      (entries) => this.handleIntersection(entries),
      {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      }
    );

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setupAnimations());
    } else {
      this.setupAnimations();
    }
  }

  setupAnimations() {
    this.addAnimationClasses();
    
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach(element => {
      this.observer.observe(element);
    });

    setTimeout(() => {
      this.checkInitiallyVisible();
    }, 100);
  }

  addAnimationClasses() {
    const textBoxNoBg = document.querySelector('.text-box.no-bg');
    if (textBoxNoBg) {
      textBoxNoBg.classList.add('animate-on-scroll');
    }

    const fullWidthImg = document.querySelector('.full-width-img');
    if (fullWidthImg) {
      fullWidthImg.classList.add('animate-on-scroll');
    }

    const mapSection = document.querySelector('.map-section');
    if (mapSection) {
      mapSection.classList.add('animate-on-scroll');
    }

    const mobileOrderItems = document.querySelectorAll('.mobile-order .gallery-item');
    mobileOrderItems.forEach(item => {
      item.classList.add('animate-on-scroll');
    });

    const mapPopups = document.querySelectorAll('.map-popup');
    mapPopups.forEach(popup => {
      popup.classList.add('animate-on-scroll');
    });
  }

  handleIntersection(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting && !this.animatedElements.has(entry.target)) {
        let delay = 50;
        
        if (entry.target.classList.contains('text-box')) {
          delay = 80;
        } else if (entry.target.classList.contains('full-width-img')) {
          delay = 100;
        } else if (entry.target.classList.contains('map-section')) {
          delay = 150;
        } else if (entry.target.classList.contains('gallery-item')) {
          delay = 30;
        }
        
        setTimeout(() => {
          entry.target.classList.add('animate-in');
          this.animatedElements.add(entry.target);
          
          if (entry.target.classList.contains('map-section')) {
            this.animateMapElements(entry.target);
          } else if (entry.target.classList.contains('text-box')) {
            this.animateTextElements(entry.target);
          }
        }, delay);
        
        this.observer.unobserve(entry.target);
      }
    });
  }

  animateTextElements(textElement) {
    const paragraphs = textElement.querySelectorAll('p');
    paragraphs.forEach((paragraph, index) => {
      setTimeout(() => {
        paragraph.style.opacity = '1';
        paragraph.style.transform = 'translateY(0)';
      }, index * 200);
    });
  }

  animateMapElements(mapElement) {
    const mapContainer = mapElement.querySelector('.map-container');
    if (mapContainer) {
      setTimeout(() => {
        mapContainer.style.opacity = '1';
        mapContainer.style.transform = 'translateX(0) scale(1)';
      }, 100);
    }

    const mapContent = mapElement.querySelector('.map-content');
    if (mapContent) {
      setTimeout(() => {
        mapContent.style.opacity = '1';
        mapContent.style.transform = 'translateX(0)';
      }, 300);

      const mapTitle = mapContent.querySelector('.map-text h2');
      const mapSubtitle = mapContent.querySelector('.map-text .subtitle');
      const categoryItems = mapContent.querySelectorAll('.category-item');

      if (mapTitle) {
        setTimeout(() => {
          mapTitle.style.opacity = '1';
          mapTitle.style.transform = 'translateY(0)';
        }, 500);
      }

      if (mapSubtitle) {
        setTimeout(() => {
          mapSubtitle.style.opacity = '1';
          mapSubtitle.style.transform = 'translateY(0)';
        }, 600);
      }

      categoryItems.forEach((item, index) => {
        setTimeout(() => {
          item.style.opacity = '1';
          item.style.transform = 'translateX(0)';
        }, 700 + (index * 100));
      });
    }
  }

  animateGalleryElements(galleryElement) {
    const galleryTitle = galleryElement.querySelector('.gallery-title');
    const gallerySubtitle = galleryElement.querySelector('.gallery-subtitle');

    if (galleryTitle) {
      setTimeout(() => {
        galleryTitle.style.opacity = '1';
        galleryTitle.style.transform = 'translateY(0) scale(1)';
      }, 200);
    }

    if (gallerySubtitle) {
      setTimeout(() => {
        gallerySubtitle.style.opacity = '1';
        gallerySubtitle.style.transform = 'translateY(0)';
      }, 400);
    }

    const galleryItems = galleryElement.querySelectorAll('.gallery-item');
    galleryItems.forEach((item, index) => {
      const baseDelay = 600;
      const staggerDelay = Math.min(index * 80, 1000);
      
      setTimeout(() => {
        item.style.opacity = '1';
        item.style.transform = 'translateY(0) scale(1)';
      }, baseDelay + staggerDelay);
    });
  }

  checkInitiallyVisible() {
    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach(element => {
      const rect = element.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight * 0.9;
      
      if (isVisible && !this.animatedElements.has(element)) {
        element.classList.add('animate-in');
        this.animatedElements.add(element);
        
        if (element.classList.contains('map-section')) {
          this.animateMapElements(element);
        } else if (element.classList.contains('text-box')) {
          this.animateTextElements(element);
        }
        
        this.observer.unobserve(element);
      }
    });
  }

  animateElement(element) {
    if (element && !this.animatedElements.has(element)) {
      element.classList.add('animate-in');
      this.animatedElements.add(element);
      
      if (element.classList.contains('map-section')) {
        this.animateMapElements(element);
      } else if (element.classList.contains('text-box')) {
        this.animateTextElements(element);
      }
      
      if (this.observer) {
        this.observer.unobserve(element);
      }
    }
  }

  animateMapPopup(popup) {
    if (popup && !popup.classList.contains('animate-in')) {
      popup.classList.add('animate-in');
      
      popup.style.animation = 'popupSlideIn 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
      
      setTimeout(() => {
        popup.style.animation = '';
      }, 300);
    }
  }

  destroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
    this.animatedElements.clear();
  }
}

let neighborhoodScrollAnimations;

document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    neighborhoodScrollAnimations = new NeighborhoodScrollAnimations();
  }, 200);
});

window.neighborhoodScrollAnimations = neighborhoodScrollAnimations;

function scrollToElementAndRemoveHash(targetId, offset = 0) {
  const targetElement = document.getElementById(targetId);
  
  if (targetElement) {
    const elementPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = elementPosition - offset;
    
    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    });
    
    setTimeout(() => {
      history.replaceState(null, null, window.location.pathname + window.location.search);
    }, 100);
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const inquireLink = document.querySelector('.inquire');
  if (inquireLink) {
    inquireLink.addEventListener('click', function(e) {
      e.preventDefault();
      scrollToElementAndRemoveHash('contact');
    });
  }
  
  const downArrowLink = document.querySelector('.down-arrow');
  if (downArrowLink) {
    downArrowLink.addEventListener('click', function(e) {
      e.preventDefault();
      scrollToElementAndRemoveHash('palma', 100);
    });
  }
});