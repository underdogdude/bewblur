/**
 * Page Transitions - 2-row curtain (cover on navigate, reveal on load).
 */

(function () {
  "use strict";

  if (document.body && document.body.hasAttribute("data-no-transition")) {
    return;
  }

  var overlay = document.getElementById("page-transition-overlay");
  if (!overlay) {
    return;
  }

  var transitionDuration = 900;
  var isNavigating = false;

  function moveOverlayToTopLayer() {
    if (typeof overlay.showPopover !== "function") {
      return;
    }

    overlay.setAttribute("popover", "manual");

    try {
      if (!overlay.matches(":popover-open")) {
        overlay.showPopover();
      }
    } catch (error) {
      // The fixed-position overlay remains as the fallback.
    }
  }

  function shouldTransition(link) {
    if (!link || !link.href || isNavigating) {
      return false;
    }
    if (link.hasAttribute("data-no-transition")) {
      return false;
    }

    var href = link.getAttribute("href");
    if (!href || href === "#" || href.indexOf("#") === 0) {
      return false;
    }

    try {
      var url = new URL(href, window.location.origin);
      if (url.origin !== window.location.origin) {
        return false;
      }
    } catch (e) {
      return false;
    }

    var hrefLower = href.toLowerCase();
    if (hrefLower.match(/\.(pdf|zip|doc|docx|xls|xlsx)$/)) {
      return false;
    }
    if (hrefLower.indexOf("mailto:") === 0 || hrefLower.indexOf("tel:") === 0) {
      return false;
    }
    if (link.target === "_blank") {
      return false;
    }

    return true;
  }

  function fadeOut(callback) {
    isNavigating = true;
    moveOverlayToTopLayer();
    overlay.classList.add("is-covering");

    setTimeout(function () {
      if (callback) {
        callback();
      }
    }, transitionDuration);
  }

  function fadeIn() {
    overlay.offsetHeight;

    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        overlay.classList.remove("is-covering");
      });
    });
  }

  function scheduleReveal() {
    fadeIn();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", scheduleReveal, { once: true });
  } else {
    scheduleReveal();
  }

  window.addEventListener(
    "load",
    function () {
      if (overlay.classList.contains("is-covering")) {
        fadeIn();
      }
    },
    { once: true }
  );

  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      isNavigating = false;
      fadeIn();
    }
  });

  document.addEventListener(
    "click",
    function (event) {
      var link = event.target.closest("a");
      if (!link || !shouldTransition(link)) {
        return;
      }

      // Freeze the destination before the transition starts. Elements inside
      // popovers can change state while the curtain animation is running.
      var destination = link.href;

      event.preventDefault();
      event.stopPropagation();

      fadeOut(function () {
        window.location.href = destination;
      });
    },
    true
  );
})();
