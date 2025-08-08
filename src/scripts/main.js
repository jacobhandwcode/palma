// Phone popup functionality
document.addEventListener('DOMContentLoaded', function() {
  const phoneButton = document.querySelector('.phone-button');
  const popup = document.getElementById('phonePopup');
  const overlay = document.getElementById('popupOverlay');
  const closeBtn = document.getElementById('closePopup');
  
  function showPopup() {
    popup.classList.add('active');
    overlay.classList.add('active');
  }
  
  function hidePopup() {
    popup.classList.remove('active');
    overlay.classList.remove('active');
  }
  
  phoneButton.addEventListener('click', function(e) {
    e.preventDefault();
    showPopup();
  });
  
  closeBtn.addEventListener('click', hidePopup);
  overlay.addEventListener('click', hidePopup);
  
  // Auto-hide after 10 seconds
  setTimeout(hidePopup, 10000);
});