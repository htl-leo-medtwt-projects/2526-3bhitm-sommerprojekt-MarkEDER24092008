// Audio controller for IndiGO
// - pop.mp3 on ANY click
// - bkgMusic.mp3 continuously when reaching home.html
// - click.mp3 when clicking ANY text field (login / sign up)

(function () {
  const POP_URL = './media/audio/pop.mp3';
  const BKG_URL = './media/audio/bkgMusic.mp3';
  const CLICK_FIELD_URL = './media/audio/click.mp3';

  let popAudio = null;
  let clickFieldAudio = null;
  let bkgAudio = null;

  let bkgAttempted = false;
  let bkgPlaying = false;

  function getNumber(key, fallback) {
    const v = parseFloat(localStorage.getItem(key));
    return Number.isFinite(v) ? v : fallback;
  }

  function applyVolumes() {
    // localStorage sliders:
    // - volume: master (0-100)
    // - music: music level (0-100)
    // - sfx: sfx level (0-100)
    const volume = getNumber('volume', 100) / 100;
    const music = getNumber('music', 100) / 100;
    const sfx = getNumber('sfx', 100) / 100;

    const masterGain = clamp01(volume);
    const musicGain = clamp01(masterGain * clamp01(music));
    const sfxGain = clamp01(masterGain * clamp01(sfx));

    if (popAudio) popAudio.volume = sfxGain;
    if (clickFieldAudio) clickFieldAudio.volume = sfxGain;
    if (bkgAudio) bkgAudio.volume = musicGain;
  }

  function clamp01(n) {
    return Math.max(0, Math.min(1, n));
  }


  function safeEnsureAudio() {
    if (!popAudio) {
      popAudio = new Audio(POP_URL);
      popAudio.preload = 'auto';
      popAudio.loop = false;
    }

    if (!clickFieldAudio) {
      clickFieldAudio = new Audio(CLICK_FIELD_URL);
      clickFieldAudio.preload = 'auto';
      clickFieldAudio.loop = false;
    }

    if (!bkgAudio) {
      bkgAudio = new Audio(BKG_URL);
      bkgAudio.preload = 'auto';
      bkgAudio.loop = true;
    }

    applyVolumes();
  }

  function playAudio(audio, { resetTime = false } = {}) {
    safeEnsureAudio();

    // Avoid restarting background when switching pages.
    if (resetTime) {
      try {
        audio.currentTime = 0;
      } catch (e) {
        // ignore
      }
    }

    const p = audio.play();
    if (p && typeof p.catch === 'function') {
      p.catch(() => {
        // Autoplay restrictions may block; ignore.
      });
    }
  }


  function playPop() {
    playAudio(popAudio);
  }

  function isTextField(el) {
    if (!el) return false;

    const tag = (el.tagName || '').toLowerCase();
    if (tag === 'textarea') return true;
    if (tag !== 'input') return false;

    const type = (el.getAttribute('type') || 'text').toLowerCase();
    // Any textual field; password is NOT a text field here.
    return type === 'text' || type === 'email' || type === 'search' || type === 'url' || type === 'tel' || type === 'number' || type === 'date' || type === 'time' || type === 'month' || type === 'week' || type === 'color';
  }

  function shouldPlayClickField(el) {
    // Requirement: clicking ANY text field (login / sign up)
    return isTextField(el);
  }

  function shouldPlayBkg() {
    // Background should persist in the main app after reaching home.
    // Persist on these pages:
    // - home.html
    // - exercise/progress/profile/streak/settings
    // - (also keep any future pages that still include audio.js)

    const path = (window.location.pathname || '').toLowerCase();

    const isHome = path.endsWith('/home.html') || path.endsWith('home.html') || path === '/home.html';
    const isInApp =
      path.endsWith('/exercise.html') ||
      path.endsWith('/progress.html') ||
      path.endsWith('/profile.php') ||
      path.endsWith('/streak.html') ||
      path.endsWith('/settings.html');

    return isHome || isInApp || window.location.href.toLowerCase().includes('home.html');
  }

  function playBkgIfHome() {
    safeEnsureAudio();

    if (!shouldPlayBkg()) return;

    // If it's already playing, keep it running (do not reset).
    if (!bkgAudio.paused && bkgAudio.currentTime > 0 && !bkgAudio.ended) {
      bkgPlaying = true;
      return;
    }

    if (bkgPlaying) return;

    if (bkgAttempted) {
      // If we already attempted and it failed due to autoplay policy, we don't spam.
      return;
    }

    bkgAttempted = true;

    applyVolumes();

    // Do NOT reset currentTime when switching pages; we want continuity.
    playAudio(bkgAudio, { resetTime: false });

    if (typeof bkgAudio.play === 'function') {
      bkgPlaying = true;
    }
  }


  function extractHref(el) {
    if (!el) return null;

    // If the click was on a nested element inside an <a>, find closest <a>
    const a = el.closest ? el.closest('a[href]') : null;
    if (a && a.getAttribute) return a.getAttribute('href');

    if (el.tagName && el.tagName.toLowerCase() === 'a') {
      return el.getAttribute('href');
    }

    return null;
  }

  function hrefPointsToHome(href) {
    if (!href) return false;

    const h = href.toLowerCase();

    // Handle relative like ./home.html or home.html, or absolute/with query/hash
    return (
      h === './home.html' ||
      h === 'home.html' ||
      h.endsWith('/home.html') ||
      h.includes('home.html')
    );
  }

  function setupListeners() {
    // Re-play pop on EVERY click (including bottom nav buttons implemented as <div> inside <a>)
    document.addEventListener(
      'click',
      (ev) => {
        safeEnsureAudio();

        const target = ev.target;

        // click.mp3 for text fields in login/signup
        if (shouldPlayClickField(target)) {
          playAudio(clickFieldAudio);
        } else {
          // pop for everything else (including text fields, per "pop on EVERY button click")
          // If you want pop on text fields too, keep it as pop always.
          playPop();
        }
      },
      true // capture phase
    );

    // Background music: start only when arriving at home.html.
    // - clicking any link that points to home.html
    // - plus initial load check

    document.addEventListener(
      'click',
      (ev) => {
        const href = extractHref(ev.target);
        if (!href) return;

        if (hrefPointsToHome(href)) {
          // Don't restart if already playing; but requirement says constantly play and not repeat on reload
          // We'll just attempt to play once if not playing.
          playBkgIfHome();
        }
      },
      true
    );

    // localStorage slider changes (volume/music/sfx) should affect currently loaded audio
    window.addEventListener('storage', () => {
      applyVolumes();
    });
  }

  // Expose a helper for Settings page to immediately apply volume changes.
  window.__indigoApplyAudioVolumes = function __indigoApplyAudioVolumes() {
    applyVolumes();
  };

  // Auto start background when already on home
  document.addEventListener('DOMContentLoaded', () => {
    safeEnsureAudio();
    applyVolumes();
    playBkgIfHome();
    setupListeners();
  });


  // Also set up early to catch clicks even before DOMContentLoaded in unusual cases
  // (listener is idempotent enough for our needs).
  if (document.readyState !== 'loading') {
    safeEnsureAudio();
    applyVolumes();
    playBkgIfHome();
    setupListeners();
  }
})();

