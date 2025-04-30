/**
 * User Navigation JavaScript
 * Handles proper navigation to/from user section
 */

// Define global updateURL function
function updateURL(section) {
    // Update URL hash
    window.location.hash = section;
    
    // Hide all sections
    const sections = document.querySelectorAll('.content-wrapper > div[class$="-section"]');
    sections.forEach(s => s.classList.add('d-none'));
    
    // Show the selected section
    let sectionClass = `${section}-section`;
    
    // Special case for user/users section
    if (section === 'user') {
        sectionClass = 'users-section';
    }
    
    const sectionToShow = document.querySelector(`.${sectionClass}`);
    if (sectionToShow) {
        sectionToShow.classList.remove('d-none');
    } else if (section === 'settings') {
        // Handle settings section if it exists
        const settingsSection = document.querySelector('.settings-section');
        if (settingsSection) {
            settingsSection.classList.remove('d-none');
        }
    }
    
    // Update active link
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => link.classList.remove('active'));
    
    const activeLink = document.querySelector(`.sidebar .nav-link[href="#${section}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Get all navigation links
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Check if there's a hash in the URL and navigate to that section
    if (window.location.hash) {
        const section = window.location.hash.substring(1);
        updateURL(section);
    }
    
    // Handle click events on navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Extract section from href
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                const section = href.substring(1);
                updateURL(section);
            }
        });
    });
}); 