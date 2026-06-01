/**
 * Geschenkly – Archiv-/Kategorie-Seite
 *
 * Verschiebt die Ratgeber-Verweise (Bild-Links der Kategorie-Beschreibung)
 * in ein kompaktes, einklappbares <details>-Akkordeon "Passende Ratgeber".
 * Die Links bleiben so fuer SEO im HTML, dominieren aber den Kopf nicht mehr.
 */
(function () {
    'use strict';

    var CFG = window.geschenklyArchive || {};
    var RATGEBER = CFG.ratgeber || 'Passende Ratgeber';

    function buildRatgeberAccordion() {
        var desc = document.querySelector('.term-description');
        if (!desc || desc.querySelector('details.gky-ratgeber')) {
            return;
        }

        // Ratgeber-Verweise = Links, die ein Bild enthalten (reine Textlinks
        // im Beschreibungstext bleiben unangetastet).
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
        summary.textContent = RATGEBER;
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

    // Popover schliessen bei Klick ausserhalb oder Escape.
    function bindCloseHandlers() {
        document.addEventListener('click', function (e) {
            var open = document.querySelector('details.gky-ratgeber[open]');
            if (open && !open.contains(e.target)) {
                open.removeAttribute('open');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                var open = document.querySelector('details.gky-ratgeber[open]');
                if (open) { open.removeAttribute('open'); }
            }
        });
    }

    function init() {
        buildRatgeberAccordion();
        bindCloseHandlers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
