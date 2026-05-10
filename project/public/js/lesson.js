// Lesson page JavaScript
// This file can be expanded later for different lesson types

document.addEventListener('DOMContentLoaded', function() {
    const skipBtn = document.querySelector('.skip-btn');
    const submitBtn = document.querySelector('.submit-btn');

    skipBtn.addEventListener('click', function() {
        // Return to home page
        window.location.href = './home.html';
    });

    submitBtn.addEventListener('click', function() {
        // TODO: Process lesson submission
        console.log('Lesson submitted');
        // window.location.href = './home.html';
    });
});
