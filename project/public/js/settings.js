/**
 * Settings Page Logic
 * Handles slider interactions, localStorage persistence, and audio control
 */

// Initialize settings on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Settings] Initializing...');
    
    // Load saved values from localStorage
    loadSettingsFromStorage();
    
    // Setup event listeners for all sliders
    setupSliderListeners();
    
    // Setup checkbox listener for night mode
    setupNightModeListener();
    
    console.log('[Settings] Initialization complete');
});

/**
 * Load saved settings from localStorage and apply to UI
 */
function loadSettingsFromStorage() {
    // Volume sliders
    const volumeSlider = document.getElementById('volume');
    const musicSlider = document.getElementById('music');
    const sfxSlider = document.getElementById('sfx');
    const brightnessSlider = document.getElementById('brightness');
    const nightModeCheckbox = document.getElementById('nightmode');
    
    if (volumeSlider) {
        volumeSlider.value = localStorage.getItem('volume') || 100;
        updateSliderDisplay(volumeSlider);
    }
    
    if (musicSlider) {
        musicSlider.value = localStorage.getItem('music') || 100;
        updateSliderDisplay(musicSlider);
    }
    
    if (sfxSlider) {
        sfxSlider.value = localStorage.getItem('sfx') || 100;
        updateSliderDisplay(sfxSlider);
    }
    
    if (brightnessSlider) {
        brightnessSlider.value = localStorage.getItem('brightness') || 100;
        updateSliderDisplay(brightnessSlider);
    }
    
    if (nightModeCheckbox) {
        nightModeCheckbox.checked = localStorage.getItem('nightMode') === 'true';
    }
}

/**
 * Setup event listeners for all range sliders
 */
function setupSliderListeners() {
    const sliders = document.querySelectorAll('input[type="range"]');
    
    sliders.forEach(slider => {
        slider.addEventListener('input', function() {
            const sliderId = this.id;
            const value = this.value;
            
            console.log('[Settings] Slider ' + sliderId + ' changed to:', value);
            
            // Save to localStorage
            localStorage.setItem(sliderId, value);
            
            // Update display
            updateSliderDisplay(this);
            
            // Apply changes immediately
            if (sliderId === 'volume' || sliderId === 'music' || sliderId === 'sfx') {
                // Update volumes for the currently loaded audio.
                // `audio.js` reads localStorage keys with the same names.
                applyAudioLevels();
                // Also notify the audio controller if it exposed an update method.
                if (typeof window.__indigoApplyAudioVolumes === 'function') {
                    window.__indigoApplyAudioVolumes();
                }
            } else if (sliderId === 'brightness') {
                applyBrightness(value);
            }

        });
        
        // Update display on load
        updateSliderDisplay(slider);
    });
}

/**
 * Setup night mode toggle
 */
function setupNightModeListener() {
    const nightModeCheckbox = document.getElementById('nightmode');
    
    if (!nightModeCheckbox) return;
    
    nightModeCheckbox.addEventListener('change', function() {
        const isEnabled = this.checked;
        console.log('[Settings] Night mode toggled:', isEnabled);
        
        // Save to localStorage
        localStorage.setItem('nightMode', isEnabled);
        
        // Apply theme
        if (isEnabled) {
            document.documentElement.setAttribute('data-theme', 'dark');
            switchImagesToTheme('dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            switchImagesToTheme('light');
        }
    });
}

/**
 * Update slider display (value label next to slider)
 */
function updateSliderDisplay(slider) {
    // Find or create value display
    let display = slider.nextElementSibling;
    
    if (!display || !display.classList.contains('slider-value')) {
        display = document.createElement('span');
        display.classList.add('slider-value');
        slider.parentNode.insertBefore(display, slider.nextSibling);
    }
    
    const value = slider.value;
    const label = slider.id.charAt(0).toUpperCase() + slider.id.slice(1);
    display.textContent = value + '%';
    
    // Update slider background gradient for visual feedback
    updateSliderTrack(slider);
}

/**
 * Update slider track background to show progress
 */
function updateSliderTrack(slider) {
    const value = (slider.value - slider.min) / (slider.max - slider.min) * 100;
    slider.style.background = `linear-gradient(to right, #7e52cf 0%, #7e52cf ${value}%, #e0e0e0 ${value}%, #e0e0e0 100%)`;
}

/**
 * Apply audio level changes to all audio elements
 */
function applyAudioLevels() {
    const volume = parseFloat(localStorage.getItem('volume')) || 100;
    const music = parseFloat(localStorage.getItem('music')) || 100;
    const sfx = parseFloat(localStorage.getItem('sfx')) || 100;
    
    // Convert 0-100 to 0-1 range
    const masterGain = volume / 100;
    const musicGain = (music / 100) * masterGain;
    const sfxGain = (sfx / 100) * masterGain;
    
    console.log('[Settings] Applying audio levels - Master:', masterGain, 'Music:', musicGain, 'SFX:', sfxGain);
    
    // Update audio elements
    const musicElements = document.querySelectorAll('audio.music-audio');
    const sfxElements = document.querySelectorAll('audio.sfx-audio');
    
    musicElements.forEach(audio => {
        audio.volume = Math.min(1, musicGain);
    });
    
    sfxElements.forEach(audio => {
        audio.volume = Math.min(1, sfxGain);
    });
}

/**
 * Apply brightness filter
 */
function applyBrightness(brightness) {
    brightness = parseFloat(brightness) || 100;
    console.log('[Settings] Applying brightness:', brightness);
    
    if (brightness === 100) {
        document.documentElement.style.filter = '';
    } else {
        document.documentElement.style.filter = `brightness(${brightness}%)`;
    }
}
