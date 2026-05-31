// Lokale WordPress-REST-API (ersetzt den früheren externen Analytics-Server).
const API_BASE = geschenklyProductData.restUrl;

jQuery(document).ready(function($) {
    const productId = geschenklyProductData.productId;

    // --- Live-Badge: "X sehen sich das gerade an" ---------------------------
    // Laeuft per JS, damit es auch hinter Full-Page-Cache aktualisiert. Der
    // POST dient gleichzeitig als 60s-Heartbeat (Praesenz) und liefert die Zahlen.
    function geschenklyPingView() {
        $.ajax({
            url: API_BASE + 'view',
            method: 'POST',
            data: JSON.stringify({ productId: productId }),
            contentType: 'application/json',
            success: function(data) {
                const badge = document.getElementById('giftLiveBadge');
                if (!badge || !data) return;
                const viewers = document.getElementById('liveViewers');
                const today = document.getElementById('viewsToday');
                if (viewers) viewers.textContent = Number(data.viewersNow || 1).toLocaleString('de-DE');
                if (today) today.textContent = Number(data.viewsToday || 0).toLocaleString('de-DE');
                badge.hidden = false;
            }
        });
    }
    geschenklyPingView();
    setInterval(geschenklyPingView, 60000);

    // Laden der Popularitätsdaten
    $.ajax({
        url: `${API_BASE}analytics/popularity/${productId}`,
        method: 'GET',
        success: function(data) {
            const popularityElement = document.getElementById('popularityValue');
            if (data && data.category) {
                let popularityText = `(${data.category})`;
                popularityElement.innerHTML = popularityText;

                // Erstellen eines neuen Elements für den Rang
                const rankElement = document.createElement('div');
                rankElement.className = 'rank-note';
                rankElement.textContent = `Rang ${data.rank} von ${data.totalProducts}`;
                rankElement.style.fontSize = '10px';
                rankElement.style.position = 'absolute';
                rankElement.style.bottom = '5px';
                rankElement.style.right = '5px';

                // Fügen Sie das Rang-Element zum übergeordneten Element hinzu
                popularityElement.parentElement.style.position = 'relative';
                popularityElement.parentElement.appendChild(rankElement);
            } else {
                popularityElement.textContent = 'Noch keine Daten';
            }
        },
        error: function(error) {
            console.error('Fehler beim Laden der Popularitätsdaten:', error);
            document.getElementById('popularityValue').textContent = 'Daten nicht verfügbar';
        }
    });

    // Laden der Trenddaten
    $.ajax({
        url: `${API_BASE}analytics/trend/${productId}`,
        method: 'GET',
        success: function(data) {
            const trendElement = document.getElementById('popularityTrend');
            if (data && data.trend !== undefined && data.trend !== null) {
                const trendText = `${data.direction === 'up' ? '↑' : '↓'} ${Math.abs(data.trend)}% diese Woche`;
                trendElement.textContent = trendText;
                trendElement.className = `trend trend-${data.direction}`;
            } else {
                trendElement.textContent = 'Trend noch nicht verfügbar';
            }
        },
        error: function(error) {
            console.error('Fehler beim Laden der Trenddaten:', error);
            document.getElementById('popularityTrend').textContent = 'Trend nicht verfügbar';
        }
    });

    // Laden der monatlichen Kaufwünsche
    $.ajax({
        url: `${API_BASE}analytics/monthly-clicks/${productId}`,
        method: 'GET',
        success: function(data) {
            const clicksElement = document.getElementById('monthlyClicks');
            const totalKaufwunsche = data.totalClicks || 0;
            clicksElement.textContent = totalKaufwunsche.toLocaleString() + ' Kaufwünsche';

            const clicksTrendElement = document.getElementById('clicksTrend');
            clicksTrendElement.textContent = 'Trend wird nicht angezeigt';
            clicksTrendElement.style.display = 'none';
        },
        error: function(error) {
            console.error('Fehler beim Laden der monatlichen Kaufwunschdaten:', error);
            document.getElementById('monthlyClicks').textContent = 'Daten nicht verfügbar';
            document.getElementById('clicksTrend').textContent = '';
        }
    });

    // Laden der stündlichen Klickdaten
    $.ajax({
        url: `${API_BASE}analytics/hourly/${productId}`,
        method: 'GET',
        success: function(data) {
            const currentHour = new Date().getHours();
            const last6HoursData = data.filter(item => {
                const itemHour = item.hour;
                const hourDifference = (currentHour - itemHour + 24) % 24;
                return hourDifference <= 6;
            });

            let totalClicks = 0;
            last6HoursData.forEach(item => {
                totalClicks += item.clicks;
            });

            let interestPercentage;
            let description;

            if (last6HoursData.length > 0) {
                if (totalClicks < 5) {
                    interestPercentage = 20;
                    description = "Momentan ruhiges Interesse an diesem Geschenk.";
                } else if (totalClicks < 20) {
                    interestPercentage = 50;
                    description = "Dieses Geschenk weckt gerade moderates Interesse.";
                } else {
                    interestPercentage = 80;
                    description = "Dieses Geschenk ist gerade sehr beliebt!";
                }
            } else {
                interestPercentage = 0;
                description = "Aktuell keine Daten verfügbar.";
            }

            document.getElementById('interestLevel').style.width = `${interestPercentage}%`;
            document.getElementById('interestDescription').textContent = description;
        },
        error: function(error) {
            console.error('Fehler beim Laden der Interessensdaten:', error);
            document.getElementById('interestDescription').textContent = "Entschuldigung, wir konnten die aktuellen Daten nicht laden.";
        }
    });

    // Laden der Daten für das Kategorien-Chart
    $.ajax({
        url: `${API_BASE}analytics/top-categories-tags/${productId}`,
        method: 'GET',
        success: function(data) {
            const topCategories = data.topCategories.map(item => item._id.category_name);
            const topCategoriesClicks = data.topCategories.map(item => item.totalClicks);
            const topTags = data.topTags.map(item => item._id.tag_name);
            const topTagsClicks = data.topTags.map(item => item.totalClicks);

            const allClicks = [...topCategoriesClicks, ...topTagsClicks];
            const maxClicks = Math.max(...allClicks);
            const normalizedData = allClicks.map(clicks => (clicks / maxClicks) * 100);

            const ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [...topCategories, ...topTags],
                    datasets: [{
                        label: 'Relative Beliebtheit',
                        data: normalizedData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Relative Beliebtheit: ${context.parsed.y.toFixed(1)}%`;
                                }
                            }
                        }
                    }
                }
            });
        },
        error: function(error) {
            console.error('Fehler beim Abrufen der Daten:', error);
        }
    });

    // Initiales Laden der Like-Zahl
  // $.ajax({
  //     url: `${API_BASE}like/${productId}`,
  //     method: 'GET',
  //     success: function(data) {
  //         document.getElementById('likesValue').textContent = data.likeCount;
  //     },
  //     error: function(error) {
  //         console.error('Fehler beim Laden der Likes:', error);
  //     }
  // });

});

function showTrendChart() {
    const productId = geschenklyProductData.productId;
    const ctx = document.getElementById('trendChart').getContext('2d');
    const trendChartContainer = document.getElementById('trendChartContainer');
    trendChartContainer.style.display = 'block';

    $.ajax({
        url: `${API_BASE}analytics/trend-details/${productId}`,
        method: 'GET',
        success: function(data) {
            // Sort the data by week in ascending order
            data.sort((a, b) => a.week - b.week);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => `Woche ${item.week}`),
                    datasets: [{
                        label: 'Beliebtheit im Zeitverlauf',
                        data: data.map(item => item.popularity),
                        borderColor: '#4a90e2',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { beginAtZero: true },
                        y: { beginAtZero: true, max: 5 }
                    }
                }
            });
        },
        error: function(error) {
            console.error('Fehler beim Laden der Trenddetails:', error);
            trendChartContainer.innerHTML = '<p>Trenddetails konnten nicht geladen werden.</p>';
        }
    });
}

let liked = false;
function likeGift() {
    liked = !liked;
    const likeButton = document.getElementById('likeButton');
    const likesValue = document.getElementById('likesValue');
    const productId = geschenklyProductData.productId;

    if (liked) {
        $.ajax({
            url: API_BASE + 'like',
            method: 'POST',
            data: JSON.stringify({ productId }),
            contentType: 'application/json',
            headers: { 'X-WP-Nonce': geschenklyProductData.nonce },
            success: function(data) {
                likeButton.innerHTML = '❤️';
                likesValue.textContent = parseInt(likesValue.textContent) + 1;
                alert('Danke für dein Feedback');
            },
            error: function(error) {
                console.error('Fehler beim Senden des Likes:', error);
                alert('Fehler beim Senden des Likes. Bitte versuchen Sie es später erneut.');
            }
        });
    } else {
        likeButton.innerHTML = '🤍';
        likesValue.textContent = parseInt(likesValue.textContent) - 1;
        alert('Geschenk von der Wunschliste entfernt.');
    }
}

function submitFeedback() {
    const feedback = document.getElementById('feedback').value;
    const productId = geschenklyProductData.productId;

    if (feedback.trim() === '') {
        alert('Bitte geben Sie Ihr Feedback ein.');
    } else {
        $.ajax({
            url: API_BASE + 'feedback',
            method: 'POST',
            data: JSON.stringify({ productId, feedback }),
            contentType: 'application/json',
            headers: { 'X-WP-Nonce': geschenklyProductData.nonce },
            success: function(data) {
                alert('Vielen Dank für Ihr Feedback!');
                document.getElementById('feedback').value = '';
            },
            error: function(error) {
                console.error('Fehler beim Senden des Feedbacks:', error);
                alert('Fehler beim Senden des Feedbacks. Bitte versuchen Sie es später erneut.');
            }
        });
    }
}

const showMoreButton = document.getElementById('show-full-description');
const fullDescription = document.getElementById('full-description');

if (showMoreButton && fullDescription) {
    showMoreButton.addEventListener('click', function() {
        let expanded = this.getAttribute('aria-expanded') === 'true';
        expanded = !expanded; // Toggle the expanded state
        this.setAttribute('aria-expanded', expanded);
        fullDescription.hidden = !expanded;
        this.innerHTML = expanded
            ? '<span class="info-icon">🔼</span> Weniger anzeigen'
            : '<span class="info-icon">ℹ️</span> Mehr Details';
    });
}
