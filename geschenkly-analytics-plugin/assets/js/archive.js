/**
 * Geschenkly – Archiv-/Kategorie-Seite
 *
 * Verschiebt die Ratgeber-Verweise (Bild-Links in der Kategorie-Beschreibung)
 * in ein natives, einklappbares <details>-Akkordeon "Passende Ratgeber".
 * Die Links bleiben so fuer SEO im HTML, dominieren aber den Kopf nicht mehr.
 */
(function () {
    'use strict';

    function buildRatgeberAccordion() {
        var desc = document.querySelector('.term-description');
        if (!desc || desc.querySelector('details.gky-ratgeber')) {
            return; // keine Beschreibung oder bereits aufgebaut
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
        summary.textContent = 'Passende Ratgeber';
        details.appendChild(summary);

        var list = document.createElement('div');
        list.className = 'gky-ratgeber-list';
        details.appendChild(list);

        links.forEach(function (a) {
            // Eventuell umschliessende, dann leere Wrapper merken (z. B. <p>).
            var parent = a.parentNode;
            list.appendChild(a); // verschiebt den Link (kein Klonen)
            if (
                parent &&
                parent !== desc &&
                parent.children.length === 0 &&
                parent.textContent.trim() === ''
            ) {
                parent.parentNode && parent.parentNode.removeChild(parent);
            }
        });

        desc.appendChild(details);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildRatgeberAccordion);
    } else {
        buildRatgeberAccordion();
    }
})();
