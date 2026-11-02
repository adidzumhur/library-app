/**
 * Simple hash-based SPA router.
 *
 * 1. When the page is first launched, every HTML template from views/ is
 *    loaded ONCE (AJAX) and placed into <main id="app"> as a hidden <section>.
 * 2. The URL hash (e.g. #books) decides which section is visible.
 *    Changing the hash only shows/hides sections - no page reload and
 *    no repeated loading of views.
 */
const Router = {
  // All pages the application knows about (file name in views/ = route name)
  routes: [
    "login", "register", "dashboard",
    "books", "book-details", "authors", "categories",
    "loans", "reviews", "users", "profile"
  ],

  // Pages shown without the navbar/footer
  publicPages: ["login", "register"],

  defaultRoute: "dashboard",

  init() {
    this.loadAllViews().then(() => {
      $(window).on("hashchange", () => this.showView());
      this.showView();
    });
  },

  // Load every view once and keep it in the DOM
  loadAllViews() {
    const requests = this.routes.map((route) =>
      $.get(`views/${route}.html`)
        .then((html) => {
          $("#app").append(`<section id="view-${route}" class="spa-view" style="display:none">${html}</section>`);
        })
        .catch(() => {
          $("#app").append(`<section id="view-${route}" class="spa-view" style="display:none">
            <div class="alert alert-danger">Page could not be loaded.</div></section>`);
        })
    );
    return Promise.all(requests);
  },

  currentRoute() {
    const route = window.location.hash.replace("#", "");
    return this.routes.includes(route) ? route : this.defaultRoute;
  },

  // Show the section for the current hash and hide the others
  showView() {
    const route = this.currentRoute();
    const isPublic = this.publicPages.includes(route);

    $(".spa-view").hide();
    $(`#view-${route}`).show();

    // Hide navigation on login/register
    $("#main-nav, #main-footer").toggle(!isPublic);

    // Highlight the active link in the navbar
    $("#main-nav .nav-link").removeClass("active");
    $(`#main-nav .nav-link[href="#${route}"]`).addClass("active");

    // Close the mobile menu after a link is clicked
    const menu = document.getElementById("navMenu");
    if (menu && menu.classList.contains("show")) {
      bootstrap.Collapse.getOrCreateInstance(menu).hide();
    }

    window.scrollTo(0, 0);
  }
};

$(document).ready(() => Router.init());
