/**
 * Global navigation function for the admin dashboard
 * This function handles the URL updating and section visibility
 */

function updateURL(section) {
    console.log(`Updating to section: ${section}`);
    
    // Update URL hash
    window.location.hash = section;
    
    // Hide all sections
    const sections = document.querySelectorAll('.content-wrapper > div[class$="-section"]');
    sections.forEach(s => s.classList.add('d-none'));
    
    // Show the selected section
    const sectionToShow = document.querySelector(`.${section}-section`);
    if (sectionToShow) {
        console.log(`Found section: .${section}-section`);
        sectionToShow.classList.remove('d-none');
    } else {
        console.warn(`Section not found: .${section}-section`);
        if (section === 'settings') {
            // Handle settings section if it exists
            const settingsSection = document.querySelector('.settings-section');
            if (settingsSection) {
                settingsSection.classList.remove('d-none');
            }
        }
    }
    
    // Update active link
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => link.classList.remove('active'));
    
    const activeLink = document.querySelector(`.sidebar .nav-link[href="#${section}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    } else {
        console.warn(`Active link not found for #${section}`);
    }
    
    // Special handling for sections
    switch (section) {
        case 'users':
            console.log('Loading users section');
            // Ensure user management is initialized
            if (typeof initUserManagement === 'function') {
                initUserManagement();
            } else {
                console.error('User management function not found!');
            }
            break;
        case 'dashboard':
            console.log('Loading dashboard section');
            if (typeof initDashboardAnalytics === 'function') {
                initDashboardAnalytics();
            }
            break;
        case 'inventory':
            console.log('Loading inventory section');
            // Any special inventory section initialization
            break;
        case 'po':
            console.log('Loading PO section');
            // Any special PO section initialization
            break;
        case 'par':
            console.log('Loading PAR section');
            // Any special PAR section initialization
            break;
    }
}

// Initialize navigation on document load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded, setting up navigation');
    
    // Get all navigation links
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Handle click events on navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                const section = href.substring(1);
                console.log(`Nav link clicked: ${section}`);
                updateURL(section);
            }
        });
    });
    
    // Check if there's a hash in the URL and navigate to that section
    if (window.location.hash) {
        const section = window.location.hash.substring(1);
        console.log(`Initial section from hash: ${section}`);
        updateURL(section);
    } else {
        console.log('No hash found, defaulting to dashboard');
        updateURL('dashboard');
    }
}); 