// Mobile Navigation Toggle Script for Motoshapi
// Handles both Spare and Modern themes

document.addEventListener('DOMContentLoaded', function() {
    // Spare Theme Navigation
    const spHamburger = document.getElementById('spHamburger');
    const spNav = document.getElementById('spNav');
    
    if (spHamburger && spNav) {
        spHamburger.addEventListener('click', function() {
            spNav.classList.toggle('active');
            spHamburger.classList.toggle('active');
        });
        
        // Close menu when clicking a link
        const spNavLinks = spNav.querySelectorAll('a');
        spNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                spNav.classList.remove('active');
                spHamburger.classList.remove('active');
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!spNav.contains(event.target) && !spHamburger.contains(event.target)) {
                spNav.classList.remove('active');
                spHamburger.classList.remove('active');
            }
        });
    }
    
    // Modern Theme Navigation
    const modernHamburger = document.getElementById('modernHamburger');
    const modernNav = document.getElementById('modernNav');
    
    if (modernHamburger && modernNav) {
        modernHamburger.addEventListener('click', function() {
            modernNav.classList.toggle('active');
            modernHamburger.classList.toggle('active');
        });
        
        // Close menu when clicking a link
        const modernNavLinks = modernNav.querySelectorAll('a');
        modernNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                modernNav.classList.remove('active');
                modernHamburger.classList.remove('active');
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!modernNav.contains(event.target) && !modernHamburger.contains(event.target)) {
                modernNav.classList.remove('active');
                modernHamburger.classList.remove('active');
            }
        });
    }
});
