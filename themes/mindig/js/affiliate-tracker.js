// affiliate-tracker.js

document.addEventListener('DOMContentLoaded', function() {
    // Wählt alle Buttons mit der Klasse 'single_add_to_cart_button' aus
    var buttons = document.querySelectorAll('.single_add_to_cart_button');

    buttons.forEach(function(button) {
        // Überprüft, ob es ein Affiliate-Link ist
        if (button.href && button.href.includes('redirect.php')) {
            
            button.addEventListener('click', function(e) {
                // Skript nur ausführen, wenn _paq existiert (Matomo/Piwik)
                if (typeof _paq !== 'undefined') {
                    // Tracking-Event auslösen
                    _paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Produktseite']);
                }
                
                // Da das Tracking asynchron ist, lassen wir den Browser kurz warten,
                // bevor er den Redirect durchführt.
                // NOTE: Wir verwenden e.preventDefault() nicht, da wir den Link
                // direkt nutzen wollen, aber das Tracking vorher senden.
                // Da wir das Link-Ziel nicht verhindern (es ist ein <a>-Tag),
                // wird der Klick sofort ausgeführt. Matomo muss schnell sein.
            });
        }
    });
});