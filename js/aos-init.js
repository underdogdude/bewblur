/**
 * AOS (Animate On Scroll) Global Initialization
 * Adds fade-up animation to divs with .s-container or .kb-row-layout-wrap
 */

(function() {
    'use strict';

    /**
     * Check if element should be excluded (header/footer or .no-transition)
     */
    function shouldExclude(element) {
        if (!element || !element.matches) return true;
        
        // Exclude header and footer
        if (element.matches('#masthead, #colophon, #footbar')) return true;
        
        // Exclude if element has .no-transition class
        if (element.classList && element.classList.contains('no-transition')) return true;
        
        // Exclude if inside header, footer, or .no-transition parent
        let parent = element.parentElement;
        while (parent && parent !== document.body) {
            if (parent.matches('#masthead, #colophon, #footbar')) return true;
            if (parent.classList && parent.classList.contains('no-transition')) return true;
            parent = parent.parentElement;
        }
        
        return false;
    }

    /**
     * Check if element is in viewport
     */
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Add data-aos="fade-up" to target divs
     */
    function addAosAttributes() {
        // Only target divs with .s-container or .kb-row-layout-wrap, excluding .no-transition
        const targetElements = document.querySelectorAll('div.s-container:not(.no-transition), div.kb-row-layout-wrap:not(.no-transition)');
        
        targetElements.forEach(function(element) {
            // Skip if excluded or already has data-aos
            if (shouldExclude(element) || element.hasAttribute('data-aos')) {
                return;
            }
            
            // Add fade-up animation
            element.setAttribute('data-aos', 'fade-up');
            
            // If element is already in viewport, ensure it's visible
            // AOS will handle the animation, but we ensure visibility
            if (isInViewport(element)) {
                element.style.opacity = '1';
                element.style.visibility = 'visible';
            }
        });
    }

    /**
     * Initialize AOS
     */
    function initAOS() {
        if (typeof AOS === 'undefined') {
            console.warn('AOS library not loaded');
            return;
        }

        // Add attributes first
        addAosAttributes();

        // Initialize AOS
        AOS.init({
            duration: 400,
            easing: 'ease-out-cubic',
            once: false,
            offset: 50,
            startEvent: 'DOMContentLoaded',
            anchorPlacement: 'top-bottom'
        });

        // Refresh to ensure all elements are detected
        AOS.refresh();
        
        // Ensure elements in viewport are visible
        const targetElements = document.querySelectorAll('div.s-container[data-aos], div.kb-row-layout-wrap[data-aos]');
        targetElements.forEach(function(element) {
            if (!shouldExclude(element) && isInViewport(element)) {
                // Force show elements already in viewport
                element.classList.add('aos-animate');
            }
        });
        
        // Force refresh again after a short delay to catch any late-loading elements
        setTimeout(function() {
            addAosAttributes();
            AOS.refresh();
            
            // Ensure viewport elements are visible
            const viewportElements = document.querySelectorAll('div.s-container[data-aos], div.kb-row-layout-wrap[data-aos]');
            viewportElements.forEach(function(element) {
                if (!shouldExclude(element) && isInViewport(element)) {
                    element.classList.add('aos-animate');
                }
            });
        }, 500);
    }

    /**
     * Handle dynamically added content
     */
    function handleDynamicContent() {
        const observer = new MutationObserver(function(mutations) {
            let hasNewElements = false;
            
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.tagName.toLowerCase() === 'div') {
                        // Check if it's a target div and doesn't have .no-transition
                        if ((node.classList.contains('s-container') || node.classList.contains('kb-row-layout-wrap')) &&
                            !shouldExclude(node) && 
                            !node.hasAttribute('data-aos') &&
                            !node.classList.contains('no-transition')) {
                            node.setAttribute('data-aos', 'fade-up');
                            hasNewElements = true;
                            
                            // If in viewport, make visible immediately
                            if (isInViewport(node)) {
                                node.classList.add('aos-animate');
                            }
                        }
                        
                        // Check children for target divs (excluding .no-transition)
                        const children = node.querySelectorAll('div.s-container:not(.no-transition), div.kb-row-layout-wrap:not(.no-transition)');
                        children.forEach(function(child) {
                            if (!shouldExclude(child) && !child.hasAttribute('data-aos')) {
                                child.setAttribute('data-aos', 'fade-up');
                                hasNewElements = true;
                                
                                // If in viewport, make visible immediately
                                if (isInViewport(child)) {
                                    child.classList.add('aos-animate');
                                }
                            }
                        });
                    }
                });
            });
            
            if (hasNewElements && typeof AOS !== 'undefined') {
                setTimeout(function() {
                    AOS.refresh();
                }, 100);
            }
        });

        // Observe body for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Wait for AOS library to load
    function waitForAOS(callback) {
        if (typeof AOS !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                waitForAOS(callback);
            }, 50);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            waitForAOS(function() {
                setTimeout(initAOS, 100);
                handleDynamicContent();
            });
        });
    } else {
        waitForAOS(function() {
            setTimeout(initAOS, 100);
            handleDynamicContent();
        });
    }

    // Refresh on window load for late-loading content
    window.addEventListener('load', function() {
        waitForAOS(function() {
            addAosAttributes();
            setTimeout(function() {
                AOS.refresh();
                
                // Ensure all viewport elements are visible
                const viewportElements = document.querySelectorAll('div.s-container[data-aos], div.kb-row-layout-wrap[data-aos]');
                viewportElements.forEach(function(element) {
                    if (!shouldExclude(element) && isInViewport(element)) {
                        element.classList.add('aos-animate');
                    }
                });
            }, 300);
        });
    });

})();
