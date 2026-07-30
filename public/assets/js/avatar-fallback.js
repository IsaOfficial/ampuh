(function(window, document) {
  'use strict';

  var fallbackSrc = '/public/assets/img/avatars/default_profile.svg';
  var selector = 'img.profile-img, img.profile-img-mini, img.preview-foto';

  function useFallback(img) {
    if (!img || img.dataset.avatarFallbackApplied === '1') {
      return;
    }

    img.dataset.avatarFallbackApplied = '1';
    img.src = fallbackSrc;
  }

  document.addEventListener('error', function(event) {
    var target = event.target;

    if (target && target.matches && target.matches(selector)) {
      useFallback(target);
    }
  }, true);

  function repairBrokenAvatars() {
    document.querySelectorAll(selector).forEach(function(img) {
      if (img.complete && img.naturalWidth === 0) {
        useFallback(img);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', repairBrokenAvatars);
  } else {
    repairBrokenAvatars();
  }
})(window, document);
