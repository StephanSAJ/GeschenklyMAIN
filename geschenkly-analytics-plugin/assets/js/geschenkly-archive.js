/**
 * Geschenkly – Archiv-/Kategorie-Seite
 *
 * 1) Verschiebt die Ratgeber-Verweise (Bild-Links der Kategorie-Beschreibung)
 *    in ein natives, einklappbares <details>-Akkordeon "Passende Ratgeber".
 * 2) Zeigt ueber dem Produktgrid einen Treffer-Zaehler und die aktiven Filter
 *    als entfernbare Chips (arbeitet mit dem BeRocket-Filter zusammen).
 */
(function () {
    'use strict';

    var CFG = window.geschenklyArchive || {};
    var L = {
        one: CFG.labelOne || 'Geschenk',
        many: CFG.labelMany || 'Geschenke',
        ratgeber: CFG.ratgeber || 'Passende Ratgeber',
        reset: CFG.resetLabel || 'Alle Filter zurücksetzen',
        total: parseInt(CFG.total, 10) || 0
    };

    /* ----------------------------------------------------------------- */
    /* 1) Ratgeber-Akkordeon                                             */
    /* ----------------------------------------------------------------- */

    function buildRatgeberAccordion() {
        var desc = document.querySelector('.term-description');
        if (!desc || desc.querySelector('details.gky-ratgeber')) {
            return;
        }

        var links = Array.prototype.filter.call(
            desc.querySelectorAll('a'),
            function (a) { return a.querySelector('img'); }
        );
        if (links.length < 1) {
            return;
        }

        var details = document.createElement('details');
        details.className = 'gky-ratgeber';

        var summary = document.createElement('summary');
        summary.textContent = L.ratgeber;
        details.appendChild(summary);

        var list = document.createElement('div');
        list.className = 'gky-ratgeber-list';
        details.appendChild(list);

        links.forEach(function (a) {
            var parent = a.parentNode;
            list.appendChild(a);
            if (
                parent && parent !== desc &&
                parent.children.length === 0 &&
                parent.textContent.trim() === ''
            ) {
                parent.parentNode && parent.parentNode.removeChild(parent);
            }
        });

        desc.appendChild(details);
    }

    /* ----------------------------------------------------------------- */
    /* 2) Treffer-Zaehler + aktive Filter als Chips                      */
    /* ----------------------------------------------------------------- */

    var bar, countEl, chipsEl, clearBtn, dividerEl;

    function checkedBoxes() {
        return Array.prototype.slice.call(
            document.querySelectorAll('.bapf_sfilter.bapf_ckbox input[type="checkbox"]:checked')
        );
    }

    function productsList() {
        return document.querySelector('ul.products');
    }

    function pluralize(n) {
        return n === 1 ? L.one : L.many;
    }

    function updateCount() {
        if (!countEl) { return; }
        var active = checkedBoxes().length > 0;
        var n;
        if (active) {
            var ul = productsList();
            n = ul ? ul.querySelectorAll('li.product').length : 0;
        } else {
            n = L.total;
        }
        countEl.innerHTML = '<b>' + n + '</b> ' + pluralize(n);
    }

    function labelFor(input) {
        if (input.getAttribute('data-name')) {
            return input.getAttribute('data-name');
        }
        if (input.id) {
            var lab = document.querySelector('label[for="' + input.id + '"]');
            if (lab) { return lab.textContent.trim(); }
        }
        return input.value || '';
    }

    function groupFor(input) {
        var grp = input.closest ? input.closest('.bapf_sfilter') : null;
        return grp ? (grp.getAttribute('data-name') || '') : '';
    }

    function renderChips() {
        if (!chipsEl) { return; }
        var boxes = checkedBoxes();
        chipsEl.innerHTML = '';

        boxes.forEach(function (input) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'gky-chip';
            var grp = groupFor(input);

            if (grp) {
                var g = document.createElement('span');
                g.className = 'gky-chip-grp';
                g.textContent = grp + ':';
                chip.appendChild(g);
            }
            var name = document.createElement('span');
            name.textContent = labelFor(input);
            chip.appendChild(name);

            var x = document.createElement('span');
            x.className = 'gky-chip-x';
            x.setAttribute('aria-hidden', 'true');
            x.textContent = '×';
            chip.appendChild(x);

            chip.setAttribute('aria-label', 'Filter entfernen: ' + labelFor(input));
            chip.addEventListener('click', function () {
                // Echter Klick auf die Checkbox => BeRocket reagiert wie gewohnt.
                input.click();
            });

            chipsEl.appendChild(chip);
        });

        var any = boxes.length > 0;
        if (clearBtn) { clearBtn.hidden = !any; }
        if (dividerEl) { dividerEl.style.display = any ? '' : 'none'; }
        updateCount();
    }

    function buildBar() {
        var ul = productsList();
        if (!ul || document.querySelector('.gky-active-filters')) {
            return;
        }

        bar = document.createElement('div');
        bar.className = 'gky-active-filters';

        countEl = document.createElement('span');
        countEl.className = 'gky-result-count';
        bar.appendChild(countEl);

        dividerEl = document.createElement('span');
        dividerEl.className = 'gky-divider';
        bar.appendChild(dividerEl);

        chipsEl = document.createElement('span');
        chipsEl.className = 'gky-chips';
        bar.appendChild(chipsEl);

        clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'gky-chip-clear';
        clearBtn.textContent = L.reset;
        clearBtn.hidden = true;
        clearBtn.addEventListener('click', function () {
            checkedBoxes().forEach(function (input) { input.click(); });
        });
        bar.appendChild(clearBtn);

        ul.parentNode.insertBefore(bar, ul);
        renderChips();
    }

    function ensureBar() {
        // Falls BeRocket den Grid-Bereich neu aufbaut und die Leiste verliert.
        if (!document.querySelector('.gky-active-filters')) {
            buildBar();
        }
    }

    function init() {
        buildRatgeberAccordion();
        buildBar();

        // Auf jede Filteraenderung reagieren (delegiert, ueberlebt AJAX-Reloads).
        document.addEventListener('change', function (e) {
            if (e.target && e.target.matches &&
                e.target.matches('.bapf_sfilter.bapf_ckbox input[type="checkbox"]')) {
                setTimeout(renderChips, 0);
            }
        });

        // Produktanzahl nach AJAX-Filterung neu zaehlen.
        var main = (productsList() && productsList().parentNode) || document.body;
        if (window.MutationObserver) {
            var t;
            new MutationObserver(function () {
                clearTimeout(t);
                t = setTimeout(function () {
                    ensureBar();
                    updateCount();
                }, 120);
            }).observe(main, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
