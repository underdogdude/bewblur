/* skip-link-focus-fix.js - https://git.io/vWdr2 */
!function(){var e=navigator.userAgent.toLowerCase().indexOf("webkit")>-1,t=navigator.userAgent.toLowerCase().indexOf("opera")>-1,n=navigator.userAgent.toLowerCase().indexOf("msie")>-1;(e||t||n)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var e,t=location.hash.substring(1);/^[A-z0-9_-]+$/.test(t)&&(e=document.getElementById(t))&&(/^(?:a|select|input|button|textarea)$/i.test(e.tagName)||(e.tabIndex=-1),e.focus())},!1)}();

/* Fluid width video - https://css-tricks.com/fluid-width-video/ */
!function(e,t,r){"use strict";var i=t.querySelectorAll(['iframe[src*="youtube.com"]','iframe[src*="vimeo.com"]'].join(","));if(i.length)for(var o=0;o<i.length;o++){var a=i[o],m=a.getAttribute("width"),n=a.getAttribute("height")/m,d=a.parentNode,c=t.createElement("div");c.className="fitVids-wrapper",c.style.paddingBottom=100*n+"%",d.insertBefore(c,a),a.remove(),c.appendChild(a),a.removeAttribute("height"),a.removeAttribute("width")}}(window,document);

/* Manage Class Funtions https://www.sitepoint.com/add-remove-css-class-vanilla-js/ */
function addClass(e,l){var elements=document.querySelectorAll(e);for(var s=0;s<elements.length;s++)elements[s].classList.add(l)}function removeClass(e,l){var elements=document.querySelectorAll(e);for(var s=0;s<elements.length;s++)elements[s].classList.remove(l)}

/* GetClostest - https://gomakethings.com/how-to-get-the-closest-parent-element-with-a-matching-selector-using-vanilla-javascript/ */
var getClosest=function(e,t){for(Element.prototype.matches||(Element.prototype.matches=Element.prototype.matchesSelector||Element.prototype.mozMatchesSelector||Element.prototype.msMatchesSelector||Element.prototype.oMatchesSelector||Element.prototype.webkitMatchesSelector||function(e){for(var t=(this.document||this.ownerDocument).querySelectorAll(e),o=t.length;--o>=0&&t.item(o)!==this;);return o>-1});e&&e!==document;e=e.parentNode)if(e.matches(t))return e;return null};

/*  Seed Modal from https://codepen.io/kimpetersend1/pen/LajgaW */
const modalTriggers=document.querySelectorAll(".s-modal-trigger"),bodyBlackout=document.querySelector(".s-modal-bg"),allModals=document.querySelectorAll(".s-modal");modalTriggers.forEach(e=>{e.addEventListener("click",()=>{const{popupTrigger:l}=e.dataset,o=document.querySelector(`[data-s-modal="${l}"]`);o.classList.add("-visible"),bodyBlackout.classList.add("-blacked-out"),o.querySelector(".s-modal-close").addEventListener("click",()=>{o.classList.remove("-visible"),bodyBlackout.classList.remove("-blacked-out")})})}),bodyBlackout.addEventListener("click",()=>{bodyBlackout.classList.remove("-blacked-out"),allModals.forEach(function(e,l){e.classList.remove("-visible")})});



/* Custom Header Scroll Show and Hide */
(function(){
    var doc = document.documentElement;
    var w = window;
    var prevScroll = w.scrollY || doc.scrollTop;
    var curScroll;
    var direction = 0;
    var prevDirection = 0;
    var header = document.getElementById('masthead');
    var checkScroll = function() {
      curScroll = w.scrollY || doc.scrollTop;
      if (curScroll > prevScroll) { 
        //scrolled up
        direction = 2;
      }
      else if (curScroll < prevScroll) { 
        //scrolled down
        direction = 1;
      }
  
        // When near top, reset nav state
      if (curScroll < 50) {
        header.classList.remove('nav-down', 'nav-up');
        prevDirection = 0;
        return;
      }
  
      if (direction !== prevDirection) {
        toggleHeader(direction, curScroll);
      }
      prevScroll = curScroll;
    };
  
    var toggleHeader = function(direction, curScroll) {
      if (direction === 2 ) { 
        header.classList.add('nav-up');
        header.classList.remove('nav-down');
        prevDirection = direction;
      }
      else if (direction === 1) {
        header.classList.remove('nav-up');
        header.classList.add('nav-down');
        prevDirection = direction;
      }
    };
    
    window.addEventListener('scroll', checkScroll);
  })();

/* Block marquee — duration from content width so speed stays consistent on all viewports */
(function () {
  var MARQUEE_SPEED_PX = 32;

  function setMarqueeDuration(root) {
    var track = root.querySelector(".block-marquee__track");
    if (!track) return;

    var halfWidth = track.scrollWidth / 2;
    if (halfWidth <= 0) return;

    var duration = halfWidth / MARQUEE_SPEED_PX;
    track.style.setProperty("--block-marquee-duration", duration + "s");
  }

  function initMarquee(root) {
    setMarqueeDuration(root);

    if (typeof ResizeObserver !== "undefined") {
      var ro = new ResizeObserver(function () {
        setMarqueeDuration(root);
      });
      ro.observe(root.querySelector(".block-marquee__track"));
    } else {
      window.addEventListener("resize", function () {
        setMarqueeDuration(root);
      });
    }

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () {
        setMarqueeDuration(root);
      });
    }
  }

  function initAllMarquees() {
    document.querySelectorAll(".block-marquee").forEach(initMarquee);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAllMarquees);
  } else {
    initAllMarquees();
  }
})();
