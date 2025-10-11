$(document).ready(function () {
    // Sidebar toggle functionality
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('collapsed');
    });

    // Activate the current sidebar link based on the page URL
    // This makes the sidebar navigation more intuitive for the user
    var path = window.location.pathname;
    // Get the base file name from the path
    path = path.split("/").pop();
    if (path === '') {
        path = 'index.php'; // Default to index if path is empty
    }

    var target = $('#sidebar ul li a[href*="' + path + '"]');
    // Add 'active' class to the parent 'li' of the link
    if (target.length) {
        target.parent().addClass('active');
    }
});