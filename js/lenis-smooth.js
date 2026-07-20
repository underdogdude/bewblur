/**
 * Lenis smooth scroll – site-wide initialization.
 * Scroll-linked left column (CodePen-style: translateY driven by scroll).
 */
import Lenis from "https://cdn.jsdelivr.net/npm/lenis@1.1.17/dist/lenis.mjs";

const lenis = new Lenis({
  duration: 1.2,
  easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
  orientation: "vertical",
  gestureOrientation: "vertical",
  smoothWheel: true,
  wheelMultiplier: 1,
  touchMultiplier: 2,
  autoRaf: true,
  anchors: true,
});

window.lenis = lenis;

const header = document.getElementById("masthead");
const scrollThreshold = 0;

const DESKTOP_MQ = window.matchMedia("(min-width: 1025px)");
const STICKY_ROW_SELECTOR = ".kb-row-layout-id751_a0ccef-8c";
const STICKY_COL_SELECTOR = ".kt-row-column-wrap > .wp-block-kadence-column:first-child";
const RIGHT_COL_SELECTOR = ".kt-row-column-wrap > .wp-block-kadence-column:last-child";
/** Smoothed so column doesn't jump when navbar toggles */
let smoothedStickyTopOffset = null;
const STICKY_OFFSET_SMOOTH = 0.18;

function getStickySection() {
  const row = document.querySelector(STICKY_ROW_SELECTOR);
  if (!row) return null;
  const col = row.querySelector(STICKY_COL_SELECTOR);
  return row && col ? { section: row, col } : null;
}

function updateStickyCol() {
  if (!DESKTOP_MQ.matches) {
    const data = getStickySection();
    if (data) data.col.style.transform = "";
    return;
  }

  const data = getStickySection();
  if (!data) return;

  const { section, col } = data;
  const scroll = lenis.scroll;
  const vh = window.innerHeight;
  const headerHeight = parseInt(
    getComputedStyle(document.documentElement).getPropertyValue("--s-header-height"),
    10
  ) || 60;
  // When navbar is hidden (scrolled down, nav-up), stick column at viewport top (0)
  const isNavHidden = header && header.classList.contains("nav-up");
  const targetTopOffset = isNavHidden ? 0 : headerHeight;
  if (smoothedStickyTopOffset == null) smoothedStickyTopOffset = targetTopOffset;
  smoothedStickyTopOffset += (targetTopOffset - smoothedStickyTopOffset) * STICKY_OFFSET_SMOOTH;
  const topOffset = smoothedStickyTopOffset;

  const rect = section.getBoundingClientRect();
  const sectionTopInDoc = rect.top + scroll;
  const sectionHeight = section.offsetHeight;

  // Use right column bottom so left column disappears in sync with right content
  const rightCol = section.querySelector(RIGHT_COL_SELECTOR);
  const rightRect = rightCol ? rightCol.getBoundingClientRect() : null;
  const rightBottomInDoc = rightRect ? rightRect.bottom + scroll : sectionTopInDoc + sectionHeight;

  // When section top hits header bottom: start sticking
  const startScroll = sectionTopInDoc - topOffset;
  // Stick until right column bottom hits viewport bottom, then animate column down
  const stickEndScroll = rightBottomInDoc - vh;
  // Column finishes moving down when right column bottom is past
  const endScroll = rightBottomInDoc;

  // Calculate max travel distance dynamically: from stick position to viewport bottom
  // This ensures column doesn't exceed container bounds and fits on every screen
  const maxTravel = vh - topOffset;

  let y = 0;

  if (scroll < startScroll) {
    y = 0;
  } else if (scroll <= stickEndScroll) {
    // Stick at top: translateY cancels scroll so column stays at topOffset
    y = scroll - startScroll;
  } else {
    // Leave phase: move from current stick position to maxTravel (viewport bottom)
    const leaveStart = stickEndScroll - startScroll;
    const leaveRange = Math.max(1, endScroll - stickEndScroll);
    const progress = Math.min(1, (scroll - stickEndScroll) / leaveRange);
    y = leaveStart + progress * (maxTravel - leaveStart);
  }

  col.style.transform = `translateY(${y}px)`;
}

lenis.on("scroll", (e) => {
  if (header) {
    const scroll = e.scroll;
    const direction = e.direction;
    if (scroll < scrollThreshold) {
      header.classList.remove("nav-up", "nav-down");
    } else if (direction === 1) {
      header.classList.add("nav-up");
      header.classList.remove("nav-down");
    } else if (direction === -1) {
      header.classList.add("nav-down");
      header.classList.remove("nav-up");
    }
  }
  updateStickyCol();
});

window.addEventListener("resize", () => {
  updateStickyCol();
});
DESKTOP_MQ.addEventListener("change", () => {
  updateStickyCol();
});

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => requestAnimationFrame(updateStickyCol));
} else {
  requestAnimationFrame(updateStickyCol);
}
