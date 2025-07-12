jQuery(document).ready(function($) {
    // --- Sidebar Toggle Functionality ---
    $("#sidebarToggle").on("click", function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");

        // Optional: Save sidebar state in localStorage
        if ($("#wrapper").hasClass("toggled")) {
            localStorage.setItem('sidebarToggled', 'true');
        } else {
            localStorage.setItem('sidebarToggled', 'false');
        }
    });

    // Optional: Check localStorage for sidebar state on page load
    if (localStorage.getItem('sidebarToggled') === 'true') {
        $("#wrapper").addClass("toggled");
    } else if (localStorage.getItem('sidebarToggled') === 'false') {
        // Ensure it's not toggled if explicitly set to false,
        // useful if default is toggled on some screen sizes via CSS
        $("#wrapper").removeClass("toggled");
    } else {
        // Default behavior if no localStorage item is set:
        // Keep sidebar open on larger screens, closed on smaller ones.
        // This part can be tricky if CSS also handles initial state.
        // A common approach is to let CSS handle initial state and JS only handles toggle.
        // Or, explicitly set based on screen size here if necessary.
        if ($(window).width() < 768) { // Matches common Bootstrap breakpoint
             $("#wrapper").addClass("toggled");
        }
    }


    // --- Sidebar Accordion Functionality ---
    // Ensures only one accordion item in the sidebar is open at a time
    $('#sidebarAccordion .accordion-collapse').on('show.bs.collapse', function () {
        // Find all sibling accordion-collapse elements within the same parent accordion
        var $parentAccordion = $(this).closest('.accordion');
        $parentAccordion.find('.accordion-collapse.show').not($(this)).collapse('hide');
    });

    // Optional: Make parent accordion item active when a nested link is active
    // This requires knowing the current page. If your server-side adds an 'active' class
    // to the current page's link (e.g., <a href="..." class="active">...</a>)
    var currentPath = window.location.pathname; // Or a more sophisticated way to get current page
    $('#sidebarAccordion .nested-item a').each(function() {
        var linkHref = $(this).attr('href');
        // Normalize paths for comparison (e.g. remove trailing slashes)
        var normalizedLinkHref = linkHref.replace(/\/$/, "");
        var normalizedCurrentPath = currentPath.replace(/\/$/, "");

        // Check if the link's href is part of the current path
        // This is a simple check; more robust checking might be needed depending on URL structure
        if (normalizedCurrentPath.indexOf(normalizedLinkHref) !== -1 && normalizedLinkHref !== "") {
            $(this).addClass('active'); // Mark the link itself as active
            $(this).closest('.accordion-collapse').addClass('show'); // Show its parent accordion
            $(this).closest('.accordion-collapse').prev('.accordion-button').removeClass('collapsed').attr('aria-expanded', 'true'); // Update button state
            return false; // Exit loop once active link is found
        }
    });
    // If a direct link in the sidebar (not in an accordion) is active
     $('#sidebarAccordion > .list-group-item-action[href]').each(function() {
        var linkHref = $(this).attr('href');
        var normalizedLinkHref = linkHref.replace(/\/$/, "");
        var normalizedCurrentPath = currentPath.replace(/\/$/, "");
        if (normalizedCurrentPath === normalizedLinkHref) { // Exact match for top-level items
            $(this).addClass('active');
            // If it's also an accordion button, expand it (though less common for direct links)
            if ($(this).hasClass('accordion-button')) {
                 $(this).removeClass('collapsed').attr('aria-expanded', 'true');
                 $($(this).data('bs-target')).addClass('show');
            }
            return false;
        }
    });


    // --- Dynamic Breadcrumb Generation (Basic Example) ---
    // This is a very basic client-side example.
    // For robust breadcrumbs, server-side generation is often better.
    // This example assumes breadcrumbs are built from sidebar links.

    function generateBreadcrumbs() {
        var $breadcrumbContainer = $("#breadcrumb-container");
        if (!$breadcrumbContainer.length) return; // No container found

        $breadcrumbContainer.empty(); // Clear existing breadcrumbs
        var path = window.location.pathname;
        var pathSegments = path.split('/').filter(function(segment) { return segment !== ""; });

        // Add Home link
        var homeUrl = $('body').data('base-url') || '/'; // Assuming BASE_URL is available, e.g. via data attribute on body
        $breadcrumbContainer.append('<li class="breadcrumb-item"><a href="' + homeUrl + '">Home</a></li>');

        var currentPath = homeUrl;

        if (pathSegments.length > 0) {
            pathSegments.forEach(function(segment, index) {
                currentPath += segment + '/';
                var pageTitle = segment.replace(/-/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase() }); // Capitalize

                // Try to find a matching link in the sidebar to get a better title
                var $matchingLink = $('#sidebarAccordion a[href*="' + segment + '"]').first();
                if ($matchingLink.length) {
                    pageTitle = $matchingLink.text().trim();
                    // Remove icon text if present
                    var $icon = $matchingLink.find('i');
                    if ($icon.length) {
                        pageTitle = pageTitle.replace($icon.text().trim(), '').trim();
                    }
                }


                if (index === pathSegments.length - 1) {
                    // Last segment is the active page
                    $breadcrumbContainer.append('<li class="breadcrumb-item active" aria-current="page">' + pageTitle + '</li>');
                } else {
                    $breadcrumbContainer.append('<li class="breadcrumb-item"><a href="' + currentPath + '">' + pageTitle + '</a></li>');
                }
            });
        } else {
            // If on the home page (no segments after base)
            // Check if the "Home" link itself should be marked active or if it's just "Home"
            if ($breadcrumbContainer.children().length === 1 && path === homeUrl) {
                 // If only "Home" is there and it's the current page.
                 // $breadcrumbContainer.find('li:first-child').addClass('active').find('a').contents().unwrap();
                 // Or simply, if it's truly the root, the "Home" link is enough.
                 // For dashboard-like apps, "Home" might be "Dashboard" and always a link.
            }
        }

        // Show breadcrumb container if it has items
        if ($breadcrumbContainer.children().length > 0) {
            $("#breadcrumb-nav-container").show();
        } else {
            $("#breadcrumb-nav-container").hide();
        }
    }

    // Call breadcrumb generation on page load
    // generateBreadcrumbs();
    // Note: The client-side breadcrumb generation is a basic example.
    // A more robust solution often involves the server providing breadcrumb data.
    // For now, the placeholder in header.php will be used, and pages can populate it.
    // If you enable client-side generation, ensure BASE_URL is correctly passed, e.g.:
    // <body data-base-url="<?php echo BASE_URL; ?>">

    // --- Initialize Bootstrap Tooltips (if used) ---
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // --- Initialize Bootstrap Popovers (if used) ---
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })

});
