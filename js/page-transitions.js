/**
 * Page Transitions - Simple Fade Up
 */

(function() {
    'use strict';

    if (document.body && document.body.hasAttribute('data-no-transition')) {
        return;
    }

    const overlay = document.getElementById('page-transition-overlay');
    if (!overlay) return;

    const transitionDuration = 900;
    let isNavigating = false;

    function shouldTransition(link) {
        if (!link || !link.href || isNavigating) return false;
        if (link.hasAttribute('data-no-transition')) return false;
        
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#')) return false;
        
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return false;
        } catch (e) {
            return false;
        }
        
        const hrefLower = href.toLowerCase();
        if (hrefLower.match(/\.(pdf|zip|doc|docx|xls|xlsx)$/)) return false;
        if (hrefLower.startsWith('mailto:') || hrefLower.startsWith('tel:')) return false;
        if (link.target === '_blank') return false;
        
        return true;
    }

    // Cover page before navigation (bottom row first, then top row).
    function fadeOut(callback) {
        isNavigating = true;
        overlay.classList.add('is-covering');
        
        setTimeout(function() {
            if (callback) callback();
        }, transitionDuration);
    }

    // Reveal page on load (top row first, then bottom row).
    function fadeIn() {
        overlay.offsetHeight; // Force reflow
        
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                overlay.classList.remove('is-covering');
            });
        });
    }

    // EVERY PAGE LOAD = fade in (slide up) – no delay
    window.addEventListener('load', function() {
        fadeIn();
    }, { once: true });

    // Back/forward: page restored from bfcache doesn't fire 'load', so overlay stays covering → white screen. Reveal on pageshow when persisted.
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            isNavigating = false;
            fadeIn();
        }
    });

    // Link click = fade out (slide down)
    document.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (!link || !shouldTransition(link)) return;
        
        event.preventDefault();
        event.stopPropagation();
        
        fadeOut(function() {
            window.location.href = link.href;
        });
    }, true);

})();
