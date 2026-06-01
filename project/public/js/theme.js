/**
 * Theme Management - Loads and applies saved settings on page load
 * This script should be included in the <head> of all pages
 */

// Images that should change based on theme
const THEME_AWARE_IMAGES = [
    'home.png',
    'settings.png',
    'profile.png',
    'speech.png',
    'brightness.png',
    'pencil.png',
    'studyHat.png'
];

function loadThemeSettings() {
    // Get saved settings from localStorage
    const nightMode = localStorage.getItem('nightMode') === 'true';
    const brightness = parseFloat(localStorage.getItem('brightness')) || 100;
    const volume = parseFloat(localStorage.getItem('volume')) || 100;
    const music = parseFloat(localStorage.getItem('music')) || 100;
    const sfx = parseFloat(localStorage.getItem('sfx')) || 100;

    // Apply night mode
    if (nightMode) {
        document.documentElement.setAttribute('data-theme', 'dark');
        switchImagesToTheme('dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        switchImagesToTheme('light');
    }

    // Apply brightness
    if (brightness !== 100) {
        document.documentElement.style.filter = `brightness(${brightness}%)`;
    }

    // Apply audio levels (if audio elements exist)
    applyAudioLevels(volume, music, sfx);
}

/**
 * Switch images between light and dark theme versions
 */
function switchImagesToTheme(theme) {
    const isDarkMode = theme === 'dark';
    
    // Find all images in the document
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        const src = img.src;
        
        // Check if this image should be theme-aware
        for (const imageName of THEME_AWARE_IMAGES) {
            // Extract the base filename without extension
            const parts = imageName.split('.');
            const name = parts[0];
            const ext = parts[1];
            const darkName = `${name}_dark.${ext}`;
            
            // Check for both original and dark versions in src
            if (src.includes(imageName) || src.includes(darkName)) {
                if (isDarkMode) {
                    // Add _dark version
                    if (!src.includes('_dark')) {
                        img.src = src.replace(imageName, darkName);
                        console.log('[Theme] Switched to dark:', imageName, '→', darkName);
                    }
                } else {
                    // Remove _dark version
                    if (src.includes('_dark')) {
                        img.src = src.replace(darkName, imageName);
                        console.log('[Theme] Switched to light:', darkName, '→', imageName);
                    }
                }
                break;
            }
        }
    });
}

function applyAudioLevels(masterVolume, musicVolume, sfxVolume) {
    // Convert 0-100 to 0-1 range
    const masterGain = masterVolume / 100;
    const musicGain = (musicVolume / 100) * masterGain;
    const sfxGain = (sfxVolume / 100) * masterGain;

    // Update audio elements if they exist
    const musicElements = document.querySelectorAll('audio.music-audio');
    const sfxElements = document.querySelectorAll('audio.sfx-audio');

    musicElements.forEach(audio => {
        audio.volume = Math.min(1, musicGain);
    });

    sfxElements.forEach(audio => {
        audio.volume = Math.min(1, sfxGain);
    });
}

// Load theme settings as soon as this script executes
if (document.readyState === 'loading') {
    // DOM is still loading
    document.addEventListener('DOMContentLoaded', loadThemeSettings);
} else {
    // DOM is already loaded
    loadThemeSettings();
}

// Listen for storage changes (settings updated in another tab)
window.addEventListener('storage', () => {
    loadThemeSettings();
});
