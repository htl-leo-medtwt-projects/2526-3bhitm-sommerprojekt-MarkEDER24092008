const CRITICAL_THRESHOLD = 10 * 60; // 10 minutes in seconds
const ALMOST_THRESHOLD = 30 * 60; // 30 minutes in seconds
const HURRY_THRESHOLD = 60 * 60; // 1 hour in seconds

let timerIntervalId = null;
let streakData = {
    streak_count: 0,
    streak_longest: 0,
    goal_met_today: false,
    streak_active: true
};

/**
 * Fetch streak data from the database
 */
async function fetchStreakData() {
    try {
        console.log('[Streak] Fetching streak data...');
        const response = await fetch('./sql/getStreakData.php');
        
        if (!response.ok) {
            console.error('[Streak] HTTP error, status:', response.status);
            return null;
        }
        
        const text = await response.text();
        console.log('[Streak] Raw response:', text);
        
        const data = JSON.parse(text);
        
        if (data.success) {
            console.log('[Streak] Data fetched successfully:', data);
            streakData = {
                streak_count: data.streak_count,
                streak_longest: data.streak_longest,
                goal_met_today: data.goal_met_today,
                last_attempt_timestamp: data.last_attempt_timestamp,
                today: data.today
            };
            return data;
        } else {
            console.error('Error fetching streak data:', data.error);
            return null;
        }
    } catch (error) {
        console.error('[Streak] Exception:', error);
        return null;
    }
}

/**
 * Check if the streak is still active (check if previous day's goal was met)
 */
async function checkStreakStatus() {
    try {
        console.log('[Streak] Checking streak status...');
        const response = await fetch('./sql/checkStreakStatus.php');
        
        if (!response.ok) {
            console.error('[Streak] HTTP error checking status, status:', response.status);
            return null;
        }
        
        const text = await response.text();
        console.log('[Streak] Raw status response:', text);
        
        const data = JSON.parse(text);
        
        if (data.success) {
            console.log('[Streak] Status checked successfully:', data);
            streakData.streak_active = data.streak_active;
            streakData.goal_met_today = data.goal_met_today;
            return data;
        } else {
            console.error('Error checking streak status:', data.error);
            return null;
        }
    } catch (error) {
        console.error('[Streak] Exception checking status:', error);
        return null;
    }
}

/**
 * Format seconds to HH:MM:SS format
 */
function formatTime(seconds) {
    if (seconds <= 0) {
        return '00:00:00';
    }
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

/**
 * Calculate seconds until midnight (00:00)
 */
function getSecondsUntilMidnight() {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);
    
    const secondsRemaining = Math.floor((tomorrow - now) / 1000);
    console.log('[Streak] Now:', now.toLocaleTimeString(), 'Tomorrow midnight:', tomorrow.toLocaleTimeString(), 'Seconds remaining:', secondsRemaining);
    return Math.max(0, secondsRemaining);
}

/**
 * Update the timer display and flame state based on time remaining
 */
function updateTimer() {
    const timeRemaining = getSecondsUntilMidnight();
    const timerElement = document.getElementById('timer');
    const hurryElement = document.getElementById('hurry-text');
    const flameImage = document.getElementById('flame-image');
    
    if (!timerElement || !hurryElement || !flameImage) {
        console.error('[Streak] Timer elements not found in DOM');
        return;
    }
    
    // Update timer display with HH:MM:SS format
    const formattedTime = formatTime(timeRemaining);
    timerElement.textContent = formattedTime;
    console.log('[Streak] Timer updated:', formattedTime);
    
    // Update urgency message and flame state based on time remaining
    if (timeRemaining <= 0) {
        // Time's up - streak expires if goal not met
        if (!streakData.goal_met_today) {
            streakData.streak_active = false;
            flameImage.src = './media/images/grayFlame.png';
            hurryElement.textContent = "Streak expired! 😢";
            hurryElement.style.display = 'block';
            timerElement.textContent = '00:00:00';
            // Refetch to update streak count in UI
            checkStreakStatus();
        }
    } else if (timeRemaining <= CRITICAL_THRESHOLD) {
        hurryElement.textContent = "Critical! Time's almost up! ⏰";
        hurryElement.style.display = 'block';
    } else if (timeRemaining <= ALMOST_THRESHOLD) {
        hurryElement.textContent = "Almost there! Keep going! 💪";
        hurryElement.style.display = 'block';
    } else if (timeRemaining <= HURRY_THRESHOLD) {
        hurryElement.textContent = "Hurry! Don't lose your streak! 🔥";
        hurryElement.style.display = 'block';
    } else {
        hurryElement.style.display = 'none';
    }
    
    // Schedule next update in 1 second
    if (timerIntervalId) {
        clearTimeout(timerIntervalId);
    }
    timerIntervalId = setTimeout(updateTimer, 1000);
}

/**
 * Update the UI with current streak data
 */
function updateUI() {
    document.getElementById('streak-num').textContent = streakData.streak_count;
    document.getElementById('streak-longest').textContent = streakData.streak_longest;
    
    const goalStatus = document.getElementById('goal-status');
    if (streakData.goal_met_today) {
        goalStatus.textContent = 'Today: ✓ Completed';
        goalStatus.style.color = '#4CAF50';
    } else {
        goalStatus.textContent = 'Today: Not completed';
        goalStatus.style.color = '#999';
    }
    
    // Update flame image based on streak status
    const flameImage = document.getElementById('flame-image');
    if (streakData.streak_active && streakData.streak_count > 0) {
        flameImage.src = './media/images/fire.gif';
    } else {
        flameImage.src = './media/images/grayFlame.png';
    }
}

/**
 * Initialize the streak page
 */
async function initializeStreak() {
    console.log('[Streak] Initializing streak page...');
    
    // Fetch streak data from database
    const data = await fetchStreakData();
    
    if (data) {
        console.log('[Streak] Fetched data, now checking status...');
        // Check streak status (handles daily reset)
        await checkStreakStatus();
        
        // Update UI with current data
        console.log('[Streak] Updating UI with:', streakData);
        updateUI();
        
        // Start the timer
        console.log('[Streak] Starting timer...');
        updateTimer();
    } else {
        console.error('[Streak] Failed to initialize streak - no data returned');
        document.getElementById('streak-num').textContent = 'Error';
    }
}

/**
 * Called when a lesson is completed (trigger from lesson.html)
 * This updates the database and refreshes the UI
 */
async function onLessonCompleted() {
    try {
        const response = await fetch('./sql/completeLesson.php', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            // Lesson marked as complete, refetch streak data
            await fetchStreakData();
            await checkStreakStatus();
            updateUI();
            
            // Show success message
            const hurryElement = document.getElementById('hurry-text');
            hurryElement.textContent = '🎉 Great job! Streak maintained!';
            hurryElement.style.display = 'block';
            hurryElement.style.color = '#4CAF50';
            
            // Clear message after 3 seconds
            setTimeout(() => {
                hurryElement.style.color = '';
                updateTimer(); // Update timer message
            }, 3000);
        } else {
            console.error('Error completing lesson:', data.error);
        }
    } catch (error) {
        console.error('Error completing lesson:', error);
    }
}

/**
 * Pop animation function for the flame
 */
function popFire() {
    const fireElement = document.getElementById('fire');
    fireElement.classList.add('pop-animation');
    
    // Remove the animation class after animation completes
    setTimeout(() => {
        fireElement.classList.remove('pop-animation');
    }, 300);
}

/**
 * Clean up timer on page unload
 */
window.addEventListener('beforeunload', () => {
    if (timerIntervalId) {
        clearTimeout(timerIntervalId);
    }
});

/**
 * Initialize when page loads
 */
window.addEventListener('DOMContentLoaded', initializeStreak);

