const TIMER_DURATION = 24 * 60 * 60; // 24 hours in seconds
const CRITICAL_THRESHOLD = 10 * 60; // 10 minutes in seconds
const ALMOST_THRESHOLD = 30 * 60; // 30 minutes in seconds
const HURRY_THRESHOLD = 60 * 60; // 1 hour in seconds

async function fetchLastAttempt() {
  try {
    const response = await fetch('./sql/getProgress.php');
    const data = await response.json();
    
    if (data.success && data.timestamp) {
      return data.timestamp;
    } else {
      return null;
    }
  } catch (error) {
    console.error('Error fetching progress data:', error);
    return null;
  }
}

function formatTime(seconds) {
  if (seconds <= 0) {
    return '00:00';
  }
  
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;
  
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function updateTimer(lastAttemptTimestamp) {
  const now = Math.floor(Date.now() / 1000);
  const timeElapsed = now - lastAttemptTimestamp;
  const timeRemaining = Math.max(0, TIMER_DURATION - timeElapsed);
  
  const timerElement = document.getElementById('timer');
  const hurryElement = document.getElementById('hurry-text');
  
//   timUpdate urgency message based on time remaining
  if (timeRemaining <= 0) {
    hurryElement.textContent = "Streak expired!";
    hurryElement.style.display = 'block';
  } else if (timeRemaining <= CRITICAL_THRESHOLD) {
    hurryElement.textContent = "Critical! Time's almost up!";
    hurryElement.style.display = 'block';
  } else if (timeRemaining <= ALMOST_THRESHOLD) {
    hurryElement.textContent = "Almost there! Keep going!";
    hurryElement.style.display = 'block';
  } else if (timeRemaining <= HURRY_THRESHOLD) {
    hurryElement.textContent = "Hurry! Don't lose your streak!";
  // Show "Hurry!" if less than 1 hour remaining
  if (timeRemaining > 0 && timeRemaining <= HURRY_THRESHOLD) {
    hurryElement.style.display = 'block';
  } else {
    hurryElement.style.display = 'none';
  }
  
  // Update timer every second
  if (timeRemaining > 0) {
    setTimeout(() => updateTimer(lastAttemptTimestamp), 1000);
  } else {
    timerElement.textContent = '00:00';
  }
}

async function initializeTimer() {
  const lastAttemptTimestamp = await fetchLastAttempt();
  
  if (lastAttemptTimestamp) {
    updateTimer(lastAttemptTimestamp);
  } else {
    // No previous attempt, timer is available
    document.getElementById('timer').textContent = 'Ready!';
  }
}

// Initialize timer when page loads
window.addEventListener('DOMContentLoaded', initializeTimer);

// Pop animation function for the flame
function popFire() {
  const fireElement = document.getElementById('fire');
  fireElement.classList.add('pop-animation');
  
  // Remove the animation class after animation completes
  setTimeout(() => {
    fireElement.classList.remove('pop-animation');
  }, 300); // Match the animation duration
} }
