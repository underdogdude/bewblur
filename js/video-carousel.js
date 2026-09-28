(function () {
  "use strict";

  var desktopMq = window.matchMedia("(min-width: 1025px)");
  var PREVIEW_DELAY = 350;

  function embedYoutubeUrl(videoId) {
    return (
      "https://www.youtube.com/embed/" +
      encodeURIComponent(videoId) +
      "?autoplay=1&rel=0&modestbranding=1"
    );
  }

  function startPreview(item) {
    if (!desktopMq.matches) {
      return;
    }

    var video = item.querySelector(".video-carousel__preview");
    if (item.previewTimer || item.classList.contains("is-previewing")) {
      return;
    }

    item.previewTimer = window.setTimeout(function () {
      item.previewTimer = null;
      item.classList.add("is-previewing");

      var row = item.closest(".video-carousel__row");
      if (row) {
        row.classList.add("has-previewing-card");
      }

      if (!video) {
        return;
      }

      video.currentTime = 0;
      var playPromise = video.play();
      if (playPromise && typeof playPromise.catch === "function") {
        playPromise.catch(function () {});
      }
    }, PREVIEW_DELAY);
  }

  function stopPreview(item) {
    if (item.previewTimer) {
      window.clearTimeout(item.previewTimer);
      item.previewTimer = null;
    }

    var video = item.querySelector(".video-carousel__preview");
    item.classList.remove("is-previewing");

    var row = item.closest(".video-carousel__row");
    if (row) {
      row.classList.remove("has-previewing-card");
    }

    if (!video) {
      return;
    }

    video.pause();
    video.currentTime = 0;
  }

  function loadPreviewVideo(video) {
    if (video.getAttribute("src")) {
      return;
    }

    var source = video.getAttribute("data-video-src");
    if (!source) {
      return;
    }

    video.preload = "metadata";
    video.setAttribute("src", source);
    video.load();
  }

  function openPopover(popover, iframe, videoId, title, permalink) {
    if (!videoId || !iframe || !popover || typeof popover.showPopover !== "function") {
      return;
    }

    var projectTitle = popover.querySelector(".video-carousel-popover__title");
    var projectLink = popover.querySelector(".video-carousel-popover__link");

    if (projectTitle) {
      projectTitle.textContent = title || "";
    }
    if (projectLink) {
      projectLink.setAttribute("href", permalink || "#");
    }

    iframe.setAttribute("src", embedYoutubeUrl(videoId));

    if (popover.matches(":popover-open")) {
      popover.hidePopover();
    }

    popover.showPopover();
  }

  function bindPopoverToggle(popover, iframe) {
    if (!popover || !iframe) {
      return;
    }

    popover.addEventListener("toggle", function (event) {
      if (event.newState === "closed") {
        iframe.removeAttribute("src");
      }
    });
  }

  var CONTENT_MAX = 1520;

  function getSiteSpace() {
    var siteSpace = 25;
    var rootStyles = getComputedStyle(document.documentElement);
    var parsed = parseFloat(rootStyles.getPropertyValue("--s-site-space"));
    if (!Number.isNaN(parsed)) {
      siteSpace = parsed;
    }

    return siteSpace;
  }

  function getCarouselEdgeOffset() {
    var siteSpace = getSiteSpace();

    var containerWidth = Math.min(window.innerWidth, CONTENT_MAX);
    var containerLeft = (window.innerWidth - containerWidth) / 2;
    return containerLeft + siteSpace;
  }

  function applyCarouselGap(root) {
    var gap = 20;

    if (window.innerWidth < 768) {
      gap = 10;
    } else if (window.innerWidth < 1025) {
      gap = 12;
    }

    root.style.setProperty("--video-carousel-gap", gap + "px");
    return gap;
  }

  function syncSwiperMetrics(swiper, root) {
    var gap = applyCarouselGap(root);
    var edgeOffset = getCarouselEdgeOffset();
    var endOffset = getSiteSpace();
    swiper.params.slidesPerView = "auto";
    swiper.params.spaceBetween = gap;
    swiper.params.slidesOffsetBefore = edgeOffset;
    swiper.params.slidesOffsetAfter = endOffset;
    swiper.update();
    updateScrollFade(swiper, root);
  }

  function updateScrollFade(swiper, root) {
    var row = root.closest(".video-carousel__row");
    if (!row) {
      return;
    }

    row.classList.toggle("is-scrolled-from-start", !swiper.isBeginning);
    row.classList.toggle("is-scrolled-from-end", !swiper.isEnd);
  }

  function initCarousel(root) {
    if (typeof Swiper === "undefined" || root.swiperInstance) {
      return;
    }

    var gap = applyCarouselGap(root);
    var edgeOffset = getCarouselEdgeOffset();
    var endOffset = getSiteSpace();

    var popoverId = root.getAttribute("data-popover-id");
    var popover = popoverId ? document.getElementById(popoverId) : null;
    var iframe = popover ? popover.querySelector(".video-carousel-popover__iframe") : null;
    bindPopoverToggle(popover, iframe);

    var swiper = new Swiper(root, {
      slidesPerView: "auto",
      spaceBetween: gap,
      speed: 500,
      centeredSlides: false,
      grabCursor: true,
      watchOverflow: true,
      touchEventsTarget: "wrapper",
      touchStartPreventDefault: false,
      slidesOffsetBefore: edgeOffset,
      slidesOffsetAfter: endOffset,
      simulateTouch: true,
      threshold: 8,
      preventClicks: true,
      preventClicksPropagation: true,
      on: {
        init: function (instance) {
          updateScrollFade(instance, root);
        },
        slideChange: function (instance) {
          updateScrollFade(instance, root);
        },
        progress: function (instance) {
          updateScrollFade(instance, root);
        },
        resize: function (instance) {
          syncSwiperMetrics(instance, root);
        },
        click: function (instance, event) {
          if (!instance.allowClick) {
            return;
          }

          var item = event.target.closest(".video-carousel__item[data-youtube-id]");
          if (!item || !root.contains(item)) {
            return;
          }

          openPopover(
            popover,
            iframe,
            item.getAttribute("data-youtube-id"),
            item.getAttribute("data-portfolio-title"),
            item.getAttribute("data-portfolio-url")
          );
        },
      },
    });

    root.swiperInstance = swiper;

    root.querySelectorAll(".video-carousel__thumb").forEach(function (img) {
      img.setAttribute("draggable", "false");

      function refreshSwiper() {
        swiper.update();
        updateScrollFade(swiper, root);
      }

      if (img.complete) {
        refreshSwiper();
      } else {
        img.addEventListener("load", refreshSwiper);
      }
    });

    root.querySelectorAll(".video-carousel__preview").forEach(function (video) {
      function applyVideoRatio() {
        if (!video.videoWidth || !video.videoHeight) {
          return;
        }

        var media = video.closest(".video-carousel__media");
        if (!media) {
          return;
        }

        media.style.aspectRatio = video.videoWidth + " / " + video.videoHeight;
        media.classList.add("has-video-ratio");
        swiper.update();
        updateScrollFade(swiper, root);
      }

      if (video.readyState >= 1) {
        applyVideoRatio();
      } else {
        video.addEventListener("loadedmetadata", applyVideoRatio, { once: true });
      }

      if (desktopMq.matches) {
        loadPreviewVideo(video);
      }
    });

    root.querySelectorAll(".video-carousel__item[data-youtube-id]").forEach(function (item) {
      item.addEventListener("keydown", function (event) {
        if (event.key !== "Enter" && event.key !== " ") {
          return;
        }
        event.preventDefault();
        openPopover(
          popover,
          iframe,
          item.getAttribute("data-youtube-id"),
          item.getAttribute("data-portfolio-title"),
          item.getAttribute("data-portfolio-url")
        );
      });
    });
  }

  document.addEventListener("mouseover", function (event) {
    if (!desktopMq.matches) {
      return;
    }

    var item = event.target.closest(".video-carousel__item");
    if (!item || !item.closest(".video-carousel")) {
      return;
    }

    startPreview(item);
  });

  document.addEventListener("mouseout", function (event) {
    if (!desktopMq.matches) {
      return;
    }

    var item = event.target.closest(".video-carousel__item");
    if (!item || !item.closest(".video-carousel")) {
      return;
    }

    var related = event.relatedTarget;
    if (related && item.contains(related)) {
      return;
    }

    stopPreview(item);
  });

  function bootCarousels() {
    if (typeof Swiper === "undefined") {
      return;
    }

    document.querySelectorAll(".video-carousel.swiper").forEach(initCarousel);
  }

  function loadDesktopPreviewVideos(event) {
    if (!event.matches) {
      return;
    }

    document.querySelectorAll(".video-carousel__preview").forEach(loadPreviewVideo);
  }

  if (typeof desktopMq.addEventListener === "function") {
    desktopMq.addEventListener("change", loadDesktopPreviewVideos);
  } else if (typeof desktopMq.addListener === "function") {
    desktopMq.addListener(loadDesktopPreviewVideos);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootCarousels);
  } else {
    bootCarousels();
  }
})();
