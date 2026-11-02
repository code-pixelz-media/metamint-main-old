/**
 * Tabs (list + detail panel, accordion on mobile).
 * Works with any markup that uses the .mm-tabs-section / .mm-tabs__* classes.
 * Uses event delegation so it also works for blocks rendered later (block editor preview).
 */
(function () {
  "use strict";

  function select(tab, focus) {
    var root = tab.closest(".mm-tabs-section");
    if (!root) return;

    var tabs = root.querySelectorAll(".mm-tabs__tab");
    var panels = root.querySelectorAll(".mm-tabs__panel");
    var details = root.querySelectorAll(".mm-tabs__detail");
    var idx = parseInt(tab.getAttribute("data-index"), 10);

    Array.prototype.forEach.call(tabs, function (t, n) {
      var on = n === idx;
      t.setAttribute("aria-selected", on ? "true" : "false");
      t.tabIndex = on ? 0 : -1;
    });

    Array.prototype.forEach.call(panels, function (p, n) {
      p.hidden = n !== idx;
    });

    // Accordion bodies only exist for rows that have a description,
    // so match them to their tab through the previous-sibling relationship.
    Array.prototype.forEach.call(details, function (d) {
      d.setAttribute(
        "data-open",
        d.previousElementSibling === tab ? "true" : "false",
      );
    });

    if (focus) tab.focus();
  }

  document.addEventListener("click", function (e) {
    var tab = e.target.closest ? e.target.closest(".mm-tabs__tab") : null;
    if (tab) select(tab, false);
  });

  document.addEventListener("keydown", function (e) {
    var tab = e.target.closest ? e.target.closest(".mm-tabs__tab") : null;
    if (!tab) return;

    var root = tab.closest(".mm-tabs-section");
    if (!root) return;

    var tabs = Array.prototype.slice.call(
      root.querySelectorAll(".mm-tabs__tab"),
    );
    var cur = tabs.indexOf(tab);
    var next = null;

    if (e.key === "ArrowDown" || e.key === "ArrowRight")
      next = (cur + 1) % tabs.length;
    else if (e.key === "ArrowUp" || e.key === "ArrowLeft")
      next = (cur - 1 + tabs.length) % tabs.length;
    else if (e.key === "Home") next = 0;
    else if (e.key === "End") next = tabs.length - 1;

    if (next !== null) {
      e.preventDefault();
      select(tabs[next], true);
    }
  });
})();
