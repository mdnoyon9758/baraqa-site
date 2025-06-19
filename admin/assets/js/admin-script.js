document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle Functionality
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const adminLayout = document.querySelector('.admin-layout');

    if (sidebarToggle && adminLayout) {
        sidebarToggle.addEventListener('click', function() {
            adminLayout.classList.toggle('sidebar-toggled');
        });
    }

    // Retain sidebar state in localStorage
    if (localStorage.getItem('sidebar_toggled') === 'true') {
        adminLayout.classList.add('sidebar-toggled');
    }

    sidebarToggle.addEventListener('click', function() {
        if (adminLayout.classList.contains('sidebar-toggled')) {
            localStorage.setItem('sidebar_toggled', 'true');
        } else {
            localStorage.setItem('sidebar_toggled', 'false');
        }
    });
});