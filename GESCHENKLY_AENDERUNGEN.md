# Geschenkly – Änderungsdokumentation

Branch: `claude/kind-hypatia-EM2Ih`
Stand: Mai 2026

Diese Doku beschreibt **alle** Änderungen dieser Arbeitssitzung, warum sie gemacht
wurden und was beim Live-Gang zu beachten ist.

---

## 1. Ziel & Ausgangslage

Die Seite betrieb **zwei parallele Analytics-Systeme**:

1. **Matomo** (self-hosted, `geschenklyanalytics.de`) – klassische Web-Analytics. **Bleibt.**
2. Ein **externer Node.js + MongoDB-Server** (`schindler-ventures.de:3002`) – erfasste
   produktbezogene Klicks/Trends/Likes/Feedback. Zusätzlich hing die Funktion
   „Wunschliste per E-Mail" an einem weiteren externen Dienst (`:3004`).

Probleme: externe Single-Points-of-Failure, hohe Latenz, hohe Serverlast schon bei
300–500 Besuchern (vor allem durch einen täglichen Cron-Job im Theme), inkonsistentes
Design der Produktseite.

**Ergebnis:** Alle produktbezogenen Daten laufen jetzt **WordPress-nativ**. Keine externe
Abhängigkeit mehr außer Matomo. Theme-Last deutlich reduziert. Produktseite überarbeitet.

---

## 2. Analytics: extern → WordPress-nativ

**Warum:** externe Node/Mongo-Infrastruktur überflüssig machen, Latenz/Ausfälle vermeiden,
DSGVO vereinfachen, Daten neben den Bestelldaten halten.

### Neu angelegt
- **DB-Tabelle `wp_geschenkly_events`** (via `dbDelta`, indexiert):
  `id, product_id, event_type, category_id, category_name, tag_id, tag_name, meta,
  session_hash, created_at`
  Event-Typen: `click`, `view`, `like`, `feedback`.
- **REST-API-Namespace `geschenkly/v1`** ersetzt alle alten `:3002`-Endpunkte 1:1
  (gleiche Response-Formate → Frontend bricht nicht):
  - `GET analytics/popularity/{id}` → `{ category, rank, totalProducts }`
  - `GET analytics/trend/{id}` → `{ trend, direction }`
  - `GET analytics/monthly-clicks/{id}` → `{ totalClicks }`
  - `GET analytics/hourly/{id}` → `[ { hour, clicks } ]`
  - `GET analytics/top-categories-tags/{id}` → `{ topCategories, topTags }`
  - `GET analytics/trend-details/{id}` → `[ { week, popularity } ]`
  - `POST view` → erfasst View + liefert `{ viewersNow, viewsToday }`
  - `POST like`, `POST feedback`, `POST wishlist`
- Lese-Endpunkte sind **Transient-gecacht** (300 s).

### Geändert
- Klick-Erfassung schreibt **lokal** in die Tabelle statt per `wp_remote_post` an die
  externe API (`update_rating_callback`).
- Frontend (`product-card.js`, `rating.js`, `ratingNewDesign.js`) zeigt auf die lokale API.

**Dateien:** `geschenkly-analytics-plugin/geschenkly-analytics-plugin.php` (neu aufgebaut),
`geschenkly-analytics-plugin/includes/class-geschenkly-analytics-rest.php` (neu).

---

## 3. Theme-Performance (Ursache der hohen Serverlast)

**Warum:** Bei nur 300–500 Besuchern war ein teurer Server nötig. Ursachen identifiziert
und behoben.

| Fix | Vorher | Nachher |
|-----|--------|---------|
| **Decay-Cron** (`functions.php`) | Täglich `UPDATE` der **gesamten** `wp_posts` + verschachtelte `get_post_meta`/`update_post_meta`-Schleifen über **alle** Produkte × Kategorien × Tags → Hunderttausende Writes/Tag | Wenige **indizierte Bulk-UPDATEs**, nur veröffentlichte Produkte. **Größter Last-Hebel.** |
| **Zähl-Queries** | `posts_per_page => -1` lud alle Post-Objekte in den RAM | `fields => ids`, `no_found_rows`, ohne Meta-/Term-Cache |
| **Archiv-Sortierung** | `meta_value_num`-Sort über per-Kategorie-Meta (Meta-Explosion) | Optional auf zentralen `_geschenkly_pop_score` umschaltbar (Default: altes Verhalten) |

**Hinweis:** Es war **keine** ausnutzbare SQL-Injection (der Decay-Faktor ist eine Konstante);
der Kommentar „5 %" war faktisch 0,1 %.

---

## 4. Performance Quick-Wins (Ladezeit / LCP)

**Warum:** weniger Render-Blocking, weniger externe Hosts, weniger ungenutztes JS.

- **Resource-Hints**: `preconnect`/`dns-prefetch` für den Matomo-Host (das Theme entfernte
  zuvor sogar WordPress' eigene Resource-Hints).
- **Chart.js lokal**: gepinnte Version `4.4.1` unter
  `Plugins/GeschenklyProductCard/assets/vendor/chart.umd.min.js` statt unversioniert vom
  CDN → ein externer Host weniger auf Produktseiten.
- **Slider konditional**: MasterSlider (JS+CSS) nicht mehr global – auf
  WooCommerce-/404-/Such-Seiten entfernt; OwlCarousel/prettyPhoto auf
  Cart/Checkout/Account/Utility entfernt.
- **Core-Entschlackung** (zuvor im nie geladenen Child dormant): `wp-embed`, Heartbeat im
  Frontend und das Emoji-Skript deaktiviert.

---

## 5. Live-Daten pro Geschenk (Produktseite)

**Warum:** echtem Social Proof am Entscheidungspunkt, funktioniert hinter Page-Cache.

- Badge im Karten-Header: **„🟢 X sehen sich das gerade an · Y mal heute angesehen"**.
- Läuft per JS gegen `POST geschenkly/v1/view` (60-s-Heartbeat). Beide Werte zählen
  **DISTINCT Session-Hashes** (pseudonym: IP+UA+Salt) → der Heartbeat bläht die Zahlen nicht auf.
- View-Events werden im Rollup-Cron nach 2 Tagen geprunt (Tabelle bleibt schlank).
- **Redis-Glättung**: `live_snapshot()` cached die `COUNT(DISTINCT)`-Abfragen 15 s pro
  Produkt → entlastet die DB bei vielen Heartbeats.

---

## 6. Trending-Badge auf Listing-/Kategorie-Karten

**Warum:** der Nutzer soll beim Scannen vieler Geschenke **einen** sinnvollen, proaktiven
Hinweis bekommen – kein Vanity, kein Live.

- Server-seitig gerendert (cache-sicher), Hook `woocommerce_before_shop_loop_item_title`.
- **Genau ein** Badge je Karte, nach Priorität:
  1. 🏆 **„Top N in [Kategorie]"** (auf Kategorie-Archiven; Top-Liste pro Kategorie 1 h gecacht)
  2. 🔥 **„Im Trend"** (Meta `_geschenkly_trend`, vom Rollup gesetzt: diese Woche > Vorwoche,
     mit Mindestvolumen gegen Rauschen)
  3. sonst **nichts**
- Minimales Inline-CSS, nur auf Shop-/Kategorie-/Tag-Archiven.

---

## 7. Popularitäts-Rollup (ersetzt Cron-Aggregation)

**Warum:** zentrale, indizierbare Popularität statt per-Kategorie-Meta-Explosion.

- **WP-Cron `geschenkly_rollup_popularity`** (stündlich): berechnet aus der Event-Tabelle
  - `_geschenkly_pop_score` (zeitgewichteter Klick-Score, letzte 30 Tage)
  - `_geschenkly_trend` (`up`/leer)
  - prunt alte View-Events, invalidiert Kategorie-Top-Caches.
- **Seeding**: beim ersten Lauf werden bestehende `_homepage_rating`-Werte als Startwert
  übernommen → die bisherige Reihenfolge bleibt erhalten, bis genug Events vorliegen.

---

## 8. Produktseite – Redesign

**Warum:** stimmiges, markentreues Design mit hohem Mehrwert.

- **Eine Designsprache**: zentrale Design-Tokens (Farben/Radien/Abstände/Schatten);
  das kollidierende Blau (`#4a90e2`) durchgängig auf die **Gold-Marke** (`#e9a400`) vereinheitlicht.
- **Stärkere CTA** „Zum Shop" (Verlauf, größer, Hover-Lift) statt 30 %-Breite.
- Hero, Eigenschaften-Karten (Hover), Insights-Panel, Interesse-Balken, Tags, Feedback,
  Mobile-Footer einheitlich; einheitliche `section-title`/`metric-label`-Klassen.
- **Alle JS-IDs unverändert** → Charts/Live-Badge/Interesse bleiben funktionsfähig.

**Datei:** `Plugins/GeschenklyProductCard/assets/css/style.css` (überarbeitet),
Markup-Feinheiten in `GeschenklyProductCard.php`.

---

## 9. Wunschliste per E-Mail → WP-nativ

**Warum:** letzte externe Abhängigkeit (`:3004`) entfernen.

- Neuer Endpunkt `POST geschenkly/v1/wishlist`: validiert die E-Mail, sammelt die
  veröffentlichten Produkte und versendet sie per `wp_mail` (HTML, escaped, max. 100).
- `wishlist-header.js` zeigt jetzt auf die lokale Route (`geschenklyWishlist.restUrl`).

---

## 10. Aufgeräumt / gelöscht

**Warum:** toter Code, Sicherheits-/Verwirrungsrisiko.

- `geschenkly-analytics-plugin/AnalyticsApp/` – der alte Node/Mongo-Server (enthielt eine
  `.env` mit DB-Zugang). **Hinweis:** Das Passwort lag in der Git-Historie → als
  kompromittiert betrachten/rotieren.
- `themes/mindig/functions2.php` – wurde nie geladen (Backup-Kopie).
- `themes/mindig-child/_functions.php` – wurde von WordPress nie geladen; die **sicheren**
  Optimierungen daraus wurden in die aktive `functions.php` portiert.
- `Plugins/GeschenklyProductCard/assets/js/_script.js` – nicht eingebunden, alte externe Refs.
- 9 Backup-Templates (`*_backup.php`, `*_old.php`) und 4 tote `_responsive*`-CSS-Varianten (~155 KB).

---

## 11. Stabilität vor Live-Gang

**Warum:** zuverlässig hinter dem Page-Cache, keine Anzeige-Fehler beim frischen Start.

- **Cache-Kompatibilität**: Tracking-Endpunkte (Klick/View/Like/Feedback/Wishlist) ohne harte
  Nonce-Prüfung – hinter Full-Page-Cache sind eingebettete Nonces abgelaufen und würden die
  Funktion still abwürgen. Es sind unkritische Telemetrie-/Feedbackdaten (Feedback wird beim
  Speichern bereinigt). Integrität der Live-Zahlen kommt aus DISTINCT Session-Hashes.
- **Robustes „keine Daten"-Handling**: Beliebtheit/Trend zeigen sauber „nicht verfügbar"
  statt „(null)" / „0 %".
- **Kein Layout-Shift**: `body`-Padding wiederhergestellt.

---

## 12. Betriebs-Referenz

**DB-Tabelle:** `wp_geschenkly_events`

**Optionen:**
- `geschenkly_analytics_db_version` – Schema-Version
- `geschenkly_pop_seeded` – Seeding einmalig erfolgt
- `geschenkly_use_event_sort` – `yes` aktiviert die neue indexierte Archiv-Sortierung
  (Default: aus → bisheriges Verhalten; **erst nach Staging-Test umstellen**)

**Post-Meta:** `_geschenkly_pop_score`, `_geschenkly_trend`

**WP-Cron:** `geschenkly_rollup_popularity` (stündlich)

**Transients (Präfix):** `gky_pop_`, `gky_trend_`, `gky_monthly_`, `gky_hourly_`,
`gky_topcat_`, `gky_trenddet_`, `gky_live_` (15 s), `gky_topcat_ids_` (1 h)

---

## 13. Go-Live-Checkliste

1. Dateien deployen (Theme `mindig` + beide Plugins).
2. **Analytics-Plugin deaktivieren → aktivieren** (legt Tabelle an + plant Rollup-Cron).
3. REST prüfen: `…/wp-json/geschenkly/v1/view` muss für anonyme Nutzer erreichbar sein
   (Security-Plugin ggf. diese Routes ausnehmen).
4. Produktseite testen (Charts, Live-Badge, „Mehr Details").
5. Wunschliste-Mail testen (setzt funktionierendes `wp_mail`/SMTP voraus).
6. Page-Cache: REST-Routen (`/wp-json/…`) vom Cache ausnehmen (Standard).
7. **Erst danach** den `schindler-ventures`-Host (Ports 3002 **und** 3004) abschalten.
   Matomo bleibt.
8. Optional nach Staging-Test: `update_option('geschenkly_use_event_sort','yes')`.

---

## 14. Offene / spätere Punkte

- Archiv-Sortierung auf den Event-Score umstellen (nach Staging-Test).
- Eigenes Shortcode-/Builder-Grid (`.product-item`/„Ansehen") bekommt das Trending-Badge
  nicht automatisch – bei Bedarf separat einhängen.
- Aggressive `defer`-Liste (inkl. jQuery) und `srcset`-Deaktivierung aus dem alten Child
  wurden bewusst **nicht** portiert (Bruchrisiko) – nur mit Tests nachziehen.

---

## 15. Commits dieser Sitzung

| Commit | Inhalt |
|--------|--------|
| `5fbe4fd` | Analytics: externen Node/Mongo-Server durch WP-native REST-API ersetzt |
| `75f616a` | Theme-Performance: Decay-Cron, Zähl-Queries, Rollup-Score, Cleanup |
| `6356e64` | Performance Quick-Wins: Resource-Hints, Chart.js lokal, Slider konditional |
| `9bd9033` | Live-Badge pro Geschenk + Child-Cleanup + Core-Entschlackung |
| `1b9b542` | Produktseite: kohärentes, markentreues Redesign (Gold-System) |
| `bd64d39` | Stabilität vor Live-Gang: Cache-Kompatibilität, Cleanup, Hardening |
| `96403da` | Wunschliste per E-Mail WP-nativ (ersetzt externen Dienst auf Port 3004) |
| `9405abf` | Trending-Badge auf Listing-Karten + Redis-Glättung der Live-Last |
